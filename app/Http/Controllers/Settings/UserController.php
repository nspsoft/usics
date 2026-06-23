<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'supplier', 'employee'])->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhereHas('supplier', function ($sq) use ($request) {
                      $sq->where('name', 'like', "%{$request->search}%");
                  })
                  ->orWhereHas('employee', function ($eq) use ($request) {
                      $eq->where('nik', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->type === 'internal') {
            $query->whereNull('supplier_id');
        } elseif ($request->type === 'supplier') {
            $query->whereNotNull('supplier_id');
        }

        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 25, 50, 100])) {
            $perPage = 10;
        }

        return Inertia::render('Settings/UserManagement', [
            'users' => $query->paginate($perPage)->withQueryString(),
            'roles' => \Spatie\Permission\Models\Role::all(),
            'suppliers' => \App\Models\Supplier::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'per_page', 'type']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'supplier_id' => $validated['supplier_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->supplier_id = $validated['supplier_id'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $user->syncRoles([$validated['role']]);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting the currently authenticated user
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}

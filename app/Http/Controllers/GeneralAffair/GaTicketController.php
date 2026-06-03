<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GaTicket;
use App\Models\GaLocation;
use App\Models\GaAsset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GaTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = GaTicket::with(['gaLocation', 'gaAsset', 'reporter', 'assignee']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('ticket_code', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return inertia('GeneralAffair/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'category', 'priority', 'status']),
        ]);
    }

    public function create()
    {
        $locations = GaLocation::all();
        // Load assets with minimal columns to match on frontend
        $assets = GaAsset::all(['id', 'name', 'asset_code', 'ga_location_id', 'pos_x', 'pos_y']);
        $users = User::all(['id', 'name']);

        return inertia('GeneralAffair/Tickets/Create', [
            'locations' => $locations,
            'assets' => $assets,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'priority' => 'required|string',
            'ga_location_id' => 'nullable|exists:ga_locations,id',
            'ga_asset_id' => 'nullable|exists:ga_assets,id',
            'image' => 'nullable|image|max:5120',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
        ]);

        // Generate Ticket Code
        $count = GaTicket::whereDate('created_at', today())->count();
        $validated['ticket_code'] = 'TKT-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        $validated['status'] = 'open';
        $validated['reporter_id'] = auth()->id();

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('ga_tickets', 'public');
        }

        $ticket = GaTicket::create($validated);

        $ticket->logs()->create([
            'action' => 'created',
            'notes' => 'Ticket created by ' . auth()->user()->name,
            'user_id' => auth()->id()
        ]);

        return redirect()->route('ga.tickets.index')->with('success', 'Ticket created successfully.');
    }

    public function show($id)
    {
        $ticket = GaTicket::with(['gaLocation', 'gaAsset', 'reporter', 'assignee', 'logs.user'])->findOrFail($id);
        $users = User::all(['id', 'name']);

        return inertia('GeneralAffair/Tickets/Show', [
            'ticket' => $ticket,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $ticket = GaTicket::findOrFail($id);
        $locations = GaLocation::all();
        $assets = GaAsset::all(['id', 'name', 'asset_code', 'ga_location_id', 'pos_x', 'pos_y']);
        $users = User::all(['id', 'name']);

        return inertia('GeneralAffair/Tickets/Edit', [
            'ticket' => $ticket,
            'locations' => $locations,
            'assets' => $assets,
            'users' => $users
        ]);
    }

    public function update(Request $request, $id)
    {
        $ticket = GaTicket::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'priority' => 'sometimes|required|string',
            'status' => 'sometimes|required|string',
            'ga_location_id' => 'nullable|exists:ga_locations,id',
            'ga_asset_id' => 'nullable|exists:ga_assets,id',
            'assignee_id' => 'nullable|exists:users,id',
            'image' => 'nullable|image|max:5120',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
            'resolution_notes' => 'nullable|string',
        ]);

        $oldStatus = $ticket->status;
        $oldAssigneeId = $ticket->assignee_id;

        if ($request->hasFile('image')) {
            if ($ticket->image_path) {
                Storage::disk('public')->delete($ticket->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('ga_tickets', 'public');
        }

        // Handle resolved transitions
        if (isset($validated['status']) && $validated['status'] === 'resolved' && $oldStatus !== 'resolved') {
            $validated['resolved_at'] = now();
        }

        $ticket->update($validated);
        $ticket->load(['assignee']);

        // Log status change
        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $notes = "Status changed from " . strtoupper($oldStatus) . " to " . strtoupper($ticket->status) . ".";
            if ($ticket->status === 'resolved' && !empty($validated['resolution_notes'])) {
                $notes .= " Notes: " . $validated['resolution_notes'];
            }
            $ticket->logs()->create([
                'action' => 'status_changed',
                'notes' => $notes,
                'user_id' => auth()->id()
            ]);
        }

        // Log assignee change
        if (array_key_exists('assignee_id', $validated) && $validated['assignee_id'] != $oldAssigneeId) {
            $assigneeName = $ticket->assignee ? $ticket->assignee->name : 'Unassigned';
            $ticket->logs()->create([
                'action' => 'assigned',
                'notes' => "Ticket assigned to " . $assigneeName,
                'user_id' => auth()->id()
            ]);
        }

        // General update log if status and assignee were not the only changes
        if (!isset($validated['status']) && !array_key_exists('assignee_id', $validated)) {
            $ticket->logs()->create([
                'action' => 'updated',
                'notes' => 'Ticket details updated.',
                'user_id' => auth()->id()
            ]);
        }

        return redirect()->route('ga.tickets.show', $ticket->id)->with('success', 'Ticket updated successfully.');
    }

    public function destroy($id)
    {
        $ticket = GaTicket::findOrFail($id);
        if ($ticket->image_path) {
            Storage::disk('public')->delete($ticket->image_path);
        }
        $ticket->delete();

        return redirect()->route('ga.tickets.index')->with('success', 'Ticket deleted successfully.');
    }
}

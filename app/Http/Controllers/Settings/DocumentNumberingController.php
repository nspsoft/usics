<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentNumbering;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentNumberingController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        return Inertia::render('Settings/DocumentNumbering', [
            'numberings' => DocumentNumbering::orderBy('module')->orderBy('name')->get()
                ->groupBy('module_name'), // Group by Module (Sales, Purchasing)
        ]);
    }

    /**
     * Register a new document type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:50',
            'code' => 'required|string|max:50|unique:document_numberings,code',
            'name' => 'required|string|max:100',
            'prefix' => 'required|string|max:10',
            'format' => 'required|string',
            'padding' => 'required|integer|min:2|max:10',
            'reset_period' => 'required|in:never,daily,monthly,yearly',
        ]);

        DocumentNumbering::create($validated);

        return back()->with('success', "New document type '{$validated['name']}' registered.");
    }

    /**
     * Update configuration
     */
    public function update(Request $request, DocumentNumbering $documentNumbering)
    {
        $validated = $request->validate([
            'prefix' => 'required|string|max:10',
            'format' => 'required|string',
            'padding' => 'required|integer|min:2|max:10',
            'reset_period' => 'required|in:never,daily,monthly,yearly',
            'separator' => 'required|string|max:5',
            'current_number' => 'sometimes|integer|min:0',
        ]);

        $documentNumbering->update($validated);

        return back()->with('success', "{$documentNumbering->name} numbering updated.");
    }
    
    /**
     * Preview generated number
     */
    public function preview(Request $request, DocumentNumberService $service, $code)
    {
        $date = $request->query('date');
        $params = $request->query();
        unset($params['date']);

        return response()->json([
            'preview' => $service->preview($code, $params, $date)
        ]);
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DatabaseManagementController extends Controller
{
    protected $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display database management page
     */
    public function index()
    {
        return Inertia::render('Settings/DatabaseManagement', [
            'modules' => $this->backupService->getModules(),
            'backups' => $this->backupService->getBackupList(),
        ]);
    }

    /**
     * Create a backup
     */
    public function backup(Request $request)
    {
        $request->validate([
            'type' => 'required|in:full,partial',
            'modules' => 'required_if:type,partial|array',
        ]);

        if ($request->type === 'full') {
            $result = $this->backupService->createFullBackup();
        } else {
            $result = $this->backupService->createPartialBackup($request->modules);
        }

        if ($result['success']) {
            return back()->with('success', 'Backup created successfully: ' . $result['filename']);
        }

        return back()->with('error', 'Backup failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Download a backup file
     */
    public function download(string $filename)
    {
        $filepath = 'backups/' . $filename;
        
        if (!Storage::exists($filepath)) {
            return back()->with('error', 'Backup file not found');
        }

        return Storage::download($filepath, $filename);
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename)
    {
        if ($this->backupService->deleteBackup($filename)) {
            return back()->with('success', 'Backup deleted successfully');
        }

        return back()->with('error', 'Failed to delete backup');
    }

    /**
     * Restore from an existing backup
     */
    public function restore(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $filepath = 'backups/' . $request->filename;
        $result = $this->backupService->restore($filepath);

        if ($result['success']) {
            return back()->with('success', 'Database restored successfully. Pre-restore backup: ' . ($result['pre_restore_backup'] ?? 'N/A'));
        }

        return back()->with('error', 'Restore failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Upload and restore from uploaded file
     */
    public function uploadRestore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // Max 100MB
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        // Store uploaded file
        $file = $request->file('file');
        $filename = 'uploaded_' . date('Y-m-d_H-i-s') . '_' . $file->getClientOriginalName();
        $filepath = $file->storeAs('backups', $filename);

        $result = $this->backupService->restore($filepath);

        if ($result['success']) {
            return back()->with('success', 'Database restored from uploaded file successfully');
        }

        return back()->with('error', 'Restore failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Soft reset - Clear transaction data
     */
    public function softReset(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:SOFT RESET',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $result = $this->backupService->softReset();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', 'Soft reset failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Hard reset - Reset entire database
     */
    public function hardReset(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:HARD RESET',
        ]);

        // Only Super Admin can do hard reset
        if (!auth()->user()->hasRole('Super Admin')) {
            return back()->with('error', 'Only Super Admin can perform hard reset');
        }

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $result = $this->backupService->hardReset();

        if ($result['success']) {
            return redirect()->route('login')->with('success', $result['message']);
        }

        return back()->with('error', 'Hard reset failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Module reset - Reset specific module
     */
    public function moduleReset(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'password' => 'required|string',
            'mode' => 'nullable|in:soft,hard',
        ]);

        // Verify password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password');
        }

        $mode = (string) ($request->input('mode') ?? 'hard');
        $result = $this->backupService->moduleReset($request->module, $mode);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', 'Module reset failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Get backup info
     */
    public function backupInfo(string $filename)
    {
        $info = $this->backupService->getBackupInfo($filename);
        
        if (!$info) {
            return response()->json(['error' => 'Backup not found'], 404);
        }

        return response()->json($info);
    }

    /**
     * Sync Roles & Permissions from seeder
     */
    public function syncPermissions()
    {
        try {
            \Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
            $output = \Artisan::output();
            
            return back()->with('success', 'Roles & Permissions synced successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync Document Numbering defaults
     */
    public function syncDocumentNumbering()
    {
        try {
            \Artisan::call('db:seed', ['--class' => 'DocumentNumberingSeeder', '--force' => true]);
            
            return back()->with('success', 'Document Numbering formats synced successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Run pending migrations
     */
    public function runMigrations()
    {
        try {
            \Artisan::call('migrate', ['--force' => true]);
            $output = \Artisan::output();
            
            return back()->with('success', 'Migrations executed successfully! Output: ' . $output);
        } catch (\Exception $e) {
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear all caches
     */
    public function clearCache()
    {
        try {
            \Artisan::call('optimize:clear');
            
            return back()->with('success', 'All caches cleared successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Cache clear failed: ' . $e->getMessage());
        }
    }
}

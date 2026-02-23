<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DatabaseOperationNotification;
use App\Models\User;
use Exception;

class DatabaseBackupService
{
    protected $backupPath = 'backups';
    
    /**
     * Module to tables mapping
     */
    protected $moduleTables = [
        'sales' => [
            'customers',
            'customer_contacts',
            'quotations',
            'quotation_items',
            'sales_orders',
            'sales_order_items',
            'delivery_orders',
            'delivery_order_items',
            'sales_invoices',
            'sales_invoice_items',
            'sales_returns',
            'sales_return_items',
        ],
        'purchasing' => [
            'suppliers',
            'purchase_requests',
            'purchase_request_items',
            'purchase_orders',
            'purchase_order_items',
            'goods_receipts',
            'goods_receipt_items',
            'purchase_invoices',
            'purchase_invoice_items',
            'purchase_returns',
            'purchase_return_items',
        ],
        'inventory' => [
            'categories',
            'products',
            'warehouses',
            'stocks',
            'stock_movements',
            'stock_opnames',
            'stock_opname_items',
        ],
        'manufacturing' => [
            'boms',
            'bom_items',
            'work_orders',
            'work_order_items',
            'production_entries',
            'machines',
            'shifts',
            'routing',
            'routing_steps',
            'subcontract_orders',
            'subcontract_order_items',
        ],
        'hr' => [
            'employees',
            'attendance',
            'payrolls',
            'payroll_items',
        ],
        'finance' => [
            'accounts',
            'journals',
            'journal_entries',
        ],
        'settings' => [
            'users',
            'roles',
            'permissions',
            'role_has_permissions',
            'model_has_roles',
            'model_has_permissions',
            'company_settings',
            'document_numbering',
            'ai_settings',
        ],
        'projects' => [
            'projects',
            'project_tasks',
            'project_members',
            'task_members',
            'project_task_attachments',
        ],
        'crm' => [
            'leads',
            'opportunities',
            'sales_forecasts',
            'whatsapp_messages',
            'email_messages',
            'email_attachments',
        ],
        'logistics' => [
            'vehicles',
            'delivery_schedules',
        ],
        'maintenance' => [
            'maintenance_schedules',
            'maintenance_logs',
            'spareparts',
            'maintenance_sparepart_usage',
        ],
    ];

    protected $moduleTransactionTables = [
        'sales' => [
            'quotation_items', 'quotations',
            'sales_order_items', 'sales_orders',
            'delivery_order_items', 'delivery_orders',
            'sales_invoice_items', 'sales_invoices',
            'sales_return_items', 'sales_returns',
        ],
        'purchasing' => [
            'purchase_request_items', 'purchase_requests',
            'purchase_order_items', 'purchase_orders',
            'goods_receipt_items', 'goods_receipts',
            'purchase_invoice_items', 'purchase_invoices',
            'purchase_return_items', 'purchase_returns',
        ],
        'inventory' => [
            'stock_movements',
            'stock_opname_items', 'stock_opnames',
        ],
        'manufacturing' => [
            'work_order_items', 'work_orders',
            'production_entries',
            'subcontract_order_items', 'subcontract_orders',
        ],
        'hr' => [
            'attendance', 'payroll_items', 'payrolls',
        ],
        'finance' => [
            'journal_entries', 'journals',
        ],
    ];

    /**
     * Get available modules for backup
     */
    public function getModules(): array
    {
        return array_keys($this->moduleTables);
    }

    /**
     * Get tables for a specific module
     */
    public function getModuleTables(string $module): array
    {
        return $this->moduleTables[$module] ?? [];
    }

    /**
     * Create a full database backup
     */
    public function createFullBackup(): array
    {
        try {
            $filename = 'backup_full_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupPath . '/' . $filename;
            
            // Get all tables
            $tables = $this->getAllTables();
            
            // Generate SQL dump
            $sql = $this->generateSqlDump($tables);
            
            // Store the backup
            Storage::put($filepath, $sql);
            
            // Compress if possible
            $this->compressBackup($filepath);
            
            $this->logSuccess('Full backup created', $filename);
            
            return [
                'success' => true,
                'filename' => $filename,
                'size' => Storage::size($filepath),
                'path' => $filepath,
            ];
        } catch (Exception $e) {
            $this->logError('Full backup failed', $e->getMessage());
            $this->notifyAdmins('Backup Failed', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a partial backup for selected modules
     */
    public function createPartialBackup(array $modules): array
    {
        try {
            $modulesStr = implode('_', $modules);
            $filename = 'backup_partial_' . $modulesStr . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupPath . '/' . $filename;
            
            // Get tables for selected modules
            $tables = [];
            foreach ($modules as $module) {
                if (isset($this->moduleTables[$module])) {
                    $tables = array_merge($tables, $this->moduleTables[$module]);
                }
            }
            
            // Remove duplicates
            $tables = array_unique($tables);
            
            // Generate SQL dump
            $sql = $this->generateSqlDump($tables);
            
            // Store the backup
            Storage::put($filepath, $sql);
            
            $this->logSuccess('Partial backup created', $filename);
            
            return [
                'success' => true,
                'filename' => $filename,
                'size' => Storage::size($filepath),
                'path' => $filepath,
                'modules' => $modules,
                'tables' => $tables,
            ];
        } catch (Exception $e) {
            $this->logError('Partial backup failed', $e->getMessage());
            $this->notifyAdmins('Backup Failed', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore database from backup file
     */
    public function restore(string $filepath): array
    {
        try {
            // First, create a backup before restore
            $beforeBackup = $this->createFullBackup();
            
            if (!$beforeBackup['success']) {
                throw new Exception('Failed to create pre-restore backup');
            }
            
            // Get SQL content
            $sql = Storage::get($filepath);
            
            // If compressed, decompress first
            if (str_ends_with($filepath, '.gz')) {
                $sql = gzdecode($sql);
            }
            
            Schema::disableForeignKeyConstraints();
            
            // Execute the SQL
            $statements = $this->parseSqlStatements($sql);
            
            foreach ($statements as $statement) {
                if (trim($statement)) {
                    DB::unprepared($statement);
                }
            }
            
            Schema::enableForeignKeyConstraints();
            
            $this->logSuccess('Database restored', $filepath);
            $this->notifyAdmins('Restore Completed', "Database restored from: {$filepath}");
            
            return [
                'success' => true,
                'message' => 'Database restored successfully',
                'pre_restore_backup' => $beforeBackup['filename'],
            ];
        } catch (Exception $e) {
            $this->logError('Restore failed', $e->getMessage());
            $this->notifyAdmins('Restore Failed', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Soft reset - Delete transactional data, keep master data
     */
    public function softReset(): array
    {
        try {
            Schema::disableForeignKeyConstraints();
            
            $transactionTables = [
                // Sales transactions
                'quotation_items', 'quotations',
                'sales_order_items', 'sales_orders',
                'delivery_order_items', 'delivery_orders',
                'sales_invoice_items', 'sales_invoices',
                'sales_return_items', 'sales_returns',
                // Purchase transactions
                'purchase_request_items', 'purchase_requests',
                'purchase_order_items', 'purchase_orders',
                'goods_receipt_items', 'goods_receipts',
                'purchase_invoice_items', 'purchase_invoices',
                'purchase_return_items', 'purchase_returns',
                // Manufacturing transactions
                'work_order_items', 'work_orders',
                'production_entries',
                'subcontract_order_items', 'subcontract_orders',
                // Inventory transactions
                'stock_movements',
                'stock_opname_items', 'stock_opnames',
                // Finance transactions
                'journal_entries', 'journals',
                // HR transactions
                'attendance', 'payroll_items', 'payrolls',
                // Notifications & logs
                'notifications', 'activity_log',
            ];
            
            foreach ($transactionTables as $table) {
                if ($this->tableExists($table)) {
                    $this->wipeTable($table);
                }
            }
            
            // Reset stocks to 0
            if ($this->tableExists('stocks')) {
                DB::table('stocks')->update(['quantity' => 0]);
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            $this->logSuccess('Soft reset completed', 'Transaction data cleared');
            $this->notifyAdmins('Soft Reset Completed', 'All transaction data has been cleared.');
            
            return [
                'success' => true,
                'message' => 'Soft reset completed. Transaction data cleared.',
            ];
        } catch (Exception $e) {
            $this->logError('Soft reset failed', $e->getMessage());
            $this->notifyAdmins('Soft Reset Failed', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Hard reset - Reset everything and run seeders
     */
    public function hardReset(): array
    {
        try {
            // Create backup first
            $backup = $this->createFullBackup();
            
            // Run migrate:fresh with seed
            \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            
            $this->logSuccess('Hard reset completed', 'Database reset to initial state');
            $this->notifyAdmins('Hard Reset Completed', 'Database has been reset to initial state.');
            
            return [
                'success' => true,
                'message' => 'Hard reset completed. Database reset to initial state.',
                'backup' => $backup['filename'] ?? null,
            ];
        } catch (Exception $e) {
            $this->logError('Hard reset failed', $e->getMessage());
            $this->notifyAdmins('Hard Reset Failed', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Module reset - Reset specific module only
     */
    /**
     * Module dependencies mapping for safety checks
     * Format: 'target_module' => ['dependent_table' => 'Dependent Module Name']
     */
    protected $moduleDependencies = [
        'inventory' => [
            'quotation_items' => 'Sales (Quotations)',
            'sales_order_items' => 'Sales (Orders)',
            'delivery_order_items' => 'Sales (Delivery)',
            'sales_invoice_items' => 'Sales (Invoices)',
            'sales_return_items' => 'Sales (Returns)',
            'purchase_request_items' => 'Purchasing (Requests)',
            'purchase_order_items' => 'Purchasing (Orders)',
            'goods_receipt_items' => 'Purchasing (Receipts)',
            'purchase_invoice_items' => 'Purchasing (Invoices)',
            'purchase_return_items' => 'Purchasing (Returns)',
            'bom_items' => 'Manufacturing (BOM)',
            'work_order_items' => 'Manufacturing (Work Orders)',
            'production_entries' => 'Manufacturing (Production)',
            'subcontract_order_items' => 'Manufacturing (Subcontract)',
        ],
        'sales' => [
            'journal_entries' => 'Finance (Journals)', // Example if sales link to finance
        ],
        'purchasing' => [
            'journal_entries' => 'Finance (Journals)',
        ],
        // Add more dependencies as needed
    ];

    /**
     * Module reset - Reset specific module only with safety checks
     */
    public function moduleReset(string $module, string $mode = 'hard'): array
    {
        try {
            if (!isset($this->moduleTables[$module])) {
                throw new Exception("Unknown module: {$module}");
            }

            if ($mode === 'soft') {
                return $this->moduleSoftReset($module);
            }

            // SAFETY CHECK: Check dependencies
            if (isset($this->moduleDependencies[$module])) {
                $dependencies = $this->moduleDependencies[$module];
                $blockingDependencies = [];

                foreach ($dependencies as $table => $dependentModule) {
                    if ($this->tableExists($table)) {
                        $count = DB::table($table)->count();
                        if ($count > 0) {
                            $blockingDependencies[] = "{$dependentModule} ({$count} records)";
                        }
                    }
                }

                if (!empty($blockingDependencies)) {
                    $errorMsg = "Cannot reset '{$module}' because data exists in dependent modules:\n" . implode("\n", $blockingDependencies);
                    $errorMsg .= "\n\nPlease perform a Soft Reset first or clear the dependent data.";
                    throw new Exception($errorMsg);
                }
            }
            
            Schema::disableForeignKeyConstraints();
            
            $tables = $this->moduleTables[$module];
            
            foreach ($tables as $table) {
                if ($this->tableExists($table)) {
                    $this->wipeTable($table);
                }
            }
            
            Schema::enableForeignKeyConstraints();
            
            $this->logSuccess('Module reset completed', "Module: {$module}");
            
            return [
                'success' => true,
                'message' => "Module '{$module}' has been reset.",
                'tables' => $tables,
            ];
        } catch (Exception $e) {
            $this->logError('Module reset failed', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function moduleSoftReset(string $module): array
    {
        try {
            if (!isset($this->moduleTransactionTables[$module])) {
                throw new Exception("Soft reset is not available for module: {$module}");
            }

            Schema::disableForeignKeyConstraints();

            $tables = $this->moduleTransactionTables[$module];
            foreach ($tables as $table) {
                if ($this->tableExists($table)) {
                    $this->wipeTable($table);
                }
            }

            if ($module === 'inventory' && $this->tableExists('stocks')) {
                DB::table('stocks')->update(['quantity' => 0]);
            }

            Schema::enableForeignKeyConstraints();

            $this->logSuccess('Module soft reset completed', "Module: {$module}");

            return [
                'success' => true,
                'message' => "Module '{$module}' has been soft reset (transactions cleared, master data kept).",
                'tables' => $tables,
            ];
        } catch (Exception $e) {
            $this->logError('Module soft reset failed', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get list of available backups
     */
    public function getBackupList(): array
    {
        $files = Storage::files($this->backupPath);
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => Storage::size($file),
                'size_human' => $this->formatBytes(Storage::size($file)),
                'created_at' => date('Y-m-d H:i:s', Storage::lastModified($file)),
            ];
        }
        
        // Sort by created_at desc
        usort($backups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        
        return $backups;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = $this->backupPath . '/' . $filename;
        
        if (Storage::exists($filepath)) {
            Storage::delete($filepath);
            $this->logSuccess('Backup deleted', $filename);
            return true;
        }
        
        return false;
    }

    /**
     * Get backup info
     */
    public function getBackupInfo(string $filename): ?array
    {
        $filepath = $this->backupPath . '/' . $filename;
        
        if (!Storage::exists($filepath)) {
            return null;
        }
        
        return [
            'filename' => $filename,
            'path' => $filepath,
            'size' => Storage::size($filepath),
            'size_human' => $this->formatBytes(Storage::size($filepath)),
            'created_at' => date('Y-m-d H:i:s', Storage::lastModified($filepath)),
        ];
    }

    /**
     * Clean old backups based on retention days
     */
    public function cleanOldBackups(int $retentionDays = 30): int
    {
        $files = Storage::files($this->backupPath);
        $deletedCount = 0;
        $timestampLimit = now()->subDays($retentionDays)->timestamp;
        
        foreach ($files as $file) {
            // Check if it's a backup file (starts with backup_ or uploaded_)
            if (!str_starts_with(basename($file), 'backup_') && !str_starts_with(basename($file), 'uploaded_')) {
                continue;
            }
            
            if (Storage::lastModified($file) < $timestampLimit) {
                Storage::delete($file);
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            $this->logSuccess('Cleanup completed', "Deleted {$deletedCount} old backup files (> {$retentionDays} days)");
        }
        
        return $deletedCount;
    }

    // ========== HELPER METHODS ==========

    protected function getAllTables(): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $tables = [];
            $result = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . config('database.connections.mysql.database');

            foreach ($result as $row) {
                $tables[] = $row->$key;
            }

            return $tables;
        }

        if ($driver === 'sqlite') {
            return array_map(
                fn ($row) => $row->name,
                DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
            );
        }

        if ($driver === 'pgsql') {
            return array_map(
                fn ($row) => $row->tablename,
                DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname='public'")
            );
        }

        return [];
    }

    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function wipeTable(string $table): void
    {
        DB::table($table)->delete();
    }

    protected function generateSqlDump(array $tables): string
    {
        $sql = "-- JICOS Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Tables: " . implode(', ', $tables) . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }
            
            // Get CREATE TABLE statement
            $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
            if (!empty($createResult)) {
                $createStatement = $createResult[0]->{'Create Table'};
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createStatement . ";\n\n";
            }
            
            // Get table data
            $rows = DB::table($table)->get();
            
            if ($rows->count() > 0) {
                $columns = array_keys((array) $rows->first());
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = array_map(function ($val) {
                        if (is_null($val)) return 'NULL';
                        return "'" . addslashes($val) . "'";
                    }, (array) $row);
                    
                    $valueList = implode(', ', $values);
                    $sql .= "INSERT INTO `{$table}` ({$columnList}) VALUES ({$valueList});\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return $sql;
    }

    protected function parseSqlStatements(string $sql): array
    {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split by semicolon
        $statements = preg_split('/;\s*$/m', $sql);
        
        return array_filter($statements, fn($s) => trim($s) !== '');
    }

    protected function compressBackup(string $filepath): void
    {
        if (function_exists('gzencode') && Storage::exists($filepath)) {
            $content = Storage::get($filepath);
            $compressed = gzencode($content, 9);
            Storage::put($filepath . '.gz', $compressed);
            Storage::delete($filepath);
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    protected function logSuccess(string $action, string $details): void
    {
        Log::info("[DatabaseBackup] {$action}: {$details}");
        
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['action' => $action, 'details' => $details])
            ->log("Database {$action}");
    }

    protected function logError(string $action, string $error): void
    {
        Log::error("[DatabaseBackup] {$action} failed: {$error}");
        
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['action' => $action, 'error' => $error])
            ->log("Database {$action} failed");
    }

    protected function notifyAdmins(string $title, string $message): void
    {
        try {
            $admins = User::role('Super Admin')->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new DatabaseOperationNotification($title, $message));
            }
        } catch (Exception $e) {
            Log::warning("[DatabaseBackup] Failed to notify admins: " . $e->getMessage());
        }
    }
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = [
    'hr_attendances',
    'hr_leaves',
    'hr_reimbursements',
    'hr_overtime_requests',
    'hr_okr_objectives',
    'hr_okr_key_results',
    'hr_job_postings',
    'hr_applicants',
    'hr_leave_types'
];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "Table: {$table}\n";
        $columns = Schema::getColumnListing($table);
        foreach ($columns as $col) {
            $type = Schema::getColumnType($table, $col);
            echo "  - {$col} ({$type})\n";
        }
    } else {
        echo "Table: {$table} (DOES NOT EXIST)\n";
    }
    echo "\n";
}

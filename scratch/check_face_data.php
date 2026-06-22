<?php

use App\Models\Employee;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emp = Employee::find(1);
if ($emp) {
    echo "Employee Name: {$emp->full_name}\n";
    echo "Linked User ID: " . ($emp->user_id ?? 'None') . "\n";
    if ($emp->user_id) {
        $user = App\Models\User::find($emp->user_id);
        if ($user) {
            echo "Linked User Name: {$user->name}\n";
            echo "Linked User Email: {$user->email}\n";
        }
    }
} else {
    echo "Employee not found\n";
}

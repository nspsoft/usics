<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $count = \App\Models\User::count();
    echo "Total users count: " . $count . "\n";
    $users = \App\Models\User::all();
    foreach ($users as $user) {
        echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email}\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

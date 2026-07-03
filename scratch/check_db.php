<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "config default: " . config('database.default') . "\n";
echo "env DB_CONNECTION: " . env('DB_CONNECTION') . "\n";
echo "env DB_DATABASE: " . env('DB_DATABASE') . "\n";
echo "active database name: " . \DB::connection()->getDatabaseName() . "\n";

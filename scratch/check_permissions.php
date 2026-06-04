<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;

$gaPerms = Permission::where('name', 'like', 'general_affair%')->pluck('name')->toArray();
$meetingPerms = Permission::where('name', 'like', 'meeting_command%')->pluck('name')->toArray();

echo "GA Permissions Count: " . count($gaPerms) . "\n";
print_r($gaPerms);

echo "\nMeeting Permissions Count: " . count($meetingPerms) . "\n";
print_r($meetingPerms);

<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Meeting;
use App\Models\User;
use App\Models\Employee;
use App\Services\MeetingNotificationService;

$meeting = Meeting::first();

if (!$meeting) {
    echo "No meetings found! Please seed meetings first.\n";
    exit(1);
}

echo "Testing notification dispatcher for meeting: '{$meeting->title}'\n";

// 1. Ensure attendees have employees with phone numbers
foreach ($meeting->attendees as $attendee) {
    if ($attendee->user) {
        $employee = $attendee->user->employee;
        if (!$employee) {
            echo "Creating employee profile for user {$attendee->user->name}...\n";
            $employee = Employee::create([
                'user_id' => $attendee->user->id,
                'nik' => 'EMP-' . str_pad($attendee->user->id, 5, '0', STR_PAD_LEFT),
                'full_name' => $attendee->user->name,
                'email' => $attendee->user->email,
                'phone' => '628123456789' . $attendee->user->id,
                'address' => 'Jl. Test No. 123',
                'joining_date' => now()->toDateString(),
                'employment_status' => 'permanent',
                'department_id' => \App\Models\Department::first()?->id ?? 1,
                'position_id' => \App\Models\Position::first()?->id ?? 1,
                'is_active' => true,
            ]);
        } else if (empty($employee->phone)) {
            echo "Updating phone number for employee {$employee->full_name}...\n";
            $employee->update(['phone' => '628123456789' . $attendee->user->id]);
        }
        echo "Attendee: {$attendee->user->name} - Phone: {$employee->phone} - Email: {$employee->email}\n";
    } else {
        echo "Attendee Guest: {$attendee->guest_name}\n";
    }
}

// 2. Clear log to verify fresh output (or we can just append a marker)
\Illuminate\Support\Facades\Log::info("=== START NOTIFICATION TEST ===");

// 3. Dispatch notifications
try {
    $dispatcher = app(MeetingNotificationService::class);
    echo "Dispatching meeting published notification...\n";
    $dispatcher->dispatchMeetingPublished($meeting);
    
    echo "Dispatching action items notification...\n";
    foreach ($meeting->actionItems as $item) {
        echo "Action Item PIC: {$item->pic->name} - Tugas: {$item->description}\n";
        $dispatcher->dispatchActionItemAssigned($item);
    }
    
    echo "SUCCESS! Notifications dispatched.\n";
    
    // 4. Print latest DB WhatsApp messages
    $messages = \App\Models\WhatsappMessage::latest()->limit(5)->get();
    echo "\nLatest WhatsApp Messages in DB:\n";
    foreach ($messages as $msg) {
        echo "[{$msg->direction}] To: {$msg->phone} | Msg: " . str_replace("\n", " ", mb_strimwidth($msg->message, 0, 80, '...')) . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

\Illuminate\Support\Facades\Log::info("=== END NOTIFICATION TEST ===");

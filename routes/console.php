<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\ScheduledBackupJob;

Schedule::job(new ScheduledBackupJob)->dailyAt('00:00');
Schedule::command('inventory:check-low-stock')->dailyAt('08:00');
Schedule::command('purchase-order:fix-creator')->hourly();

<?php

use Illuminate\Support\Facades\Schedule;

// Send appointment reminders daily at 8 AM
Schedule::command('appointments:send-reminders')->dailyAt('08:00');

<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Default command (Optional)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks for Enterprise Maintenance
Schedule::command('sanctum:prune-expired --hours=24')->daily(); // Expired token clean korbe
Schedule::command('model:prune')->daily(); // Old model records clean korbe (jodi thake)
Schedule::command('queue:prune-failed --hours=48')->daily(); // Failed jobs clean korbe

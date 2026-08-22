<?php

use App\Console\Commands\OpenDueRoundsCommand;
use App\Console\Commands\QueueHeartbeatCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(OpenDueRoundsCommand::class)->everyMinute()->withoutOverlapping();
Schedule::command(QueueHeartbeatCommand::class)->everyMinute()->withoutOverlapping();

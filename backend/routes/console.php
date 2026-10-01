<?php

use App\Models\Phim;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping(5);
Schedule::command('movies:sync-status')->dailyAt('00:00')->timezone('Asia/Ho_Chi_Minh');

Artisan::command('movies:sync-status', function () {
    $count = Phim::synchronizeStatuses();
    $this->info("Updated movie statuses: {$count}");
})->purpose('Synchronize movie statuses from release and end dates');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

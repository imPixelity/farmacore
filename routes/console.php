<?php

use App\Jobs\Inventory\CheckExpiry;
use App\Jobs\Inventory\CheckReorderPoint;
use App\Jobs\Inventory\RecalculateStockPolicy;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RecalculateStockPolicy)
    ->dailyAt('01:00')
    ->withoutOverlapping();

Schedule::job(new CheckReorderPoint)
    ->hourly()
    ->withoutOverlapping();

Schedule::job(new CheckExpiry)
    ->dailyAt('06:00')
    ->withoutOverlapping();

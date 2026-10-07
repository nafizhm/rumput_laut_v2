<?php

use App\Services\Finance\OperationalAccountingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('accounting:post-due', function () {
    $count = app(OperationalAccountingService::class)->postPending();
    $this->info($count.' jurnal operasional diposting.');
})->purpose('Post due operational journals after verified opening balances');

Schedule::command('accounting:post-due')->dailyAt('00:05')->timezone('Asia/Jakarta')->withoutOverlapping();

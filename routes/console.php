<?php
use App\Jobs\BaixarBcaJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new BaixarBcaJob(now()->format('Y-m-d')))
    ->hourlyAt(0)
    ->between('08:00', '17:00')
    ->weekdays()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('buscar-bca-diario');

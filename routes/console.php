<?php

use App\Jobs\BaixarBcaJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cron em vez de between(): between('08:00', '17:00') descarta a execução das 17:00,
// porque o scheduler dispara em 17:00:00.x, já depois do limite.
Schedule::job(BaixarBcaJob::paraHoje())
    ->cron('0 8-17 * * 1-5')
    ->timezone(config('app.local_timezone'))
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('buscar-bca-diario');

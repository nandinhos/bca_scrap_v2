<?php

use App\Jobs\BaixarBcaJob;
use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Support\CalendarioFab;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cron em vez de between(): between('08:00', '17:00') descarta a execução das 17:00,
// porque o scheduler dispara em 17:00:00.x, já depois do limite.
// Feriado FAB nao dispara (antes tentava e registrava sem_bca igual a pane).
// As 08h, lookback: se o dia util anterior ficou sem BCA e sem execucao
// (ex.: publicado apos as 17h, ou sistema fora do ar), busca ele primeiro.
Schedule::call(function () {
    $hoje = CarbonImmutable::now(config('app.local_timezone'));

    if ($hoje->hour === 8) {
        $anterior = CalendarioFab::diaUtilAnterior($hoje);
        $dataAnterior = $anterior->format('Y-m-d');

        $jaTratado = Bca::where('data', $dataAnterior)->exists()
            || BcaExecucao::where('mensagem', 'like', "%{$dataAnterior}%")->exists();

        if (! $jaTratado) {
            BaixarBcaJob::dispatch($dataAnterior);
        }
    }

    if (CalendarioFab::eDiaUtil($hoje)) {
        BaixarBcaJob::dispatch($hoje->format('Y-m-d'));
    }
})
    ->cron('0 8-17 * * 1-5')
    ->timezone(config('app.local_timezone'))
    ->name('buscar-bca-diario')
    ->withoutOverlapping(10)
    ->onOneServer();

<?php

use App\Jobs\BaixarBcaJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;

function eventoBuscarBca()
{
    app(Kernel::class)->bootstrap();

    return collect(app(Schedule::class)->events())
        ->firstOrFail(fn ($e) => $e->description === 'buscar-bca-diario');
}

afterEach(fn () => Carbon::setTestNow());

it('roda de hora em hora das 08h às 17h de Brasília em dias úteis', function (string $utc, bool $esperado) {
    Carbon::setTestNow(Carbon::parse($utc, 'UTC')->addMilliseconds(300));

    expect(eventoBuscarBca()->isDue(app()))->toBe($esperado);
})->with([
    '07:00 BRT (antes da janela)' => ['2026-09-29 10:00:00', false],
    '08:00 BRT (início)' => ['2026-09-29 11:00:00', true],
    '12:00 BRT' => ['2026-09-29 15:00:00', true],
    '17:00 BRT (fim)' => ['2026-09-29 20:00:00', true],
    '18:00 BRT (depois da janela)' => ['2026-09-29 21:00:00', false],
    '12:30 BRT (fora do minuto 0)' => ['2026-09-29 15:30:00', false],
    'sábado 12:00 BRT' => ['2026-10-03 15:00:00', false],
]);

it('usa a data de Brasília para o BCA do dia', function (string $utc, string $data) {
    Carbon::setTestNow(Carbon::parse($utc, 'UTC'));

    expect(BaixarBcaJob::paraHoje()->data)->toBe($data);
})->with([
    'meio-dia' => ['2026-09-29 15:00:00', '2026-09-29'],
    '23:30 BRT já é dia seguinte em UTC' => ['2026-09-30 02:30:00', '2026-09-29'],
]);

it('limita o timeout do SMTP abaixo do timeout do job de e-mail', function () {
    $jobTimeout = (new ReflectionClass(\App\Jobs\EnviarEmailNotificacaoJob::class))
        ->getProperty('timeout')->getDefaultValue();

    expect(config('mail.mailers.smtp.timeout'))
        ->toBeInt()
        ->toBeLessThan($jobTimeout);
});

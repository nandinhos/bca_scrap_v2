<?php

use App\Support\CalendarioFab;
use Carbon\CarbonImmutable;

it('reconhece feriados FAB e dias uteis', function (string $data, bool $feriado, bool $util) {
    $d = CarbonImmutable::parse($data);

    expect(CalendarioFab::eFeriado($d))->toBe($feriado)
        ->and(CalendarioFab::eDiaUtil($d))->toBe($util);
})->with([
    'Dia do Aviador 2026' => ['2026-10-23', true, false],
    'Aparecida 2026' => ['2026-10-12', true, false],
    'Sexta-feira Santa 2026' => ['2026-04-03', true, false],
    'sabado comum' => ['2026-10-24', false, false],
    'segunda comum' => ['2026-10-26', false, true],
]);

it('dia util anterior pula fim de semana e feriado', function () {
    // Segunda 26/10/2026 -> anterior e quinta 22/10 (sexta 23 e feriado, fds depois)
    expect(CalendarioFab::diaUtilAnterior(CarbonImmutable::parse('2026-10-26'))->format('Y-m-d'))
        ->toBe('2026-10-22');
});

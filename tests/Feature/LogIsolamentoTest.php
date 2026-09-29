<?php

use Illuminate\Support\Facades\Log;

it('grava o log da suíte em testing.log, nunca no laravel.log de produção', function () {
    $marcador = 'isolamento-log-'.uniqid();

    Log::info($marcador);

    $producao = storage_path('logs/laravel.log');
    expect(file_exists($producao) ? file_get_contents($producao) : '')->not->toContain($marcador)
        ->and(file_get_contents(storage_path('logs/testing.log')))->toContain($marcador);
});

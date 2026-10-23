<?php

use Illuminate\Support\Facades\Log;

it('grava o log da suíte em testing.log, nunca no laravel.log de produção', function () {
    $marcador = 'isolamento-log-'.uniqid();

    Log::info($marcador);

    $producao = storage_path('logs/laravel.log');
    // Canal daily rotaciona: o arquivo ativo da suite e testing-<data>.log
    $arquivosTeste = glob(storage_path('logs/testing*.log')) ?: [];
    $conteudoTeste = implode('', array_map('file_get_contents', $arquivosTeste));

    expect(file_exists($producao) ? file_get_contents($producao) : '')->not->toContain($marcador)
        ->and($conteudoTeste)->toContain($marcador);
});

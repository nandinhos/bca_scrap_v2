<?php

it('sobe sozinho após reboot nos serviços de longa duração', function (string $servico) {
    $compose = file_get_contents(base_path('docker-compose.yml'));

    // Bloco do serviço: de "  <servico>:" até o próximo serviço (2 espaços) ou networks:
    $padrao = '/^  '.preg_quote($servico, '/').":\n(.*?)(?=^  \S|\\z)/ms";
    expect(preg_match($padrao, $compose, $m))->toBe(1)
        ->and($m[1])->toContain('restart: unless-stopped');
})->with(['nginx', 'php', 'queue', 'scheduler', 'postgres', 'redis']);

it('mantém storage-init como one-shot sem restart', function () {
    $compose = file_get_contents(base_path('docker-compose.yml'));

    expect(preg_match('/^  storage-init:\n(.*?)(?=^  \S|\z)/ms', $compose, $m))->toBe(1)
        ->and($m[1])->toContain('restart: "no"');
});

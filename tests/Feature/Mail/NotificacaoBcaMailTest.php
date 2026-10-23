<?php

use App\Mail\NotificacaoBcaMail;
use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function htmlNotificacao(array $efetivo): string
{
    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => Bca::factory()->create()->id,
        'efetivo_id' => Efetivo::factory()->create($efetivo)->id,
        'snippet' => 'trecho',
    ]);

    return (new NotificacaoBcaMail($ocorrencia->fresh(['bca', 'efetivo'])))->render();
}

it('saúda o militar com posto/graduação e nome de guerra', function () {
    expect(htmlNotificacao(['posto' => 'Cel', 'nome_guerra' => 'FONTES']))
        ->toContain('Olá, Cel FONTES!');
});

it('exibe o adendo excepcional somente quando informado', function () {
    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => Bca::factory()->create()->id,
        'efetivo_id' => Efetivo::factory()->create()->id,
        'snippet' => 'trecho',
    ]);
    $ocorrencia = $ocorrencia->fresh(['bca', 'efetivo']);

    expect((new NotificacaoBcaMail($ocorrencia, 'Nota excepcional de teste.'))->render())
        ->toContain('Nota excepcional de teste.')
        ->and((new NotificacaoBcaMail($ocorrencia))->render())
        ->not->toContain('Nota excepcional de teste.');
});

it('omite o posto de civis', function () {
    expect(htmlNotificacao(['posto' => 'Civil', 'nome_guerra' => 'SILVA']))
        ->toContain('Olá, SILVA!')
        ->not->toContain('Civil SILVA');
});

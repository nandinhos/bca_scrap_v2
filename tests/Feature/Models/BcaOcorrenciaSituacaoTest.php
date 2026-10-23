<?php

use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('classifica situacao de envio terminal vs pendente', function (array $efetivoAttrs, bool $enviado, string $esperado) {
    $efetivo = Efetivo::factory()->create($efetivoAttrs);
    $oc = BcaOcorrencia::create([
        'bca_id' => Bca::factory()->create()->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'x',
        'enviado_em' => $enviado ? now() : null,
    ]);

    expect($oc->fresh()->situacaoEnvio())->toBe($esperado);
})->with([
    'enviado' => [['email' => 'a@x.test', 'oculto' => false], true, 'enviado'],
    'oculto' => [['email' => 'a@x.test', 'oculto' => true], false, 'oculto (nao envia)'],
    'sem email' => [['email' => null, 'oculto' => false], false, 'sem e-mail (nao envia)'],
    'pendente' => [['email' => 'a@x.test', 'oculto' => false], false, 'pendente'],
]);

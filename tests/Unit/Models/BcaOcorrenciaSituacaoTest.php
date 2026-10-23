<?php

use App\Models\BcaOcorrencia;
use App\Models\Efetivo;

it('classifica situacao de envio terminal vs pendente', function (array $efetivoAttrs, ?string $enviadoEm, string $esperado) {
    $oc = new BcaOcorrencia(['enviado_em' => $enviadoEm]);
    $oc->setRelation('efetivo', new Efetivo($efetivoAttrs));

    expect($oc->situacaoEnvio())->toBe($esperado);
})->with([
    'enviado' => [['email' => 'a@x.test', 'oculto' => false], '2026-10-23 10:00:00', 'enviado'],
    'oculto' => [['email' => 'a@x.test', 'oculto' => true], null, 'oculto (nao envia)'],
    'sem email' => [['email' => null, 'oculto' => false], null, 'sem e-mail (nao envia)'],
    'pendente' => [['email' => 'a@x.test', 'oculto' => false], null, 'pendente'],
]);

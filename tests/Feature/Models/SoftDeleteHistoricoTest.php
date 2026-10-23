<?php

use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('excluir efetivo (soft) preserva ocorrencias e historico', function () {
    $efetivo = Efetivo::factory()->create();
    $oc = BcaOcorrencia::create([
        'bca_id' => Bca::factory()->create()->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'x',
        'enviado_em' => now(),
    ]);

    $efetivo->delete();

    expect(Efetivo::find($efetivo->id))->toBeNull()
        ->and(Efetivo::withTrashed()->find($efetivo->id))->not->toBeNull()
        ->and(BcaOcorrencia::find($oc->id))->not->toBeNull()
        ->and(BcaOcorrencia::find($oc->id)->efetivo->id)->toBe($efetivo->id);
});

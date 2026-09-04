<?php

use App\Jobs\BaixarBcaJob;
use App\Jobs\EnviarEmailNotificacaoJob;
use App\Livewire\BuscaBca;
use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Cria um BCA no estado "ja baixado, convertido e analisado" — exatamente o que
 * sobra depois de uma busca feita antes de o efetivo existir.
 */
function bcaJaAnalisado(string $data = '2026-07-01'): Bca
{
    return Bca::create([
        'numero' => '123',
        'data' => $data,
        'url' => "bcas/{$data}.pdf",
        'texto_completo' => "PORTARIA\nCONCEDE FERIAS AO SGT FULANO DE TAL SARAM 1234567\nFIM",
        'processado_em' => now()->subDay(),
        'analisado_em' => now()->subDay(),
    ]);
}

function efetivoFulano(array $extra = []): Efetivo
{
    return Efetivo::create(array_merge([
        'saram' => '1234567',
        'nome_guerra' => 'FULANO',
        'nome_completo' => 'FULANO DE TAL',
        'posto' => '3S',
        'email' => 'fulano@example.test',
        'ativo' => true,
        'oculto' => false,
    ], $extra));
}

it('reanalisa BCA ja analisado quando o efetivo foi cadastrado depois', function () {
    $this->actingAs(User::factory()->create(['role' => 'operador']));

    // Busca anterior rodou sem efetivo cadastrado: 0 ocorrencias.
    $bca = bcaJaAnalisado();
    expect($bca->ocorrencias()->count())->toBe(0);

    // Usuario importa o efetivo depois.
    efetivoFulano();

    Livewire::test(BuscaBca::class)
        ->set('data', $bca->data->format('Y-m-d'))
        ->call('buscar')
        ->call('executarBusca');

    expect($bca->fresh()->ocorrencias()->count())->toBe(1)
        ->and($bca->fresh()->analisado_em)->not->toBeNull();
});

it('nao baixa o PDF de novo na reanalise, reaproveitando o texto armazenado', function () {
    Queue::fake();
    $this->actingAs(User::factory()->create(['role' => 'operador']));

    $bca = bcaJaAnalisado();
    efetivoFulano();

    Livewire::test(BuscaBca::class)
        ->set('data', $bca->data->format('Y-m-d'))
        ->call('buscar')
        ->call('executarBusca');

    Queue::assertNotPushed(BaixarBcaJob::class);
});

it('nao reanalisa quando nada mudou desde a ultima analise', function () {
    $this->actingAs(User::factory()->create(['role' => 'operador']));

    // Efetivo ja existia ANTES da analise (analisado_em = ontem).
    $efetivo = efetivoFulano();
    $efetivo->timestamps = false;
    $efetivo->updated_at = now()->subDays(2);
    $efetivo->save();

    $bca = bcaJaAnalisado();
    $analisadoEmOriginal = $bca->analisado_em;

    Livewire::test(BuscaBca::class)
        ->set('data', $bca->data->format('Y-m-d'))
        ->call('buscar')
        ->call('executarBusca');

    expect($bca->fresh()->analisado_em->timestamp)->toBe($analisadoEmOriginal->timestamp);
});

it('nao reenvia email de ocorrencia antiga que o operador optou por nao notificar', function () {
    Queue::fake();
    $this->actingAs(User::factory()->create(['role' => 'operador']));

    $bca = bcaJaAnalisado();
    $efetivo = efetivoFulano();

    // Ocorrencia ja existia e nunca foi enviada (operador decidiu nao notificar).
    BcaOcorrencia::create([
        'bca_id' => $bca->id,
        'efetivo_id' => $efetivo->id,
        'snippet' => 'antigo',
        'tipo_match' => 'SARAM',
        'quantidade' => 1,
    ]);

    // Outro militar entra no efetivo, disparando a reanalise.
    efetivoFulano([
        'saram' => '7654321',
        'nome_guerra' => 'BELTRANO',
        'nome_completo' => 'BELTRANO DE TAL',
        'email' => 'beltrano@example.test',
    ]);

    Livewire::test(BuscaBca::class)
        ->set('data', $bca->data->format('Y-m-d'))
        ->call('buscar')
        ->call('executarBusca');

    // BELTRANO nao esta no texto, FULANO ja tinha ocorrencia: nenhum email novo.
    Queue::assertNotPushed(EnviarEmailNotificacaoJob::class);
});

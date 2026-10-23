<?php

use App\Jobs\EnviarEmailNotificacaoJob;
use App\Livewire\BuscaBca;
use App\Models\Bca;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use App\Models\PalavraChave;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function cenarioDuasUnidades(): array
{
    $unidadeA = Unidade::create(['nome' => 'Unidade A', 'sigla' => 'UA', 'codigo' => 'UA', 'ativo' => true]);
    $unidadeB = Unidade::create(['nome' => 'Unidade B', 'sigla' => 'UB', 'codigo' => 'UB', 'ativo' => true]);
    $efetivoA = Efetivo::factory()->create(['unidade_id' => $unidadeA->id, 'email' => 'a@exemplo.test']);
    $bca = Bca::factory()->create();
    $ocorrencia = BcaOcorrencia::create([
        'bca_id' => $bca->id, 'efetivo_id' => $efetivoA->id, 'snippet' => 'trecho',
    ]);

    return [$unidadeA, $unidadeB, $ocorrencia];
}

it('operador sem unidade nao ve palavras de nenhuma unidade (fail-closed)', function () {
    PalavraChave::create(['palavra' => 'SIGILOSA', 'ativa' => true]);
    $user = User::factory()->create(['role' => 'operador', 'unidade_id' => null]);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)->assertSet('palavrasDisponiveis', []);
});

it('operador de outra unidade nao envia e-mail de ocorrencia alheia (IDOR)', function () {
    Queue::fake();
    [, $unidadeB, $ocorrencia] = cenarioDuasUnidades();
    $user = User::factory()->create(['role' => 'operador', 'unidade_id' => $unidadeB->id]);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)->call('enviarEmail', $ocorrencia->id);

    Queue::assertNotPushed(EnviarEmailNotificacaoJob::class);
});

it('operador de outra unidade nao previsualiza e-mail de ocorrencia alheia (IDOR)', function () {
    [, $unidadeB, $ocorrencia] = cenarioDuasUnidades();
    $user = User::factory()->create(['role' => 'operador', 'unidade_id' => $unidadeB->id]);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)
        ->call('abrirPreview', $ocorrencia->id)
        ->assertSet('previewData.error', fn ($v) => ! empty($v));
});

it('operador da mesma unidade continua enviando normalmente', function () {
    Queue::fake();
    [$unidadeA, , $ocorrencia] = cenarioDuasUnidades();
    $user = User::factory()->create(['role' => 'operador', 'unidade_id' => $unidadeA->id]);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)->call('enviarEmail', $ocorrencia->id);

    Queue::assertPushed(EnviarEmailNotificacaoJob::class);
});

<?php

use App\Jobs\AnalisarEfetivoJob;
use App\Jobs\BaixarBcaJob;
use App\Jobs\ProcessarBcaJob;
use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Models\PalavraChave;
use App\Services\BcaAnalysisService;
use App\Services\BcaDownloadService;
use App\Services\BcaProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Cache::flush();
});

it('BaixarBcaJob cria BcaExecucao quando BCA nao encontrado', function () {
    Queue::fake();

    $job = new BaixarBcaJob('2026-03-14', []);
    $job->handle(app(BcaDownloadService::class));

    expect(BcaExecucao::where('status', 'sem_bca')->exists())->toBeTrue();
});

it('ProcessarBcaJob cria BcaExecucao quando falhar ao processar', function () {
    Queue::fake();

    $bca = Bca::factory()->create(['url' => null]);

    $job = new ProcessarBcaJob($bca->id, []);
    $job->handle(app(BcaProcessingService::class));

    expect(BcaExecucao::where('status', 'falha')->exists())->toBeTrue();
});

it('AnalisarEfetivoJob registra execucao com keywords', function () {
    $bca = Bca::factory()->create([
        'texto_completo' => 'BCA com KC-390 mencionado',
    ]);

    PalavraChave::factory()->create(['palavra' => 'KC-390', 'ativa' => true]);

    $job = new AnalisarEfetivoJob($bca->id, 'manual', ['KC-390']);
    $job->handle(app(BcaAnalysisService::class));

    $execucao = BcaExecucao::latest()->first();
    expect($execucao->tipo)->toBe('manual');
    expect($execucao->status)->toBe('sucesso');
});

it('AnalisarEfetivoJob filtra keywords inativas quando nao ha parametro', function () {
    $bca = Bca::factory()->create([
        'texto_completo' => 'BCA com FX-2 mencionado',
    ]);

    PalavraChave::factory()->create(['palavra' => 'FX-2', 'ativa' => false]);

    $job = new AnalisarEfetivoJob($bca->id, 'automatica', []);
    $job->handle(app(BcaAnalysisService::class));

    $execucao = BcaExecucao::latest()->first();
    $mensagem = json_decode($execucao->mensagem, true);

    expect($mensagem)->toBeNull();
});

<?php

use App\Models\Bca;
use App\Services\BcaProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Storage::fake('public');
    Log::shouldReceive('info')->andReturnNull()->byDefault();
    Log::shouldReceive('warning')->andReturnNull()->byDefault();
    Log::shouldReceive('error')->andReturnNull()->byDefault();
});

it('retorna null quando BCA nao tem URL', function () {
    $bca = Bca::factory()->create([
        'url' => null,
    ]);

    $service = app(BcaProcessingService::class);
    $result = $service->processarPdf($bca);

    expect($result)->toBeNull();
});

it('retorna null quando arquivo PDF nao existe', function () {
    $bca = Bca::factory()->create([
        'url' => 'bcas/nao-existe.pdf',
    ]);

    $service = app(BcaProcessingService::class);
    $result = $service->processarPdf($bca);

    expect($result)->toBeNull();
});

it('ignora cache de texto obsoleto e usa o PDF como fonte', function () {
    // As chaves bca:texto/bca:url foram removidas: a extracao le o arquivo e
    // grava em bcas.texto_completo, que passa a ser a unica fonte de verdade.
    // Antes, um texto em cache era devolvido mesmo sem o PDF existir.
    $bca = Bca::factory()->create(['url' => 'bcas/nao-existe.pdf']);

    Cache::put("bca:texto:{$bca->data->format('Y-m-d')}", 'texto obsoleto', now()->addDays(30));

    expect(app(BcaProcessingService::class)->processarPdf($bca))->toBeNull();
});

it('nao escreve mais as chaves de cache de texto', function () {
    Storage::disk('public')->put('bcas/test.pdf', '%PDF-1.4 conteudo');

    $bca = Bca::factory()->create(['url' => 'bcas/test.pdf']);
    $data = $bca->data->format('Y-m-d');

    app(BcaProcessingService::class)->processarPdf($bca);

    expect(Cache::get("bca:texto:{$data}"))->toBeNull()
        ->and(Cache::get("bca:url:{$data}"))->toBeNull();
});

it('atualiza BCA com texto extraido e marca processado_em', function () {
    Storage::disk('public')->put('bcas/test.pdf', '%PDF-1.4 test content');

    $bca = Bca::factory()->create([
        'url' => 'bcas/test.pdf',
        'texto_completo' => null,
        'processado_em' => null,
    ]);

    $service = app(BcaProcessingService::class);
    $result = $service->processarPdf($bca);

    expect($result)->not->toBeNull();

    $bca->refresh();
    expect($bca->texto_completo)->not->toBeNull();
    expect($bca->processado_em)->not->toBeNull();
})->skip('pdftotext nao disponivel no ambiente de teste');

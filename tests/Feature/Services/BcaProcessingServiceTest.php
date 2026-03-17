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

it('retorna texto do cache quando disponivel', function () {
    $bca = Bca::factory()->create();
    $cachedText = 'Texto em cache';

    Cache::put("bca:texto:{$bca->data->format('Y-m-d')}", $cachedText, now()->addDays(30));

    $service = app(BcaProcessingService::class);
    $result = $service->processarPdf($bca);

    expect($result)->toBe($cachedText);
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

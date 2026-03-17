<?php

use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Models\BcaOcorrencia;
use App\Models\Efetivo;
use App\Models\PalavraChave;
use App\Services\BcaAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('detects efetivo by SARAM in BCA text', function () {
    $efetivo = Efetivo::factory()->create([
        'saram' => '1234567',
        'nome_guerra' => 'TESTEIRO',
        'nome_completo' => 'NOME TESTEIRO DA SILVA',
        'ativo' => true,
        'oculto' => false,
    ]);

    $bca = Bca::factory()->create([
        'texto_completo' => 'BCA 047 de 14/03/2026. Art. 1234 Militar 1234567 promovido.',
    ]);

    $service = app(BcaAnalysisService::class);
    $count = $service->analisar($bca);

    expect($count)->toBe(1);
    expect(BcaOcorrencia::where('bca_id', $bca->id)->where('efetivo_id', $efetivo->id)->exists())->toBeTrue();
});

it('detects efetivo by hyphenated SARAM', function () {
    $efetivo = Efetivo::factory()->create([
        'saram' => '1234567',
        'nome_guerra' => 'TESTEIRO',
        'nome_completo' => 'NOME TESTEIRO DA SILVA',
        'ativo' => true,
        'oculto' => false,
    ]);

    $bca = Bca::factory()->create([
        'texto_completo' => 'BCA 047 de 14/03/2026. Art. 1234 Militar 123456-7 promovido.',
    ]);

    $service = app(BcaAnalysisService::class);
    $count = $service->analisar($bca);

    expect($count)->toBe(1);
});

it('does not include oculto efetivos', function () {
    Efetivo::factory()->create([
        'saram' => '1234567',
        'nome_guerra' => 'TESTEIRO',
        'nome_completo' => 'NOME TESTEIRO DA SILVA',
        'ativo' => true,
        'oculto' => true, // oculto!
    ]);

    $bca = Bca::factory()->create([
        'texto_completo' => 'BCA 047. Militar 1234567 promovido.',
    ]);

    $service = app(BcaAnalysisService::class);
    $count = $service->analisar($bca);

    expect($count)->toBe(0);
});

it('does not create duplicate occurrences', function () {
    $efetivo = Efetivo::factory()->create([
        'saram' => '1234567',
        'nome_guerra' => 'TESTEIRO',
        'nome_completo' => 'NOME TESTEIRO DA SILVA',
        'ativo' => true,
        'oculto' => false,
    ]);

    $bca = Bca::factory()->create([
        'texto_completo' => 'BCA 047. Militar 1234567 promovido.',
    ]);

    $service = app(BcaAnalysisService::class);
    $service->analisar($bca);
    $service->analisar($bca); // second time should not create duplicate

    expect(BcaOcorrencia::where('bca_id', $bca->id)->count())->toBe(1);
});

it('records active keywords found in BCA text', function () {
    PalavraChave::factory()->create(['palavra' => 'KC-390', 'ativa' => true]);
    PalavraChave::factory()->create(['palavra' => 'FX-2', 'ativa' => false]); // inactive - should NOT match

    $bca = Bca::factory()->create([
        'texto_completo' => 'BCA 047. Aeronave KC-390 mencionada no artigo 5.',
    ]);

    $service = app(BcaAnalysisService::class);
    $service->analisar($bca);

    $execucao = BcaExecucao::latest()->first();
    $mensagem = json_decode($execucao->mensagem, true);

    expect(array_keys($mensagem['keywords_encontradas']))->toContain('KC-390');
    expect(array_keys($mensagem['keywords_encontradas']))->not->toContain('FX-2');
});

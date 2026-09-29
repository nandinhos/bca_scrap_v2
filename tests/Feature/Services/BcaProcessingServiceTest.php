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

// PDF mínimo e válido (com xref correto) contendo apenas $texto — o suficiente para o pdftotext real.
function pdfMinimo(string $texto): string
{
    $conteudo = "BT /F1 12 Tf 20 50 Td ({$texto}) Tj ET";
    $objetos = [
        '<</Type/Catalog/Pages 2 0 R>>',
        '<</Type/Pages/Kids[3 0 R]/Count 1>>',
        '<</Type/Page/Parent 2 0 R/MediaBox[0 0 300 100]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>',
        '<</Length '.strlen($conteudo).">>stream\n{$conteudo}\nendstream",
        '<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>',
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objetos as $i => $objeto) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1)." 0 obj\n{$objeto}\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= 'xref
0 '.(count($objetos) + 1)."\n0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    return $pdf.'trailer<</Size '.(count($objetos) + 1)."/Root 1 0 R>>\nstartxref\n{$xref}\n%%EOF\n";
}

it('atualiza BCA com texto extraido e marca processado_em', function () {
    Storage::disk('public')->put('bcas/test.pdf', pdfMinimo('BOLETIM DO COMANDO DA AERONAUTICA'));

    $bca = Bca::factory()->create([
        'url' => 'bcas/test.pdf',
        'texto_completo' => null,
        'processado_em' => null,
    ]);

    $service = app(BcaProcessingService::class);
    $result = $service->processarPdf($bca);

    expect($result)->not->toBeNull();

    expect($result)->toContain('BOLETIM DO COMANDO DA AERONAUTICA');

    $bca->refresh();
    expect($bca->texto_completo)->toContain('BOLETIM DO COMANDO DA AERONAUTICA');
    expect($bca->processado_em)->not->toBeNull();
});

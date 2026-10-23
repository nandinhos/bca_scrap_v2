<?php

use App\Services\BcaDownloadService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('bcas');
    Cache::flush();
    config([
        'bca.base_url' => 'http://cendoc.test/consulta/',
        'bca.icea_url' => 'http://icea.test/boletim/',
    ]);
});

function pdfFake(): string
{
    return str_repeat('%PDF-1.4 fake ', 200);
}

it('descobre pelo CENDOC e baixa pela URL indicada sem zero-leading na data', function () {
    $urlsPedidas = [];
    Http::fake(function (Request $request) use (&$urlsPedidas) {
        $urlsPedidas[] = $request->url();
        if (str_contains($request->url(), 'busca_bca_data.php')) {
            return Http::response("BCA nº.: 63 de hoje <a href='download.php?ano=2026&bca=bca_63_8-4-2026'>baixar</a>", 200);
        }
        if (str_contains($request->url(), 'download.php')) {
            return Http::response(pdfFake(), 200, ['Content-Type' => 'application/save']);
        }

        return Http::response('nf', 404);
    });

    $path = app(BcaDownloadService::class)->baixarBca('2026-04-08');

    expect($path)->toBe('bcas/2026-04-08.pdf')
        ->and(Storage::disk('bcas')->exists('bcas/2026-04-08.pdf'))->toBeTrue();
    // Regressao 08/05/2026: dia/mes sem zero a esquerda (8-4, nunca 08-04)
    expect(collect($urlsPedidas)->contains(fn ($u) => str_contains($u, '08-04-2026')))->toBeFalse();
});

it('CENDOC fora: usa o numero descoberto e cai para o ICEA', function () {
    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'busca_bca_data.php')) {
            return Http::response("BCA nº.: 63 <a href='download.php?ano=2026&bca=bca_63_8-4-2026'>x</a>", 200);
        }
        if (str_contains($request->url(), 'cendoc.test')) {
            return Http::response('erro', 500);
        }
        if (str_contains($request->url(), 'icea.test/boletim/bca_63_8-4-2026.pdf')) {
            return Http::response(pdfFake(), 200, ['Content-Type' => 'application/pdf']);
        }

        return Http::response('nf', 404);
    });

    $path = app(BcaDownloadService::class)->baixarBca('2026-04-08');

    expect($path)->toBe('bcas/2026-04-08.pdf');
});

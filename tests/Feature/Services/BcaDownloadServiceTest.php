<?php
use App\Services\BcaDownloadService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Cache::flush();
});

it('returns null when cache says nao_encontrado', function () {
    Cache::put('bca:query:2026-03-14', 'nao_encontrado', now()->addHour());

    $service = app(BcaDownloadService::class);
    $result = $service->baixarBca('2026-03-14');

    expect($result)->toBeNull();
});

it('returns storage path when BCA is found via cache url', function () {
    Cache::put('bca:query:2026-03-14', 'http://fake-url.mil.br/bca.pdf', now()->addHours(24));

    Http::fake([
        'http://fake-url.mil.br/bca.pdf' => Http::response('%PDF-1.4 fake pdf content for testing purposes larger than 1000 chars ' . str_repeat('x', 1000), 200, ['Content-Type' => 'application/pdf']),
    ]);

    $service = app(BcaDownloadService::class);
    $result = $service->baixarBca('2026-03-14');

    expect($result)->toBe('bcas/2026-03-14.pdf');
    Storage::disk('public')->assertExists('bcas/2026-03-14.pdf');
});

it('caches nao_encontrado when BCA is not found', function () {
    Http::fake(Http::response('not found', 404));

    $service = app(BcaDownloadService::class);
    $result = $service->baixarBca('2026-03-14');

    expect($result)->toBeNull();
    expect(Cache::get('bca:query:2026-03-14'))->toBe('nao_encontrado');
});

it('rejects PDF larger than max size', function () {
    $hugeBody = str_repeat('x', 51 * 1024 * 1024); // 51MB
    Cache::put('bca:query:2026-03-14', 'http://fake-url.mil.br/bca.pdf', now()->addHours(24));

    Http::fake([
        'http://fake-url.mil.br/bca.pdf' => Http::response($hugeBody, 200, ['Content-Type' => 'application/pdf']),
    ]);

    $service = app(BcaDownloadService::class);
    $result = $service->baixarBca('2026-03-14');

    expect($result)->toBeNull();
})->skip('51MB payload excede memory_limit=128M; requer php -d memory_limit=256M');

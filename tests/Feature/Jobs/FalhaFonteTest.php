<?php

use App\Jobs\BaixarBcaJob;
use App\Models\BcaExecucao;
use App\Services\BcaDownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Cache::flush();
});

it('fonte inacessivel registra falha_fonte, nao sem_bca', function () {
    Http::fake(fn () => throw new ConnectionException('DNS falhou'));

    (new BaixarBcaJob('2026-10-26', []))->handle(app(BcaDownloadService::class));

    expect(BcaExecucao::where('status', 'falha_fonte')->exists())->toBeTrue()
        ->and(BcaExecucao::where('status', 'sem_bca')->exists())->toBeFalse();
});

it('fonte respondendo sem BCA continua sem_bca', function () {
    Http::fake(Http::response('not found', 404));

    (new BaixarBcaJob('2026-10-26', []))->handle(app(BcaDownloadService::class));

    expect(BcaExecucao::where('status', 'sem_bca')->exists())->toBeTrue();
});

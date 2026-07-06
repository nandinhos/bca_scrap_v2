<?php

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Log::shouldReceive('warning')->andReturnNull()->byDefault();
});

it('retorna 403 para usuario sem role necessaria', function () {
    $user = User::factory()->create(['role' => 'operador']);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsureRole;

    expect(fn () => $middleware->handle($request, fn () => response()->json(['ok' => true]), 'admin'))
        ->toThrow(HttpException::class, 'Acesso não autorizado.');
});

it('permite acesso para usuario com role correta', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsureRole;

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]), 'admin');

    expect($response->getStatusCode())->toBe(200);
});

it('permite acesso para usuario com uma das roles', function () {
    $user = User::factory()->create(['role' => 'operador']);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new EnsureRole;

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]), 'admin', 'operador');

    expect($response->getStatusCode())->toBe(200);
});

it('retorna 403 para usuario nao autenticado', function () {
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => null);

    $middleware = new EnsureRole;

    expect(fn () => $middleware->handle($request, fn () => response()->json(['ok' => true]), 'admin'))
        ->toThrow(HttpException::class, 'Acesso não autorizado.');
});

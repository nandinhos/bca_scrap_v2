<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('health e metrics exigem autenticacao ou token', function () {
    config(['bca.health_token' => 'token-de-teste']);

    $this->get('/health')->assertUnauthorized();
    $this->get('/metrics')->assertUnauthorized();
    $this->get('/health', ['X-Health-Token' => 'errado'])->assertUnauthorized();
    $this->get('/health', ['X-Health-Token' => 'token-de-teste'])->assertOk();

    $user = User::factory()->create();
    $this->actingAs($user)->get('/health')->assertOk();
});

it('health expoe contagem de failed_jobs e fila', function () {
    config(['bca.health_token' => 'token-de-teste']);

    $this->get('/health', ['X-Health-Token' => 'token-de-teste'])
        ->assertOk()
        ->assertJsonStructure(['checks' => ['failed_jobs', 'jobs_na_fila']]);
});

it('health calcula fila_mais_antigo_seg quando ha job na tabela jobs', function () {
    config(['bca.health_token' => 'token-de-teste']);

    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->subSeconds(30)->timestamp,
    ]);

    $response = $this->get('/health', ['X-Health-Token' => 'token-de-teste'])
        ->assertOk()
        ->assertJsonPath('checks.jobs_na_fila', 1);

    expect($response->json('checks.fila_mais_antigo_seg'))->toBeGreaterThanOrEqual(25);
});

it('login sofre throttle apos 5 tentativas', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->post('/login', ['email' => 'x@exemplo.test', 'password' => 'errada']);
    }

    $this->post('/login', ['email' => 'x@exemplo.test', 'password' => 'errada'])
        ->assertTooManyRequests();
});

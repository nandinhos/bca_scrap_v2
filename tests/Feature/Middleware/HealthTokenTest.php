<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

it('login sofre throttle apos 5 tentativas', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->post('/login', ['email' => 'x@exemplo.test', 'password' => 'errada']);
    }

    $this->post('/login', ['email' => 'x@exemplo.test', 'password' => 'errada'])
        ->assertTooManyRequests();
});

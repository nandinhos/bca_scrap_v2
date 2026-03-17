<?php

use App\Livewire\BuscaBca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('pode ser renderizado', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)
        ->assertStatus(200);
});

it('valida que a data é obrigatória', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)
        ->set('data', '')
        ->call('buscar')
        ->assertHasErrors(['data' => 'required']);
});

it('valida formato de data', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)
        ->set('data', 'data-invalida')
        ->call('buscar')
        ->assertHasErrors(['data' => 'date_format']);
});

it('valida que data nao pode ser futura', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)
        ->set('data', now()->addDay()->format('Y-m-d'))
        ->call('buscar')
        ->assertHasErrors(['data' => 'before_or_equal']);
});

it('exibe estado inicial sem resultados', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)
        ->assertSet('ocorrencias', [])
        ->assertSet('buscando', false);
});

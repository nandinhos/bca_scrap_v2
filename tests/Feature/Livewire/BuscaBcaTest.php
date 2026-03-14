<?php

use App\Livewire\BuscaBca;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
        ->assertHasErrors(['data' => 'date']);
});

it('exibe estado inicial sem resultados', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(BuscaBca::class)
        ->assertSet('ocorrencias', [])
        ->assertSet('buscando', false);
});

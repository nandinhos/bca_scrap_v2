<?php

use App\Livewire\HistoricoOcorrencias;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('pode ser renderizado', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(HistoricoOcorrencias::class)
        ->assertStatus(200);
});

it('exibe estado inicial', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(HistoricoOcorrencias::class)
        ->assertSet('filtroData', '')
        ->assertSet('filtroMilitar', '');
});

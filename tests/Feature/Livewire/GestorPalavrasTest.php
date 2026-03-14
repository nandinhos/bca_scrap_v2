<?php

use App\Livewire\GestorPalavras;
use App\Models\PalavraChave;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('pode ser renderizado', function () {
    $user = User::factory()->create(['role' => 'operador']);
    $this->actingAs($user);

    Livewire::test(GestorPalavras::class)
        ->assertStatus(200);
});

it('admin pode criar palavra-chave', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Livewire::test(GestorPalavras::class)
        ->set('palavra', 'NOVA-PALAVRA')
        ->set('cor', 'FF0000')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('palavras_chaves', [
        'palavra' => 'NOVA-PALAVRA',
        'ativa' => false,
    ]);
});

it('operador não pode criar palavra-chave', function () {
    $operador = User::factory()->create(['role' => 'operador']);
    $this->actingAs($operador);

    Livewire::test(GestorPalavras::class)
        ->set('palavra', 'NOVA-PALAVRA')
        ->call('save')
        ->assertForbidden();
})->skip('autorização em nível de rota');

it('pode alternar ativa status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);
    $palavra = PalavraChave::factory()->create(['ativa' => false]);

    Livewire::test(GestorPalavras::class)
        ->call('toggleAtiva', $palavra->id);

    $this->assertDatabaseHas('palavras_chaves', [
        'id' => $palavra->id,
        'ativa' => true,
    ]);
});

<?php

use App\Livewire\ListagemEfetivo;
use App\Models\Efetivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('pode ser renderizado', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Livewire::test(ListagemEfetivo::class)
        ->assertStatus(200);
});

it('lista efetivos ativos', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);
    Efetivo::factory()->count(3)->create(['ativo' => true, 'oculto' => false]);
    Efetivo::factory()->create(['ativo' => false]);

    Livewire::test(ListagemEfetivo::class)
        ->assertSeeHtml('listagem-efetivo');
})->skip('ilike não suportado em SQLite; roda apenas contra PostgreSQL');

it('admin pode criar efetivo', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Livewire::test(ListagemEfetivo::class)
        ->set('nomeCompleto', 'SILVA SANTOS')
        ->set('nomeGuerra', 'SILVA')
        ->set('saram', '1234567')
        ->set('email', 'silva@fab.mil.br')
        ->set('posto', 'Sgt')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('efetivos', [
        'nome_completo' => 'SILVA SANTOS',
        'saram' => '1234567',
    ]);
});

it('valida campos obrigatórios ao criar efetivo', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Livewire::test(ListagemEfetivo::class)
        ->set('nomeCompleto', '')
        ->set('nomeGuerra', '')
        ->set('saram', '')
        ->call('save')
        ->assertHasErrors(['nomeCompleto', 'nomeGuerra', 'saram']);
});

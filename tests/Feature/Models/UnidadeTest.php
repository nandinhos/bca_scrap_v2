<?php

use App\Models\Unidade;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a unidade', function () {
    $unidade = Unidade::create([
        'nome' => 'Grupo de Aviação',
        'sigla' => 'GAC-PAC',
        'codigo' => 'GAC-PAC',
        'ativo' => true,
    ]);

    expect($unidade->id)->toBeInt();
    expect($unidade->nome)->toBe('Grupo de Aviação');
    expect($unidade->sigla)->toBe('GAC-PAC');
    expect($unidade->ativo)->toBeTrue();
});

it('ativa scope returns only active unidades', function () {
    Unidade::create([
        'nome' => 'Ativa',
        'sigla' => 'UNI-1',
        'codigo' => 'UNI-1',
        'ativo' => true,
    ]);

    Unidade::create([
        'nome' => 'Inativa',
        'sigla' => 'UNI-2',
        'codigo' => 'UNI-2',
        'ativo' => false,
    ]);

    $ativas = Unidade::ativa()->get();

    expect($ativas->count())->toBe(1);
    expect($ativas->first()->sigla)->toBe('UNI-1');
});

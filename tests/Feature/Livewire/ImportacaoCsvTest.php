<?php

use App\Livewire\ListagemEfetivo;
use App\Models\Efetivo;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('importa CSV sem duplicar SARAM repetido e conta linha invalida como erro', function () {
    $unidade = Unidade::create(['nome' => 'U', 'sigla' => 'U', 'codigo' => 'U', 'ativo' => true]);
    $user = User::factory()->create(['role' => 'operador', 'unidade_id' => $unidade->id]);
    $this->actingAs($user);

    $csv = "SARAM,Nome Guerra,Nome Completo,Posto,Email\n"
        ."111111-1,ALPHA,ALPHA UM,Maj,a@x.test\n"
        ."111111-1,ALPHA2,ALPHA DOIS,Maj,a@x.test\n"
        ."linha-invalida\n";

    $file = UploadedFile::fake()->createWithContent('efetivo.csv', $csv);

    Livewire::test(ListagemEfetivo::class)
        ->set('uploadedFile', $file)
        ->call('importCsv');

    expect(Efetivo::where('saram', '111111-1')->count())->toBe(1)
        ->and(Efetivo::where('saram', '111111-1')->first()->nome_guerra)->toBe('ALPHA2');
});

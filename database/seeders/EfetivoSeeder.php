<?php

namespace Database\Seeders;

use App\Models\Efetivo;
use Illuminate\Database\Seeder;

class EfetivoSeeder extends Seeder
{
    public function run(): void
    {
        $efetivos = [
            ['saram' => '1000001', 'nome_guerra' => 'EXEMPLO1',   'nome_completo' => 'MILITAR EXEMPLO UM',            'posto' => 'Ten Cel',   'email' => 'exemplo1@bca.local'],
            ['saram' => '1000002', 'nome_guerra' => 'EXEMPLO2',   'nome_completo' => 'MILITAR EXEMPLO DOIS',          'posto' => 'Maj',       'email' => 'exemplo2@bca.local'],
            ['saram' => '1000003', 'nome_guerra' => 'EXEMPLO3',   'nome_completo' => 'MILITAR EXEMPLO TRES',          'posto' => 'Cap',       'email' => 'exemplo3@bca.local'],
            ['saram' => '1000004', 'nome_guerra' => 'EXEMPLO4',   'nome_completo' => 'MILITAR EXEMPLO QUATRO',        'posto' => '1 Ten',     'email' => 'exemplo4@bca.local'],
            ['saram' => '1000005', 'nome_guerra' => 'EXEMPLO5',   'nome_completo' => 'MILITAR EXEMPLO CINCO',         'posto' => 'S Ten',     'email' => 'exemplo5@bca.local'],
        ];

        foreach ($efetivos as $e) {
            Efetivo::firstOrCreate(
                ['saram' => $e['saram']],
                array_merge($e, ['om_origem' => 'EXEMPLO1', 'ativo' => true, 'oculto' => false])
            );
        }
    }
}

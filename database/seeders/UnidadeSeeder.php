<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadeSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            [
                'nome' => 'Grupo de Aviação',
                'sigla' => 'GAC-PAC',
                'codigo' => 'GAC-PAC',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Escritório de Coordenação de Projetos',
                'sigla' => 'ECP-GPX',
                'codigo' => 'ECP-GPX',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Escritório de Coordenação de Projetos',
                'sigla' => 'ECP-POA',
                'codigo' => 'ECP-POA',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Escritório de Coordenação de Projetos',
                'sigla' => 'ECP-IJA',
                'codigo' => 'ECP-IJA',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($unidades as $unidade) {
            DB::table('unidades')->updateOrInsert(
                ['codigo' => $unidade['codigo']],
                $unidade
            );
        }
    }
}

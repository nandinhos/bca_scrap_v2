<?php

namespace Database\Seeders;

use App\Models\Unidade;
use Illuminate\Database\Seeder;

class UnidadeSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotente: firstOrCreate por sigla (unica).
        // Le env vars para suportar instalacao one-click (install.sh define OM_*).
        // Defaults genericos (sem dados reais de OM) para uso local/dev.
        $sigla = env('OM_SIGLA', 'EXEMPLO1');
        $nome = env('OM_NAME', 'EXEMPLO1');
        $codigo = env('OM_CODE', '00001');

        Unidade::firstOrCreate(
            ['sigla' => $sigla],
            [
                'nome' => $nome,
                'codigo' => $codigo,
                'ativo' => true,
            ]
        );
    }
}

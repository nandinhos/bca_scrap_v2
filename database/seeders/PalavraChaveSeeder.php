<?php

namespace Database\Seeders;

use App\Models\PalavraChave;
use Illuminate\Database\Seeder;

class PalavraChaveSeeder extends Seeder
{
    public function run(): void
    {
        $palavras = [
            ['palavra' => 'GAC-PAC',  'cor' => '3498DB', 'ativa' => false],
            ['palavra' => 'COPAC',    'cor' => '2d54f0', 'ativa' => false],
            ['palavra' => 'LINK-BR',  'cor' => 'db2424', 'ativa' => false],
            ['palavra' => 'KC-390',   'cor' => '24db42', 'ativa' => false],
            ['palavra' => 'KC-X',     'cor' => '48d560', 'ativa' => false],
            ['palavra' => 'FX-2',     'cor' => 'd3d548', 'ativa' => false],
            ['palavra' => 'CAS',      'cor' => '48abd5', 'ativa' => false],
            ['palavra' => 'CAA',      'cor' => '48abd5', 'ativa' => false],
            ['palavra' => 'CEAG',     'cor' => '48abd5', 'ativa' => false],
        ];

        foreach ($palavras as $p) {
            PalavraChave::firstOrCreate(['palavra' => $p['palavra']], $p);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Unidade;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@bca.local');
        $adminName = env('ADMIN_NAME', 'Administrador');
        $adminPassword = env('ADMIN_PASSWORD', 'change-me-in-production');

        // Tenta vincular o admin a uma unidade (a do env OM_SIGLA, ou a primeira cadastrada).
        // Se nao houver nenhuma unidade, deixa unidade_id null (admin "global").
        $unidadeId = null;
        $sigla = env('OM_SIGLA');
        if ($sigla) {
            $u = Unidade::where('sigla', $sigla)->first();
            if ($u) {
                $unidadeId = $u->id;
            }
        }
        if ($unidadeId === null) {
            $u = Unidade::first();
            if ($u) {
                $unidadeId = $u->id;
            }
        }

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'unidade_id' => $unidadeId,
            ]
        );
    }
}

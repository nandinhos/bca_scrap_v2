<?php

namespace Database\Seeders;

use App\Models\Unidade;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminDevSeeder extends Seeder
{
    public function run(): void
    {
        // Guard de ambiente: este seeder cria um admin com senha conhecida
        // (admin@bca.local / password) e SÓ deve rodar em dev/testes.
        // Em produção, o admin é criado pelo UserSeeder a partir das vars ADMIN_*.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // 1) Garante unidade EXEMPLO1
        $unidade = Unidade::firstOrCreate(
            ['sigla' => 'EXEMPLO1'],
            ['nome' => 'EXEMPLO1', 'codigo' => '00001', 'ativo' => true]
        );

        // 2) Garante admin@bca.local com senha 'password'
        $admin = User::firstOrCreate(
            ['email' => 'admin@bca.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'unidade_id' => $unidade->id,
            ]
        );

        // 3) Garante senha correta e vinculacao a unidade
        $admin->password = Hash::make('password');
        $admin->role = 'admin';
        if ($admin->unidade_id === null) {
            $admin->unidade_id = $unidade->id;
        }
        $admin->save();

        echo 'OK: admin@bca.local / password vinculado a ' . $unidade->sigla . PHP_EOL;
    }
}

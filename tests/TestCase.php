<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Trava de seguranca: RefreshDatabase roda migrate:fresh, entao apontar a
     * suite para o banco errado apaga dados reais. Aborta antes de qualquer
     * teste se o banco em uso nao for o de teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // A suíte roda no mesmo container da produção: sem isso, o log dos testes
        // cai no laravel.log real e polui o diagnóstico de incidentes.
        config(['logging.channels.single.path' => storage_path('logs/testing.log')]);
        // Producao usa canal daily (rotacao); a suite isola ele tambem.
        config(['logging.channels.daily.path' => storage_path('logs/testing.log')]);
        app('log')->forgetChannel('stack');
        app('log')->forgetChannel('single');
        app('log')->forgetChannel('daily');

        $banco = DB::connection()->getDatabaseName();

        if ($banco !== 'bca_test' && ! str_contains($banco, ':memory:')) {
            $this->fail(
                "Ambiente de teste inseguro: conectado ao banco [{$banco}], esperado [bca_test]. ".
                'Verifique tests/bootstrap.php e as variaveis de ambiente do container.'
            );
        }
    }
}

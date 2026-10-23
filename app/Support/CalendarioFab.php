<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

class CalendarioFab
{
    /**
     * Feriado (nacional/FAB) segundo config/bca.php:
     * - 'feriados_md': recorrentes todo ano no formato m-d (inclui 10-23, Dia do Aviador)
     * - 'feriados': datas moveis por ano (Carnaval, Sexta-feira Santa, Corpus Christi)
     * ponytail: array versionado em config, sem tabela/CRUD; revisar 1x ao ano por commit.
     */
    public static function eFeriado(Carbon|CarbonImmutable $data): bool
    {
        $fixos = config('bca.feriados_md', []);
        $moveis = config('bca.feriados', []);

        return in_array($data->format('m-d'), $fixos, true)
            || in_array($data->format('Y-m-d'), $moveis, true);
    }

    public static function eDiaUtil(Carbon|CarbonImmutable $data): bool
    {
        return ! $data->isWeekend() && ! self::eFeriado($data);
    }

    public static function diaUtilAnterior(Carbon|CarbonImmutable $data): CarbonImmutable
    {
        $anterior = CarbonImmutable::instance($data)->subDay();

        while (! self::eDiaUtil($anterior)) {
            $anterior = $anterior->subDay();
        }

        return $anterior;
    }
}

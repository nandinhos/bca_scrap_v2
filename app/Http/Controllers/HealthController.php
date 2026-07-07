<?php

namespace App\Http\Controllers;

use App\Models\BcaExecucao;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function check()
    {
        $status = 'healthy';
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error: '.$e->getMessage();
            $status = 'unhealthy';
        }

        try {
            $recentExecucoes = BcaExecucao::where('data_execucao', '>=', now()->subHours(24))
                ->select('status')
                ->get()
                ->groupBy('status')
                ->map(fn ($group) => $group->count());

            $checks['execucoes_24h'] = [
                'sucesso' => $recentExecucoes['sucesso'] ?? 0,
                'falha' => $recentExecucoes['falha'] ?? 0,
                'sem_bca' => $recentExecucoes['sem_bca'] ?? 0,
            ];
        } catch (\Exception $e) {
            $checks['execucoes_24h'] = 'error: '.$e->getMessage();
            $status = 'unhealthy';
        }

        $response = [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        $httpCode = $status === 'healthy' ? 200 : 503;

        return response()->json($response, $httpCode);
    }

    public function metrics()
    {
        $ultimos7dias = BcaExecucao::where('data_execucao', '>=', now()->subDays(7))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        $tempoMedio = null;
        try {
            $diffSegundos = DB::table('bca_execucoes')
                ->where('data_execucao', '>=', now()->subDays(7))
                ->whereNotNull('data_execucao')
                ->select(DB::raw("extract(epoch from (data_execucao - lag(data_execucao) over (order by data_execucao))) as diff"))
                ->get()
                ->pluck('diff')
                ->filter()
                ->values();

            $tempoMedio = $diffSegundos->isNotEmpty() ? round($diffSegundos->avg(), 2) : null;
        } catch (\Throwable $e) {
            $tempoMedio = null;
        }

        return response()->json([
            'periodo' => 'ultimos_7_dias',
            'total_execucoes' => array_sum($ultimos7dias),
            'por_status' => $ultimos7dias,
            'tempo_medio_segundos' => $tempoMedio,
        ]);
    }
}

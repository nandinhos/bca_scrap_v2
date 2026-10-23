<?php

namespace App\Http\Controllers;

use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Support\CalendarioFab;
use Carbon\CarbonImmutable;
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
            $checks['database'] = 'error';
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
            $checks['execucoes_24h'] = 'error';
            $status = 'unhealthy';
        }

        try {
            $checks['failed_jobs'] = DB::table('failed_jobs')->count();
            $checks['jobs_na_fila'] = DB::table('jobs')->count();
            $maisAntigo = DB::table('jobs')->min('created_at');
            $checks['fila_mais_antigo_seg'] = $maisAntigo ? (int) now()->diffInSeconds($maisAntigo, true) : 0;
        } catch (\Exception $e) {
            $checks['failed_jobs'] = 'error';
            $checks['jobs_na_fila'] = 'error';
        }

        try {
            $ultimaAuto = BcaExecucao::where('tipo', 'automatica')->max('data_execucao');
            $checks['ultima_execucao_automatica'] = $ultimaAuto;
            $livre = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());
            $checks['disco_livre_pct'] = $total ? round($livre / $total * 100, 1) : null;
            if ($checks['disco_livre_pct'] !== null && $checks['disco_livre_pct'] < 10) {
                $status = 'unhealthy';
            }
        } catch (\Exception $e) {
            $checks['operacao'] = 'error';
        }

        try {
            $hoje = CarbonImmutable::now(config('app.local_timezone'));
            if (CalendarioFab::eDiaUtil($hoje) && $hoje->hour >= 12) {
                $checks['bca_do_dia'] = Bca::where('data', $hoje->format('Y-m-d'))->exists() ? 'ok' : 'pendente';
            } else {
                $checks['bca_do_dia'] = 'fora_de_janela';
            }
            $checks['falha_fonte_24h'] = BcaExecucao::where('status', 'falha_fonte')
                ->where('data_execucao', '>=', now()->subHours(24))->count();
        } catch (\Exception $e) {
            $checks['bca_do_dia'] = 'error';
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
            ->pluck('total', 'status')
            ->toArray();

        $tempoMedio = null;
        try {
            $diffSegundos = DB::query()
                ->fromSub(
                    DB::table('bca_execucoes')
                        ->where('data_execucao', '>=', now()->subDays(7))
                        ->whereNotNull('data_execucao')
                        ->select(DB::raw('extract(epoch from (data_execucao - lag(data_execucao) over (order by data_execucao))) as diff')),
                    't'
                )
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

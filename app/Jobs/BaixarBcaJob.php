<?php

namespace App\Jobs;

use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Services\BcaDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BaixarBcaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly string $data,
        public readonly array $keywords = [],
        public readonly bool $suppressEmails = false
    ) {}

    public static function paraHoje(): self
    {
        return new self(now(config('app.local_timezone'))->format('Y-m-d'));
    }

    public function handle(BcaDownloadService $service): void
    {
        Log::info("BaixarBcaJob: starting for date {$this->data}");

        $path = $service->baixarBca($this->data);

        if ($path === null) {
            // falha_fonte (pane/DNS/fonte fora) nao pode parecer "nao publicado"
            $fonteOk = $service->fonteRespondeu;
            Log::info("BaixarBcaJob: no BCA found for {$this->data} (fonte respondeu: ".($fonteOk ? 'sim' : 'nao').')');

            BcaExecucao::create([
                'tipo' => 'automatica',
                'data_execucao' => now(),
                'status' => $fonteOk ? 'sem_bca' : 'falha_fonte',
                'mensagem' => $fonteOk
                    ? "BCA não encontrado para {$this->data}"
                    : "Fonte CENDOC/ICEA inacessivel ao buscar BCA de {$this->data}",
                'registros_processados' => 0,
            ]);

            return;
        }

        $cachedUrl = Cache::get("bca:query:{$this->data}");
        preg_match('/bca_(\d+)_/', basename((string) ($cachedUrl ?? $path)), $m);
        $numero = $m[1] ?? '0';

        try {
            DB::transaction(function () use ($path, $numero) {
                $bca = Bca::updateOrCreate(
                    ['data' => $this->data],
                    ['numero' => $numero, 'url' => $path]
                );

                ProcessarBcaJob::dispatch($bca->id, $this->keywords, $this->suppressEmails);
            });
        } catch (\Throwable $e) {
            Log::error("BaixarBcaJob: transaction failed for {$this->data}: ".$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("BaixarBcaJob failed for {$this->data}: ".$exception->getMessage());

        BcaExecucao::create([
            'tipo' => 'automatica',
            'data_execucao' => now(),
            'status' => 'falha',
            'mensagem' => 'Download falhou: '.$exception->getMessage(),
            'registros_processados' => 0,
        ]);
    }
}

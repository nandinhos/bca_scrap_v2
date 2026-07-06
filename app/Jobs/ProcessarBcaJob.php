<?php

namespace App\Jobs;

use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Services\BcaProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarBcaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $bcaId,
        public readonly array $keywords = []
    ) {}

    public function handle(BcaProcessingService $service): void
    {
        Log::info("ProcessarBcaJob: starting for BCA ID {$this->bcaId}");

        $bca = Bca::findOrFail($this->bcaId);
        Log::info("ProcessarBcaJob: processing BCA {$bca->data}");

        $texto = $service->processarPdf($bca);

        if ($texto === null) {
            Log::error("ProcessarBcaJob: failed to extract text from BCA {$bca->data}");

            BcaExecucao::create([
                'tipo' => 'automatica',
                'data_execucao' => now(),
                'status' => 'falha',
                'mensagem' => "Processamento falhou: não foi possível extrair texto do BCA {$bca->data}",
                'registros_processados' => 0,
            ]);

            return;
        }

        AnalisarEfetivoJob::dispatch($this->bcaId, 'manual', $this->keywords);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessarBcaJob failed for BCA ID {$this->bcaId}: ".$exception->getMessage());

        BcaExecucao::create([
            'tipo' => 'automatica',
            'data_execucao' => now(),
            'status' => 'falha',
            'mensagem' => 'Processamento falhou: '.$exception->getMessage(),
            'registros_processados' => 0,
        ]);
    }
}

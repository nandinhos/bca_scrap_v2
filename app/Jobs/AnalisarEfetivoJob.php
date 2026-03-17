<?php

namespace App\Jobs;

use App\Models\Bca;
use App\Services\BcaAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalisarEfetivoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $bcaId,
        public readonly string $tipo = 'automatica',
        public readonly array $keywords = []
    ) {}

    public function handle(BcaAnalysisService $service): void
    {
        $bca = Bca::findOrFail($this->bcaId);
        Log::info("AnalisarEfetivoJob: analyzing BCA {$bca->data}");

        $count = $service->analisar($bca, $this->tipo, $this->keywords);

        Log::info("AnalisarEfetivoJob: done — {$count} occurrences for BCA {$bca->data}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("AnalisarEfetivoJob failed for BCA ID {$this->bcaId}: ".$exception->getMessage());
    }
}

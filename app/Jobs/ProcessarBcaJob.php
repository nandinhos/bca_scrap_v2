<?php
namespace App\Jobs;

use App\Models\Bca;
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
        public readonly int $bcaId
    ) {}

    public function handle(BcaProcessingService $service): void
    {
        $bca = Bca::findOrFail($this->bcaId);
        Log::info("ProcessarBcaJob: processing BCA {$bca->data}");

        $texto = $service->processarPdf($bca);

        if ($texto === null) {
            Log::error("ProcessarBcaJob: failed to extract text from BCA {$bca->data}");
            return;
        }

        AnalisarEfetivoJob::dispatch($this->bcaId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessarBcaJob failed for BCA ID {$this->bcaId}: " . $exception->getMessage());
    }
}

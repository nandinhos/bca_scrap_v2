<?php

namespace App\Jobs;

use App\Mail\CompiladoSadMail;
use App\Models\Bca;
use App\Models\BcaExecucao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarCompiladoSADJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $bcaId
    ) {}

    public function handle(): void
    {
        $bca = Bca::with('ocorrencias.efetivo.unidade')->findOrFail($this->bcaId);

        $ocorrencias = $bca->ocorrencias()
            ->with('efetivo.unidade')
            ->get();

        if ($ocorrencias->isEmpty()) {
            Log::info("EnviarCompiladoSADJob: sem ocorrencias para BCA {$bca->data->format('Y-m-d')}, pulando");

            return;
        }

        $sadEmail = Config::get('bca.sad_email');

        if (empty($sadEmail)) {
            Log::warning('EnviarCompiladoSADJob: sad_email não configurado');

            return;
        }

        Mail::to($sadEmail)->send(new CompiladoSadMail($bca, $ocorrencias));

        Log::info("EnviarCompiladoSADJob: compilado enviado para SAD ({$sadEmail}), {$ocorrencias->count()} militares");

        BcaExecucao::create([
            'tipo' => 'compilado_sad',
            'data_execucao' => now(),
            'status' => 'sucesso',
            'mensagem' => "Compilado enviado para SAD: {$ocorrencias->count()} militares",
            'registros_processados' => $ocorrencias->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("EnviarCompiladoSADJob failed for BCA ID {$this->bcaId}: ".$exception->getMessage());

        BcaExecucao::create([
            'tipo' => 'compilado_sad',
            'data_execucao' => now(),
            'status' => 'falha',
            'mensagem' => 'Compilado SAD falhou: '.$exception->getMessage(),
            'registros_processados' => 0,
        ]);
    }
}

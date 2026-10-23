<?php

namespace App\Jobs;

use App\Mail\NotificacaoBcaMail;
use App\Models\BcaExecucao;
use App\Models\BcaOcorrencia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarEmailNotificacaoJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public int $maxExceptions = 3;

    public function __construct(
        public readonly int $ocorrenciaId,
        public readonly ?string $adendo = null
    ) {}

    public function uniqueId(): string
    {
        return 'notificacao-'.$this->ocorrenciaId;
    }

    public function handle(): void
    {
        $ocorrencia = BcaOcorrencia::with(['efetivo', 'bca'])->findOrFail($this->ocorrenciaId);

        // Guarda anti-duplicidade: dispatch repetido nao reenvia.
        // (Reenvio intencional usa novo dispatch apos decisao do operador;
        //  aqui protege contra cliques/jobs duplicados em voo.)
        if ($ocorrencia->foiEnviado() && $this->adendo === null) {
            Log::info("EnviarEmailNotificacaoJob: ocorrencia {$this->ocorrenciaId} ja enviada, pulando");

            return;
        }

        if ($ocorrencia->efetivo->oculto) {
            Log::info("EnviarEmailNotificacaoJob: skipping oculto efetivo {$ocorrencia->efetivo->nome_guerra}");

            return;
        }

        if (empty($ocorrencia->efetivo->email)) {
            Log::warning("EnviarEmailNotificacaoJob: no email for {$ocorrencia->efetivo->nome_guerra}");

            return;
        }

        try {
            Mail::to($ocorrencia->efetivo->email)
                ->send(new NotificacaoBcaMail($ocorrencia, $this->adendo));

            $ocorrencia->update(['enviado_em' => now()]);

            Log::info("EnviarEmailNotificacaoJob: email sent to {$ocorrencia->efetivo->email}");
        } catch (\Exception $e) {
            Log::error("EnviarEmailNotificacaoJob: failed to send email to {$ocorrencia->efetivo->email}: ".$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("EnviarEmailNotificacaoJob failed for ocorrencia {$this->ocorrenciaId}: ".$exception->getMessage());

        BcaExecucao::create([
            'tipo' => 'automatica',
            'data_execucao' => now(),
            'status' => 'falha',
            'mensagem' => "Falha no envio de email para ocorrencia {$this->ocorrenciaId}: ".$exception->getMessage(),
            'registros_processados' => 0,
        ]);
    }
}

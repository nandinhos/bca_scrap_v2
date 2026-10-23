<?php

namespace App\Jobs;

use App\Mail\CompiladoSadMail;
use App\Models\Bca;
use App\Models\BcaExecucao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarCompiladoSADJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $bcaId,
        public readonly ?string $adendo = null,
        public readonly array $destinatariosExtras = []
    ) {}

    public function uniqueId(): string
    {
        return 'compilado-sad-'.$this->bcaId;
    }

    /** Quantas vezes ja esperou os e-mails individuais (serializado na fila). */
    public int $esperas = 0;

    public function handle(): void
    {
        $bca = Bca::with('ocorrencias.efetivo.unidade')->findOrFail($this->bcaId);

        // Encadeamento pobre e confiavel: enquanto houver individual enviavel
        // pendente, o compilado se reagenda (max ~5 min) em vez de sair
        // parcial/vazio. ponytail: release-loop no proprio job dispensa
        // Bus::chain atraves do listener de eventos.
        $pendentes = $bca->ocorrencias()
            ->whereNull('enviado_em')
            ->whereHas('efetivo', fn ($q) => $q->where('oculto', false)->whereNotNull('email')->where('email', '!=', ''))
            ->count();

        if ($pendentes > 0 && $this->esperas < 20) {
            $this->esperas++;
            Log::info("EnviarCompiladoSADJob: aguardando {$pendentes} e-mail(is) individuais do BCA {$bca->data->format('Y-m-d')} (espera {$this->esperas})");
            $this->release(15);

            return;
        }

        $ocorrencias = $bca->ocorrencias()
            ->whereNotNull('enviado_em')
            ->with('efetivo.unidade')
            ->get();

        if ($ocorrencias->isEmpty()) {
            Log::info("EnviarCompiladoSADJob: sem ocorrencias enviadas para BCA {$bca->data->format('Y-m-d')}, pulando");

            return;
        }

        $sadEmail = Config::get('bca.sad_email');

        $destinatarios = array_values(array_unique(array_filter(
            array_merge($sadEmail ? [$sadEmail] : [], $this->destinatariosExtras)
        )));

        if (empty($destinatarios)) {
            Log::warning('EnviarCompiladoSADJob: sad_email não configurado');

            BcaExecucao::create([
                'tipo' => 'compilado_sad',
                'data_execucao' => now(),
                'status' => 'falha',
                'mensagem' => 'Compilado nao enviado: BCA_SAD_EMAIL nao configurado',
                'registros_processados' => 0,
            ]);

            return;
        }

        Mail::to($destinatarios)->send(new CompiladoSadMail($bca, $ocorrencias, $this->adendo));

        Log::info('EnviarCompiladoSADJob: compilado enviado para '.implode(', ', $destinatarios).", {$ocorrencias->count()} militares");

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

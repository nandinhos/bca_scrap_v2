<?php
namespace App\Livewire;

use App\Jobs\BaixarBcaJob;
use App\Jobs\EnviarEmailNotificacaoJob;
use App\Models\Bca;
use App\Models\BcaOcorrencia;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Busca BCA')]
class BuscaBca extends Component
{
    public string $data = '';
    public ?int $bcaId = null;
    public array $ocorrencias = [];
    public bool $buscando = false;
    public ?string $mensagem = null;
    public string $mensagemTipo = 'info';
    public ?string $pdfUrl = null;
    public int $pollCount = 0;

    public function mount(): void
    {
        $this->data = now()->format('Y-m-d');
    }

    public function buscar(): void
    {
        $this->validate(['data' => 'required|date']);
        $this->ocorrencias = [];
        $this->bcaId = null;
        $this->pdfUrl = null;
        $this->mensagem = null;
        $this->pollCount = 0;
        $this->buscando = true;
    }

    public function executarBusca(): void
    {
        if (!$this->buscando) return;

        // Cache hit — already processed and analyzed
        $bca = \App\Models\Bca::where('data', $this->data)->whereNotNull('analisado_em')->first();
        if ($bca) {
            // Re-run analysis to ensure missing emails are sent
            app(\App\Services\BcaAnalysisService::class)->analisar($bca, 'manual');
            $this->finalizarBusca($bca);
            return;
        }

        try {
            \App\Jobs\BaixarBcaJob::dispatch($this->data);
            $this->mensagem = "Iniciada busca e download do BCA...";
            $this->mensagemTipo = 'info';
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("BuscaBca dispatch job failed: " . $e->getMessage());
            $this->mensagem = "Erro ao iniciar busca.";
            $this->mensagemTipo = 'error';
            $this->buscando = false;
        }

        $bca = \App\Models\Bca::where('data', $this->data)->whereNotNull('processado_em')->first();
        if ($bca) {
            $this->finalizarBusca($bca);
        } else {
            // Check if it's already known to be missing
            $execucao = \App\Models\BcaExecucao::where('status', 'sem_bca')
                ->where('mensagem', 'like', "%{$this->data}%")
                ->latest()->first();

            if ($execucao) {
                $this->mensagem = "BCA não encontrado para {$this->data}.";
                $this->mensagemTipo = 'warning';
                $this->buscando = false;
            } else {
                // Keep buscando=true and let wire:poll handle checkStatus
                $this->mensagem = "Download concluído, extraindo informações...";
                $this->mensagemTipo = 'info';
            }
        }
    }

    public function checkStatus(): void
    {
        if (!$this->buscando || $this->bcaId) return;

        $this->pollCount++;

        // Timeout after 40 polls × 3s = 2 minutes
        if ($this->pollCount > 40) {
            $this->mensagem = 'Tempo limite excedido. Tente novamente.';
            $this->mensagemTipo = 'error';
            $this->buscando = false;
            return;
        }

        $bca = \App\Models\Bca::where('data', $this->data)->whereNotNull('analisado_em')->first();
        if ($bca) {
            $this->finalizarBusca($bca);
            return;
        }

        $execucao = \App\Models\BcaExecucao::where('data_execucao', '>=', now()->subMinutes(5))
            ->where('mensagem', 'like', "%{$this->data}%")
            ->latest()->first();

        if ($execucao?->status === 'sem_bca') {
            $this->mensagem = "BCA não encontrado para {$this->data}.";
            $this->mensagemTipo = 'warning';
            $this->buscando = false;
        } elseif ($execucao?->status === 'falha') {
            $this->mensagem = "Falha no processamento.";
            $this->mensagemTipo = 'error';
            $this->buscando = false;
        }
    }

    private function finalizarBusca(\App\Models\Bca $bca): void
    {
        $this->bcaId = $bca->id;
        $this->pdfUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($bca->url);
        $this->ocorrencias = \App\Models\BcaOcorrencia::with('efetivo')
            ->where('bca_id', $bca->id)
            ->get()->map(function($oc) {
                return array_merge($oc->toArray(), [
                    'saram' => $oc->efetivo->saram
                ]);
            })->toArray();

        $n = count($this->ocorrencias);
        $this->mensagem = $n > 0 ? "{$n} militar(es) encontrado(s)." : 'BCA processado, nenhum militar encontrado.';
        $this->mensagemTipo = $n > 0 ? 'success' : 'warning';
        $this->buscando = false;
    }

    public function enviarEmail(int $ocorrenciaId): void
    {
        $oc = BcaOcorrencia::find($ocorrenciaId);
        if ($oc && !$oc->foiEnviado()) {
            EnviarEmailNotificacaoJob::dispatch($ocorrenciaId);
            $this->mensagem = 'Email enviado para ' . $oc->efetivo->nome_guerra;
            $this->mensagemTipo = 'success';
            if ($this->bcaId) {
                $this->ocorrencias = BcaOcorrencia::with('efetivo')
                    ->where('bca_id', $this->bcaId)->get()->toArray();
            }
        }
    }

    public function render()
    {
        return view('livewire.busca-bca');
    }
}

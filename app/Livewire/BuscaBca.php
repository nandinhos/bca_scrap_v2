<?php

namespace App\Livewire;

use App\Jobs\BaixarBcaJob;
use App\Jobs\EnviarEmailNotificacaoJob;
use App\Models\Bca;
use App\Models\BcaExecucao;
use App\Models\BcaOcorrencia;
use App\Models\PalavraChave;
use App\Services\BcaAnalysisService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
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

    public array $palavrasDisponiveis = [];

    public array $palavrasSelecionadas = [];

    public array $keywordsEncontradas = [];

    public bool $buscando = false;

    public ?string $mensagem = null;

    public string $mensagemTipo = 'info';

    public ?string $pdfUrl = null;

    public int $pollCount = 0;

    protected function rules(): array
    {
        return [
            'data' => 'required|date_format:Y-m-d|before_or_equal:today',
        ];
    }

    protected function messages(): array
    {
        return [
            'data.required' => 'A data é obrigatória.',
            'data.date_format' => 'O formato da data deve ser AAAA-MM-DD.',
            'data.before_or_equal' => 'A data não pode ser futura.',
        ];
    }

    public function mount(): void
    {
        $this->data = now()->format('Y-m-d');
        $this->palavrasDisponiveis = PalavraChave::orderBy('palavra')->get()->toArray();
    }

    public function togglePalavra(string $palavra): void
    {
        if (in_array($palavra, $this->palavrasSelecionadas)) {
            $this->palavrasSelecionadas = array_values(array_diff($this->palavrasSelecionadas, [$palavra]));
        } else {
            $this->palavrasSelecionadas[] = $palavra;
        }
    }

    public function buscar(): void
    {
        $this->validate();

        if (! $this->verificarRateLimit()) {
            $this->mensagem = 'Muitas tentativas. Aguarde 1 minuto.';
            $this->mensagemTipo = 'error';

            return;
        }

        $this->ocorrencias = [];
        $this->bcaId = null;
        $this->pdfUrl = null;
        $this->mensagem = null;
        $this->pollCount = 0;
        $this->buscando = true;
    }

    private function verificarRateLimit(): bool
    {
        $key = 'busca-bca:'.($this->data ?? 'default');

        return RateLimiter::attempt($key, 5, fn () => true, 60);
    }

    public function executarBusca(): void
    {
        if (! $this->buscando) {
            return;
        }

        $bca = Bca::where('data', $this->data)->whereNotNull('analisado_em')->first();
        if ($bca) {
            app(BcaAnalysisService::class)->analisar($bca, 'manual', $this->palavrasSelecionadas);
            $this->finalizarBusca($bca);

            return;
        }

        try {
            BaixarBcaJob::dispatch($this->data, $this->palavrasSelecionadas);
            $this->mensagem = 'Iniciada busca e download do BCA...';
            $this->mensagemTipo = 'info';
        } catch (\Throwable $e) {
            Log::error('BuscaBca dispatch job failed: '.$e->getMessage());
            $this->mensagem = 'Erro ao iniciar busca.';
            $this->mensagemTipo = 'error';
            $this->buscando = false;
        }

        $bca = Bca::where('data', $this->data)->whereNotNull('processado_em')->first();
        if ($bca) {
            $this->finalizarBusca($bca);
        } else {
            $execucao = BcaExecucao::where('status', 'sem_bca')
                ->where('mensagem', 'like', "%{$this->data}%")
                ->latest()->first();

            if ($execucao) {
                $this->mensagem = "BCA não encontrado para {$this->data}.";
                $this->mensagemTipo = 'warning';
                $this->buscando = false;
            } else {
                $this->mensagem = 'Download concluído, extraindo informações...';
                $this->mensagemTipo = 'info';
            }
        }
    }

    public function checkStatus(): void
    {
        if (! $this->buscando || $this->bcaId) {
            return;
        }

        $this->pollCount++;

        if ($this->pollCount > 60) {
            $this->mensagem = 'Tempo limite excedido (3min). O arquivo pode estar sendo processado em segundo plano. Atualize a página em instantes.';
            $this->mensagemTipo = 'warning';
            $this->buscando = false;

            return;
        }

        $bca = Bca::where('data', $this->data)->whereNotNull('analisado_em')->first();
        if ($bca) {
            $this->finalizarBusca($bca);

            return;
        }

        $execucao = BcaExecucao::where('data_execucao', '>=', now()->subMinutes(5))
            ->where('mensagem', 'like', "%{$this->data}%")
            ->latest()->first();

        if ($execucao?->status === 'sem_bca') {
            $this->mensagem = "BCA não encontrado para {$this->data}.";
            $this->mensagemTipo = 'warning';
            $this->buscando = false;
        } elseif ($execucao?->status === 'falha') {
            $this->mensagem = 'Falha no processamento.';
            $this->mensagemTipo = 'error';
            $this->buscando = false;
        }
    }

    private function finalizarBusca(Bca $bca): void
    {
        $this->bcaId = $bca->id;
        $this->pdfUrl = Storage::disk('public')->url($bca->url);

        $this->ocorrencias = BcaOcorrencia::with('efetivo')
            ->where('bca_id', $bca->id)
            ->get()->map(function ($oc) {
                return array_merge($oc->toArray(), [
                    'saram' => $oc->efetivo->saram,
                ]);
            })->toArray();

        $cacheData = Cache::get("bca:analise:{$bca->data->format('Y-m-d')}");
        $this->keywordsEncontradas = $cacheData['keywords'] ?? [];

        $n = count($this->ocorrencias);
        $this->mensagem = $n > 0 ? "{$n} militar(es) encontrado(s)." : 'BCA processado, nenhum militar encontrado.';
        $this->mensagemTipo = $n > 0 ? 'success' : 'warning';
        $this->buscando = false;
    }

    public function enviarEmail(int $ocorrenciaId): void
    {
        $oc = BcaOcorrencia::find($ocorrenciaId);
        if ($oc && ! $oc->foiEnviado()) {
            EnviarEmailNotificacaoJob::dispatch($ocorrenciaId);
            $this->mensagem = 'Email enviado para '.$oc->efetivo->nome_guerra;
            $this->mensagemTipo = 'success';
            if ($this->bcaId) {
                $this->ocorrencias = BcaOcorrencia::with('efetivo')
                    ->where('bca_id', $this->bcaId)->get()->map(function ($oc) {
                        return array_merge($oc->toArray(), [
                            'saram' => $oc->efetivo->saram,
                        ]);
                    })->toArray();
            }
        }
    }

    public function enviarTodos(): void
    {
        if (! $this->bcaId) {
            return;
        }

        $count = 0;
        $pendentes = BcaOcorrencia::where('bca_id', $this->bcaId)
            ->whereNull('enviado_em')
            ->get();

        foreach ($pendentes as $oc) {
            EnviarEmailNotificacaoJob::dispatch($oc->id);
            $count++;
        }

        if ($count > 0) {
            $this->mensagem = "{$count} email(s) disparado(s).";
            $this->mensagemTipo = 'success';
            $this->finalizarBusca(Bca::find($this->bcaId));
        } else {
            $this->mensagem = 'Nenhum email pendente para enviar.';
            $this->mensagemTipo = 'info';
        }
    }

    public function render()
    {
        return view('livewire.busca-bca');
    }
}

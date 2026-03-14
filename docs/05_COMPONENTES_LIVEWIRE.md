# 05 - Componentes Livewire

## 📦 Visão Geral dos Componentes

| Componente | Arquivo | Responsabilidade |
|------------|---------|-----------------|
| `busca.busca-bca` | `Busca/BuscaBca.php` | Seleção de data e disparo da busca |
| `busca.resultado-busca` | `Busca/ResultadoBusca.php` | Exibe BCA com snippets de ocorrências |
| `busca.palavras-chave-selector` | `Busca/PalavrasChaveSelector.php` | Seleção de palavras-chave para filtrar |
| `efetivo.listagem-efetivo` | `Efetivo/ListagemEfetivo.php` | Tabela paginada + busca de efetivos |
| `efetivo.formulario-efetivo` | `Efetivo/FormularioEfetivo.php` | Criar / editar efetivo |
| `palavras.gestor-palavras` | `Palavras/GestorPalavras.php` | CRUD completo de palavras-chave |

---

## 🔍 BuscaBca

**Responsabilidade**: Componente principal — seleção de data e disparo da busca.

```php
// app/Http/Livewire/Busca/BuscaBca.php
namespace App\Http\Livewire\Busca;

use App\Models\Bca;
use App\Services\BcaDownloadService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BuscaBca extends Component
{
    #[Validate('required|date_format:d/m/Y')]
    public string $dataSelecionada = '';

    public ?int $bcaId = null;
    public bool $buscando = false;
    public string $erro = '';

    public function mount(): void
    {
        $this->dataSelecionada = now()->format('d/m/Y');
    }

    public function buscar(BcaDownloadService $service): void
    {
        $this->validate();
        $this->erro = '';
        $this->buscando = true;
        $this->bcaId = null;

        try {
            $data = \Carbon\Carbon::createFromFormat('d/m/Y', $this->dataSelecionada)
                ->format('d-m-Y');

            $resultado = $service->buscarBca($data);

            if (!$resultado) {
                $this->erro = "BCA não encontrado para {$this->dataSelecionada}.";
                return;
            }

            $bca = Bca::firstOrCreate(
                ['data_publicacao' => $data],
                [
                    'arquivo_pdf' => $resultado['arquivo'],
                    'fonte'       => $resultado['fonte'],
                    'status'      => 'pendente',
                ]
            );

            $this->bcaId = $bca->id;
            $this->dispatch('bca-carregado', bcaId: $bca->id);

        } catch (\Exception $e) {
            $this->erro = 'Erro ao buscar BCA: ' . $e->getMessage();
        } finally {
            $this->buscando = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.busca.busca-bca');
    }
}
```

```blade
{{-- resources/views/livewire/busca/busca-bca.blade.php --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-semibold text-fab-700 mb-4">Buscar BCA</h2>

    <div class="flex gap-3">
        {{-- Datepicker com Flatpickr via Alpine.js --}}
        <div class="flex-1" x-data x-init="
            flatpickr($refs.input, {
                locale: 'pt',
                dateFormat: 'd/m/Y',
                maxDate: 'today',
                defaultDate: '{{ $dataSelecionada }}',
                onChange: (dates, dateStr) => {
                    $wire.dataSelecionada = dateStr;
                }
            })
        ">
            <input
                x-ref="input"
                type="text"
                placeholder="DD/MM/AAAA"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg
                       focus:ring-2 focus:ring-fab-500 focus:border-fab-500"
            >
        </div>

        <button
            wire:click="buscar"
            wire:loading.attr="disabled"
            :disabled="{{ $buscando ? 'true' : 'false' }}"
            class="px-6 py-2 bg-fab-600 text-white rounded-lg hover:bg-fab-700
                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
            <span wire:loading.remove wire:target="buscar">Buscar BCA</span>
            <span wire:loading wire:target="buscar">Buscando...</span>
        </button>
    </div>

    @error('dataSelecionada')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

    @if($erro)
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm">
            {{ $erro }}
        </div>
    @endif

    @if($bcaId)
        <div class="mt-6">
            <livewire:busca.resultado-busca :bca-id="$bcaId" :key="$bcaId" lazy />
        </div>
    @endif
</div>
```

---

## 📋 ResultadoBusca

**Responsabilidade**: Exibe BCA carregado com listagem de efetivos encontrados e snippets.

```php
// app/Http/Livewire/Busca/ResultadoBusca.php
namespace App\Http\Livewire\Busca;

use App\Models\Bca;
use Livewire\Attributes\Lazy;
use Livewire\Component;

class ResultadoBusca extends Component
{
    public int $bcaId;
    public ?Bca $bca = null;

    #[Lazy]
    public function mount(): void
    {
        $this->bca = Bca::with([
            'ocorrencias.efetivo',
            'execucoes' => fn ($q) => $q->latest()->limit(1),
        ])->find($this->bcaId);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.busca.resultado-busca');
    }
}
```

```blade
{{-- resources/views/livewire/busca/resultado-busca.blade.php --}}
<div>
    {{-- Placeholder enquanto carrega (lazy) --}}
    <div wire:loading class="animate-pulse space-y-3">
        <div class="h-8 bg-gray-200 rounded w-1/3"></div>
        <div class="h-4 bg-gray-200 rounded w-full"></div>
        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
    </div>

    @if($bca)
        <div wire:loading.remove>
            {{-- Cabeçalho do BCA --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold">
                        BCA Nº {{ str_pad($bca->numero, 3, '0', STR_PAD_LEFT) }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ $bca->data_publicacao->format('d/m/Y') }}
                        • Fonte: {{ $bca->fonte }}
                    </p>
                </div>

                @if($bca->arquivo_pdf)
                    <a href="{{ Storage::url($bca->arquivo_pdf) }}"
                       target="_blank"
                       class="flex items-center gap-1 text-sm text-fab-600 hover:text-fab-700">
                        📄 Abrir PDF
                    </a>
                @endif
            </div>

            {{-- Status do processamento --}}
            <div class="mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ match($bca->status) {
                        'processado' => 'bg-green-100 text-green-800',
                        'processando' => 'bg-blue-100 text-blue-800 animate-pulse',
                        'erro' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">
                    {{ ucfirst($bca->status) }}
                </span>
            </div>

            {{-- Efetivos encontrados --}}
            @if($bca->ocorrencias->isNotEmpty())
                <h4 class="font-medium text-gray-700 mb-3">
                    {{ $bca->ocorrencias->count() }} efetivo(s) encontrado(s):
                </h4>
                <div class="space-y-3">
                    @foreach($bca->ocorrencias as $ocorrencia)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <p class="font-medium text-gray-800">
                                {{ $ocorrencia->efetivo->posto }}
                                {{ $ocorrencia->efetivo->nome_guerra }}
                            </p>
                            @if($ocorrencia->snippet)
                                <p class="mt-1 text-sm text-gray-600 font-mono">
                                    ...{!! $ocorrencia->snippet !!}...
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">
                    Nenhum efetivo do GAC-PAC encontrado neste BCA.
                </p>
            @endif
        </div>
    @endif
</div>
```

---

## 👥 ListagemEfetivo

**Responsabilidade**: Tabela paginada de efetivos com busca em tempo real.

```php
// app/Http/Livewire/Efetivo/ListagemEfetivo.php
namespace App\Http\Livewire\Efetivo;

use App\Models\Efetivo;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ListagemEfetivo extends Component
{
    use WithPagination;

    #[Url]  // Persiste o filtro na URL (?busca=FERNANDO)
    public string $busca = '';

    public bool $mostrarInativos = false;

    // Resetar paginação ao mudar filtros
    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function updatedMostrarInativos(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $efetivos = Efetivo::query()
            ->when(!$this->mostrarInativos, fn ($q) => $q->where('ativo', true))
            ->when($this->busca, fn ($q) =>
                $q->where('nome_guerra', 'ilike', "%{$this->busca}%")
                  ->orWhere('nome_completo', 'ilike', "%{$this->busca}%")
                  ->orWhere('saram', 'like', "%{$this->busca}%")
            )
            ->orderBy('nome_guerra')
            ->paginate(15);

        return view('livewire.efetivo.listagem-efetivo', compact('efetivos'));
    }
}
```

---

## 📝 FormularioEfetivo

**Responsabilidade**: Modal de criação e edição de efetivo com validação.

```php
// app/Http/Livewire/Efetivo/FormularioEfetivo.php
namespace App\Http\Livewire\Efetivo;

use App\Models\Efetivo;
use Livewire\Component;

class FormularioEfetivo extends Component
{
    public bool $modalAberto = false;
    public ?int $efetivoId = null;

    public string $saram = '';
    public string $nomeGuerra = '';
    public string $nomeCompleto = '';
    public string $posto = '';
    public string $email = '';
    public bool $ativo = true;

    protected function rules(): array
    {
        $uniqueSaram = $this->efetivoId
            ? "unique:efetivos,saram,{$this->efetivoId}"
            : 'unique:efetivos,saram';

        return [
            'saram'         => "required|string|size:8|{$uniqueSaram}",
            'nomeGuerra'    => 'required|string|max:50',
            'nomeCompleto'  => 'required|string|max:200',
            'posto'         => 'required|string|max:20',
            'email'         => 'nullable|email|max:255',
            'ativo'         => 'boolean',
        ];
    }

    public function abrirCriacao(): void
    {
        $this->reset(['efetivoId', 'saram', 'nomeGuerra', 'nomeCompleto', 'posto', 'email']);
        $this->ativo = true;
        $this->modalAberto = true;
    }

    public function abrirEdicao(int $id): void
    {
        $efetivo = Efetivo::findOrFail($id);
        $this->efetivoId    = $efetivo->id;
        $this->saram        = $efetivo->saram;
        $this->nomeGuerra   = $efetivo->nome_guerra;
        $this->nomeCompleto = $efetivo->nome_completo;
        $this->posto        = $efetivo->posto;
        $this->email        = $efetivo->email ?? '';
        $this->ativo        = $efetivo->ativo;
        $this->modalAberto  = true;
    }

    public function salvar(): void
    {
        $this->validate();

        $dados = [
            'saram'          => strtoupper($this->saram),
            'nome_guerra'    => strtoupper($this->nomeGuerra),
            'nome_completo'  => strtoupper($this->nomeCompleto),
            'posto'          => strtoupper($this->posto),
            'email'          => $this->email ?: null,
            'ativo'          => $this->ativo,
        ];

        if ($this->efetivoId) {
            Efetivo::findOrFail($this->efetivoId)->update($dados);
            session()->flash('sucesso', 'Efetivo atualizado com sucesso.');
        } else {
            Efetivo::create($dados);
            session()->flash('sucesso', 'Efetivo cadastrado com sucesso.');
        }

        $this->modalAberto = false;
        $this->dispatch('efetivo-salvo');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.efetivo.formulario-efetivo', [
            'postos' => ['SD', 'CB', '3S', '2S', '1S', 'ST', 'TEN', 'CAP', 'MAJ', 'TC', 'CEL'],
        ]);
    }
}
```

---

## 🏷️ GestorPalavras

**Responsabilidade**: CRUD de palavras-chave para filtragem da análise de efetivo.

```php
// app/Http/Livewire/Palavras/GestorPalavras.php
namespace App\Http\Livewire\Palavras;

use App\Models\PalavraChave;
use Livewire\Component;

class GestorPalavras extends Component
{
    public string $novaPalavra = '';
    public string $descricao = '';

    protected $rules = [
        'novaPalavra' => 'required|string|max:100|unique:palavras_chaves,palavra',
        'descricao'   => 'nullable|string|max:255',
    ];

    public function adicionar(): void
    {
        $this->validate();

        PalavraChave::create([
            'palavra'   => strtoupper($this->novaPalavra),
            'descricao' => $this->descricao,
            'ativo'     => true,
        ]);

        $this->reset(['novaPalavra', 'descricao']);
        session()->flash('sucesso', 'Palavra-chave adicionada.');
    }

    public function toggleAtivo(int $id): void
    {
        $palavra = PalavraChave::findOrFail($id);
        $palavra->update(['ativo' => !$palavra->ativo]);
    }

    public function excluir(int $id): void
    {
        PalavraChave::findOrFail($id)->delete();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.palavras.gestor-palavras', [
            'palavras' => PalavraChave::orderBy('palavra')->get(),
        ]);
    }
}
```

---

## 🎨 Layout Principal

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'BCA Scrap') }} - GAC-PAC</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans antialiased">

    {{-- Navbar --}}
    <nav class="bg-fab-700 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <span class="font-bold text-lg">GAC-PAC · BCA Scrap</span>
                    <a href="{{ route('busca') }}"
                       class="text-fab-200 hover:text-white px-3 py-2 rounded-md text-sm transition-colors
                              {{ request()->routeIs('busca') ? 'bg-fab-800 text-white' : '' }}">
                        Busca BCA
                    </a>
                    <a href="{{ route('efetivo.index') }}"
                       class="text-fab-200 hover:text-white px-3 py-2 rounded-md text-sm transition-colors
                              {{ request()->routeIs('efetivo.*') ? 'bg-fab-800 text-white' : '' }}">
                        Efetivo
                    </a>
                    <a href="{{ route('palavras.index') }}"
                       class="text-fab-200 hover:text-white px-3 py-2 rounded-md text-sm transition-colors
                              {{ request()->routeIs('palavras.*') ? 'bg-fab-800 text-white' : '' }}">
                        Palavras-chave
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Conteúdo --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Flash messages --}}
        @if(session('sucesso'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                {{ session('sucesso') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
```

---

## 🧪 Testando Componentes Livewire

```php
// tests/Feature/Livewire/BuscaBcaTest.php
use App\Http\Livewire\Busca\BuscaBca;
use App\Services\BcaDownloadService;
use Livewire\Livewire;

it('renderiza o componente de busca', function () {
    Livewire::test(BuscaBca::class)
        ->assertOk()
        ->assertSee('Buscar BCA');
});

it('valida data obrigatória', function () {
    Livewire::test(BuscaBca::class)
        ->set('dataSelecionada', '')
        ->call('buscar')
        ->assertHasErrors(['dataSelecionada' => 'required']);
});

it('busca BCA com sucesso', function () {
    // Mock do Service
    $mock = mock(BcaDownloadService::class)
        ->expect(buscarBca: fn () => ['arquivo' => 'bcas/047.pdf', 'fonte' => 'cache']);

    app()->instance(BcaDownloadService::class, $mock);

    Livewire::test(BuscaBca::class)
        ->set('dataSelecionada', '14/03/2026')
        ->call('buscar')
        ->assertHasNoErrors()
        ->assertSet('bcaId', fn ($id) => $id > 0);
});
```

---

**Próximo documento**: [06 - Sistema de Filas e Jobs](06_SISTEMA_FILAS_JOBS.md)

**Última atualização**: 14/03/2026

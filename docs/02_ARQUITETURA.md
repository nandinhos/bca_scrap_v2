# 02 - Arquitetura do Sistema

## 🏗️ Visão Geral

O BCA Scrap v2 segue a arquitetura **MVC + Repository + Service Layer** do Laravel 12, com componentes Livewire gerenciando o estado da UI de forma reativa.

```
┌─────────────────────────────────────────────────────────────────┐
│  CAMADA DE APRESENTAÇÃO                                         │
│  Livewire 4 + Blade Templates + Alpine.js 3                     │
│  BuscaBca | ListagemEfetivo | GestorPalavras | ResultadoBusca   │
└─────────────────────┬───────────────────────────────────────────┘
                      │  wire:click / wire:model / dispatch()
┌─────────────────────▼───────────────────────────────────────────┐
│  CAMADA DE APLICAÇÃO (Services)                                 │
│  BcaDownloadService | BcaProcessingService | EfetivoAnalysis    │
│  CendocApiService   | EmailNotificationService                  │
└─────────────────────┬───────────────────────────────────────────┘
                      │  Injeção de dependência / DI Container
┌─────────────────────▼───────────────────────────────────────────┐
│  CAMADA DE DOMÍNIO (Models + Repositories)                      │
│  Bca | Efetivo | PalavraChave | BcaEmail | BcaOcorrencia        │
│  BcaRepository | EfetivoRepository (interfaces + implementações)│
└─────────────────────┬───────────────────────────────────────────┘
                      │  Eloquent ORM (PDO prepared statements)
┌─────────────────────▼───────────────────────────────────────────┐
│  CAMADA DE DADOS                                                 │
│  PostgreSQL 16 (tabelas + índices GIN/FTS)                      │
│  Redis 7 (cache multi-layer + queue backend)                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📂 Estrutura de Diretórios Completa

```
app/
├── Console/
│   └── Commands/
│       ├── BuscaBcaAutomaticaCommand.php   # php artisan bca:buscar-automatica
│       ├── LimparBcasAntigosCommand.php    # php artisan bca:limpar-antigos
│       └── ReenviarEmailsFalhosCommand.php # php artisan bca:reenviar-emails-falhos
│
├── Events/
│   ├── MilitarEncontradoEvent.php          # Disparado quando militar é encontrado no BCA
│   └── BcaProcessadoEvent.php             # Disparado quando BCA termina processamento
│
├── Http/
│   ├── Controllers/
│   │   └── Api/                           # APIs REST (uso futuro)
│   └── Livewire/
│       ├── Busca/
│       │   ├── BuscaBca.php               # Componente principal de busca
│       │   ├── ResultadoBusca.php         # Exibe resultado com snippets
│       │   └── PalavrasChaveSelector.php  # Seletor múltiplo de palavras-chave
│       ├── Efetivo/
│       │   ├── ListagemEfetivo.php        # Tabela paginada + busca
│       │   └── FormularioEfetivo.php      # Criação/edição de efetivo
│       └── Palavras/
│           └── GestorPalavras.php         # CRUD de palavras-chave
│
├── Jobs/
│   ├── BaixarBcaJob.php                   # Fila: download do PDF
│   ├── ProcessarBcaJob.php                # Fila: extração de texto + análise
│   ├── AnalisarEfetivoJob.php             # Fila: busca militares no texto
│   └── EnviarEmailNotificacaoJob.php      # Fila: envio de email
│
├── Listeners/
│   ├── NotificarMilitarListener.php       # Ouve MilitarEncontradoEvent → email
│   └── RegistrarExecucaoListener::php     # Ouve BcaProcessadoEvent → log
│
├── Mail/
│   └── MencaoBcaMail.php                  # Template email de notificação
│
├── Models/
│   ├── Bca.php                            # BCA baixado (PDF + metadados)
│   ├── Efetivo.php                        # Militar do efetivo
│   ├── BcaEmail.php                       # Email associado ao efetivo
│   ├── PalavraChave.php                   # Palavra-chave para busca
│   ├── BcaOcorrencia.php                  # Ocorrência de efetivo em BCA
│   └── BcaExecucao.php                    # Log de execução da busca
│
├── Repositories/
│   ├── Contracts/
│   │   ├── BcaRepositoryInterface.php
│   │   └── EfetivoRepositoryInterface.php
│   ├── BcaRepository.php
│   └── EfetivoRepository.php
│
└── Services/
    ├── BcaDownloadService.php             # Download + estratégias de busca
    ├── BcaProcessingService.php           # Extração de texto PDF (pdftotext)
    ├── EfetivoAnalysisService.php         # Análise de efetivo no texto BCA
    └── CendocApiService.php               # Integração API CENDOC

resources/
├── css/app.css                            # Tailwind CSS 4 (diretivas @import)
├── js/app.js                              # Alpine.js + inicialização
└── views/
    ├── layouts/
    │   └── app.blade.php                  # Layout principal
    ├── components/                        # Blade components reutilizáveis
    │   ├── button.blade.php
    │   ├── badge.blade.php
    │   └── modal.blade.php
    ├── livewire/                          # Views dos componentes Livewire
    │   ├── busca/
    │   ├── efetivo/
    │   └── palavras/
    └── mail/                             # Templates email
        └── mencao-bca.blade.php
```

---

## 🔄 Fluxo Completo: Busca de BCA

```
1. Usuário seleciona data no BuscaBca (Livewire)
         ↓ wire:click="buscar"
2. BuscaBca::buscar() valida data (required|date_format:d/m/Y)
         ↓
3. BcaDownloadService::buscarBca($data)
   ├── 3a. Cache Redis? → retorno <1s (fim)
   ├── 3b. API CENDOC (número direto)? → ~2s (fim)
   ├── 3c. Busca paralela HTTP (10 req/chunk, timeout 5s) → ~5s (fim)
   └── 3d. Fallback ICEA → ~10s
         ↓
4. BCA encontrado → Bca::create([...]) salvo no PostgreSQL
         ↓
5. BaixarBcaJob::dispatch($bca)->onQueue('default')
         ↓ (assíncrono — usuário não espera)
6. [Queue Worker] BaixarBcaJob confirma download do PDF
         ↓
7. ProcessarBcaJob::dispatch($bca)
         ↓
8. [Queue Worker] BcaProcessingService::extrairTexto()
   → shell_exec("pdftotext {$arquivo} -")
   → salva em bca.conteudo_texto (cache Redis 30 dias)
         ↓
9. AnalisarEfetivoJob::dispatch($bca)
         ↓
10. [Queue Worker] EfetivoAnalysisService::analisar()
    → PostgreSQL FTS: to_tsvector @@ plainto_tsquery
    → Para cada militar encontrado: event(new MilitarEncontradoEvent(...))
         ↓
11. NotificarMilitarListener::handle()
    → EnviarEmailNotificacaoJob::dispatch($efetivo, $bca)
         ↓
12. [Queue Worker] Email enviado via Laravel Mail
    → BcaOcorrencia registrada no banco
```

---

## 🔌 Repository Pattern

O Repository Pattern desacopla a lógica de negócio do acesso a dados, permitindo testar Services sem banco de dados real.

```php
// app/Repositories/Contracts/BcaRepositoryInterface.php
namespace App\Repositories\Contracts;

use App\Models\Bca;
use Illuminate\Support\Collection;

interface BcaRepositoryInterface
{
    public function findByData(string $data): ?Bca;
    public function findRecentes(int $limit = 10): Collection;
    public function findComOcorrencia(int $efetivoId): Collection;
    public function create(array $dados): Bca;
    public function updateStatus(Bca $bca, string $status): void;
}

// app/Repositories/BcaRepository.php
namespace App\Repositories;

use App\Models\Bca;
use App\Repositories\Contracts\BcaRepositoryInterface;
use Illuminate\Support\Collection;

class BcaRepository implements BcaRepositoryInterface
{
    public function findByData(string $data): ?Bca
    {
        return Bca::whereDate('data_publicacao', $data)
            ->with(['ocorrencias.efetivo', 'execucoes'])
            ->first();
    }

    public function findRecentes(int $limit = 10): Collection
    {
        return Bca::orderByDesc('data_publicacao')
            ->limit($limit)
            ->get();
    }

    public function findComOcorrencia(int $efetivoId): Collection
    {
        return Bca::whereHas('ocorrencias', fn ($q) =>
            $q->where('efetivo_id', $efetivoId)
        )
        ->orderByDesc('data_publicacao')
        ->get();
    }

    public function create(array $dados): Bca
    {
        return Bca::create($dados);
    }

    public function updateStatus(Bca $bca, string $status): void
    {
        $bca->update(['status' => $status]);
    }
}
```

**Registrar no AppServiceProvider**:

```php
// app/Providers/AppServiceProvider.php
use App\Repositories\Contracts\BcaRepositoryInterface;
use App\Repositories\BcaRepository;

public function register(): void
{
    $this->app->bind(BcaRepositoryInterface::class, BcaRepository::class);
    $this->app->bind(EfetivoRepositoryInterface::class, EfetivoRepository::class);
}
```

---

## 📡 Sistema de Eventos

```php
// app/Events/MilitarEncontradoEvent.php
namespace App\Events;

use App\Models\Bca;
use App\Models\Efetivo;

class MilitarEncontradoEvent
{
    public function __construct(
        public readonly Efetivo $efetivo,
        public readonly Bca $bca,
        public readonly string $snippet  // Trecho do texto onde foi encontrado
    ) {}
}

// app/Events/BcaProcessadoEvent.php
namespace App\Events;

use App\Models\Bca;
use Illuminate\Support\Collection;

class BcaProcessadoEvent
{
    public function __construct(
        public readonly Bca $bca,
        public readonly Collection $militaresEncontrados,
        public readonly int $totalAnalisados
    ) {}
}

// app/Listeners/NotificarMilitarListener.php
namespace App\Listeners;

use App\Events\MilitarEncontradoEvent;
use App\Jobs\EnviarEmailNotificacaoJob;

class NotificarMilitarListener
{
    public function handle(MilitarEncontradoEvent $event): void
    {
        // Verificar se efetivo tem email cadastrado
        if (!$event->efetivo->email) {
            return;
        }

        EnviarEmailNotificacaoJob::dispatch(
            $event->efetivo,
            $event->bca,
            $event->snippet
        )->onQueue('emails');
    }
}

// app/Providers/EventServiceProvider.php
protected $listen = [
    MilitarEncontradoEvent::class => [
        NotificarMilitarListener::class,
    ],
    BcaProcessadoEvent::class => [
        RegistrarExecucaoListener::class,
    ],
];
```

---

## 🔐 Segurança por Camadas

| Camada | Ameaça | Proteção | Implementação |
|--------|--------|----------|---------------|
| **HTTP** | CSRF | Token automático | `@csrf` em forms, Livewire inclui automaticamente |
| **Input** | XSS | Escaping automático | `{{ $var }}` — Blade escapa por padrão |
| **Input** | Injeção HTML | Sanitização | Nunca usar `{!! $userInput !!}` sem sanitização |
| **Database** | SQL Injection | Prepared statements | Eloquent ORM usa PDO bindings |
| **API** | Rate Limiting | Throttle | `Route::middleware('throttle:60,1')` |
| **Autenticação** | Acesso não autorizado | Sanctum | `auth:sanctum` middleware |
| **Secrets** | Exposição de credenciais | Env vars | `.env` nunca no Git (`.gitignore`) |
| **Upload** | Arquivo malicioso | Validação MIME | `$request->validate(['pdf' => 'mimes:pdf'])` |
| **Shell** | Command injection | Escape | `escapeshellarg()` antes de `shell_exec()` |

**Atenção especial — pdftotext:**

```php
// ❌ VULNERÁVEL — nunca fazer assim
shell_exec("pdftotext {$arquivo} -");

// ✅ CORRETO — sempre escapar
shell_exec("pdftotext " . escapeshellarg($arquivo) . " -");
```

---

## 🗺️ Rotas do Sistema

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    // Busca BCA
    Route::get('/', \App\Http\Livewire\Busca\BuscaBca::class)->name('busca');

    // Gestão de efetivo
    Route::get('/efetivo', \App\Http\Livewire\Efetivo\ListagemEfetivo::class)->name('efetivo.index');

    // Gestão de palavras-chave
    Route::get('/palavras-chave', \App\Http\Livewire\Palavras\GestorPalavras::class)->name('palavras.index');
});

// routes/api.php (uso futuro)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/bcas', [BcaController::class, 'index']);
    Route::get('/bcas/{bca}', [BcaController::class, 'show']);
    Route::get('/efetivos', [EfetivoController::class, 'index']);
});
```

---

**Próximo documento**: [03 - Banco de Dados](03_BANCO_DE_DADOS.md)

**Última atualização**: 14/03/2026

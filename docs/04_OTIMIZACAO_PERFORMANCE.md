# 04 - Otimização de Performance

## 🏎️ Estratégia Geral

O sistema usa **5 camadas de otimização** para atingir os benchmarks definidos:

| Camada | Técnica | Ganho Esperado |
|--------|---------|---------------|
| 1 | Cache Redis (3 camadas) | 10x queries repetidas |
| 2 | Busca paralela com Http::pool | 85% na busca BCA |
| 3 | PostgreSQL FTS + índices GIN | 75% na análise de efetivo |
| 4 | Queue assíncrona (Horizon) | 100% — não bloqueia UI |
| 5 | Lazy Loading Livewire | Carregamento sob demanda |

---

## 💾 Cache Redis — 3 Camadas

### Camada 1: Cache de Queries (TTL: 24h)

```php
// app/Services/BcaDownloadService.php

public function buscarBcaPorData(string $data): ?Bca
{
    $cacheKey = "bca_query_{$data}";

    return Cache::remember($cacheKey, now()->addHours(24), function () use ($data) {
        return Bca::whereDate('data_publicacao', $data)
            ->with(['ocorrencias.efetivo'])
            ->first();
    });
}
```

### Camada 2: Cache de Texto PDF (TTL: 30 dias)

```php
// app/Services/BcaProcessingService.php

public function extrairTexto(string $arquivoPdf): string
{
    $cacheKey = 'bca_texto_' . md5($arquivoPdf);

    return Cache::store('redis')->remember(
        $cacheKey,
        now()->addDays(30),
        function () use ($arquivoPdf) {
            // Extrair texto com pdftotext (Poppler Utils)
            $safeArquivo = escapeshellarg(storage_path("app/{$arquivoPdf}"));
            $texto = shell_exec("pdftotext {$safeArquivo} - 2>/dev/null");

            if (empty($texto)) {
                throw new \RuntimeException("Falha ao extrair texto: {$arquivoPdf}");
            }

            return $texto;
        }
    );
}
```

### Camada 3: Cache de Resultados de Análise (TTL: 1h)

```php
// app/Services/EfetivoAnalysisService.php

public function analisar(Bca $bca): array
{
    $cacheKey = "bca_analise_{$bca->id}";

    return Cache::remember($cacheKey, now()->addHour(), function () use ($bca) {
        return $this->executarAnalise($bca);
    });
}

// Invalidar quando BCA for reprocessado
public function invalidarCache(Bca $bca): void
{
    Cache::forget("bca_analise_{$bca->id}");
    Cache::forget("bca_query_{$bca->data_publicacao->format('Y-m-d')}");
}
```

### Configuração Redis no Laravel

```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver'     => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

// config/database.php — conexões Redis separadas para cache e queue
'redis' => [
    'client' => 'phpredis',

    'default' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', 6379),
        'database' => 0,  // Queue
    ],

    'cache' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', 6379),
        'database' => 1,  // Cache (database separado)
    ],
],
```

---

## ⚡ Busca Paralela (CORRIGIDA)

> ⚠️ **Chunk de 10, não 50** — evita rate limiting e IP ban no CENDOC/ICEA.

```php
// app/Services/BcaDownloadService.php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class BcaDownloadService
{
    private const CENDOC_BASE_URL = 'https://www2.fab.mil.br/cendoc';
    private const ICEA_BASE_URL   = 'https://www.icea.fab.mil.br';
    private const CHUNK_SIZE      = 10;   // Máximo de requests simultâneos
    private const TIMEOUT_SECONDS = 5;
    private const MAX_RETRIES     = 2;

    public function buscarBca(string $data): ?array
    {
        // Rate limiting: máximo 60 buscas por minuto
        $executed = RateLimiter::attempt(
            "buscar-bca:{$data}",
            perMinute: 60,
            callback: fn () => $this->executarEstrategias($data)
        );

        if ($executed === false) {
            throw new \RuntimeException('Rate limit atingido. Aguarde 1 minuto.');
        }

        return $executed;
    }

    private function executarEstrategias(string $data): ?array
    {
        // Estratégia 1: Cache local (< 1s)
        if ($cached = $this->verificarCacheLocal($data)) {
            return array_merge($cached, ['fonte' => 'cache']);
        }

        // Estratégia 2: API CENDOC com número direto (~2s)
        if ($resultado = $this->buscarViaCendocApi($data)) {
            return array_merge($resultado, ['fonte' => 'cendoc_api']);
        }

        // Estratégia 3: Busca paralela (~5s)
        if ($resultado = $this->buscaParalela($data)) {
            return array_merge($resultado, ['fonte' => 'busca_paralela']);
        }

        // Estratégia 4: Fallback ICEA (~10s)
        if ($resultado = $this->buscarViaIcea($data)) {
            return array_merge($resultado, ['fonte' => 'icea']);
        }

        return null;
    }

    private function buscaParalela(string $data): ?array
    {
        return collect(range(1, 366))
            ->chunk(self::CHUNK_SIZE)
            ->map(function ($chunk) use ($data) {
                // Http::pool faz requests em paralelo dentro do chunk
                $responses = Http::pool(fn ($pool) =>
                    $chunk->values()->map(fn ($i) =>
                        $pool
                            ->timeout(self::TIMEOUT_SECONDS)
                            ->retry(self::MAX_RETRIES, 100)
                            ->get(self::CENDOC_BASE_URL . "/bca_{$i}.pdf")
                    )->toArray()
                );

                return collect($responses)
                    ->first(fn ($r) =>
                        $r->successful() &&
                        strlen($r->body()) > 1000 &&  // PDF real (não página 404)
                        str_contains($r->header('Content-Type') ?? '', 'pdf')
                    );
            })
            ->filter()
            ->first();
    }

    private function verificarCacheLocal(string $data): ?array
    {
        $cacheKey = "bca_arquivo_{$data}";
        return Cache::get($cacheKey);
    }
}
```

---

## 🔍 PostgreSQL Full-Text Search

### Por que FTS no banco em vez de PHP?

| Abordagem | Tempo para 47 efetivos | Escalabilidade |
|-----------|----------------------|----------------|
| Loop PHP | 3-5s | O(n) efetivos |
| LIKE SQL | 1-2s | O(n) sem índice |
| **FTS com GIN** | **<100ms** | O(1) com índice |

### Implementação

```php
// app/Services/EfetivoAnalysisService.php

public function analisarEfetivosNoBca(Bca $bca): array
{
    // Uma query que analisa TODOS os efetivos de uma vez
    $resultados = DB::select("
        SELECT
            e.id,
            e.nome_guerra,
            e.nome_completo,
            ts_headline(
                'portuguese',
                :texto,
                to_tsquery('portuguese', :query_nome),
                'MaxWords=40, MinWords=15, StartSel=[, StopSel=]'
            ) AS snippet
        FROM efetivos e
        WHERE e.ativo = true
          AND to_tsvector('portuguese', :texto2)
              @@ to_tsquery('portuguese', translate(e.nome_completo, ' ', ' & '))
        ORDER BY e.nome_guerra
    ", [
        'texto'      => $bca->conteudo_texto,
        'query_nome' => str_replace(' ', ' & ', $bca->conteudo_texto),
        'texto2'     => $bca->conteudo_texto,
    ]);

    return $resultados;
}
```

### Adicionar índices GIN após migração

```php
// database/seeders/FtsIndexSeeder.php (rodar após migração de dados)
public function run(): void
{
    // Efetivos: índice no nome_completo
    DB::statement("
        CREATE INDEX IF NOT EXISTS efetivos_nome_fts_idx
        ON efetivos
        USING gin(to_tsvector('portuguese', nome_completo))
    ");

    // BCAs: índice no conteúdo completo
    DB::statement("
        CREATE INDEX IF NOT EXISTS bcas_conteudo_fts_idx
        ON bcas
        USING gin(to_tsvector('portuguese', coalesce(conteudo_texto, '')))
    ");

    $this->command->info('Índices FTS criados com sucesso.');
}
```

---

## 🚫 Eliminar N+1 Queries

```php
// ❌ N+1 — 1 query para bcas + N queries para ocorrências
$bcas = Bca::processados()->get();
foreach ($bcas as $bca) {
    echo $bca->ocorrencias->count(); // Query extra por BCA!
}

// ✅ Eager loading — 2 queries no total
$bcas = Bca::processados()
    ->with(['ocorrencias.efetivo'])  // Carrega relacionamentos de uma vez
    ->get();
foreach ($bcas as $bca) {
    echo $bca->ocorrencias->count(); // Sem query adicional
}

// ✅ Para Livewire com paginação
$bcas = Bca::processados()
    ->with(['ocorrencias'])
    ->withCount('ocorrencias')       // SELECT COUNT(*) como coluna
    ->latest('data_publicacao')
    ->paginate(15);
```

---

## 🦥 Lazy Loading Livewire

```blade
{{-- Componente que demora carrega em segundo plano --}}
<livewire:busca.resultado-busca :bca="$bcaId" lazy />

{{-- Placeholder exibido enquanto carrega --}}
<div wire:loading class="animate-pulse bg-gray-200 h-32 rounded-lg"></div>
```

```php
// app/Http/Livewire/Busca/ResultadoBusca.php
class ResultadoBusca extends Component
{
    public int $bcaId;
    public ?Bca $bca = null;

    // Lazy loading — só carrega quando componente entra na viewport
    #[Lazy]
    public function mount(): void
    {
        $this->bca = $this->bcaRepo->find($this->bcaId);
    }
}
```

---

## 📊 Benchmarks Esperados

| Operação | Sistema Antigo | Meta v2 | Como Medir |
|----------|---------------|---------|------------|
| Busca BCA (cache hit) | 5-15s | <1s | `php artisan tinker` |
| Busca BCA (API CENDOC) | 5-15s | <3s | Laravel Telescope |
| Busca BCA (paralela) | 5-15s | <6s | Laravel Telescope |
| Análise de efetivo (47 mil.) | 3-5s | <100ms | `EXPLAIN ANALYZE` |
| Extração texto PDF | 2s | <500ms | benchmark no tinker |
| Envio de email | 2-5s (bloqueante) | <10ms (assíncrono) | Horizon dashboard |
| Carregamento da página | 1-2s | <300ms | Chrome DevTools |

```php
// Medir performance no tinker
php artisan tinker
>>> $start = microtime(true);
>>> app(App\Services\BcaDownloadService::class)->buscarBca('14-03-2026');
>>> echo round((microtime(true) - $start) * 1000) . 'ms';
```

---

**Próximo documento**: [05 - Componentes Livewire](05_COMPONENTES_LIVEWIRE.md)

**Última atualização**: 14/03/2026

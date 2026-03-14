# 09 - Migração Passo a Passo (7 Semanas)

## 📅 Cronograma Executivo

| Semana | Fase | Entregas | Status |
|--------|------|----------|--------|
| 0 | Pré-Projeto | Validações infraestrutura, backup, staging | 📝 Planejado |
| 1 | Preparação | Setup, Docker, Migrations | 📝 Planejado |
| 2-3 | Backend | Models, Services, Jobs | 📝 Planejado |
| 4-5 | Frontend | Livewire Components | 📝 Planejado |
| 5.5 | Teste Migração | Teste migração dados em ambiente dev | 📝 Planejado |
| 6 | Otimização | Cache, Performance | 📝 Planejado |
| 7 | Testes Finais | Integração, UAT, Staging | 📝 Planejado |
| 8 | Deploy | Migração dados produção, Go-live | 📝 Planejado |

---

## Semana 0: Pré-Projeto (Validações e Preparação)

> ⚠️ **OBRIGATÓRIO** — Executar antes de qualquer código. Falhar aqui = falhar na Semana 8.

### Dia -5 a -3: Validações de Infraestrutura

**Perguntas para responder COM TI/GAC-PAC ANTES de começar:**

```bash
# Checklist de validação — resposta esperada: "SIM" para todos
[ ] Docker é permitido no servidor de produção?
[ ] PostgreSQL 16 está aprovado pela política de TI?
[ ] Redis é permitido? (alternativa: Memcached via CACHE_DRIVER=memcached)
[ ] Porta 8080 (HTTP) está liberada no firewall?
[ ] Portas 5432 (PostgreSQL) e 6379 (Redis) estão acessíveis internamente?
[ ] O servidor tem pelo menos 4GB RAM e 20GB disco livre?
[ ] PHP 8.3+ disponível ou aprovado para instalação?
[ ] Há acesso externo ao CENDOC/ICEA do servidor de produção?
```

```bash
# Testar conectividade para APIs externas
curl -I "https://www2.fab.mil.br/cendoc/" 2>/dev/null | head -1 || echo "⚠️ CENDOC inacessível!"
```

### Dia -2: Testar Migração de Dados em Ambiente Isolado

```bash
# NUNCA testar migração pela primeira vez em produção!

# 1. Backup do sistema atual
mysqldump -u root -p bca_db > backup_$(date +%Y%m%d)_pre_migracao.sql
echo "Backup criado: $(ls -lh backup_*.sql | tail -1)"

# 2. Criar banco PostgreSQL de teste
docker exec bca-postgres psql -U bca_user -c "CREATE DATABASE bca_db_test;"

# 3. Testar migração com pgloader
docker run --rm dimitri/pgloader \
    pgloader mysql://root:senha@host_mysql/bca_db \
              pgsql://bca_user:bca_pass@bca-postgres/bca_db_test

# 4. Validar contagem de registros (documentar como baseline)
docker exec bca-postgres psql -U bca_user bca_db_test -c "
    SELECT 'efetivos'      AS tabela, COUNT(*) AS registros FROM efetivos
    UNION ALL
    SELECT 'palavras_chaves',          COUNT(*) FROM palavras_chaves
    UNION ALL
    SELECT 'bcas',                     COUNT(*) FROM bcas;
"
# ⬆️ Salvar esses números! Serão usados na validação da Semana 8.
```

### Dia -1: Preparar Repositório e Staging

```bash
# Criar repositório Git
git init bca-scrap-laravel
cd bca-scrap-laravel
git branch -M main

# Configurar ambiente de staging (servidor separado de dev e prod)
cp .env.example .env.staging
# Editar .env.staging com credenciais do servidor de staging

# Verificar recursos do servidor de produção
# (Executar no servidor alvo)
free -h   # RAM disponível (mínimo 4GB)
df -h /   # Espaço disco (mínimo 20GB)
php -v    # Versão PHP (mínimo 8.3)
```

### ✅ Checklist Semana 0
- [ ] TI confirmou Docker e PostgreSQL aprovados
- [ ] Servidor de produção tem recursos suficientes (≥4GB RAM, ≥20GB disco)
- [ ] Acesso às APIs externas (CENDOC/ICEA) validado a partir do servidor de produção
- [ ] Migração MySQL→PostgreSQL testada em ambiente isolado com sucesso
- [ ] Baseline de registros documentado (efetivos, palavras_chaves, bcas)
- [ ] Backup do sistema atual criado e armazenado com segurança
- [ ] Repositório Git criado e configurado
- [ ] Ambiente de staging disponível e acessível
- [ ] [Plano de rollback](../ROLLBACK_PLAN.md) revisado e aprovado pela equipe

---

## Semana 1: Preparação e Fundação

### Dia 1-2: Setup Inicial

```bash
# 1. Criar projeto Laravel 12
composer create-project laravel/laravel bca-scrap-laravel
cd bca-scrap-laravel

# 2. Instalar dependências TALL
composer require livewire/livewire
npm install -D tailwindcss @tailwindcss/forms @tailwindcss/typography
npm install alpinejs flatpickr

# 3. Instalar pacotes adicionais
composer require laravel/horizon
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require maatwebsite/excel
```

### Dia 3: Configurar Docker

Criar `docker-compose.yml`:
```yaml
version: '3.8'
services:
  nginx:
    image: nginx:alpine
    ports: ["8080:80"]
    volumes:
      - ./:/var/www/html
      - ./docker/nginx:/etc/nginx/conf.d
    networks: [bca-network]

  php:
    build: ./docker/php
    volumes: [./:/var/www/html]
    environment:
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      REDIS_HOST: redis
    networks: [bca-network]

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: bca_db
      POSTGRES_USER: bca_user
      POSTGRES_PASSWORD: bca_pass
    ports: ["5432:5432"]
    volumes: [postgres_data:/var/lib/postgresql/data]
    networks: [bca-network]

  pgadmin:
    image: dpage/pgadmin4
    ports: ["5050:80"]
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@gacpac.fab.mil.br
      PGADMIN_DEFAULT_PASSWORD: admin123
    networks: [bca-network]

  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]
    networks: [bca-network]

volumes:
  postgres_data:

networks:
  bca-network:
```

### Dia 4-5: Migrations

```bash
# Criar migrations
php artisan make:migration create_efetivos_table
php artisan make:migration create_bcas_table
php artisan make:migration create_bca_emails_table
php artisan make:migration create_palavras_chaves_table
php artisan make:migration create_bca_ocorrencias_table
php artisan make:migration create_bca_execucoes_table
```

**Exemplo** (`create_efetivos_table.php`):
```php
Schema::create('efetivos', function (Blueprint $table) {
    $table->id();
    $table->string('saram', 8)->unique()->index();
    $table->string('nome_guerra', 50);
    $table->string('nome_completo', 200);
    $table->string('posto', 20);
    $table->string('email')->nullable();
    $table->boolean('ativo')->default(true);
    $table->boolean('oculto')->default(false);
    $table->timestamps();
    $table->softDeletes();

    // Full-text search
    DB::statement('CREATE INDEX efetivos_nome_fulltext ON efetivos USING gin(to_tsvector(\'portuguese\', nome_completo))');
});
```

### ✅ Checklist Semana 1
- [ ] Laravel 12 instalado
- [ ] Docker configurado e rodando
- [ ] PostgreSQL + pgAdmin acessíveis
- [ ] Redis funcionando
- [ ] Todas migrations criadas
- [ ] Testes básicos rodando

---

## Semana 2-3: Backend (Services, Jobs, Models)

### Dia 6-7: Models e Repositories

```bash
php artisan make:model Efetivo
php artisan make:model Bca
php artisan make:model BcaEmail
php artisan make:model PalavraChave
```

**Model Efetivo**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Efetivo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'saram', 'nome_guerra', 'nome_completo',
        'posto', 'email', 'ativo', 'oculto'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'oculto' => 'boolean',
    ];

    public function ocorrencias()
    {
        return $this->hasMany(BcaOcorrencia::class);
    }

    public function emails()
    {
        return $this->hasMany(BcaEmail::class);
    }
}
```

### Dia 8-10: Services

**BcaDownloadService**:
```php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BcaDownloadService
{
    public function buscarBca(string $data): ?array
    {
        // 1. Cache local
        if ($arquivo = $this->verificarCache($data)) {
            return ['arquivo' => $arquivo, 'fonte' => 'cache'];
        }

        // 2. API CENDOC
        if ($numero = $this->buscarNumeroCendoc($data)) {
            return $this->baixarDoCendoc($numero, $data);
        }

        // 3. Busca paralela
        return $this->buscaParalela($data);
    }

    private function buscaParalela(string $data): ?array
    {
        // IMPORTANTE: chunk(10) — máximo 10 requests simultâneos
        // Evita rate limiting / IP ban no CENDOC/ICEA
        return collect(range(1, 366))
            ->chunk(10)
            ->map(function ($chunk) use ($data) {
                $responses = Http::pool(fn ($pool) =>
                    $chunk->map(fn ($i) =>
                        $pool->timeout(5)->retry(2, 100)
                            ->get("https://www2.fab.mil.br/cendoc/bca_{$i}.pdf")
                    )->toArray()
                );

                return collect($responses)
                    ->filter(fn ($r) => $r->successful() && strlen($r->body()) > 1000)
                    ->first();
            })
            ->filter()
            ->first();
    }
}
```

### Dia 11-12: Jobs

```bash
php artisan make:job BaixarBcaJob
php artisan make:job ProcessarBcaJob
php artisan make:job AnalisarEfetivoJob
php artisan make:job EnviarEmailNotificacaoJob
```

**ProcessarBcaJob**:
```php
namespace App\Jobs;

use App\Models\Bca;
use App\Services\BcaProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessarBcaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Bca $bca) {}

    public function handle(BcaProcessingService $service)
    {
        $this->bca->update(['status' => 'processando']);

        $texto = $service->extrairTexto($this->bca->arquivo_pdf);

        $this->bca->update([
            'conteudo_texto' => $texto,
            'status' => 'processado',
            'processado_em' => now(),
        ]);

        AnalisarEfetivoJob::dispatch($this->bca);
    }
}
```

### Dia 13-15: Commands

```bash
php artisan make:command BuscaBcaAutomaticaCommand
php artisan make:command LimparBcasAntigosCommand
```

**BuscaBcaAutomaticaCommand**:
```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BcaDownloadService;
use App\Jobs\ProcessarBcaJob;

class BuscaBcaAutomaticaCommand extends Command
{
    protected $signature = 'bca:buscar-automatica';

    public function handle(BcaDownloadService $service)
    {
        $hoje = now();

        $this->info("Buscando BCA de {$hoje->format('d/m/Y')}");

        $resultado = $service->buscarBca($hoje->format('d-m-Y'));

        if (!$resultado) {
            $this->warn('BCA não encontrado');
            return Command::FAILURE;
        }

        $bca = Bca::create([
            'numero' => $resultado['numero'] ?? 0,
            'data_publicacao' => $hoje,
            'arquivo_pdf' => $resultado['arquivo'],
        ]);

        ProcessarBcaJob::dispatch($bca);

        $this->info("BCA agendado para processamento");
        return Command::SUCCESS;
    }
}
```

### ✅ Checklist Semana 2-3
- [ ] Todos Models criados com relationships
- [ ] Services implementados (Download, Processing, Analysis)
- [ ] Jobs configurados (Download, Processar, Email)
- [ ] Commands Artisan funcionando
- [ ] Testes unitários para Services (80%+)
- [ ] Horizon instalado e configurado

---

## Semana 4-5: Frontend (Livewire Components)

### Dia 16-18: Componentes de Busca

```bash
php artisan make:livewire Busca/BuscaBca
php artisan make:livewire Busca/ResultadoBusca
php artisan make:livewire Busca/PalavrasChaveSelector
```

**BuscaBca.php**:
```php
namespace App\Http\Livewire\Busca;

use Livewire\Component;
use App\Models\Bca;

class BuscaBca extends Component
{
    public string $dataSelecionada = '';
    public ?Bca $bcaAtual = null;

    public function buscar()
    {
        $this->validate(['dataSelecionada' => 'required|date_format:d/m/Y']);

        // Lógica de busca...

        $this->dispatch('bca-carregado', bcaId: $this->bcaAtual->id);
    }

    public function render()
    {
        return view('livewire.busca.busca-bca');
    }
}
```

**View** (`busca-bca.blade.php`):
```blade
<div class="bg-white rounded-xl shadow p-6">
    <input
        type="text"
        wire:model="dataSelecionada"
        x-data
        x-init="flatpickr($el, {locale: 'pt', dateFormat: 'd/m/Y'})"
        class="px-4 py-2 border rounded-lg"
    >

    <button
        wire:click="buscar"
        wire:loading.attr="disabled"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg"
    >
        <span wire:loading.remove>Buscar</span>
        <span wire:loading>Buscando...</span>
    </button>

    @if($bcaAtual)
        <livewire:busca.resultado-busca :bca="$bcaAtual" />
    @endif
</div>
```

### Dia 19-21: Componentes de Efetivo

```bash
php artisan make:livewire Efetivo/ListagemEfetivo
php artisan make:livewire Efetivo/FormularioEfetivo
```

**ListagemEfetivo.php**:
```php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Efetivo;

class ListagemEfetivo extends Component
{
    use WithPagination;

    public string $busca = '';

    public function render()
    {
        return view('livewire.efetivo.listagem-efetivo', [
            'efetivos' => Efetivo::where('ativo', true)
                ->when($this->busca, fn($q) =>
                    $q->where('nome_guerra', 'like', "%{$this->busca}%")
                      ->orWhere('nome_completo', 'like', "%{$this->busca}%")
                )
                ->paginate(15)
        ]);
    }
}
```

### Dia 22-25: Layout e UI Components

Configurar Tailwind (`tailwind.config.js`):
```js
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        fab: {
          50: '#f0f5fa',
          600: '#3b6a9a',
          700: '#2a4570',
        }
      }
    }
  }
}
```

### ✅ Checklist Semana 4-5
- [ ] Todos componentes Livewire criados
- [ ] Views Blade organizadas
- [ ] Tailwind CSS configurado
- [ ] Alpine.js integrado
- [ ] Interface responsiva
- [ ] Testes de componentes

---

## Semana 6: Otimização e Performance

### Dia 26-27: Cache Redis

**Configurar cache** (`config/cache.php`):
```php
'default' => env('CACHE_DRIVER', 'redis'),
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
],
```

**Implementar cache em Services**:
```php
use Illuminate\Support\Facades\Cache;

class BcaProcessingService
{
    public function extrairTexto(string $arquivo): string
    {
        return Cache::remember("bca_texto_{$arquivo}", now()->addDays(30),
            fn() => shell_exec("pdftotext {$arquivo} -")
        );
    }
}
```

### Dia 28-29: PostgreSQL Full-Text Search

```php
// Migration: adicionar índice GIN
DB::statement('CREATE INDEX bcas_conteudo_idx ON bcas USING gin(to_tsvector(\'portuguese\', conteudo_texto))');

// Query otimizada
Bca::whereRaw("to_tsvector('portuguese', conteudo_texto) @@ plainto_tsquery('portuguese', ?)", [$termo])
    ->get();
```

### Dia 30: Testes de Performance

```bash
# Benchmark
php artisan test --parallel --coverage

# Profile queries
php artisan debugbar:publish
```

### ✅ Checklist Semana 6
- [ ] Cache Redis em todas camadas
- [ ] PostgreSQL FTS implementado
- [ ] Busca paralela otimizada
- [ ] Benchmarks < metas (3s, 1s)
- [ ] Queries otimizadas (N+1 resolvidos)

---

## Semanas 7-8: Testes Finais e Deploy em Produção

### Dia 31-32: Migração de Dados

**Script** (`migrate-data.sh`):
```bash
#!/bin/bash

echo "Migrando dados MySQL → PostgreSQL"

# 1. Dump MySQL
docker exec bca_scrap-mariadb-1 mysqldump -u root -p bca_db > backup.sql

# 2. Converter com pgloader
docker run --rm dimitri/pgloader \
    pgloader mysql://user:pass@oldhost/bca_db \
              pgsql://user:pass@newhost/bca_db

# 3. Copiar PDFs
cp -r ../bca_scrap/arcadia/busca_bca/boletim_bca/* storage/app/bcas/

# 4. Reindexar
docker exec bca-php php artisan scout:import "App\Models\Bca"

echo "Migração concluída!"
```

### Dia 33-34: Staging e Testes

```bash
# Deploy staging
docker-compose -f docker-compose.staging.yml up -d

# Rodar testes completos
php artisan test --coverage --min=80

# Smoke tests
php artisan bca:buscar-automatica --dry-run
```

### Dia 35: Deploy Produção

```bash
# Backup produção atual
php artisan backup:run

# Deploy
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
docker-compose restart
php artisan horizon:terminate
php artisan queue:restart
```

### ✅ Checklist Semanas 7-8
- [ ] Testes de integração completos (80%+ cobertura — Services/Jobs)
- [ ] Cobertura Livewire ≥60%
- [ ] Todos os testes passando (zero falhas)
- [ ] Deploy em staging bem-sucedido
- [ ] Validação UAT concluída com usuários reais do GAC-PAC
- [ ] Dados migrados com sucesso (validados vs baseline da Semana 0)
- [ ] Deploy em produção realizado
- [ ] Monitoring e alertas ativos
- [ ] Documentação atualizada
- [ ] [Plano de rollback](../ROLLBACK_PLAN.md) testado em staging
- [ ] Equipe treinada no novo sistema

---

## 📋 Checklist Geral de Migração

### Pré-Deploy
- [ ] Código em repositório Git
- [ ] Testes com cobertura 80%+
- [ ] Documentação completa
- [ ] Variáveis .env configuradas
- [ ] Secrets do Docker configurados
- [ ] Backup do banco antigo

### Deploy
- [ ] Docker Compose rodando
- [ ] PostgreSQL acessível
- [ ] Redis funcionando
- [ ] Nginx configurado
- [ ] Horizon ativo
- [ ] Scheduler rodando
- [ ] Logs monitorados

### Pós-Deploy
- [ ] Smoke tests OK
- [ ] Métricas baseline capturadas
- [ ] Equipe treinada
- [ ] Runbook documentado
- [ ] Monitoramento ativo
- [ ] Plano de rollback pronto

---

## 🚨 Troubleshooting

### Problema: Jobs não processam
```bash
# Verificar Horizon
php artisan horizon:status

# Reiniciar
php artisan horizon:terminate
php artisan horizon
```

### Problema: Busca lenta
```sql
-- Verificar índices
SELECT * FROM pg_indexes WHERE tablename = 'bcas';

-- Reindexar
REINDEX TABLE bcas;
```

### Problema: Cache não funciona
```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear

# Verificar Redis
redis-cli ping
```

---

**Próximo**: [10 - Guia de Comandos](10_GUIA_COMANDOS.md)

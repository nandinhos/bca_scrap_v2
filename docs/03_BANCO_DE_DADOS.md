# 03 - Banco de Dados (PostgreSQL 16)

## 🗺️ Diagrama de Entidades

```
efetivos (1) ──────────── (*) bca_ocorrencias (*) ──────── (1) bcas
    │                              │                              │
    └── (*) bca_emails             │                   (*) bca_execucoes
                                   │
palavras_chaves (usadas como filtro na análise de efetivo)
```

| Tabela | Descrição | Registros esperados |
|--------|-----------|---------------------|
| `efetivos` | Militares do efetivo GAC-PAC | ~50 |
| `bcas` | BCAs baixados e processados | Crescente (1/dia útil) |
| `bca_emails` | Emails de notificação associados a efetivos | ~50-100 |
| `palavras_chaves` | Palavras para filtrar análise | ~20-50 |
| `bca_ocorrencias` | Registro de quem apareceu em qual BCA | Crescente |
| `bca_execucoes` | Log de execuções da busca automática | Crescente |

---

## 📋 Migrations Completas

### Efetivos

```php
// database/migrations/2026_03_XX_create_efetivos_table.php
Schema::create('efetivos', function (Blueprint $table) {
    $table->id();
    $table->string('saram', 8)->unique()->comment('Código SARAM do militar');
    $table->string('nome_guerra', 50)->comment('Nome de guerra (apelido militar)');
    $table->string('nome_completo', 200)->comment('Nome completo para busca no BCA');
    $table->string('posto', 20)->comment('Ex: 1S, 2S, CB, SD');
    $table->string('email')->nullable()->comment('Email principal de notificação');
    $table->boolean('ativo')->default(true)->comment('Se deve ser incluído nas buscas');
    $table->boolean('oculto')->default(false)->comment('Oculto da listagem mas ainda analisado');
    $table->timestamps();
    $table->softDeletes();

    $table->index('ativo');
    $table->index(['ativo', 'oculto']);
});

// Índice GIN para Full-Text Search em português
DB::statement("
    CREATE INDEX efetivos_nome_fts_idx
    ON efetivos
    USING gin(to_tsvector('portuguese', nome_completo))
");
```

### BCAs

```php
// database/migrations/2026_03_XX_create_bcas_table.php
Schema::create('bcas', function (Blueprint $table) {
    $table->id();
    $table->unsignedSmallInteger('numero')->nullable()->comment('Número do BCA (ex: 047)');
    $table->date('data_publicacao')->unique()->comment('Data de publicação do BCA');
    $table->string('arquivo_pdf', 500)->nullable()->comment('Caminho relativo do PDF em storage');
    $table->longText('conteudo_texto')->nullable()->comment('Texto extraído do PDF via pdftotext');
    $table->string('fonte', 50)->nullable()->comment('cendoc_api | busca_paralela | icea | cache');
    $table->enum('status', ['pendente', 'baixando', 'processando', 'processado', 'erro'])
          ->default('pendente');
    $table->string('erro_mensagem')->nullable()->comment('Mensagem de erro se status = erro');
    $table->timestamp('processado_em')->nullable();
    $table->timestamps();

    $table->index('data_publicacao');
    $table->index('status');
    $table->index('numero');
});

// Índice GIN para Full-Text Search no conteúdo do BCA
DB::statement("
    CREATE INDEX bcas_conteudo_fts_idx
    ON bcas
    USING gin(to_tsvector('portuguese', coalesce(conteudo_texto, '')))
");
```

### BCA Emails

```php
// database/migrations/2026_03_XX_create_bca_emails_table.php
Schema::create('bca_emails', function (Blueprint $table) {
    $table->id();
    $table->foreignId('efetivo_id')->constrained('efetivos')->cascadeOnDelete();
    $table->foreignId('bca_id')->constrained('bcas')->cascadeOnDelete();
    $table->string('email_destino')->comment('Email para onde foi enviado');
    $table->enum('status', ['pendente', 'enviado', 'falhou', 'rejeitado'])->default('pendente');
    $table->timestamp('enviado_em')->nullable();
    $table->string('erro_mensagem')->nullable();
    $table->unsignedTinyInteger('tentativas')->default(0);
    $table->timestamps();

    $table->index(['efetivo_id', 'bca_id']);
    $table->index('status');
});
```

### Palavras-Chave

```php
// database/migrations/2026_03_XX_create_palavras_chaves_table.php
Schema::create('palavras_chaves', function (Blueprint $table) {
    $table->id();
    $table->string('palavra', 100)->unique()->comment('Palavra ou frase a buscar no BCA');
    $table->string('descricao', 255)->nullable()->comment('Descrição do propósito desta palavra');
    $table->boolean('ativo')->default(true);
    $table->timestamps();
});
```

### BCA Ocorrências

```php
// database/migrations/2026_03_XX_create_bca_ocorrencias_table.php
Schema::create('bca_ocorrencias', function (Blueprint $table) {
    $table->id();
    $table->foreignId('efetivo_id')->constrained('efetivos')->cascadeOnDelete();
    $table->foreignId('bca_id')->constrained('bcas')->cascadeOnDelete();
    $table->text('snippet')->nullable()->comment('Trecho do BCA onde o militar aparece');
    $table->string('tipo', 50)->nullable()->comment('Ex: promoção, transferência, louvor');
    $table->timestamps();

    $table->unique(['efetivo_id', 'bca_id']);
    $table->index('efetivo_id');
    $table->index('bca_id');
});
```

### BCA Execuções

```php
// database/migrations/2026_03_XX_create_bca_execucoes_table.php
Schema::create('bca_execucoes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bca_id')->nullable()->constrained('bcas')->nullOnDelete();
    $table->date('data_busca')->comment('Data para a qual se buscou o BCA');
    $table->enum('resultado', ['sucesso', 'nao_encontrado', 'erro'])->default('nao_encontrado');
    $table->unsignedSmallInteger('militares_analisados')->default(0);
    $table->unsignedSmallInteger('militares_encontrados')->default(0);
    $table->unsignedSmallInteger('emails_enviados')->default(0);
    $table->unsignedSmallInteger('emails_com_falha')->default(0);
    $table->float('tempo_execucao_segundos')->nullable();
    $table->string('fonte', 50)->nullable();
    $table->text('erro_mensagem')->nullable();
    $table->timestamps();

    $table->index('data_busca');
    $table->index('resultado');
});
```

---

## 🏛️ Models Eloquent

### Efetivo

```php
// app/Models/Efetivo.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Efetivo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'saram', 'nome_guerra', 'nome_completo', 'posto', 'email', 'ativo', 'oculto',
    ];

    protected $casts = [
        'ativo'  => 'boolean',
        'oculto' => 'boolean',
    ];

    // Relationships
    public function ocorrencias(): HasMany
    {
        return $this->hasMany(BcaOcorrencia::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(BcaEmail::class);
    }

    // Scopes
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeVisiveis(Builder $query): Builder
    {
        return $query->where('ativo', true)->where('oculto', false);
    }

    // Full-Text Search
    public function scopeBuscarNome(Builder $query, string $termo): Builder
    {
        return $query->whereRaw(
            "to_tsvector('portuguese', nome_completo) @@ plainto_tsquery('portuguese', ?)",
            [$termo]
        );
    }
}
```

### Bca

```php
// app/Models/Bca.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Bca extends Model
{
    protected $fillable = [
        'numero', 'data_publicacao', 'arquivo_pdf', 'conteudo_texto',
        'fonte', 'status', 'erro_mensagem', 'processado_em',
    ];

    protected $casts = [
        'data_publicacao' => 'date',
        'processado_em'   => 'datetime',
    ];

    // Relationships
    public function ocorrencias(): HasMany
    {
        return $this->hasMany(BcaOcorrencia::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(BcaEmail::class);
    }

    public function execucoes(): HasMany
    {
        return $this->hasMany(BcaExecucao::class);
    }

    // Scopes
    public function scopeProcessados(Builder $query): Builder
    {
        return $query->where('status', 'processado');
    }

    // Full-Text Search no conteúdo do BCA
    public function scopeBuscarConteudo(Builder $query, string $termo): Builder
    {
        return $query->whereRaw(
            "to_tsvector('portuguese', coalesce(conteudo_texto, '')) @@ plainto_tsquery('portuguese', ?)",
            [$termo]
        );
    }
}
```

---

## 🌱 Seeders

```php
// database/seeders/EfetivoSeeder.php
namespace Database\Seeders;

use App\Models\Efetivo;
use Illuminate\Database\Seeder;

class EfetivoSeeder extends Seeder
{
    public function run(): void
    {
        // Migrar do MySQL existente (usar DatabaseSeeder::importFromMysql)
        // OU criar dados de desenvolvimento:
        Efetivo::factory()->count(10)->create();

        // Exemplo de efetivo real (desenvolvimento)
        Efetivo::firstOrCreate(['saram' => '00000001'], [
            'saram'         => '00000001',
            'nome_guerra'   => 'FERNANDO',
            'nome_completo' => 'FERNANDO EXEMPLO DA SILVA',
            'posto'         => '1S',
            'email'         => 'fernando@gacpac.test',
            'ativo'         => true,
        ]);
    }
}

// database/factories/EfetivoFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EfetivoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'saram'         => $this->faker->unique()->numerify('########'),
            'nome_guerra'   => strtoupper($this->faker->firstName()),
            'nome_completo' => strtoupper($this->faker->name()),
            'posto'         => $this->faker->randomElement(['1S', '2S', 'CB', 'SD', 'CAP', 'TEN']),
            'email'         => $this->faker->unique()->safeEmail(),
            'ativo'         => true,
            'oculto'        => false,
        ];
    }
}
```

---

## 🔍 Full-Text Search — Guia de Uso

```php
// Busca simples por nome de militar
Efetivo::buscarNome('FERNANDO SILVA')->get();

// Busca com múltiplos termos (AND implícito)
Efetivo::buscarNome('FERNANDO TRANSFERENCIA')->get();

// Busca no conteúdo de BCAs
Bca::buscarConteudo('promoção tenente')->get();

// Snippet: encontrar trecho onde o termo aparece
DB::select("
    SELECT id, data_publicacao,
           ts_headline(
               'portuguese',
               conteudo_texto,
               plainto_tsquery('portuguese', ?),
               'MaxWords=30, MinWords=10, StartSel=**,StopSel=**'
           ) AS snippet
    FROM bcas
    WHERE to_tsvector('portuguese', coalesce(conteudo_texto, ''))
          @@ plainto_tsquery('portuguese', ?)
    ORDER BY data_publicacao DESC
", [$termo, $termo]);
```

---

## 📦 Migração MySQL → PostgreSQL

```bash
# 1. Instalar pgloader (conversor automático)
docker pull dimitri/pgloader

# 2. Criar arquivo de configuração pgloader
cat > /tmp/pgloader.load << 'EOF'
LOAD DATABASE
    FROM    mysql://root:SENHA@mysql_host/bca_db
    INTO    postgresql://bca_user:bca_pass@postgres_host/bca_db

WITH include drop, create tables, create indexes, reset sequences

SET PostgreSQL PARAMETERS
    maintenance_work_mem to '128MB',
    work_mem to '64MB'

CAST type tinyint(1) to boolean using tinyint-to-boolean,
     type datetime   to timestamptz,
     type date       to date

EXCLUDING TABLE NAMES MATCHING 'jobs', 'failed_jobs', 'migrations'
;
EOF

# 3. Executar migração
docker run --rm --network host -v /tmp/pgloader.load:/tmp/load.pgload \
    dimitri/pgloader pgloader /tmp/load.pgload

# 4. Validar (comparar com baseline da Semana 0)
docker exec bca-postgres psql -U bca_user bca_db -c "
    SELECT 'efetivos'      AS tabela, COUNT(*) FROM efetivos
    UNION ALL
    SELECT 'palavras_chaves',          COUNT(*) FROM palavras_chaves
    UNION ALL
    SELECT 'bcas',                     COUNT(*) FROM bcas;
"

# 5. Recriar índices FTS (pgloader não migra estes)
docker exec bca-php php artisan migrate --pretend  # Verificar o que seria rodado
docker exec bca-php php artisan db:seed --class=FtsIndexSeeder
```

---

**Próximo documento**: [04 - Otimização de Performance](04_OTIMIZACAO_PERFORMANCE.md)

**Última atualização**: 14/03/2026

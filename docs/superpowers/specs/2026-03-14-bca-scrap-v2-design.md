# BCA Scrap v2 — Design Spec

**Data**: 2026-03-14
**Status**: Aprovado
**Stack**: Laravel 12 · Livewire 4 · Alpine.js 3 · Tailwind CSS 4 · PostgreSQL 16 · Redis 7

---

## 1. Contexto e Objetivo

Migração do sistema BCA Scrap (PHP vanilla + Bootstrap 5 + MySQL) para Laravel 12 TALL Stack. O sistema monitora automaticamente o Boletim do Comando da Aeronáutica (BCA), identificando militares do GAC-PAC e palavras-chave de interesse, enviando notificações por email.

**Servidor**: VM no data center da intranet FAB, acesso via VPN. Deploy via Docker Compose.

---

## 2. Direção Visual

**Opção B — Dashboard Moderno**: fundo claro (`#f8fafc`), cards com sombra suave, azul navy (`#1e3a5f`) como cor primária. Visual SaaS limpo, confortável para uso diário prolongado. Tipografia Inter. Componentes Tailwind CSS 4 com `@tailwindcss/forms`.

Paleta:
- Primary: `#1e3a5f` (navy)
- Secondary: `#3b6aab` (blue)
- Background: `#f8fafc`
- Surface: `#ffffff`
- Text: `#1e293b`
- Muted: `#64748b`
- Accent success: `#16a34a`
- Accent danger: `#dc2626`

---

## 3. Arquitetura

### Camadas

```
Presentation  → Livewire 4 Components + Alpine.js + Tailwind 4
Application   → Services (BcaDownload, BcaProcessing, BcaAnalysis)
Domain        → Eloquent Models + Repository Interfaces
Infrastructure→ PostgreSQL 16 + Redis 7 + Queue Worker + Mail
```

### Estrutura de Diretórios (Laravel)

```
app/
├── Http/
│   ├── Livewire/
│   │   ├── BuscaBca.php
│   │   ├── ResultadoBusca.php
│   │   ├── ListagemEfetivo.php
│   │   ├── FormularioEfetivo.php
│   │   ├── GestorPalavras.php
│   │   ├── HistoricoOcorrencias.php
│   │   └── LogExecucoes.php
│   └── Middleware/
│       └── EnsureRole.php
├── Mail/
│   └── NotificacaoBcaMail.php       ← Mailable com template Blade
├── Models/
│   ├── User.php
│   ├── Efetivo.php
│   ├── Bca.php
│   ├── BcaOcorrencia.php
│   ├── PalavraChave.php
│   └── BcaExecucao.php
├── Services/
│   ├── BcaDownloadService.php
│   ├── BcaProcessingService.php
│   └── BcaAnalysisService.php
├── Repositories/
│   ├── Contracts/
│   │   ├── BcaRepositoryInterface.php
│   │   └── EfetivoRepositoryInterface.php
│   ├── BcaRepository.php
│   └── EfetivoRepository.php
├── Jobs/
│   ├── BaixarBcaJob.php
│   ├── ProcessarBcaJob.php
│   ├── AnalisarEfetivoJob.php
│   └── EnviarEmailNotificacaoJob.php
└── Events/
    └── MilitarEncontradoEvent.php

resources/views/
├── layouts/
│   └── app.blade.php
├── livewire/          ← views dos componentes
└── mail/
    └── notificacao-bca.blade.php   ← template HTML do email
```

---

## 4. Banco de Dados

### PostgreSQL — Configurações Globais

- **Extensão obrigatória**: `CREATE EXTENSION IF NOT EXISTS unaccent;`
- **Text Search Config**: `CREATE TEXT SEARCH CONFIGURATION portuguese_unaccent (COPY = portuguese);` com dicionário `unaccent` aplicado — garante matching insensível a acentos (MACÊDO ↔ MACEDO, PROENÇA ↔ PROENCA)
- **Coluna tsvector**: sempre gerada com `to_tsvector('portuguese_unaccent', ...)` via trigger ou coluna `GENERATED ALWAYS`

### Tabelas

```sql
-- efetivos (migrado do MySQL efetivo)
id BIGSERIAL PRIMARY KEY
saram VARCHAR(8) UNIQUE NOT NULL
nome_guerra VARCHAR(50) NOT NULL
nome_completo VARCHAR(200) NOT NULL
posto VARCHAR(20) NOT NULL
especialidade VARCHAR(50) NULL
email VARCHAR(255) NULL
om_origem VARCHAR(50) DEFAULT 'GAC-PAC'
ativo BOOLEAN DEFAULT true
oculto BOOLEAN DEFAULT false
  -- oculto=true: pessoa consta no sistema mas é excluída
  -- de buscas, resultados e envio de email (ex: transferidos
  -- que ainda constam no DB por histórico)
nome_tsvector TSVECTOR GENERATED ALWAYS AS
  (to_tsvector('portuguese_unaccent', nome_completo)) STORED
-- GIN index em nome_tsvector
created_at, updated_at TIMESTAMPS

-- bcas
id BIGSERIAL PRIMARY KEY
numero VARCHAR(20) NOT NULL
data DATE UNIQUE NOT NULL
url VARCHAR(500) NULL
texto_completo TEXT NULL
processado_em TIMESTAMP NULL
texto_tsvector TSVECTOR GENERATED ALWAYS AS
  (to_tsvector('portuguese_unaccent', COALESCE(texto_completo, ''))) STORED
-- GIN index em texto_tsvector
created_at, updated_at TIMESTAMPS

-- bca_ocorrencias
id BIGSERIAL PRIMARY KEY
bca_id BIGINT NOT NULL REFERENCES bcas(id)
efetivo_id BIGINT NOT NULL REFERENCES efetivos(id)
snippet TEXT NULL         ← trecho destacado do boletim
enviado_em TIMESTAMP NULL ← NULL = email pendente
UNIQUE(bca_id, efetivo_id) ← previne duplicatas em retries
created_at TIMESTAMP

-- palavras_chaves (migrado do MySQL palavras_chave)
id BIGSERIAL PRIMARY KEY
palavra VARCHAR(100) UNIQUE NOT NULL
cor CHAR(6) NOT NULL   ← cor individual por keyword (não usa default branco)
ativa BOOLEAN DEFAULT false  ← desabilitada por padrão
created_at, updated_at TIMESTAMPS

-- bca_execucoes (1 registro por ciclo de execução do scheduler ou busca manual)
id BIGSERIAL PRIMARY KEY
tipo VARCHAR(20) NOT NULL         ← 'automatica' | 'manual'
data_execucao TIMESTAMP NOT NULL
status VARCHAR(20) NOT NULL       ← 'sucesso' | 'falha' | 'sem_bca'
mensagem TEXT NULL
registros_processados INT DEFAULT 0
created_at TIMESTAMP

-- users (Laravel auth + role)
id BIGSERIAL PRIMARY KEY
name VARCHAR(255) NOT NULL
email VARCHAR(255) UNIQUE NOT NULL
password VARCHAR(255) NOT NULL
role VARCHAR(20) DEFAULT 'operador'  ← 'admin' | 'operador'
remember_token VARCHAR(100) NULL
created_at, updated_at TIMESTAMPS
```

### Seeds Iniciais

- **PalavraChaveSeeder**: preserva cores originais do `init.sql`
  - GAC-PAC (#3498DB), COPAC (#2d54f0), LINK-BR (#db2424), KC-390 (#24db42),
    KC-X (#48d560), FX-2 (#d3d548), CAS (#48abd5), CAA (#48abd5), CEAG (#48abd5)
  - Todas com `ativa = false`
- **EfetivoSeeder**: os 47+ militares do `init.sql` existente
- **UserSeeder**: 1 usuário admin padrão (email/senha via `.env`)

---

## 5. Lógica de Matching — Busca no BCA

O texto extraído do PDF é pesquisado por **duas estratégias combinadas (OR)**:

### 5.1 Matching por Nome (FTS)

```php
// PostgreSQL FTS com configuração portuguese_unaccent
// Busca cada efetivo ativo e não-oculto no texto do BCA
WHERE to_tsvector('portuguese_unaccent', texto_bca)
   @@ to_tsquery('portuguese_unaccent', 'NOME & SOBRENOME')
```

### 5.2 Matching por SARAM (texto simples)

SARAM é numérico — não usa FTS. Usa `POSITION()` ou `LIKE` diretamente no texto:

```php
// Verifica SARAM direto (ex: '3047512') e variante com hífen ('304751-2')
$saramHifenado = substr($saram, 0, -1) . '-' . substr($saram, -1);
str_contains($textoBca, $saram) || str_contains($textoBca, $saramHifenado)
```

### 5.3 Matching por Palavras-chave

```php
// Apenas palavras_chaves com ativa=true
// Busca simples: str_contains ou ILIKE no texto do BCA
// Registrado em bca_execucoes, não em bca_ocorrencias
// (keywords não são vinculadas a um efetivo específico)
```

> **Nota**: hits de palavras-chave sem efetivo associado são registrados em `bca_execucoes.mensagem` como JSON (`{"keywords_encontradas": ["KC-390", "GAC-PAC"]}`), não em `bca_ocorrencias`.

---

## 6. Fluxo Principal — Busca Automática

### Scheduler

```php
// routes/console.php
Schedule::job(new BaixarBcaJob())
    ->hourlyAt(0)
    ->between('08:00', '17:00')
    ->weekdays()
    ->withoutOverlapping(10)   ← lock Redis por 10 min; previne runs concorrentes
    ->onOneServer();           ← garante execução única em cluster
```

### Pipeline de Jobs

```
BaixarBcaJob
  ├── Verifica cache Redis 'bca:query:{data}'
  │     ├── Cache hit com valor 'nao_encontrado' (TTL 1h) → skip
  │     ├── Cache hit com URL válida → usa URL cacheada
  │     └── Cache miss → consulta CENDOC/ICEA
  │
  ├── Consulta CENDOC: Http::pool() com chunk(10) sobre lista de
  │   possíveis números de BCA do dia (loop 1..366 em chunks de 10
  │   requisições paralelas, timeout=5s, retry=2 com backoff 100ms)
  │   Valida: strlen($response->body()) > 1000 e Content-Type PDF
  │   Valida: tamanho máximo 50MB
  │
  ├── Se não encontrado: cache 'nao_encontrado' (TTL 1h)
  │   registra bca_execucoes(status='sem_bca') → fim
  │
  ├── Se encontrado: salva PDF em storage/app/bcas/{data}.pdf
  │   cache URL válida (TTL 24h)
  │   registra em tabela bcas
  └── Dispara ProcessarBcaJob::dispatch($bcaId)
           │
           ▼
      ProcessarBcaJob
        ├── pdftotext -enc UTF-8 -layout {arquivo}.pdf -  → texto UTF-8
        ├── Normaliza encoding: mb_convert_encoding($text, 'UTF-8', 'UTF-8')
        ├── Salva em cache Redis 'bca:texto:{data}' (TTL 30d)
        ├── Atualiza bcas.texto_completo + processado_em
        └── Dispara AnalisarEfetivoJob::dispatch($bcaId)
                  │
                  ▼
           AnalisarEfetivoJob
             ├── Recupera texto do BCA (cache Redis ou DB)
             ├── Para cada efetivo com ativo=true AND oculto=false:
             │     busca por nome (FTS) OU saram (POSITION/LIKE)
             │     Se encontrado: cria bca_ocorrencias (ignora se já existe)
             │
             ├── Busca keywords com ativa=true no texto (str_contains)
             │   Registra keywords encontradas em bca_execucoes.mensagem
             │
             ├── Registra bca_execucoes(status='sucesso', registros_processados=N)
             │
             └── Para cada bca_ocorrencia nova:
                   → dispara EnviarEmailNotificacaoJob::dispatch($ocorrenciaId)
                              │
                              ▼
                   EnviarEmailNotificacaoJob
                     tries = 3, backoff = [30, 60, 120] segundos
                     ├── Recupera ocorrencia + efetivo + bca
                     ├── Destinatário: efetivo.email (o próprio militar encontrado)
                     ├── Envia NotificacaoBcaMail com: nome_guerra, numero_bca,
                     │   data_bca, snippet, link para download (URL do BCA)
                     ├── Atualiza bca_ocorrencias.enviado_em = now()
                     └── Se falha após 3 tentativas:
                           failed(): registra em bca_execucoes(status='falha')
                           bca_ocorrencias.enviado_em permanece NULL
```

### Modo Manual

- Usuário acessa tela Busca, seleciona data, clica "Buscar BCA"
- Mesmo pipeline, disparado via `BaixarBcaJob::dispatch()` sob demanda
- Registrado em bca_execucoes com `tipo='manual'`

### Botão "Enviar email" manual na tela de resultado

- Visível apenas se `bca_ocorrencias.enviado_em IS NULL` (não enviado ainda)
- Se já enviado: exibe badge "Email enviado em {data}" (não permite reenvio pela UI)
- Admin pode forçar reenvio via tela de Log de Execuções (fora do escopo v2 — future)

---

## 7. Controle de Acesso (Roles)

| Funcionalidade | Admin | Operador |
|---|---|---|
| Busca manual por data | ✅ | ✅ |
| Ver resultados / snippets | ✅ | ✅ |
| Ver histórico de ocorrências | ✅ | ✅ |
| Ativar/desativar palavras-chave | ✅ | ✅ |
| CRUD palavras-chave | ✅ | ❌ |
| CRUD efetivo | ✅ | ❌ |
| Gerenciar usuários | ✅ | ❌ |
| Ver log de execuções | ✅ | ❌ |

Implementação: coluna `role` na tabela `users` + middleware `EnsureRole`.

---

## 8. Interface — Telas

### Layout Base
- Sidebar fixa à esquerda com navegação
- Header com nome do usuário e logout
- Conteúdo principal com padding adequado
- Responsivo (mobile via sidebar colapsável com Alpine.js)

### Tela: Busca BCA (tela inicial padrão)
- Input de data com flatpickr (estilo navy)
- Botão "Buscar BCA"
- Estado de loading com spinner (Livewire wire:loading)
- Cards de resultado: nome_guerra em destaque, posto, snippet do BCA, badge cache/live
- Badge "Nenhum resultado" quando não encontrado
- Por resultado encontrado: badge de status email (enviado/pendente) + botão "Enviar" se pendente

### Tela: Histórico de Ocorrências
- Tabela paginada: data BCA, número, militar, posto, snippet, status email
- Filtros por data e militar

### Tela: Palavras-chave
- Lista com toggle ativo/inativo (Alpine.js x-data)
- Badge colorido por palavra (cor individual configurável)
- Admin: botões criar/editar/excluir
- Operador: apenas toggle ativo/inativo

### Tela: Efetivo (admin)
- Tabela paginada com busca inline (Livewire)
- Modal de criação/edição
- Toggle ativo / oculto com tooltip explicativo

### Tela: Usuários (admin)
- CRUD de usuários com definição de role

### Tela: Log de Execuções (admin)
- Tabela: tipo, data, status, mensagem (inclui keywords encontradas em JSON), registros_processados
- Badge de status colorido (sucesso=verde, falha=vermelho, sem_bca=cinza)

---

## 9. Cache — Estratégia Redis (3 camadas)

| Camada | Chave | TTL | Conteúdo |
|---|---|---|---|
| Query — encontrado | `bca:query:{data}` | 24h | URL do BCA encontrado |
| Query — não encontrado | `bca:query:{data}` | 1h | `'nao_encontrado'` (evita spam à API) |
| PDF texto | `bca:texto:{data}` | 30d | Texto UTF-8 extraído do PDF |
| Análise | `bca:analise:{data}` | 1h | Resultados de ocorrências |

> **Regra**: cache `nao_encontrado` tem TTL de 1h, não 24h — permite que o BCA seja encontrado
> na próxima hora caso tenha sido publicado após a primeira tentativa.

---

## 10. Testes (Pest PHP)

### Estratégia
- Banco de teste isolado: `bca_test` (PostgreSQL, com extensão `unaccent`)
- Queue driver: `sync` em testes
- Mail driver: `array` em testes
- Execução: `php artisan test --parallel`

### Cobertura Alvo

| Camada | Meta |
|---|---|
| Services | ≥80% |
| Jobs | ≥80% |
| Livewire Components | ≥60% |
| Models/Repositories | ≥70% |

### Casos de Teste Principais
- `BcaDownloadService`: cache hit (URL), cache hit (nao_encontrado), API CENDOC, PDF > 50MB rejeitado
- `BcaProcessingService`: pdftotext UTF-8, cache, exception handling
- `AnalisarEfetivoJob`: FTS encontra (com acento), SARAM direto, SARAM hifenado, oculto excluído, keyword ativa encontrada, duplicata ignorada (UNIQUE constraint)
- `EnviarEmailNotificacaoJob`: envio OK, retry após falha SMTP, falha após 3 tentativas (failed())
- `BuscaBca` (Livewire): render, validação, resultado com email pendente, resultado já enviado
- `GestorPalavras` (Livewire): toggle ativo, CRUD (admin), rejeição toggle por operador bloqueado em CRUD

---

## 11. Infraestrutura Docker

```yaml
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80"]
    depends_on: [php]

  php:
    build: Dockerfile  # PHP 8.3-fpm-alpine
    # Inclui: poppler-utils, pdo_pgsql, redis PECL 6.0.2 (pinado)
    volumes:
      - ./:/var/www/html
      - bcas_storage:/var/www/html/storage/app/bcas  ← volume persistente para PDFs

  postgres:
    image: postgres:16-alpine
    volumes: [pgdata:/var/lib/postgresql/data]
    environment: POSTGRES_DB, POSTGRES_USER, POSTGRES_PASSWORD

  redis:
    image: redis:7-alpine

  queue:
    # mesmo build do php, comando: php artisan queue:work --sleep=3 --tries=3
    # Nota: Laravel Horizon não é usado — queue:work é suficiente para este workload
    # (1 BCA/hora, ~47 efetivos). Horizon adicionaria complexidade sem benefício real.
    depends_on: [redis, postgres]

  pgadmin:
    image: dpage/pgadmin4
    ports: ["5050:80"]
    profiles: ["dev"]  ← apenas com: docker compose --profile dev up

volumes:
  pgdata:
  bcas_storage:   ← PDFs persistidos entre restarts do container
```

> **pgAdmin**: excluído do compose padrão via `profiles: ["dev"]`. Em produção,
> `docker compose up` não sobe o pgAdmin. Apenas `docker compose --profile dev up` inclui.

---

## 12. Migração de Dados

Script `docs/anexos/scripts/migrate-data.sh`:
- Exporta MySQL → CSV via `mysqldump --tab`
- Importa no PostgreSQL via `COPY` ou INSERT em lote
- Tabelas migradas: `efetivo` → `efetivos`, `palavras_chave` → `palavras_chaves`, `bca_execucoes` → `bca_execucoes`
- Após import: `UPDATE efetivos SET nome_tsvector = to_tsvector('portuguese_unaccent', nome_completo)` para popular coluna gerada
- Validação: contagem de registros antes/depois com assert

---

## 13. Fora do Escopo (v2)

- API REST pública
- App mobile
- Notificações push / WhatsApp
- SSO / integração com AD FAB
- Multi-OM (apenas GAC-PAC)
- Reenvio forçado de email via UI (admin pode fazer diretamente via tinker se necessário)
- Calendário de feriados (status `sem_bca` é suficiente para dias sem publicação)

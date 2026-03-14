# BCA Scrap v2 - Atualização e Completude da Documentação

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplicar todas as recomendações da análise técnica e criar os documentos faltantes (02–08 + anexos + scripts), transformando a documentação de 40% para 100% de cobertura.

**Architecture:** Cada documento segue o padrão Markdown já estabelecido no projeto (emojis de seção, tabelas comparativas, exemplos de código reais). As correções nos docs existentes são cirúrgicas — alterar apenas o que foi identificado como problema, sem reescrever o que já está bom.

**Tech Stack:** Markdown, Bash scripts, YAML (docker-compose), PHP (Laravel 12), configurações nginx/tailwind/horizon

---

## Mapeamento de Arquivos

### Modificar (6 arquivos)
| Arquivo | O que muda |
|---------|------------|
| `README.md` | Roadmap 7→8 semanas; atualizar fase 2 com semana 0 |
| `docs/09_MIGRACAO_PASSO_A_PASSO.md` | Adicionar Semana 0 (pré-projeto); corrigir chunk paralelo de 50→10; estender para 8 semanas |
| `docs/INICIO_RAPIDO.md` | Referências "7 semanas" → "8 semanas"; adicionar link ROLLBACK_PLAN |
| `docs/00_INDICE.md` | Adicionar 7 documentos novos (02–08) + ROLLBACK_PLAN + anexos novos |
| `ESTRUTURA.md` | Atualizar lista de arquivos e estatísticas |
| `docs/anexos/checklist_migracao.md` | Adicionar Fase 0 (validações pré-projeto); ajustar meta testes Livewire 75%→60% |

### Criar (15 arquivos)
| Arquivo | Descrição |
|---------|-----------|
| `docs/ROLLBACK_PLAN.md` | Plano de rollback detalhado (3 cenários) |
| `docs/02_ARQUITETURA.md` | Arquitetura TALL Stack completa (MVC, Services, Repositories, Events) |
| `docs/03_BANCO_DE_DADOS.md` | PostgreSQL: migrations completas, models, seeders, FTS |
| `docs/04_OTIMIZACAO_PERFORMANCE.md` | Cache Redis multi-layer, busca paralela corrigida, PostgreSQL FTS |
| `docs/05_COMPONENTES_LIVEWIRE.md` | Todos os componentes com código completo |
| `docs/06_SISTEMA_FILAS_JOBS.md` | Jobs, Horizon, Scheduler, Events |
| `docs/07_DOCKER_INFRAESTRUTURA.md` | Dockerfiles, nginx, supervisor, pgAdmin |
| `docs/08_TESTES.md` | Estratégia Pest PHP, organização dos testes, metas ajustadas |
| `docs/anexos/comparacao_performance.md` | Benchmarks detalhados + análise ROI |
| `docs/exemplos/nginx.conf.example` | Config Nginx para Laravel |
| `docs/exemplos/package.json.example` | Dependências NPM (Tailwind 4, Alpine, Flatpickr) |
| `docs/exemplos/tailwind.config.js.example` | Config Tailwind com tema FAB |
| `docs/exemplos/horizon.php.example` | Config Horizon (filas, workers, alertas) |
| `docs/anexos/scripts/setup-docker.sh` | Script de setup inicial do ambiente Docker |
| `docs/anexos/scripts/backup.sh` | Script de backup automatizado PostgreSQL |

---

## Chunk 1: Correções Críticas nos Documentos Existentes

> Prioridade máxima — estes erros podem causar problemas reais na implementação.

### Tarefa 1.1: Corrigir `docs/09_MIGRACAO_PASSO_A_PASSO.md`

**Arquivos:**
- Modify: `docs/09_MIGRACAO_PASSO_A_PASSO.md`

- [ ] **Passo 1.1.1: Adicionar Semana 0 (Validações Pré-Projeto) ao topo do cronograma**

Localizar o bloco da tabela `## 📅 Cronograma Executivo` e substituir por:

```markdown
## 📅 Cronograma Executivo

| Semana | Fase | Entregas | Status |
|--------|------|----------|--------|
| 0 | Pré-Projeto | Validações infraestrutura, backup, staging | 📝 Planejado |
| 1 | Preparação | Setup, Docker, Migrations | 📝 Planejado |
| 2-3 | Backend | Models, Services, Jobs | 📝 Planejado |
| 4-5 | Frontend | Livewire Components | 📝 Planejado |
| 5.5 | Teste Migração | Teste migração dados em dev | 📝 Planejado |
| 6 | Otimização | Cache, Performance | 📝 Planejado |
| 7 | Testes Finais | Integração, UAT, Staging | 📝 Planejado |
| 8 | Deploy | Migração dados, Produção | 📝 Planejado |
```

- [ ] **Passo 1.1.2: Inserir bloco completo da Semana 0 antes da Semana 1**

Inserir antes do título `## Semana 1: Preparação e Fundação`:

```markdown
---

## Semana 0: Pré-Projeto (Validações e Preparação)

> ⚠️ **OBRIGATÓRIO** — Executar antes de qualquer código. Falhar aqui = falhar na Semana 7.

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
[ ] O PHP do servidor suporta versão 8.3+?
[ ] Há acesso externo ao CENDOC/ICEA do servidor de produção?
```

### Dia -2: Testar Migração de Dados em Dev

```bash
# Testar migração ANTES da semana 7 — nunca primeiro em produção
# 1. Backup do sistema atual
mysqldump -u root -p bca_db > backup_$(date +%Y%m%d)_pre_migracao.sql

# 2. Instalar pgloader (ferramenta de migração)
docker pull dimitri/pgloader

# 3. Testar migração em ambiente isolado
docker run --rm dimitri/pgloader \
    pgloader mysql://root:senha@localhost/bca_db \
              pgsql://bca_user:bca_pass@localhost/bca_db_test

# 4. Validar integridade
docker exec bca-postgres psql -U bca_user bca_db_test -c "
    SELECT
        'efetivos' as tabela, COUNT(*) as registros FROM efetivos
    UNION ALL
    SELECT 'palavras_chaves', COUNT(*) FROM palavras_chaves
    UNION ALL
    SELECT 'bcas', COUNT(*) FROM bcas;
"
```

### Dia -1: Preparar Ambiente

```bash
# Criar repositório Git
git init bca-scrap-laravel
cd bca-scrap-laravel
git branch -M main

# Configurar ambiente de staging (servidor separado do dev)
# Este ambiente será usado para testes antes de ir para produção
cp .env.example .env.staging
# Editar .env.staging com credenciais do servidor de staging

# Verificar acesso às APIs externas
curl -I "https://www2.fab.mil.br/cendoc/" || echo "⚠️ CENDOC inacessível do servidor!"
```

### ✅ Checklist Semana 0
- [ ] TI confirmou Docker e PostgreSQL aprovados
- [ ] Servidor de produção tem recursos suficientes (RAM, disco)
- [ ] Acesso às APIs externas (CENDOC/ICEA) validado
- [ ] Migração MySQL→PostgreSQL testada em ambiente isolado
- [ ] Backup do sistema atual criado e armazenado com segurança
- [ ] Repositório Git criado
- [ ] Ambiente de staging disponível
- [ ] Plano de rollback revisado ([ROLLBACK_PLAN.md](../ROLLBACK_PLAN.md))

---
```

- [ ] **Passo 1.1.3: Corrigir busca paralela de chunk(50) para chunk(10)**

Localizar o método `buscaParalela` no arquivo e substituir o bloco `private function buscaParalela` por:

```php
    private function buscaParalela(string $data): ?array
    {
        // IMPORTANTE: Limitar a 10 requests simultâneos para não sobrecarregar CENDOC
        return collect(range(1, 366))
            ->chunk(10) // Máximo 10 simultâneos (evita rate limiting / IP ban)
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
```

- [ ] **Passo 1.1.4: Adicionar seção de Rate Limiting no Dia 8-10 (Services)**

Após o bloco `BcaDownloadService`, adicionar:

```markdown
#### Rate Limiting para APIs Externas

```php
use Illuminate\Support\Facades\RateLimiter;

class BcaDownloadService
{
    public function buscarBca(string $data): ?array
    {
        // Máximo 60 buscas por minuto
        $executed = RateLimiter::attempt(
            'buscar-bca',
            $perMinute = 60,
            fn () => $this->executarBuscaCompleta($data)
        );

        if (!$executed) {
            throw new \RuntimeException('Rate limit atingido. Aguarde 1 minuto.');
        }

        return $executed;
    }
}
```
```

- [ ] **Passo 1.1.5: Renomear Semana 7 atual para Semana 7-8 e atualizar checklist**

Localizar `## Semana 7: Deploy e Migração de Dados` e substituir por `## Semanas 7-8: Testes Finais e Deploy em Produção`

Atualizar o checklist da semana:

```markdown
### ✅ Checklist Semanas 7-8
- [ ] Testes de integração completos (80%+ coverage)
- [ ] Todos os testes passando
- [ ] Deploy em staging OK
- [ ] Validação UAT concluída com usuários reais
- [ ] Dados migrados com sucesso (validados com checksums)
- [ ] Deploy em produção
- [ ] Monitoring e alertas ativos
- [ ] Documentação atualizada
- [ ] Plano de rollback testado
- [ ] Equipe treinada
```

---

### Tarefa 1.2: Corrigir `README.md`

**Arquivos:**
- Modify: `README.md`

- [ ] **Passo 1.2.1: Atualizar tabela de Roadmap — adicionar Semana 0 e estender para 8 semanas**

Localizar a seção `## 📝 Roadmap` e substituir as fases:

```markdown
## 📝 Roadmap

### Semana 0 - Pré-Projeto ⚠️ (Antes de Começar)
- [ ] Validações de infraestrutura com TI
- [ ] Teste de migração MySQL→PostgreSQL em ambiente isolado
- [ ] Backup completo do sistema atual
- [ ] Ambiente de staging configurado
- [ ] Acesso às APIs externas (CENDOC/ICEA) validado

### Fase 1 - MVP (Semanas 1-5)
- [ ] Setup inicial Laravel 12
- [ ] Migrations PostgreSQL
- [ ] Models e Repositories
- [ ] Services (Download, Processing) com busca paralela (10 req/chunk)
- [ ] Componentes Livewire básicos
- [ ] Teste de migração de dados em ambiente de dev (Semana 5.5)

### Fase 2 - Otimização e Qualidade (Semanas 6-7)
- [ ] Sistema de Queue completo com Horizon
- [ ] Otimizações de performance (Cache Redis 3 camadas)
- [ ] Testes automatizados (80%+ cobertura Services/Jobs, 60%+ Livewire)
- [ ] Testes de integração e UAT

### Fase 3 - Deploy (Semana 8)
- [ ] Migração de dados produção (com rollback testado)
- [ ] Deploy produção
- [ ] Monitoramento e alertas

### Fase 4 - Melhorias Futuras 📅 (Pós-lançamento)
- [ ] Notificações Telegram
- [ ] Relatórios em PDF
- [ ] Dashboard de estatísticas
- [ ] API pública documentada
- [ ] PWA (Progressive Web App)
```

---

### Tarefa 1.3: Corrigir `docs/anexos/checklist_migracao.md`

**Arquivos:**
- Modify: `docs/anexos/checklist_migracao.md`

- [ ] **Passo 1.3.1: Adicionar Fase 0 (Pré-Projeto) antes da Fase 1**

Inserir antes de `## 📋 Fase 1: Preparação (Semana 1)`:

```markdown
## 📋 Fase 0: Pré-Projeto (Semana 0) ⚠️ OBRIGATÓRIO

### Validações de Infraestrutura
- [ ] TI confirmou Docker permitido em produção
- [ ] PostgreSQL 16 aprovado pela política de TI
- [ ] Redis aprovado (ou alternativa Memcached definida)
- [ ] Recursos do servidor verificados (≥4GB RAM, ≥20GB disco)
- [ ] Firewall: portas 8080, 5432, 6379 liberadas internamente
- [ ] PHP 8.3+ disponível ou aprovado para instalação

### Conectividade Externa
- [ ] Acesso ao CENDOC validado do servidor de produção
- [ ] Acesso ao ICEA validado do servidor de produção
- [ ] Limite de requests das APIs externas documentado

### Backup e Dados
- [ ] Backup completo do MySQL atual criado e validado
- [ ] Script de migração MySQL→PostgreSQL testado em ambiente isolado
- [ ] Contagem de registros documentada (baseline para validação pós-migração)
- [ ] PDFs existentes inventariados (quantidade, tamanho total)

### Ambiente
- [ ] Repositório Git criado
- [ ] Ambiente de staging disponível (separado do dev e prod)
- [ ] Plano de rollback revisado e aprovado

---
```

- [ ] **Passo 1.3.2: Ajustar meta de testes Livewire de 75% para 60%**

Localizar na Fase 3 as linhas de testes de componentes Livewire e adicionar comentário:

```markdown
### Componentes Livewire - Busca
- [ ] BuscaBca (com testes de integração Livewire)
- [ ] ResultadoBusca (com testes de integração Livewire)
- [ ] PalavrasChaveSelector (com testes de integração Livewire)
- [ ] Views Blade
- [ ] Testes de componentes (meta: 60%+ cobertura — Livewire é intrinsecamente complexo de testar)
```

- [ ] **Passo 1.3.3: Atualizar Critérios de Aceitação com metas ajustadas**

Localizar `### Qualidade` nos critérios de aceitação e substituir por:

```markdown
### Qualidade
- [ ] 80%+ cobertura testes (Services e Jobs — negócio crítico)
- [ ] 60%+ cobertura testes (Livewire Components — UI reativa)
- [ ] 0 erros PHPStan level 5
- [ ] 0 bugs críticos
- [ ] Documentação completa
```

---

### Tarefa 1.4: Atualizar referências em `docs/INICIO_RAPIDO.md`

**Arquivos:**
- Modify: `docs/INICIO_RAPIDO.md`

- [ ] **Passo 1.4.1: Atualizar texto "7 semanas" e adicionar link para Semana 0**

Localizar `## 🎯 Por onde começar?` e substituir por:

```markdown
## 🎯 Por onde começar?

> ⚠️ **PRIMEIRO**: Antes de qualquer código, execute a [Semana 0 - Pré-Projeto](docs/09_MIGRACAO_PASSO_A_PASSO.md#semana-0-pré-projeto-validações-e-preparação)

1. **Se você é GESTOR/PO**: Leia [01 - Visão Geral](docs/01_VISAO_GERAL.md) (10 min)
2. **Se você vai DESENVOLVER**: Leia [09 - Migração Passo a Passo](docs/09_MIGRACAO_PASSO_A_PASSO.md) (Guia 8 semanas)
3. **Se vai fazer DEPLOY**: Use [Checklist de Migração](docs/anexos/checklist_migracao.md)
4. **Se ocorrer problema**: Consulte [Plano de Rollback](docs/ROLLBACK_PLAN.md)
5. **Referência rápida**: [10 - Guia de Comandos](docs/10_GUIA_COMANDOS.md)
```

---

## Chunk 2: Novo Documento — Plano de Rollback

### Tarefa 2.1: Criar `docs/ROLLBACK_PLAN.md`

**Arquivos:**
- Create: `docs/ROLLBACK_PLAN.md`

- [ ] **Passo 2.1.1: Criar o documento completo de rollback**

```markdown
# 🔄 Plano de Rollback — BCA Scrap v2

> **Meta**: Retornar ao sistema anterior em no máximo **30 minutos** após detecção de problema crítico.

---

## 📋 Quando Acionar o Rollback

| Gatilho | Severidade | Ação |
|---------|-----------|------|
| Busca BCA com erro >5 min | 🔴 Crítico | Rollback imediato |
| Emails não sendo enviados >1h | 🔴 Crítico | Rollback imediato |
| Dados corrompidos/perdidos | 🔴 Crítico | Rollback imediato + RCA |
| Performance >10s por operação | 🟡 Alto | Investigar 30min, depois rollback |
| Erros isolados <10% das req | 🟢 Baixo | Corrigir no novo sistema |

---

## 🚨 Cenário 1: Bug Crítico Pós-Deploy (Sem Perda de Dados)

**Tempo estimado: 10-15 minutos**

```bash
# PASSO 1: Documentar o problema (30 segundos)
echo "$(date): Iniciando rollback — motivo: [DESCREVER]" >> /var/log/bca_rollback.log

# PASSO 2: Preservar logs do sistema novo
docker exec bca-php cp storage/logs/laravel.log /tmp/laravel_$(date +%Y%m%d_%H%M).log

# PASSO 3: Voltar ao código anterior
cd /home/gacpac/projects/bca-scrap-laravel
git log --oneline -10  # Identificar tag/commit anterior
git checkout <tag-versao-anterior>  # Ex: git checkout v1.9.2

# PASSO 4: Reinstalar dependências da versão anterior
composer install --no-dev --optimize-autoloader

# PASSO 5: Desfazer migrations (SE houver novas migrations problemáticas)
php artisan migrate:rollback  # Desfaz apenas a última batch
# OU para voltar várias batches:
php artisan migrate:rollback --step=3

# PASSO 6: Reiniciar serviços
docker-compose restart
php artisan horizon:terminate
php artisan queue:restart
php artisan cache:clear

# PASSO 7: Validar
php artisan bca:buscar-automatica --dry-run
echo "Smoke test: $(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)"
```

---

## 🚨 Cenário 2: Dados Corrompidos Durante Migração

**Tempo estimado: 20-30 minutos**

```bash
# PASSO 1: PARAR TUDO IMEDIATAMENTE
php artisan down  # Modo manutenção
php artisan horizon:pause

# PASSO 2: Identificar o backup mais recente
ls -la /backups/bca_*.sql | tail -5
# Escolher o backup PRÉ-migração

# PASSO 3: Restaurar banco PostgreSQL
docker exec bca-postgres psql -U bca_user -c "DROP DATABASE bca_db;"
docker exec bca-postgres psql -U bca_user -c "CREATE DATABASE bca_db;"
docker exec -i bca-postgres psql -U bca_user bca_db < /backups/bca_backup_pre_migracao.sql

# PASSO 4: Validar integridade
docker exec bca-postgres psql -U bca_user bca_db -c "
    SELECT
        (SELECT COUNT(*) FROM efetivos) as efetivos,
        (SELECT COUNT(*) FROM palavras_chaves) as palavras,
        (SELECT COUNT(*) FROM bcas) as bcas;
"
# Comparar com baseline documentado na Semana 0

# PASSO 5: Voltar código anterior
git checkout <versao-anterior>
composer install --no-dev
php artisan config:cache

# PASSO 6: Reativar sistema
php artisan up
php artisan horizon:continue

# PASSO 7: Notificar equipe
echo "Rollback concluído em $(date). Sistema restaurado para versão anterior." | \
    mail -s "BCA Scrap - Rollback Executado" gacpac@fab.mil.br
```

---

## 🚨 Cenário 3: Problema de Performance Grave

**Tempo estimado: 5-10 minutos**

```bash
# PASSO 1: Diagnóstico rápido (2 min)
php artisan horizon:status
redis-cli ping
docker exec bca-postgres psql -U bca_user bca_db -c "
    SELECT pid, now() - pg_stat_activity.query_start AS duration, query
    FROM pg_stat_activity
    WHERE (now() - pg_stat_activity.query_start) > interval '5 seconds';
"

# PASSO 2: Limpar gargalos de cache
php artisan cache:clear
php artisan horizon:terminate
php artisan queue:restart
php artisan horizon

# PASSO 3: SE não resolver em 10 min → Rollback completo (Cenário 1)
```

---

## 📊 Baseline de Validação Pós-Rollback

> Documentar antes do deploy. Preencher na Semana 0.

| Métrica | Valor Baseline | Data Medição |
|---------|---------------|--------------|
| Registros em `efetivos` | ___ | ___ |
| Registros em `palavras_chaves` | ___ | ___ |
| Registros em `bcas` | ___ | ___ |
| PDFs em storage (qtd) | ___ | ___ |
| Tempo médio busca BCA | ___s | ___ |

---

## 📞 Contatos de Emergência

| Função | Responsável | Contato |
|--------|------------|---------|
| Desenvolvedor | 1S BMB FERNANDO | gacpac@fab.mil.br |
| TI GAC-PAC | ___ | ___ |
| Responsável banco | ___ | ___ |

---

**Última atualização**: 14/03/2026
**Testado em staging**: [ ] Sim / [ ] Não — Data: ___
```

---

## Chunk 3: Documento de Arquitetura (02_ARQUITETURA.md)

### Tarefa 3.1: Criar `docs/02_ARQUITETURA.md`

**Arquivos:**
- Create: `docs/02_ARQUITETURA.md`

- [ ] **Passo 3.1.1: Criar documento completo de arquitetura**

```markdown
# 02 - Arquitetura do Sistema

## 🏗️ Visão Geral da Arquitetura

O BCA Scrap v2 segue a arquitetura **MVC + Repository + Service Layer** do Laravel, com componentes Livewire gerenciando o estado da UI.

```
┌─────────────────────────────────────────────────────────────┐
│  CAMADA DE APRESENTAÇÃO (Livewire + Blade + Alpine.js)      │
│  BuscaBca | ListagemEfetivo | GestorPalavras               │
└─────────────────────┬───────────────────────────────────────┘
                      │ wire:click / wire:model
┌─────────────────────▼───────────────────────────────────────┐
│  CAMADA DE APLICAÇÃO (Services)                             │
│  BcaDownloadService | BcaProcessingService | EfetivoService │
└─────────────────────┬───────────────────────────────────────┘
                      │ injeção de dependência
┌─────────────────────▼───────────────────────────────────────┐
│  CAMADA DE DOMÍNIO (Models + Repositories)                  │
│  Bca | Efetivo | PalavraChave | BcaEmail                   │
└─────────────────────┬───────────────────────────────────────┘
                      │ Eloquent ORM
┌─────────────────────▼───────────────────────────────────────┐
│  CAMADA DE DADOS (PostgreSQL 16 + Redis 7)                  │
│  Tabelas + Índices GIN + Cache multi-layer                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 📂 Estrutura de Diretórios

```
app/
├── Console/Commands/          # Artisan commands
│   ├── BuscaBcaAutomaticaCommand.php
│   ├── LimparBcasAntigosCommand.php
│   └── ReenviarEmailsFalhosCommand.php
├── Events/                    # Eventos do domínio
│   ├── MilitarEncontradoEvent.php
│   └── BcaProcessadoEvent.php
├── Http/
│   ├── Controllers/Api/       # APIs REST (futuro)
│   └── Livewire/              # Componentes Livewire
│       ├── Busca/
│       │   ├── BuscaBca.php
│       │   ├── ResultadoBusca.php
│       │   └── PalavrasChaveSelector.php
│       ├── Efetivo/
│       │   ├── ListagemEfetivo.php
│       │   └── FormularioEfetivo.php
│       └── Palavras/
│           └── GestorPalavras.php
├── Jobs/                      # Jobs assíncronos
│   ├── BaixarBcaJob.php
│   ├── ProcessarBcaJob.php
│   ├── AnalisarEfetivoJob.php
│   └── EnviarEmailNotificacaoJob.php
├── Listeners/                 # Event listeners
│   ├── NotificarMilitarListener.php
│   └── RegistrarExecucaoListener.php
├── Mail/                      # Templates email
│   └── MencaoBcaMail.php
├── Models/                    # Eloquent Models
│   ├── Bca.php
│   ├── Efetivo.php
│   ├── BcaEmail.php
│   ├── PalavraChave.php
│   ├── BcaOcorrencia.php
│   └── BcaExecucao.php
├── Repositories/              # Abstração de acesso a dados
│   ├── Contracts/
│   │   ├── BcaRepositoryInterface.php
│   │   └── EfetivoRepositoryInterface.php
│   ├── BcaRepository.php
│   └── EfetivoRepository.php
└── Services/                  # Lógica de negócio
    ├── BcaDownloadService.php
    ├── BcaProcessingService.php
    ├── EfetivoAnalysisService.php
    └── CendocApiService.php
```

---

## 🔄 Fluxo de Busca de BCA

```
1. Usuário clica "Buscar" no BuscaBca (Livewire)
        ↓
2. BuscaBca::buscar() valida data
        ↓
3. BcaDownloadService::buscarBca($data)
   ├── 3a. Verificar cache Redis → retorno instantâneo (<1s)
   ├── 3b. API CENDOC (número direto) → ~2s
   ├── 3c. Busca paralela HTTP (10 req/chunk) → ~5s
   └── 3d. Fallback ICEA → ~10s
        ↓
4. BCA encontrado → Salva no PostgreSQL
        ↓
5. Dispatcha ProcessarBcaJob (assíncrono)
        ↓
6. Job extrai texto PDF (pdftotext)
        ↓
7. Job dispatcha AnalisarEfetivoJob
        ↓
8. Job busca militares no texto (PostgreSQL FTS)
        ↓
9. Para cada militar encontrado: dispatcha EnviarEmailNotificacaoJob
        ↓
10. Email enviado em background — usuário não espera
```

---

## 🔌 Repository Pattern

O Repository Pattern desacopla a lógica de negócio do acesso a dados:

```php
// Interface (Contrato)
interface BcaRepositoryInterface
{
    public function findByData(string $data): ?Bca;
    public function findRecentes(int $limit = 10): Collection;
    public function create(array $dados): Bca;
}

// Implementação
class BcaRepository implements BcaRepositoryInterface
{
    public function findByData(string $data): ?Bca
    {
        return Bca::where('data_publicacao', $data)
            ->with(['ocorrencias', 'execucoes'])
            ->first();
    }
}

// Injeção no Service
class BcaDownloadService
{
    public function __construct(
        private readonly BcaRepositoryInterface $bcaRepo
    ) {}
}
```

**Benefícios**:
- Services testáveis sem banco de dados real
- Troca de ORM sem alterar lógica de negócio
- Queries complexas encapsuladas

---

## 📡 Sistema de Eventos

```php
// Evento disparado quando BCA é processado
class BcaProcessadoEvent
{
    public function __construct(
        public readonly Bca $bca,
        public readonly array $militaresEncontrados
    ) {}
}

// Listener: notifica militares
class NotificarMilitarListener
{
    public function handle(BcaProcessadoEvent $event): void
    {
        foreach ($event->militaresEncontrados as $efetivo) {
            EnviarEmailNotificacaoJob::dispatch($efetivo, $event->bca)
                ->onQueue('emails');
        }
    }
}

// Registrar em EventServiceProvider
protected $listen = [
    BcaProcessadoEvent::class => [
        NotificarMilitarListener::class,
        RegistrarExecucaoListener::class,
    ],
];
```

---

## 🔐 Segurança por Camadas

| Camada | Proteção | Implementação |
|--------|----------|---------------|
| **HTTP** | CSRF | Tokens automáticos Blade/Livewire |
| **Input** | XSS | Blade escaping (`{{ }}`) |
| **Database** | SQL Injection | Eloquent ORM (PDO bound params) |
| **API** | Rate Limiting | `throttle:60,1` em rotas |
| **Autenticação** | Sanctum | `auth:sanctum` middleware |
| **Secrets** | Env vars | `.env` nunca no Git |

---

**Próximo documento**: [03 - Banco de Dados](03_BANCO_DE_DADOS.md)
```

---

## Chunk 4: Banco de Dados, Performance e Livewire

### Tarefa 4.1: Criar `docs/03_BANCO_DE_DADOS.md`

**Arquivos:**
- Create: `docs/03_BANCO_DE_DADOS.md`

- [ ] **Passo 4.1.1: Criar documento completo do banco de dados**

O documento deve cobrir:
1. Diagrama de relacionamento entre tabelas (texto ASCII)
2. Todas as migrations completas (efetivos, bcas, bca_emails, palavras_chaves, bca_ocorrencias, bca_execucoes)
3. Configuração de Full-Text Search com índices GIN
4. Todos os Models com relationships, fillable, casts e scopes
5. Seeders com dados de exemplo
6. Processo de migração MySQL → PostgreSQL com pgloader

**Estrutura do documento**:

```markdown
# 03 - Banco de Dados (PostgreSQL 16)

## 🗺️ Diagrama de Entidades

```
efetivos (1) ─────────── (*) bca_ocorrencias (*) ─────────── (1) bcas
    │                                                              │
    └── (*) bca_emails                              (*) bca_execucoes

palavras_chaves (N) ── (relação futura para filtragem)
```

## 📋 Migrations

[Migrations completas de todas as tabelas com FTS indexes]

## 🏛️ Models

[Models com todas relationships, scopes, casts]

## 🌱 Seeders

[Seeders de exemplo para desenvolvimento]

## 🔍 Full-Text Search

[Queries otimizadas com to_tsvector e índices GIN]

## 📦 Migração de Dados (MySQL → PostgreSQL)

[Processo detalhado com pgloader + validação]
```

---

### Tarefa 4.2: Criar `docs/04_OTIMIZACAO_PERFORMANCE.md`

**Arquivos:**
- Create: `docs/04_OTIMIZACAO_PERFORMANCE.md`

- [ ] **Passo 4.2.1: Criar documento de otimização com busca paralela corrigida**

O documento deve cobrir:
1. Estratégia de cache Redis em 3 camadas (query cache, PDF text cache, resultado cache)
2. Busca paralela com `chunk(10)` e `Http::pool()` — já corrigida
3. PostgreSQL Full-Text Search com exemplos de queries
4. Eliminação de N+1 queries com eager loading
5. Lazy loading de componentes Livewire
6. Benchmarks esperados por operação

```markdown
# 04 - Otimização de Performance

## 🏎️ Estratégia Geral

### Camadas de Otimização
1. Cache (Redis) — evitar I/O desnecessário
2. Paralelismo — download simultâneo de múltiplas fontes
3. PostgreSQL FTS — busca textual nativa no banco
4. Queue (Horizon) — operações lentas em background
5. Lazy Loading Livewire — componentes carregam sob demanda

## 💾 Cache Redis (3 Camadas)

### Camada 1: Cache de Queries (24h TTL)
### Camada 2: Cache de Texto PDF (30 dias TTL)
### Camada 3: Cache de Resultados de Análise (1h TTL)

## ⚡ Busca Paralela (CORRIGIDA)
> chunk(10) — máximo 10 requests simultâneos

## 🔍 PostgreSQL Full-Text Search
> Índices GIN com to_tsvector('portuguese', ...)

## 🚫 Eliminar N+1 Queries
> with() e load() estratégicos
```

---

### Tarefa 4.3: Criar `docs/05_COMPONENTES_LIVEWIRE.md`

**Arquivos:**
- Create: `docs/05_COMPONENTES_LIVEWIRE.md`

- [ ] **Passo 4.3.1: Criar documento com todos os componentes Livewire**

O documento deve cobrir (com código completo de cada):
1. **BuscaBca** — componente de busca principal com flatpickr
2. **ResultadoBusca** — exibe resultado com snippets
3. **PalavrasChaveSelector** — seleção múltipla de palavras-chave
4. **ListagemEfetivo** — tabela paginada com busca
5. **FormularioEfetivo** — criação/edição de efetivo
6. **GestorPalavras** — CRUD de palavras-chave
7. **Layout principal** — app.blade.php com navegação

---

## Chunk 5: Filas, Docker e Testes

### Tarefa 5.1: Criar `docs/06_SISTEMA_FILAS_JOBS.md`

**Arquivos:**
- Create: `docs/06_SISTEMA_FILAS_JOBS.md`

- [ ] **Passo 5.1.1: Criar documento do sistema de filas**

O documento deve cobrir:
1. Configuração do Laravel Horizon (filas, workers, limites)
2. Todos os Jobs com código completo (BaixarBcaJob, ProcessarBcaJob, AnalisarEfetivoJob, EnviarEmailNotificacaoJob)
3. Configuração do Scheduler (artisan schedule)
4. Monitoramento e alertas via Horizon
5. Configuração do supervisor para produção
6. Troubleshooting de jobs travados

---

### Tarefa 5.2: Criar `docs/07_DOCKER_INFRAESTRUTURA.md`

**Arquivos:**
- Create: `docs/07_DOCKER_INFRAESTRUTURA.md`

- [ ] **Passo 5.2.1: Criar documento de infraestrutura Docker**

O documento deve cobrir:
1. **Dockerfile PHP** completo (PHP 8.3-FPM + extensões necessárias + pdftotext)
2. **docker-compose.yml** — revisão do exemplo existente com healthchecks
3. **nginx.conf** — configuração para Laravel (try_files, gzip, etc.)
4. **supervisor.conf** — para Horizon em produção
5. Volumes e persistência de dados
6. Procedimentos de backup e restore via Docker

---

### Tarefa 5.3: Criar `docs/08_TESTES.md`

**Arquivos:**
- Create: `docs/08_TESTES.md`

- [ ] **Passo 5.3.1: Criar documento de estratégia de testes com metas ajustadas**

```markdown
# 08 - Estratégia de Testes (Pest PHP)

## 🎯 Metas de Cobertura (Revisadas)

| Camada | Meta | Justificativa |
|--------|------|---------------|
| **Services** | 90%+ | Lógica de negócio crítica |
| **Jobs** | 85%+ | Processamento assíncrono |
| **Commands** | 80%+ | Automação do sistema |
| **Models/Repositories** | 80%+ | Acesso a dados |
| **Livewire Components** | 60%+ | UI reativa — complexidade de teste inerente |
| **API Controllers** | 75%+ | Endpoints REST (futuro) |

> **Por que 60% para Livewire?**
> Testes de componentes Livewire requerem setup complexo (TestResponse, assertSeeLivewire, etc.).
> Priorizar cobertura dos Services/Jobs — onde está a lógica crítica de negócio.
> Complementar Livewire com testes E2E (Laravel Dusk) para flows principais.
```

O documento deve cobrir:
1. Configuração do Pest PHP
2. Testes unitários de Services (com mocks de Http, Cache, Storage)
3. Testes de Jobs (com fake queues)
4. Testes de Livewire components (Livewire::test())
5. Testes de integração com banco de dados real
6. Testes E2E com Laravel Dusk (smoke tests)
7. Configuração de CI/CD com execução de testes

---

## Chunk 6: Exemplos e Scripts Faltantes

### Tarefa 6.1: Criar arquivos de configuração de exemplo

**Arquivos:**
- Create: `docs/exemplos/nginx.conf.example`
- Create: `docs/exemplos/package.json.example`
- Create: `docs/exemplos/tailwind.config.js.example`
- Create: `docs/exemplos/horizon.php.example`

- [ ] **Passo 6.1.1: Criar `docs/exemplos/nginx.conf.example`**

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }

    # Cache assets estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Livewire uploads
    location /livewire {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

- [ ] **Passo 6.1.2: Criar `docs/exemplos/package.json.example`**

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "autoprefixer": "^10.4.20",
        "axios": "^1.7.4",
        "laravel-vite-plugin": "^1.0.0",
        "tailwindcss": "^4.0.0",
        "@tailwindcss/vite": "^4.0.0",
        "@tailwindcss/forms": "^0.5.9",
        "@tailwindcss/typography": "^0.5.15",
        "vite": "^6.0.0"
    },
    "dependencies": {
        "alpinejs": "^3.14.1",
        "flatpickr": "^4.6.13",
        "@alpinejs/persist": "^3.14.1"
    }
}
```

- [ ] **Passo 6.1.3: Criar `docs/exemplos/tailwind.config.js.example`**

```js
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Cores da Força Aérea Brasileira
                fab: {
                    50:  '#f0f5fa',
                    100: '#dce8f4',
                    200: '#b8d1e9',
                    300: '#88b0d8',
                    400: '#5588c3',
                    500: '#3b6aab',
                    600: '#2d528a',
                    700: '#254270',
                    800: '#1f3659',
                    900: '#1b2d4a',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
```

- [ ] **Passo 6.1.4: Criar `docs/exemplos/horizon.php.example`**

```php
<?php
// config/horizon.php

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
    ],

    // Alertas — notificar se fila crescer demais
    'trim' => [
        'recent'        => 60,   // minutos
        'pending'       => 60,
        'completed'     => 60,
        'recent_failed' => 10080, // 1 semana
        'failed'        => 10080,
        'monitored'     => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job'  => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 64,

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue'      => ['default', 'emails', 'processing'],
            'balance'    => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 4,
            'minProcesses' => 1,
            'maxTime'    => 3600,    // 1 hora máximo por job
            'maxJobs'    => 1000,
            'memory'     => 128,
            'tries'      => 3,
            'timeout'    => 60,
            'nice'       => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 4,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 2,
            ],
        ],
    ],
];
```

---

### Tarefa 6.2: Criar scripts Shell faltantes

**Arquivos:**
- Create: `docs/anexos/scripts/setup-docker.sh`
- Create: `docs/anexos/scripts/backup.sh`

- [ ] **Passo 6.2.1: Criar `docs/anexos/scripts/setup-docker.sh`**

```bash
#!/bin/bash
# setup-docker.sh — Setup inicial completo do ambiente BCA Scrap v2
# Uso: chmod +x setup-docker.sh && ./setup-docker.sh

set -e  # Para na primeira falha

echo "🚀 Iniciando setup BCA Scrap v2..."
echo "======================================"

# Verificar pré-requisitos
command -v docker >/dev/null 2>&1 || { echo "❌ Docker não encontrado. Instale: https://docs.docker.com/get-docker/"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "❌ docker-compose não encontrado."; exit 1; }
command -v git >/dev/null 2>&1 || { echo "❌ Git não encontrado."; exit 1; }

echo "✅ Pré-requisitos verificados"

# Configurar .env
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "✅ .env criado a partir do .env.example"
    echo "⚠️  ATENÇÃO: Edite o .env com suas credenciais antes de continuar!"
    echo "   Pressione ENTER quando estiver pronto..."
    read
else
    echo "✅ .env já existe"
fi

# Subir containers
echo "📦 Subindo containers Docker..."
docker-compose up -d

# Aguardar PostgreSQL ficar pronto
echo "⏳ Aguardando PostgreSQL ficar pronto..."
until docker exec bca-postgres pg_isready -U bca_user 2>/dev/null; do
    echo "   PostgreSQL ainda iniciando..."
    sleep 2
done
echo "✅ PostgreSQL pronto"

# Aguardar Redis
echo "⏳ Aguardando Redis..."
until docker exec bca-redis redis-cli ping 2>/dev/null | grep -q PONG; do
    sleep 1
done
echo "✅ Redis pronto"

# Instalar dependências PHP
echo "📦 Instalando dependências Composer..."
docker exec bca-php composer install

# Gerar chave da aplicação
echo "🔑 Gerando chave da aplicação..."
docker exec bca-php php artisan key:generate

# Rodar migrations
echo "🗄️ Rodando migrations..."
docker exec bca-php php artisan migrate --seed

# Instalar dependências NPM e build
echo "📦 Instalando dependências NPM..."
docker exec bca-php npm install
echo "🔨 Compilando assets..."
docker exec bca-php npm run build

# Publicar assets Livewire
echo "📦 Publicando assets Livewire..."
docker exec bca-php php artisan livewire:publish --assets 2>/dev/null || true

# Configurar permissões
echo "🔐 Configurando permissões..."
docker exec bca-php chown -R www-data:www-data storage bootstrap/cache
docker exec bca-php chmod -R 775 storage bootstrap/cache

echo ""
echo "======================================"
echo "✅ Setup completo!"
echo ""
echo "Acesse o sistema:"
echo "  🌐 Aplicação:  http://localhost:8080"
echo "  📊 Horizon:    http://localhost:8080/horizon"
echo "  🗄️  pgAdmin:    http://localhost:5050"
echo ""
echo "Para iniciar o Horizon (filas):"
echo "  docker exec bca-php php artisan horizon"
```

- [ ] **Passo 6.2.2: Criar `docs/anexos/scripts/backup.sh`**

```bash
#!/bin/bash
# backup.sh — Backup automatizado do BCA Scrap v2
# Uso: ./backup.sh [--restore backup_file.tar.gz]
# Cron: 0 2 * * * /path/to/backup.sh >> /var/log/bca_backup.log 2>&1

set -e

BACKUP_DIR="/backups/bca_scrap"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/bca_backup_${DATE}.tar.gz"
RETENTION_DAYS=30

# Modo restore
if [ "$1" = "--restore" ] && [ -n "$2" ]; then
    echo "⚠️  RESTORE: Isso irá sobrescrever os dados atuais!"
    echo "   Arquivo: $2"
    read -p "   Confirmar? (sim/não): " confirm
    if [ "$confirm" = "sim" ]; then
        echo "🔄 Restaurando backup..."
        tar xzf "$2" -C /tmp/bca_restore
        docker exec -i bca-postgres psql -U bca_user -c "DROP DATABASE IF EXISTS bca_db;"
        docker exec -i bca-postgres psql -U bca_user -c "CREATE DATABASE bca_db;"
        docker exec -i bca-postgres psql -U bca_user bca_db < /tmp/bca_restore/bca_db.sql
        rsync -a /tmp/bca_restore/storage/ /var/www/html/storage/
        rm -rf /tmp/bca_restore
        echo "✅ Restore concluído!"
    fi
    exit 0
fi

# Criar diretório de backup
mkdir -p "$BACKUP_DIR"

echo "$(date): Iniciando backup BCA Scrap v2..."

# Backup do PostgreSQL
echo "  📦 Backup do banco de dados..."
TEMP_DIR=$(mktemp -d)
docker exec bca-postgres pg_dump -U bca_user --format=plain bca_db > "${TEMP_DIR}/bca_db.sql"

# Backup dos PDFs (storage)
echo "  📁 Backup dos arquivos de storage..."
rsync -a --exclude='logs/' --exclude='framework/cache/' \
    /var/www/html/storage/ "${TEMP_DIR}/storage/"

# Compactar tudo
echo "  🗜️  Compactando backup..."
tar czf "$BACKUP_FILE" -C "$TEMP_DIR" .
rm -rf "$TEMP_DIR"

# Calcular tamanho
SIZE=$(du -sh "$BACKUP_FILE" | cut -f1)
echo "  ✅ Backup criado: $BACKUP_FILE ($SIZE)"

# Limpeza: remover backups com mais de N dias
echo "  🧹 Removendo backups com mais de ${RETENTION_DAYS} dias..."
find "$BACKUP_DIR" -name "bca_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Listar backups disponíveis
echo "  📋 Backups disponíveis:"
ls -lh "$BACKUP_DIR"/bca_backup_*.tar.gz 2>/dev/null || echo "    (nenhum)"

echo "$(date): Backup concluído com sucesso!"
```

---

### Tarefa 6.3: Criar `docs/anexos/comparacao_performance.md`

**Arquivos:**
- Create: `docs/anexos/comparacao_performance.md`

- [ ] **Passo 6.3.1: Criar documento de comparação de performance com benchmarks detalhados**

```markdown
# Comparação de Performance — Sistema Atual vs v2

## Benchmarks (47 militares, 1 BCA)

[Tabela completa com medições por operação]

## Análise de ROI

[Cálculo detalhado de retorno sobre investimento]

## Metodologia de Medição

[Como medir performance antes/depois do deploy]
```

---

## Chunk 7: Atualizar Índices

### Tarefa 7.1: Atualizar `docs/00_INDICE.md`

**Arquivos:**
- Modify: `docs/00_INDICE.md`

- [ ] **Passo 7.1.1: Adicionar novos documentos na tabela "Fundamentos"**

Atualizar tabela de Fundamentos para incluir 02 e 03:

```markdown
| [02 - Arquitetura](02_ARQUITETURA.md) | Estrutura MVC, Services, Repositories, Events | 15 min |
| [03 - Banco de Dados](03_BANCO_DE_DADOS.md) | PostgreSQL, migrations, models, FTS | 20 min |
```

- [ ] **Passo 7.1.2: Adicionar documento ROLLBACK_PLAN na seção implantação**

```markdown
| [ROLLBACK_PLAN](ROLLBACK_PLAN.md) | 3 cenários de rollback com scripts | 10 min |
```

- [ ] **Passo 7.1.3: Adicionar novos exemplos e scripts na seção de Anexos**

---

### Tarefa 7.2: Atualizar `ESTRUTURA.md`

**Arquivos:**
- Modify: `ESTRUTURA.md`

- [ ] **Passo 7.2.1: Atualizar diagrama de estrutura e estatísticas**

Atualizar estatísticas:
- **Arquivos totais**: 12 → 27
- **Documentos Markdown**: 8 → 16
- **Scripts Shell**: 1 → 3
- **Exemplos de config**: 3 → 7
- **Total de linhas**: ~3.500 → ~9.000 linhas

---

## Validação Final

- [ ] **Verificar todos os links internos nos documentos criados**
  ```bash
  # Testar links markdown (instalar markdown-link-check se disponível)
  find docs/ -name "*.md" -exec grep -l "\[.*\](.*\.md)" {} \;
  ```

- [ ] **Verificar que scripts são executáveis**
  ```bash
  chmod +x docs/anexos/scripts/*.sh
  ls -la docs/anexos/scripts/
  ```

- [ ] **Verificar estrutura final de arquivos**
  ```bash
  find /home/gacpac/projects/bca_scrap_v2 -name "*.md" -o -name "*.sh" -o -name "*.example" | sort
  ```

- [ ] **Commit final da documentação**
  ```bash
  cd /home/gacpac/projects/bca_scrap_v2
  git add -A
  git commit -m "docs: aplicar recomendações da análise técnica e criar documentos faltantes (02-08)"
  ```

---

## Resumo das Melhorias Aplicadas

| Recomendação | Arquivo(s) Impactado(s) | Status |
|-------------|------------------------|--------|
| Adicionar Semana 0 (validações pré-projeto) | 09_MIGRACAO, README, checklist | Chunk 1 |
| Estender cronograma para 8 semanas | 09_MIGRACAO, README, INICIO_RAPIDO | Chunk 1 |
| Corrigir busca paralela (50→10 requests) | 09_MIGRACAO, 04_PERFORMANCE | Chunk 1 + 4 |
| Ajustar meta testes Livewire (75%→60%) | checklist, 08_TESTES | Chunk 1 + 5 |
| Criar plano de rollback detalhado | ROLLBACK_PLAN.md (novo) | Chunk 2 |
| Criar documentos técnicos faltantes (02-08) | 7 novos documentos | Chunks 3-5 |
| Criar exemplos e scripts faltantes | nginx, package.json, tailwind, horizon, scripts | Chunk 6 |
| Atualizar índices | 00_INDICE, ESTRUTURA | Chunk 7 |

**Total de arquivos impactados**: 27 (6 modificados + 15 criados + 1 plano de implementação)

---

**Plano criado em**: 14/03/2026
**Versão**: 1.0
**Status**: Pronto para execução

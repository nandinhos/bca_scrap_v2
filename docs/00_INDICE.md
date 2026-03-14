# 📑 Índice Completo - Documentação BCA Scrap v2

> Guia de navegação rápida para toda a documentação do projeto

---

## 🎯 Início Rápido

- **[README Principal](../README.md)** - Visão geral do projeto
- **[Checklist de Migração](anexos/checklist_migracao.md)** - Lista de tarefas
- **[Guia de Comandos](10_GUIA_COMANDOS.md)** - Referência rápida

---

## 📚 Documentação Principal

### 1. Fundamentos

| Documento | Descrição | Tempo Leitura |
|-----------|-----------|---------------|
| [01 - Visão Geral](01_VISAO_GERAL.md) | Introdução, justificativa e benefícios | 10 min |
| [02 - Arquitetura](02_ARQUITETURA.md) | MVC, Services, Repositories, Events | 15 min |
| [03 - Banco de Dados](03_BANCO_DE_DADOS.md) | PostgreSQL, migrations, models, FTS | 20 min |

### 2. Desenvolvimento

| Documento | Descrição | Tempo Leitura |
|-----------|-----------|---------------|
| [04 - Otimização de Performance](04_OTIMIZACAO_PERFORMANCE.md) | Cache Redis 3 camadas, busca paralela (10 req/chunk), FTS | 25 min |
| [05 - Componentes Livewire](05_COMPONENTES_LIVEWIRE.md) | Todos os componentes com código completo | 30 min |
| [06 - Sistema de Filas e Jobs](06_SISTEMA_FILAS_JOBS.md) | Horizon, Jobs completos, Scheduler, Supervisor | 20 min |

### 3. Infraestrutura

| Documento | Descrição | Tempo Leitura |
|-----------|-----------|---------------|
| [07 - Docker e Infraestrutura](07_DOCKER_INFRAESTRUTURA.md) | Dockerfiles, nginx, supervisor, compose completo | 15 min |
| [08 - Testes](08_TESTES.md) | Pest PHP, metas revisadas (60%+ Livewire), exemplos | 15 min |

### 4. Implantação

| Documento | Descrição | Tempo Leitura |
|-----------|-----------|---------------|
| [09 - Migração Passo a Passo](09_MIGRACAO_PASSO_A_PASSO.md) | Guia completo 8 semanas (inclui Semana 0) | 30 min |
| [10 - Guia de Comandos](10_GUIA_COMANDOS.md) | Referência rápida | 5 min |
| [ROLLBACK_PLAN](ROLLBACK_PLAN.md) | 3 cenários de rollback com scripts prontos | 10 min |

---

## 📎 Anexos e Recursos

### Comparações e Análises

- **[Comparação de Performance](anexos/comparacao_performance.md)** - Benchmarks detalhados + ROI
- **[Análise de ROI](anexos/comparacao_performance.md#-análise-de-roi)** - Payback em 2 anos

### Checklists

- **[Checklist de Migração](anexos/checklist_migracao.md)** - Todas as fases (inclui Fase 0 — pré-projeto)
- **[Plano de Rollback](ROLLBACK_PLAN.md)** - 3 cenários com scripts completos

### Scripts

- **[migrate-data.sh](anexos/scripts/migrate-data.sh)** - Migração MySQL → PostgreSQL
- **[setup-docker.sh](anexos/scripts/setup-docker.sh)** - Setup inicial Docker (automatizado)
- **[backup.sh](anexos/scripts/backup.sh)** - Backup + restore automatizado

### Exemplos de Configuração

- **[.env.example](exemplos/.env.example)** - Variáveis de ambiente completas
- **[docker-compose.yml](exemplos/docker-compose.yml.example)** - Docker Compose com healthchecks
- **[nginx.conf](exemplos/nginx.conf.example)** - Nginx para Laravel (gzip, cache, segurança)
- **[composer.json](exemplos/composer.json.example)** - Dependências PHP
- **[package.json](exemplos/package.json.example)** - Dependências NPM (Tailwind 4, Alpine, Flatpickr)
- **[tailwind.config.js](exemplos/tailwind.config.js.example)** - Tailwind com tema FAB
- **[horizon.php](exemplos/horizon.php.example)** - Horizon (workers, alertas, retenção)

---

## 🗺️ Mapa do Projeto

### Por Tecnologia

#### Backend (Laravel)
- [Models e Eloquent](03_BANCO_DE_DADOS.md#models)
- [Repositories](02_ARQUITETURA.md#repositories)
- [Services](02_ARQUITETURA.md#services)
- [Jobs](06_SISTEMA_FILAS_JOBS.md#jobs)
- [Commands](06_SISTEMA_FILAS_JOBS.md#commands)
- [Events](02_ARQUITETURA.md#events)

#### Frontend (Livewire)
- [Componentes de Busca](05_COMPONENTES_LIVEWIRE.md#busca)
- [Componentes de Efetivo](05_COMPONENTES_LIVEWIRE.md#efetivo)
- [Componentes de Palavras-chave](05_COMPONENTES_LIVEWIRE.md#palavras-chave)
- [Layouts e UI](05_COMPONENTES_LIVEWIRE.md#layouts)

#### Database (PostgreSQL)
- [Migrations](03_BANCO_DE_DADOS.md#migrations)
- [Seeders](03_BANCO_DE_DADOS.md#seeders)
- [Full-Text Search](04_OTIMIZACAO_PERFORMANCE.md#postgresql-fts)
- [Índices](04_OTIMIZACAO_PERFORMANCE.md#indices)

#### Performance
- [Cache Redis](04_OTIMIZACAO_PERFORMANCE.md#cache)
- [Busca Paralela](04_OTIMIZACAO_PERFORMANCE.md#paralelo)
- [Otimização de Queries](04_OTIMIZACAO_PERFORMANCE.md#queries)

### Por Funcionalidade

#### Busca de BCA
1. [Download de PDF](04_OTIMIZACAO_PERFORMANCE.md#download)
2. [Extração de Texto](04_OTIMIZACAO_PERFORMANCE.md#extracao)
3. [Análise de Efetivo](04_OTIMIZACAO_PERFORMANCE.md#analise)
4. [Geração de Snippets](05_COMPONENTES_LIVEWIRE.md#snippets)

#### Notificações
1. [Sistema de Email](06_SISTEMA_FILAS_JOBS.md#email)
2. [Queue de Envio](06_SISTEMA_FILAS_JOBS.md#queue)
3. [Templates Blade](05_COMPONENTES_LIVEWIRE.md#mail)

#### Administração
1. [CRUD Efetivo](05_COMPONENTES_LIVEWIRE.md#crud-efetivo)
2. [CRUD Palavras-chave](05_COMPONENTES_LIVEWIRE.md#crud-palavras)
3. [Dashboard Horizon](06_SISTEMA_FILAS_JOBS.md#horizon)

---

## 🔍 Busca por Tópico

### A

- **Alpine.js** → [Visão Geral](01_VISAO_GERAL.md#stack), [Componentes](05_COMPONENTES_LIVEWIRE.md)
- **Análise de Performance** → [Comparação](anexos/comparacao_performance.md)
- **API REST** → [Arquitetura](02_ARQUITETURA.md#api)
- **Artisan Commands** → [Sistema de Filas](06_SISTEMA_FILAS_JOBS.md#commands)

### B

- **Backup** → [Scripts](anexos/scripts/backup.sh), [Comandos](10_GUIA_COMANDOS.md#backup)
- **BCA Download** → [Performance](04_OTIMIZACAO_PERFORMANCE.md#download)
- **Benchmarks** → [Comparação](anexos/comparacao_performance.md)

### C

- **Cache Redis** → [Otimização](04_OTIMIZACAO_PERFORMANCE.md#cache)
- **CENDOC API** → [Performance](04_OTIMIZACAO_PERFORMANCE.md#cendoc)
- **Composer** → [Exemplo](exemplos/composer.json.example)
- **CRUD** → [Livewire](05_COMPONENTES_LIVEWIRE.md#crud)

### D

- **Database Migration** → [Banco de Dados](03_BANCO_DE_DADOS.md#migracao)
- **Docker** → [Infraestrutura](07_DOCKER_INFRAESTRUTURA.md)
- **Docker Compose** → [Exemplo](exemplos/docker-compose.yml.example)

### E

- **Eloquent ORM** → [Models](03_BANCO_DE_DADOS.md#models)
- **Email** → [Jobs](06_SISTEMA_FILAS_JOBS.md#email), [Templates](05_COMPONENTES_LIVEWIRE.md#mail)
- **Events** → [Arquitetura](02_ARQUITETURA.md#events)

### F

- **Full-Text Search** → [PostgreSQL FTS](04_OTIMIZACAO_PERFORMANCE.md#fts)

### H

- **Horizon** → [Sistema de Filas](06_SISTEMA_FILAS_JOBS.md#horizon)

### J

- **Jobs** → [Sistema de Filas](06_SISTEMA_FILAS_JOBS.md#jobs)

### L

- **Laravel 12** → [Visão Geral](01_VISAO_GERAL.md), [Arquitetura](02_ARQUITETURA.md)
- **Livewire 4** → [Componentes](05_COMPONENTES_LIVEWIRE.md)

### M

- **Migrations** → [Banco de Dados](03_BANCO_DE_DADOS.md#migrations)
- **Models** → [Banco de Dados](03_BANCO_DE_DADOS.md#models)

### N

- **Nginx** → [Docker](07_DOCKER_INFRAESTRUTURA.md#nginx), [Exemplo](exemplos/nginx.conf.example)

### P

- **Performance** → [Otimização](04_OTIMIZACAO_PERFORMANCE.md)
- **Pest PHP** → [Testes](08_TESTES.md)
- **pgAdmin** → [Docker](07_DOCKER_INFRAESTRUTURA.md#pgadmin)
- **PHPMailer (legacy)** → Substituído por [Laravel Mail](06_SISTEMA_FILAS_JOBS.md#email)
- **PostgreSQL** → [Banco de Dados](03_BANCO_DE_DADOS.md)

### Q

- **Queue System** → [Sistema de Filas](06_SISTEMA_FILAS_JOBS.md)

### R

- **Redis** → [Cache](04_OTIMIZACAO_PERFORMANCE.md#redis), [Queue](06_SISTEMA_FILAS_JOBS.md#redis)
- **Repositories** → [Arquitetura](02_ARQUITETURA.md#repositories)

### S

- **Scheduler** → [Sistema de Filas](06_SISTEMA_FILAS_JOBS.md#scheduler)
- **Scripts Shell** → [Anexos](anexos/scripts/)
- **Seeders** → [Banco de Dados](03_BANCO_DE_DADOS.md#seeders)
- **Services** → [Arquitetura](02_ARQUITETURA.md#services)

### T

- **Tailwind CSS** → [Visão Geral](01_VISAO_GERAL.md#stack), [Exemplo](exemplos/tailwind.config.js.example)
- **TALL Stack** → [Visão Geral](01_VISAO_GERAL.md)
- **Testes** → [Testes](08_TESTES.md)

---

## 📖 Guias de Leitura Sugeridos

### Para Desenvolvedores Backend

1. [01 - Visão Geral](01_VISAO_GERAL.md) ⏱️ 10min
2. [02 - Arquitetura](02_ARQUITETURA.md) ⏱️ 15min
3. [03 - Banco de Dados](03_BANCO_DE_DADOS.md) ⏱️ 20min
4. [04 - Otimização de Performance](04_OTIMIZACAO_PERFORMANCE.md) ⏱️ 25min
5. [06 - Sistema de Filas](06_SISTEMA_FILAS_JOBS.md) ⏱️ 20min
6. [08 - Testes](08_TESTES.md) ⏱️ 15min

**Total**: ~1h45min

### Para Desenvolvedores Frontend

1. [01 - Visão Geral](01_VISAO_GERAL.md) ⏱️ 10min
2. [05 - Componentes Livewire](05_COMPONENTES_LIVEWIRE.md) ⏱️ 30min
3. [Exemplos](exemplos/) ⏱️ 15min

**Total**: ~55min

### Para DevOps

1. [07 - Docker e Infraestrutura](07_DOCKER_INFRAESTRUTURA.md) ⏱️ 15min
2. [09 - Migração Passo a Passo](09_MIGRACAO_PASSO_A_PASSO.md) ⏱️ 30min
3. [Scripts](anexos/scripts/) ⏱️ 10min
4. [10 - Guia de Comandos](10_GUIA_COMANDOS.md) ⏱️ 5min

**Total**: ~1h

### Para Gestores/Product Owners

1. [01 - Visão Geral](01_VISAO_GERAL.md) ⏱️ 10min
2. [Comparação de Performance](anexos/comparacao_performance.md) ⏱️ 10min
3. [09 - Migração Passo a Passo](09_MIGRACAO_PASSO_A_PASSO.md) (seções 1-3) ⏱️ 15min

**Total**: ~35min

---

## 🎯 Próximos Passos

### Se você é novo no projeto:
1. ✅ Leia o [README](../README.md)
2. ✅ Revise a [Visão Geral](01_VISAO_GERAL.md)
3. ✅ Configure ambiente com [Docker](07_DOCKER_INFRAESTRUTURA.md)

### Se vai desenvolver:
1. ✅ Estude a [Arquitetura](02_ARQUITETURA.md)
2. ✅ Configure IDE seguindo [Guia de Comandos](10_GUIA_COMANDOS.md)
3. ✅ Rode testes conforme [Testes](08_TESTES.md)

### Se vai fazer deploy:
1. ✅ Siga o [Guia de Migração](09_MIGRACAO_PASSO_A_PASSO.md)
2. ✅ Execute [Scripts de Migração](anexos/scripts/)
3. ✅ Valide com [Checklist](anexos/checklist_migracao.md)

---

## 📞 Ajuda e Suporte

- **Dúvidas técnicas**: Consulte a documentação específica
- **Problemas**: Veja [Troubleshooting](10_GUIA_COMANDOS.md#troubleshooting)
- **Contribuir**: Leia [CONTRIBUTING](../README.md#contribuindo)

---

**Última atualização**: 13/03/2026
**Versão da documentação**: 2.0.0

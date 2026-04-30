# BCA Scrap v2 - Laravel 12 TALL Stack

## Sistema de Busca e Análise de Boletins de Comando da Aeronáutica

> Migração completa do sistema PHP vanilla para Laravel 12 com TALL Stack (Tailwind, Alpine.js, Livewire 4, Laravel)

---

## Sobre o Projeto

O **BCA Scrap v2** é uma reescrita completa do sistema de busca automatizada de Boletins de Comando da Aeronáutica (BCA) para o GAC-PAC (Grupo de Acompanhamento e Controle do Programa Aeronave de Combate).

### Principais Melhorias

| Aspecto | Sistema Antigo | Sistema Novo | Ganho |
|---------|---------------|--------------|-------|
| **Matching** | Busca frouxa (FTS) | **Estrita (SARAM/Nome)** | Alta Precisão |
| **Performance** | 5-15s | 1-3s | **80% mais rápido** |
| **UX** | PDF Externo | **Visualização In-app** | Fluxo Contínuo |
| **E-mails** | Manual/CRON | **Automático Real-time** | Notificação Instantânea |
| **Cache** | Arquivos .txt | Redis multi-layer | Alta Performance |
| **Processamento** | Síncrono (Trava UI) | **Assíncrono (Filas)** | Interface Fluida |
| **Testes** | 0% | 80%+ | Qualidade garantida |

---

## Arquitetura TALL Stack

```
┌─────────────────────────────────────────────────────────┐
│  T - Tailwind CSS 4.x (JIT mode)                       │
│  A - Alpine.js 3.x (integrado via Livewire)            │
│  L - Laravel 12 (PHP 8.3+)                             │
│  L - Livewire 4 (componentes reativos full-page)       │
└─────────────────────────────────────────────────────────┘
```

**Versão**: 2.2.0
**Última atualização**: 30/04/2026
**Status**: Funcional & Homologado

### Stack Completo

- **Backend**: Laravel 12 + PHP 8.3
- **Frontend**: Livewire 4 + Tailwind CSS 4 + Alpine.js 3
- **Database**: PostgreSQL 16 + pgAdmin 4
- **Cache**: Redis 7
- **Queue**: Laravel Queue (`queue:work`) via Docker
- **Email**: Laravel Mail + SMTP FAB
- **PDF**: pdftotext (Poppler Utils)
- **Tests**: Pest PHP
- **Deploy**: Docker + Docker Compose

---

## Documentação

A documentação completa está organizada em módulos:

### Documentos Principais

1. **[00 - Índice Completo](docs/00_INDICE.md)** - Navegação rápida
2. **[01 - Visão Geral](docs/01_VISAO_GERAL.md)** - Introdução e justificativa
3. **[02 - Arquitetura](docs/02_ARQUITETURA.md)** - Estrutura do sistema Laravel
4. **[03 - Banco de Dados](docs/03_BANCO_DE_DADOS.md)** - PostgreSQL, migrations e migração
5. **[04 - Otimização de Performance](docs/04_OTIMIZACAO_PERFORMANCE.md)** - Cache, paralelo, FTS
6. **[05 - Componentes Livewire](docs/05_COMPONENTES_LIVEWIRE.md)** - Todos os componentes
7. **[06 - Sistema de Filas](docs/06_SISTEMA_FILAS_JOBS.md)** - Jobs, queue worker, schedulers
8. **[07 - Docker e Infraestrutura](docs/07_DOCKER_INFRAESTRUTURA.md)** - Containers e deploy
9. **[08 - Testes](docs/08_TESTES.md)** - Estratégia com Pest
10. **[09 - Migração Passo a Passo](docs/09_MIGRACAO_PASSO_A_PASSO.md)** - Guia completo 7 semanas
11. **[10 - Guia de Comandos](docs/10_GUIA_COMANDOS.md)** - Referência rápida

### Aprendizados Operacionais

- **[Lições Aprendidas](docs/LICOES_APRENDIDAS.md)** - Bugs e incidentes documentados
- **[AGENTS.md](AGENTS.md)** - Regras críticas para agentes de IA e operadores

### Anexos

- **[Comparação de Performance](docs/anexos/comparacao_performance.md)**
- **[Checklist de Migração](docs/anexos/checklist_migracao.md)**
- **[Scripts de Migração](docs/anexos/scripts/)**

### Exemplos

- **[.env.example](docs/exemplos/.env.example)** - Variáveis de ambiente
- **[docker-compose.yml](docs/exemplos/docker-compose.yml.example)** - Configuração Docker
- **[composer.json](docs/exemplos/composer.json.example)** - Dependências
- **[tailwind.config.js](docs/exemplos/tailwind.config.js.example)** - Configuração Tailwind

---

## Quick Start

### Pré-requisitos

- Docker & Docker Compose (v2+)
- Git

### Instalação

```bash
# 1. Clone o repositório
git clone https://github.com/gacpac/bca-scrap-v2.git
cd bca-scrap-v2

# 2. Configure ambiente
cp .env.example .env
# Edite .env com suas credenciais (MAIL_*, DB_*, APP_KEY)

# 3. Suba os containers
docker compose up -d

# 4. Instale dependências PHP
docker compose exec php composer install

# 5. Gere chave da aplicação
docker compose exec php php artisan key:generate

# 6. Rode as migrations
docker compose exec php php artisan migrate --seed

# 7. Build dos assets (opcional em desenvolvimento)
docker compose exec php npm install && npm run build
```

### Acessar Sistema

- **Aplicação**: http://localhost:18080
- **pgAdmin**: http://localhost:15050

> **Nota sobre a fila (queue):** O container `queue` sobe automaticamente com `docker compose up -d`. Após editar qualquer classe em `app/Jobs/`, execute `docker compose restart queue` para recarregar o código. Veja [AGENTS.md](AGENTS.md) para detalhes.

---

## Estrutura do Projeto

```
bca_scrap_v2/
├── app/
│   ├── Console/Commands/       # Comandos Artisan (busca automática, etc)
│   ├── Events/                 # Eventos do sistema
│   ├── Http/
│   │   ├── Controllers/        # Controllers web e auth
│   │   └── Middleware/         # Middleware personalizado (EnsureRole, etc)
│   ├── Jobs/                   # Jobs assíncronos (download, email, análise)
│   ├── Livewire/               # Componentes Livewire reativos
│   ├── Mail/                   # Mailables (notificação BCA, compilado SAD)
│   ├── Models/                 # Eloquent Models
│   ├── Repositories/           # Repository pattern (contratos + implementações)
│   └── Services/               # Business logic (Download, Analysis, etc)
├── database/
│   ├── migrations/             # Migrations PostgreSQL
│   ├── seeders/                # Seeders de dados
│   └── factories/              # Factories para testes
├── resources/
│   ├── css/app.css            # Tailwind CSS
│   ├── js/app.js              # Alpine.js + scripts
│   └── views/
│       ├── components/         # Blade components
│       ├── livewire/           # Views Livewire
│       └── mail/               # Templates email
├── routes/
│   ├── web.php                # Rotas web (dashboard, efetivo, palavras-chave)
│   └── console.php            # Comandos console
├── tests/
│   ├── Feature/               # Testes de integração
│   └── Unit/                  # Testes unitários
├── docker/                    # Configuração Docker (php, nginx, postgres)
├── docs/                      # Documentação completa
├── AGENTS.md                  # Regras operacionais para agentes de IA
└── docker-compose.yml         # Orquestração dos 6 containers
```

---

## Comandos Principais

### Desenvolvimento

```bash
# Rodar testes
docker compose exec php php artisan test

# Com cobertura
docker compose exec php php artisan test --coverage

# Análise estática
docker compose exec php ./vendor/bin/phpstan analyse

# Formatar código
docker compose exec php ./vendor/bin/pint

# Build assets (watch mode)
npm run dev

# Build produção
npm run build
```

### Manutenção do Sistema

```bash
# Busca automática de BCA
docker compose exec php php artisan bca:buscar-automatica

# Ver jobs na fila
docker compose exec php php artisan queue:failed

# Reprocessar job com falha
docker compose exec php php artisan queue:retry all

# Limpar cache
docker compose exec php php artisan cache:clear
```

### Queue (Fila de Jobs)

> **IMPORTANTE:** Após modificar qualquer `app/Jobs/*.php`, reinicie o container `queue`.

```bash
# Reiniciar queue worker (obrigatório após editar Jobs)
docker compose restart queue

# Verificar logs da fila
docker compose logs queue -f

# Status de todos os containers
docker compose ps

# Ver jobs pendentes no banco
docker compose exec php php artisan tinker --execute="echo DB::table('jobs')->count();"
```

---

## Testes

O projeto utiliza **Pest PHP** para testes modernos e legíveis.

```bash
# Rodar todos os testes
docker compose exec php php artisan test

# Testes com cobertura
docker compose exec php php artisan test --coverage --min=80

# Testes específicos
docker compose exec php php artisan test --filter BuscaBcaTest

# Testes em paralelo
docker compose exec php php artisan test --parallel
```

### Cobertura Esperada

- **Services**: 90%+
- **Jobs**: 85%+
- **Commands**: 80%+
- **Livewire Components**: 75%+

---

## Performance

### Benchmarks (47 militares, 1 BCA)

| Operação | Tempo Atual | Tempo Novo | Melhoria |
|----------|-------------|------------|----------|
| Busca BCA | 5-15s | 1-3s | **80%** |
| Extração PDF | 2s | 0.5s | **75%** |
| Análise Efetivo | 3-5s | <1s | **70%** |
| Envio Email | Bloqueia | Assíncrono | não bloqueia |

### Otimizações Implementadas

1. **Busca Paralela**: Download simultâneo de múltiplas fontes
2. **Cache Redis**: 3 camadas (query, texto PDF, resultados)
3. **PostgreSQL FTS**: Full-text search nativo com índices GIN
4. **Queue System**: Processamento assíncrono não bloqueia UI
5. **Chunking no Analysis**: Processamento em batches para grandes efetivos

---

## Segurança

- **Eloquent ORM**: Previne SQL injection por padrão
- **CSRF Protection**: Tokens em todos os forms
- **XSS Prevention**: Blade escaping automático
- **Rate Limiting**: APIs protegidas contra abuse
- **Role Middleware**: Acesso admin separado de usuário comum
- **Credenciais**: Gerenciadas via `.env` (nunca no repositório)

---

## Contribuindo

### Workflow de Desenvolvimento

1. Crie branch para feature (`git checkout -b feature/nova-funcionalidade`)
2. Escreva testes primeiro (TDD)
3. Implemente a funcionalidade
4. Rode testes e análise estática
5. Commit (`git commit -m 'feat: adiciona nova funcionalidade'`)
6. Push e abra Pull Request

### Padrões de Commit

Seguimos [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` Nova funcionalidade
- `fix:` Correção de bug
- `docs:` Documentação
- `style:` Formatação
- `refactor:` Refatoração
- `test:` Adicionar testes
- `chore:` Manutenção

---

## Roadmap

### Semana 0 - Pré-Projeto (Concluído)
- [x] Validações de infraestrutura com TI (Docker, PostgreSQL, Redis aprovados)
- [x] Backup completo do sistema atual com baseline de registros documentado
- [x] Ambiente de staging configurado e acessível

### Fase 1 - MVP (Concluído)
- [x] Setup inicial Laravel 12 + Docker
- [x] Migrations PostgreSQL com índices FTS
- [x] Models e Repositories
- [x] Services (Download, Processing, Analysis)
- [x] Componentes Livewire (busca, efetivo, palavras-chave, usuários)

### Fase 2 - Otimização e Qualidade (Concluído)
- [x] Sistema de Queue com `queue:work` containerizado
- [x] Otimizações de performance (Cache Redis 3 camadas, FTS, chunking)
- [x] Testes automatizados (80%+ Services/Jobs)
- [x] Sistema de email (notificação individual + compilado SAD)
- [x] Health check e métricas de execução

### Fase 3 - Deploy (Concluído)
- [x] Deploy em produção via Docker Compose
- [x] Monitoramento via `/health` e `/metrics`
- [x] Documentação operacional (AGENTS.md, lições aprendidas)

### Fase 4 - Melhorias Futuras (Pós-lançamento)
- [ ] Notificações Telegram
- [ ] Relatórios em PDF
- [ ] Dashboard de estatísticas
- [ ] API pública documentada

---

## Suporte

### Documentação
- **Docs completas**: [/docs](/docs)
- **Regras operacionais**: [AGENTS.md](AGENTS.md)
- **Laravel Docs**: https://laravel.com/docs/12.x
- **Livewire Docs**: https://livewire.laravel.com/docs

### Contato
- **Email**: gacpac@fab.mil.br
- **Desenvolvedor**: 1S BMB FERNANDO
- **Sistema**: GAC-PAC

---

## Licenca

Uso interno — Força Aérea Brasileira © 2026

---

**Versão**: 2.2.0
**Última atualização**: 30/04/2026
**Status**: Funcional & Homologado

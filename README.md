# BCA Scrap v2 - Laravel 12 TALL Stack

## 🚀 Sistema de Busca e Análise de Boletins de Comando da Aeronáutica

> Migração completa do sistema PHP vanilla para Laravel 12 com TALL Stack (Tailwind, Alpine.js, Livewire 4, Laravel)

---

## 📋 Sobre o Projeto

O **BCA Scrap v2** é uma reescrita completa do sistema de busca automatizada de Boletins de Comando da Aeronáutica (BCA) para o GAC-PAC (Grupo de Acompanhamento e Controle do Programa Aeronave de Combate).

### Principais Melhorias

| Aspecto | Sistema Antigo | Sistema Novo | Ganho |
|---------|---------------|--------------|-------|
| **Performance** | 5-15s | 1-3s | ⚡ **80% mais rápido** |
| **Framework** | PHP vanilla | Laravel 12 | 🎯 Modular e testável |
| **Frontend** | Alpine.js CDN | Livewire 4 | 🎨 Componentes reativos |
| **Database** | MariaDB | PostgreSQL 16 | 🔍 Full-text search nativo |
| **Cache** | Arquivos .txt | Redis multi-layer | 💾 Performance otimizada |
| **Jobs** | CRON scripts | Laravel Queue + Horizon | 🔄 Processamento assíncrono |
| **Testes** | 0% | 80%+ | ✅ Qualidade garantida |

---

## 🏗️ Arquitetura TALL Stack

```
┌─────────────────────────────────────────────────────────┐
│  T - Tailwind CSS 4.x (JIT mode)                       │
│  A - Alpine.js 3.x (integrado via Livewire)            │
│  L - Laravel 12 (PHP 8.3+)                             │
│  L - Livewire 4 (componentes reativos full-page)       │
└─────────────────────────────────────────────────────────┘
```

### Stack Completo

- **Backend**: Laravel 12 + PHP 8.3
- **Frontend**: Livewire 4 + Tailwind CSS 4 + Alpine.js 3
- **Database**: PostgreSQL 16 + pgAdmin 4
- **Cache**: Redis 7
- **Queue**: Laravel Horizon
- **Email**: Laravel Mail + Queue
- **PDF**: pdftotext (Poppler Utils)
- **Tests**: Pest PHP
- **Deploy**: Docker + Docker Compose

---

## 📚 Documentação

A documentação completa está organizada em módulos:

### 📖 Documentos Principais

1. **[00 - Índice Completo](docs/00_INDICE.md)** - Navegação rápida
2. **[01 - Visão Geral](docs/01_VISAO_GERAL.md)** - Introdução e justificativa
3. **[02 - Arquitetura](docs/02_ARQUITETURA.md)** - Estrutura do sistema Laravel
4. **[03 - Banco de Dados](docs/03_BANCO_DE_DADOS.md)** - PostgreSQL, migrations e migração
5. **[04 - Otimização de Performance](docs/04_OTIMIZACAO_PERFORMANCE.md)** - Cache, paralelo, FTS
6. **[05 - Componentes Livewire](docs/05_COMPONENTES_LIVEWIRE.md)** - Todos os componentes
7. **[06 - Sistema de Filas](docs/06_SISTEMA_FILAS_JOBS.md)** - Jobs, Horizon, schedulers
8. **[07 - Docker e Infraestrutura](docs/07_DOCKER_INFRAESTRUTURA.md)** - Containers e deploy
9. **[08 - Testes](docs/08_TESTES.md)** - Estratégia com Pest
10. **[09 - Migração Passo a Passo](docs/09_MIGRACAO_PASSO_A_PASSO.md)** - Guia completo 7 semanas
11. **[10 - Guia de Comandos](docs/10_GUIA_COMANDOS.md)** - Referência rápida

### 📎 Anexos

- **[Comparação de Performance](docs/anexos/comparacao_performance.md)**
- **[Checklist de Migração](docs/anexos/checklist_migracao.md)**
- **[Scripts de Migração](docs/anexos/scripts/)**

### 💾 Exemplos

- **[.env.example](docs/exemplos/.env.example)** - Variáveis de ambiente
- **[docker-compose.yml](docs/exemplos/docker-compose.yml.example)** - Configuração Docker
- **[composer.json](docs/exemplos/composer.json.example)** - Dependências
- **[tailwind.config.js](docs/exemplos/tailwind.config.js.example)** - Configuração Tailwind

---

## 🚀 Quick Start

### Pré-requisitos

- Docker & Docker Compose
- Git
- Node.js 20+ (para build assets)

### Instalação

```bash
# 1. Clone o repositório (quando estiver pronto)
git clone https://github.com/gacpac/bca-scrap-v2.git
cd bca-scrap-v2

# 2. Configure ambiente
cp .env.example .env
# Edite .env com suas credenciais

# 3. Suba containers
docker-compose up -d

# 4. Instale dependências
docker exec bca-php composer install
docker exec bca-php npm install

# 5. Gere chave da aplicação
docker exec bca-php php artisan key:generate

# 6. Rode migrations
docker exec bca-php php artisan migrate --seed

# 7. Build assets
docker exec bca-php npm run build

# 8. Inicie Horizon (filas)
docker exec bca-php php artisan horizon
```

### Acessar Sistema

- **Aplicação**: http://localhost:8080
- **Horizon (Queue Dashboard)**: http://localhost:8080/horizon
- **pgAdmin**: http://localhost:5050

---

## 📊 Estrutura do Projeto

```
bca-scrap-laravel/
├── app/
│   ├── Console/Commands/       # Comandos Artisan (busca automática, etc)
│   ├── Events/                 # Eventos do sistema
│   ├── Http/
│   │   ├── Controllers/Api/    # APIs REST
│   │   └── Livewire/          # Componentes Livewire
│   ├── Jobs/                   # Jobs assíncronos (download, email, etc)
│   ├── Mail/                   # Templates de email
│   ├── Models/                 # Eloquent Models
│   ├── Notifications/          # Notificações
│   ├── Repositories/           # Repositories pattern
│   └── Services/               # Business logic
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
│   ├── web.php                # Rotas web
│   ├── api.php                # Rotas API
│   └── console.php            # Comandos console
├── tests/
│   ├── Feature/               # Testes de integração
│   └── Unit/                  # Testes unitários
├── docker/                    # Configuração Docker
└── docs/                      # Esta documentação
```

---

## 🔧 Comandos Principais

### Desenvolvimento

```bash
# Rodar testes
php artisan test
# ou
./vendor/bin/pest

# Análise estática
./vendor/bin/phpstan analyse

# Formatar código
./vendor/bin/pint

# Build assets (watch mode)
npm run dev

# Build production
npm run build
```

### Manutenção

```bash
# Busca automática de BCA
php artisan bca:buscar-automatica

# Limpar BCAs antigos
php artisan bca:limpar-antigos

# Reenviar emails com falha
php artisan bca:reenviar-emails-falhos

# Backup do banco
php artisan backup:run --only-db
```

### Queue/Horizon

```bash
# Iniciar Horizon
php artisan horizon

# Pausar queue
php artisan horizon:pause

# Continuar queue
php artisan horizon:continue

# Ver estatísticas
php artisan horizon:list
```

---

## 🧪 Testes

O projeto utiliza **Pest PHP** para testes modernos e legíveis.

```bash
# Rodar todos os testes
php artisan test

# Testes com cobertura
php artisan test --coverage

# Testes específicos
php artisan test --filter BuscaBcaTest

# Testes em paralelo
php artisan test --parallel
```

### Cobertura Esperada

- ✅ **Services**: 90%+
- ✅ **Jobs**: 85%+
- ✅ **Commands**: 80%+
- ✅ **Livewire Components**: 75%+

---

## 📈 Performance

### Benchmarks (47 militares, 1 BCA)

| Operação | Tempo Atual | Tempo Novo | Melhoria |
|----------|-------------|------------|----------|
| Busca BCA | 5-15s | 1-3s | **80%** |
| Extração PDF | 2s | 0.5s | **75%** |
| Análise Efetivo | 3-5s | <1s | **70%** |
| Envio Email | Bloqueia | Assíncrono | **∞** |

### Otimizações Implementadas

1. **Busca Paralela**: Download simultâneo de múltiplas fontes
2. **Cache Redis**: 3 camadas (query, texto PDF, resultados)
3. **PostgreSQL FTS**: Full-text search nativo com índices GIN
4. **Queue System**: Processamento assíncrono não bloqueia UI
5. **Lazy Loading**: Componentes Livewire carregam sob demanda

---

## 🔐 Segurança

- ✅ **Eloquent ORM**: Previne SQL injection por padrão
- ✅ **CSRF Protection**: Tokens em todos os forms
- ✅ **XSS Prevention**: Blade escaping automático
- ✅ **Rate Limiting**: APIs protegidas contra abuse
- ✅ **Sanctum**: Autenticação API segura
- ✅ **Encrypted Secrets**: Credenciais em Docker secrets

---

## 🤝 Contribuindo

### Workflow de Desenvolvimento

1. Fork o repositório
2. Crie branch para feature (`git checkout -b feature/nova-funcionalidade`)
3. Escreva testes primeiro (TDD)
4. Implemente a funcionalidade
5. Rode testes e análise estática
6. Commit (`git commit -m 'feat: adiciona nova funcionalidade'`)
7. Push (`git push origin feature/nova-funcionalidade`)
8. Abra Pull Request

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

## 📝 Roadmap

### Semana 0 - Pré-Projeto ⚠️ (Antes de Começar)
- [ ] Validações de infraestrutura com TI (Docker, PostgreSQL, Redis aprovados)
- [ ] Teste de migração MySQL→PostgreSQL em ambiente isolado
- [ ] Backup completo do sistema atual com baseline de registros documentado
- [ ] Ambiente de staging configurado e acessível
- [ ] Acesso às APIs externas (CENDOC/ICEA) validado a partir do servidor de produção

### Fase 1 - MVP (Semanas 1-5)
- [ ] Setup inicial Laravel 12 + Docker
- [ ] Migrations PostgreSQL com índices FTS
- [ ] Models e Repositories
- [ ] Services (Download com busca paralela limitada a 10 req/chunk, Processing)
- [ ] Componentes Livewire básicos
- [ ] Teste de migração de dados em ambiente de dev (Semana 5.5)

### Fase 2 - Otimização e Qualidade (Semanas 6-7)
- [ ] Sistema de Queue completo com Horizon
- [ ] Otimizações de performance (Cache Redis 3 camadas, FTS)
- [ ] Testes automatizados (80%+ Services/Jobs, 60%+ Livewire)
- [ ] Testes de integração e UAT com usuários reais

### Fase 3 - Deploy (Semana 8)
- [ ] Migração de dados produção (com rollback testado e baseline validado)
- [ ] Deploy produção
- [ ] Monitoramento e alertas configurados
- [ ] Treinamento da equipe

### Fase 4 - Melhorias Futuras 📅 (Pós-lançamento)
- [ ] Notificações Telegram
- [ ] Relatórios em PDF
- [ ] Dashboard de estatísticas
- [ ] API pública documentada
- [ ] PWA (Progressive Web App)

---

## 📞 Suporte

### Documentação
- **Docs completas**: [/docs](/docs)
- **Laravel Docs**: https://laravel.com/docs/12.x
- **Livewire Docs**: https://livewire.laravel.com/docs/4.x

### Contato
- **Email**: gacpac@fab.mil.br
- **Desenvolvedor**: 1S BMB FERNANDO
- **Sistema**: GAC-PAC

---

## 📄 Licença

Uso interno - Força Aérea Brasileira © 2026

---

## 🙏 Agradecimentos

- Equipe GAC-PAC
- Comunidade Laravel Brasil
- CENDOC/ICEA (fontes de dados)

---

**Versão**: 2.0.0
**Última atualização**: 13/03/2026
**Status**: 📝 Em Documentação

# 10 - Guia de Comandos (Referência Rápida)

## Setup Inicial

```bash
# Clonar e configurar
git clone https://github.com/gacpac/bca-scrap-v2
cd bca_scrap_v2
cp .env.example .env
# Edite .env com credenciais reais (MAIL_*, DB_*, APP_KEY)

# Subir containers
docker compose up -d

# Instalar dependências e configurar
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed

# Build assets
npm install && npm run build
```

---

## Docker

```bash
# Subir todos containers
docker compose up -d

# Ver status dos containers
docker compose ps

# Ver logs
docker compose logs -f
docker compose logs -f php       # apenas PHP
docker compose logs -f nginx     # apenas Nginx
docker compose logs -f queue     # apenas Queue worker

# Parar
docker compose stop

# Parar e remover
docker compose down

# Rebuild (após mudanças no Dockerfile)
docker compose up -d --build

# Acessar container PHP
docker compose exec php bash

# Acessar PostgreSQL
docker compose exec postgres psql -U bca_user bca_db
```

---

## Banco de Dados

```bash
# Migrations
docker compose exec php php artisan migrate                    # rodar migrations
docker compose exec php php artisan migrate:fresh --seed       # limpar e recriar
docker compose exec php php artisan migrate:rollback           # desfazer última
docker compose exec php php artisan migrate:status             # ver status

# Seeders
docker compose exec php php artisan db:seed                    # todos seeders
docker compose exec php php artisan db:seed --class=EfetivoSeeder  # específico

# PostgreSQL CLI
docker compose exec postgres psql -U bca_user -d bca_db

# Backup
docker compose exec postgres pg_dump -U bca_user bca_db > backup_$(date +%Y%m%d).sql

# Restore
docker compose exec -T postgres psql -U bca_user bca_db < backup.sql
```

---

## Frontend

```bash
# Desenvolvimento (watch mode)
npm run dev

# Build produção
npm run build

# Limpar cache Tailwind
rm -rf node_modules/.cache
npm run build

# Publish Livewire assets
docker compose exec php php artisan livewire:publish --assets
```

---

## Laravel Artisan

### Manutenção do Sistema

```bash
# Busca automática de BCA
docker compose exec php php artisan bca:buscar-automatica

# Limpar BCAs antigos (manter últimos 30)
docker compose exec php php artisan bca:limpar-antigos
```

### Cache

```bash
# Limpar todos caches
docker compose exec php php artisan cache:clear
docker compose exec php php artisan config:clear
docker compose exec php php artisan route:clear
docker compose exec php php artisan view:clear

# Criar caches (produção)
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache
```

### Queue (Fila de Jobs)

> **REGRA CRÍTICA:** Após modificar qualquer `app/Jobs/*.php`, reinicie o container `queue`. O OPcache do PHP mantém o código antigo em memória até o processo ser reiniciado. Ver [AGENTS.md](../AGENTS.md) e [Lições Aprendidas](LICOES_APRENDIDAS.md) para detalhes.

```bash
# Reiniciar queue worker (OBRIGATÓRIO após editar Jobs)
docker compose restart queue

# Verificar logs do queue
docker compose logs queue -f
docker compose logs queue --tail=20

# Ver jobs pendentes
docker compose exec php php artisan queue:failed

# Reprocessar job específico (por ID)
docker compose exec php php artisan queue:retry 5

# Reprocessar todos os jobs falhos
docker compose exec php php artisan queue:retry all

# Limpar failed jobs
docker compose exec php php artisan queue:flush

# Processar queue manualmente (1 job, para debug)
docker compose exec php php artisan queue:work --once
```

### Scheduler

```bash
# Rodar scheduler manualmente (dev)
docker compose exec php php artisan schedule:run

# Ver comandos agendados
docker compose exec php php artisan schedule:list
```

---

## Testes

```bash
# Rodar todos testes
docker compose exec php php artisan test

# ou com Pest
docker compose exec php ./vendor/bin/pest

# Com cobertura
docker compose exec php php artisan test --coverage
docker compose exec php php artisan test --coverage --min=80

# Testes específicos
docker compose exec php php artisan test --filter BuscaBcaTest
docker compose exec php php artisan test tests/Feature/BuscaBcaTest.php

# Parallel
docker compose exec php php artisan test --parallel

# Stop on failure
docker compose exec php php artisan test --stop-on-failure

# Ver detalhes
docker compose exec php php artisan test --verbose
```

---

## Análise de Código

```bash
# Laravel Pint (formatar)
docker compose exec php ./vendor/bin/pint
docker compose exec php ./vendor/bin/pint --test  # apenas verificar

# PHPStan / Larastan (análise estática)
docker compose exec php ./vendor/bin/phpstan analyse --memory-limit=2G
```

---

## Debug / Tinker

```bash
# Abrir REPL interativo
docker compose exec php php artisan tinker

# Executar comando rápido
docker compose exec php php artisan tinker --execute="echo App\Models\Bca::count();"

# Verificar ocorrências de um BCA
docker compose exec php php artisan tinker --execute="
\$bca = App\Models\Bca::where('data', '2026-04-10')->first();
echo 'Ocorrencias: ' . \$bca->ocorrencias()->count();
"

# Ver jobs na fila
docker compose exec php php artisan tinker --execute="echo DB::table('jobs')->count();"

# Logs da aplicação
docker compose exec php tail -f storage/logs/laravel.log
```

---

## Deploy / Atualização

```bash
# Atualizar sistema em produção
git pull origin main
docker compose exec php composer install --no-dev --optimize-autoloader
npm run build
docker compose exec php php artisan migrate --force
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache
docker compose restart queue   # SEMPRE reiniciar queue após deploy
docker compose restart php

# Rollback de migration
docker compose exec php php artisan migrate:rollback
docker compose restart queue
```

---

## Utilitários

```bash
# Gerar Models
docker compose exec php php artisan make:model Bca -mfs
# -m = migration, -f = factory, -s = seeder

# Gerar Livewire
docker compose exec php php artisan make:livewire Busca/BuscaBca

# Gerar Job
docker compose exec php php artisan make:job ProcessarBcaJob

# Gerar Mail
docker compose exec php php artisan make:mail NotificacaoBcaMail

# Gerar Test
docker compose exec php php artisan make:test BuscaBcaTest

# Listar rotas
docker compose exec php php artisan route:list
docker compose exec php php artisan route:list --name=bca  # filtrar
```

---

## Troubleshooting

### Problema: "Class not found" após editar arquivo
```bash
docker compose exec php composer dump-autoload
docker compose exec php php artisan clear-compiled
```

### Problema: Jobs não usam o código atualizado
```bash
# OPcache mantém código antigo — reiniciar obrigatório
docker compose restart queue
docker compose logs queue --tail=5
```

### Problema: Permissões em storage/
```bash
docker compose exec php chown -R www-data:www-data storage bootstrap/cache
docker compose exec php chmod -R 775 storage bootstrap/cache
```

### Problema: Queue travada / jobs presos
```bash
# Ver jobs falhados
docker compose exec php php artisan queue:failed

# Limpar todos os falhos e reiniciar
docker compose exec php php artisan queue:flush
docker compose restart queue
```

### Problema: Migrations falham
```bash
# Ver status
docker compose exec php php artisan migrate:status

# Dropar tudo e recriar (desenvolvimento apenas)
docker compose exec php php artisan migrate:fresh --seed
```

### Problema: Assets não atualizam
```bash
rm -rf public/build
npm run build
docker compose exec php php artisan view:clear
# Hard reload no browser: Ctrl+Shift+R
```

### Problema: Email não chega
```bash
# Verificar configuração SMTP
docker compose exec php php artisan tinker --execute="
Illuminate\Support\Facades\Mail::raw('Teste', fn(\$m) => \$m->to('seu@email.com')->subject('Teste'));
"
# Verificar logs:
docker compose exec php tail -f storage/logs/laravel.log
```

---

## URLs do Sistema

| Serviço | URL | Observação |
|---------|-----|------------|
| Aplicação | http://localhost:18080 | Login: usuário cadastrado no sistema |
| pgAdmin | http://localhost:15050 | Gestão do banco PostgreSQL |
| Health Check | http://localhost:18080/health | Status dos serviços |
| Metrics | http://localhost:18080/metrics | Métricas de execução |

---

## Referências Rápidas

- **Laravel Docs**: https://laravel.com/docs/12.x
- **Livewire Docs**: https://livewire.laravel.com/docs
- **Pest Docs**: https://pestphp.com/docs
- **Tailwind Docs**: https://tailwindcss.com/docs

---

**Última atualização**: 30/04/2026

# 10 - Guia de Comandos (Referência Rápida)

## 🚀 Setup Inicial

```bash
# Clonar e instalar
git clone https://github.com/gacpac/bca-scrap-v2
cd bca-scrap-v2
cp .env.example .env
composer install
npm install

# Docker
docker-compose up -d
docker exec bca-php php artisan key:generate
docker exec bca-php php artisan migrate --seed

# Build assets
npm run build
```

---

## 🐳 Docker

```bash
# Subir todos containers
docker-compose up -d

# Ver logs
docker-compose logs -f
docker-compose logs -f php    # apenas PHP
docker-compose logs -f nginx  # apenas Nginx

# Parar
docker-compose stop

# Parar e remover
docker-compose down

# Rebuild
docker-compose up -d --build

# Acessar container
docker exec -it bca-php bash
docker exec -it bca-postgres psql -U bca_user bca_db
```

---

## 🗄️ Banco de Dados

```bash
# Migrations
php artisan migrate                    # rodar migrations
php artisan migrate:fresh --seed       # limpar e recriar
php artisan migrate:rollback           # desfazer última
php artisan migrate:status             # ver status

# Seeders
php artisan db:seed                    # todos seeders
php artisan db:seed --class=EfetivoSeeder  # específico

# PostgreSQL CLI
docker exec -it bca-postgres psql -U bca_user -d bca_db

# Backup
docker exec bca-postgres pg_dump -U bca_user bca_db > backup_$(date +%Y%m%d).sql

# Restore
docker exec -i bca-postgres psql -U bca_user bca_db < backup.sql
```

---

## 🎨 Frontend

```bash
# Desenvolvimento (watch mode)
npm run dev

# Build produção
npm run build

# Limpar cache Tailwind
rm -rf node_modules/.cache
npm run build

# Publish Livewire assets
php artisan livewire:publish --assets
```

---

## ⚙️ Laravel Artisan

### Manutenção do Sistema

```bash
# Busca automática de BCA
php artisan bca:buscar-automatica

# Limpar BCAs antigos (manter últimos 30)
php artisan bca:limpar-antigos

# Reenviar emails com falha
php artisan bca:reenviar-emails-falhos

# Limpar logs antigos
php artisan bca:limpar-logs --days=30
```

### Cache

```bash
# Limpar todos caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Criar caches (produção)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Cache Redis específico
php artisan cache:clear --store=redis
```

### Queue e Horizon

```bash
# Iniciar Horizon
php artisan horizon

# Status
php artisan horizon:status

# Pausar
php artisan horizon:pause

# Continuar
php artisan horizon:continue

# Terminar (gracefully)
php artisan horizon:terminate

# Listar workers
php artisan horizon:list

# Processar queue manualmente (dev)
php artisan queue:work

# Ver jobs falhados
php artisan queue:failed

# Reprocessar job específico
php artisan queue:retry 5  # ID do job

# Reprocessar todos
php artisan queue:retry all
```

### Scheduler

```bash
# Rodar scheduler manualmente (dev)
php artisan schedule:run

# Ver comandos agendados
php artisan schedule:list

# Testar comando específico
php artisan schedule:test
```

---

## 🧪 Testes

```bash
# Rodar todos testes
php artisan test

# ou com Pest
./vendor/bin/pest

# Com cobertura
php artisan test --coverage
php artisan test --coverage --min=80

# Testes específicos
php artisan test --filter BuscaBcaTest
php artisan test tests/Feature/BuscaBcaTest.php

# Parallel
php artisan test --parallel

# Stop on failure
php artisan test --stop-on-failure

# Ver detalhes
php artisan test --verbose
```

---

## 📊 Análise de Código

```bash
# Laravel Pint (formatar)
./vendor/bin/pint
./vendor/bin/pint --test  # apenas verificar

# PHPStan (análise estática)
./vendor/bin/phpstan analyse

# Larastan (Laravel-specific)
./vendor/bin/phpstan analyse --memory-limit=2G
```

---

## 🔍 Debug

```bash
# Telescope (Laravel Debug)
php artisan telescope:install
php artisan migrate

# Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev

# Logs
tail -f storage/logs/laravel.log
tail -f storage/logs/horizon.log

# Dump server (para dd())
php artisan dump-server
```

---

## 📦 Deploy

```bash
# Produção
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan horizon:terminate
php artisan queue:restart
docker-compose restart

# Rollback
git checkout previous-version
composer install --no-dev
php artisan migrate:rollback
docker-compose restart
```

---

## 🔐 Segurança

```bash
# Gerar nova App Key
php artisan key:generate

# Limpar sessions
php artisan session:clear

# Verificar permissões
docker exec bca-php find storage bootstrap/cache -type d -exec chmod 775 {} \;
docker exec bca-php find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

---

## 📈 Performance

```bash
# Opcache reset
php artisan opcache:clear

# Queue optimization
php artisan queue:prune-batches  # limpar batches antigos
php artisan queue:prune-failed   # limpar failed jobs

# Database optimization
php artisan db:monitor           # monitorar conexões
```

---

## 🛠️ Utilitários

```bash
# Gerar Models
php artisan make:model Bca -mfs
# -m = migration, -f = factory, -s = seeder

# Gerar Livewire
php artisan make:livewire Busca/BuscaBca

# Gerar Job
php artisan make:job ProcessarBcaJob

# Gerar Command
php artisan make:command BuscaAutomaticaCommand

# Gerar Test
php artisan make:test BuscaBcaTest
php artisan make:test BuscaBcaTest --unit

# Gerar Mail
php artisan make:mail MencaoBcaMail --markdown=mail.mencao-bca

# Listar rotas
php artisan route:list
php artisan route:list --name=bca  # filtrar

# Ver configuração
php artisan config:show database
php artisan config:show cache
```

---

## 🚨 Troubleshooting

### Problema: "Class not found"
```bash
composer dump-autoload
php artisan clear-compiled
php artisan optimize
```

### Problema: Permissões
```bash
docker exec bca-php chown -R www-data:www-data storage bootstrap/cache
docker exec bca-php chmod -R 775 storage bootstrap/cache
```

### Problema: Queue travada
```bash
php artisan horizon:terminate
redis-cli FLUSHALL  # CUIDADO: apaga todo cache
php artisan horizon
```

### Problema: Migrations falham
```bash
# Ver último erro
docker exec bca-postgres psql -U bca_user bca_db -c "SELECT * FROM pg_stat_activity;"

# Dropar tudo e recriar
php artisan migrate:fresh --seed
```

### Problema: Assets não atualizam
```bash
rm -rf public/build
npm run build
php artisan view:clear
Ctrl+Shift+R  # no navegador (hard reload)
```

---

## 📞 Atalhos Úteis

```bash
# Alias (adicionar no ~/.bashrc ou ~/.zshrc)
alias art='php artisan'
alias pest='./vendor/bin/pest'
alias pint='./vendor/bin/pint'

# Uso
art migrate
art test --filter=BuscaBca
pest --coverage
```

---

## 🔗 URLs do Sistema

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| Aplicação | http://localhost:8080 | - |
| Horizon | http://localhost:8080/horizon | - |
| pgAdmin | http://localhost:5050 | admin@gacpac.fab.mil.br / admin123 |
| PostgreSQL | localhost:5432 | bca_user / bca_pass |
| Redis | localhost:6379 | - |

---

## 📚 Referências Rápidas

- **Laravel Docs**: https://laravel.com/docs/12.x
- **Livewire Docs**: https://livewire.laravel.com/docs/4.x
- **Pest Docs**: https://pestphp.com/docs
- **Tailwind Docs**: https://tailwindcss.com/docs

---

**Última atualização**: 13/03/2026

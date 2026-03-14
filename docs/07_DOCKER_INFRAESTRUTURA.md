# 07 - Docker e Infraestrutura

## 🏗️ Stack de Containers

```
┌──────────────────────────────────────────────────────────────┐
│  bca-network (bridge)                                        │
│                                                              │
│  ┌──────────┐   ┌──────────┐   ┌────────────┐              │
│  │  nginx   │──▶│   php    │──▶│  postgres  │              │
│  │ :8080→80 │   │ PHP-FPM  │   │  :5432     │              │
│  └──────────┘   │  :9000   │   └────────────┘              │
│                 └────┬─────┘          │                     │
│                      │           ┌────────────┐             │
│                      └──────────▶│   redis    │             │
│                                  │  :6379     │             │
│  ┌──────────┐                    └────────────┘             │
│  │ pgadmin  │                                               │
│  │ :5050→80 │                                               │
│  └──────────┘                                               │
└──────────────────────────────────────────────────────────────┘
```

---

## 🐳 Dockerfile PHP

```dockerfile
# docker/php/Dockerfile
FROM php:8.3-fpm-alpine

# Instalar dependências do sistema
RUN apk add --no-cache \
    # Poppler Utils para pdftotext
    poppler-utils \
    # Git para composer
    git \
    # Extensões PHP
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    supervisor

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        mbstring \
        bcmath \
        opcache \
        pcntl    # Necessário para Horizon

# Instalar Redis extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Configurar OPcache para produção
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js e NPM (para build de assets)
RUN apk add --no-cache nodejs npm

# Configurar usuário não-root
RUN addgroup -g 1000 bca && adduser -u 1000 -G bca -h /home/bca -D bca
RUN chown -R bca:bca /var/www

WORKDIR /var/www/html

USER bca

EXPOSE 9000
```

---

## 🐳 Dockerfile PHP com Supervisor (Produção)

```dockerfile
# docker/php/Dockerfile.prod — PHP-FPM + Horizon via Supervisor
FROM php:8.3-fpm-alpine AS base

# ... (mesmas dependências do Dockerfile de dev)

# Copiar configuração do supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Script de entrada
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
```

```bash
# docker/php/entrypoint.sh
#!/bin/sh
set -e

# Aguardar PostgreSQL
until php -r "new PDO('pgsql:host=$DB_HOST;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
    echo "Aguardando PostgreSQL..."
    sleep 2
done

# Executar migrations automaticamente (apenas se necessário)
php artisan migrate --force --no-interaction

# Criar caches de produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Iniciar supervisor (PHP-FPM + Horizon)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
```

---

## 📋 Supervisor Config (PHP-FPM + Horizon)

```ini
; docker/supervisor/supervisord.conf
[supervisord]
nodaemon=true
logfile=/var/log/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:horizon]
process_name=%(program_name)s
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
user=bca
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log
stopwaitsecs=3600
```

---

## 🌐 Nginx Config

```nginx
# docker/nginx/default.conf (ver também exemplos/nginx.conf.example)
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    # Segurança
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    server_tokens off;

    # Gzip
    gzip on;
    gzip_comp_level 6;
    gzip_types text/plain text/css application/json application/javascript
               text/xml application/xml image/svg+xml;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass    php:9000;
        fastcgi_index   index.php;
        fastcgi_param   SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include         fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # Bloquear .env e arquivos ocultos
    location ~ /\.(env|git|htaccess) {
        deny all;
        return 404;
    }

    # Cache para assets estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

---

## 🐳 Docker Compose Completo

```yaml
# docker-compose.yml (produção) — ver exemplos/docker-compose.yml.example para versão comentada
version: '3.8'

services:
  nginx:
    image: nginx:1.25-alpine
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html:ro
      - ./docker/nginx:/etc/nginx/conf.d:ro
    depends_on:
      php:
        condition: service_healthy
    networks: [bca-network]
    restart: unless-stopped

  php:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - ./:/var/www/html
      - bca_storage:/var/www/html/storage
    environment:
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_DATABASE: ${DB_DATABASE:-bca_db}
      DB_USERNAME: ${DB_USERNAME:-bca_user}
      DB_PASSWORD: ${DB_PASSWORD:-bca_pass}
      REDIS_HOST: redis
      CACHE_DRIVER: redis
      QUEUE_CONNECTION: redis
    healthcheck:
      test: ["CMD", "php", "artisan", "about", "--only=environment"]
      interval: 30s
      timeout: 10s
      retries: 3
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks: [bca-network]
    restart: unless-stopped

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ${DB_DATABASE:-bca_db}
      POSTGRES_USER: ${DB_USERNAME:-bca_user}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-bca_pass}
      PGDATA: /var/lib/postgresql/data/pgdata
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-bca_user} -d ${DB_DATABASE:-bca_db}"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks: [bca-network]
    restart: unless-stopped

  pgadmin:
    image: dpage/pgadmin4:latest
    ports:
      - "5050:80"
    environment:
      PGADMIN_DEFAULT_EMAIL: ${PGADMIN_EMAIL:-admin@gacpac.fab.mil.br}
      PGADMIN_DEFAULT_PASSWORD: ${PGADMIN_PASSWORD:-admin123}
      PGADMIN_CONFIG_SERVER_MODE: 'False'
    volumes:
      - pgadmin_data:/var/lib/pgadmin
    depends_on:
      - postgres
    networks: [bca-network]
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks: [bca-network]
    restart: unless-stopped

volumes:
  postgres_data:
  pgadmin_data:
  redis_data:
  bca_storage:

networks:
  bca-network:
    driver: bridge
```

---

## 🔑 Permissões e Segurança

```bash
# Configurar permissões corretas do storage
docker exec bca-php chown -R www-data:www-data storage bootstrap/cache
docker exec bca-php chmod -R 775 storage bootstrap/cache

# Verificar que .env não está acessível pelo nginx
curl -I http://localhost:8080/.env
# Esperado: HTTP 404 (não 200!)
```

---

## 📊 Monitoramento dos Containers

```bash
# Status de todos os containers
docker-compose ps

# Uso de recursos em tempo real
docker stats --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"

# Logs em tempo real
docker-compose logs -f --tail=50

# Logs de container específico
docker-compose logs -f php | grep -E "ERROR|WARN|horizon"
```

---

**Próximo documento**: [08 - Testes](08_TESTES.md)

**Última atualização**: 14/03/2026

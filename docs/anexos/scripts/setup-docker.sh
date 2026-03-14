#!/bin/bash
# =============================================================================
# setup-docker.sh — Setup inicial completo do ambiente BCA Scrap v2
# =============================================================================
# Uso:
#   chmod +x setup-docker.sh
#   ./setup-docker.sh
#
# Pré-requisitos:
#   - Docker e docker-compose instalados
#   - Git instalado
#   - Arquivo .env.example presente no diretório raiz do projeto
# =============================================================================

set -e  # Para na primeira falha

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info()    { echo -e "${BLUE}  ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}  ✅ $1${NC}"; }
log_warn()    { echo -e "${YELLOW}  ⚠️  $1${NC}"; }
log_error()   { echo -e "${RED}  ❌ $1${NC}"; exit 1; }

echo ""
echo "🚀 Setup BCA Scrap v2 — Laravel 12 TALL Stack"
echo "=============================================="
echo ""

# =============================================================================
# PASSO 1: Verificar pré-requisitos
# =============================================================================
log_info "Verificando pré-requisitos..."

command -v docker >/dev/null 2>&1 || \
    log_error "Docker não encontrado. Instale: https://docs.docker.com/get-docker/"

command -v docker-compose >/dev/null 2>&1 || \
    log_error "docker-compose não encontrado."

[ -f ".env.example" ] || \
    log_error "Arquivo .env.example não encontrado. Execute este script na raiz do projeto."

log_success "Pré-requisitos verificados"

# =============================================================================
# PASSO 2: Configurar .env
# =============================================================================
if [ ! -f ".env" ]; then
    cp .env.example .env
    log_warn ".env criado a partir do .env.example"
    log_warn "ATENÇÃO: Edite o .env com suas credenciais antes de continuar!"
    echo ""
    echo "  Abra .env em outro terminal e configure:"
    echo "  - APP_KEY (será gerada automaticamente)"
    echo "  - DB_PASSWORD (defina uma senha segura)"
    echo "  - MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD"
    echo ""
    read -p "  Pressione ENTER quando terminar de editar o .env..."
else
    log_success ".env já existe — mantendo configuração atual"
fi

# =============================================================================
# PASSO 3: Verificar docker-compose.yml
# =============================================================================
if [ ! -f "docker-compose.yml" ]; then
    if [ -f "docs/exemplos/docker-compose.yml.example" ]; then
        cp docs/exemplos/docker-compose.yml.example docker-compose.yml
        log_success "docker-compose.yml criado a partir do exemplo"
    else
        log_error "docker-compose.yml não encontrado."
    fi
else
    log_success "docker-compose.yml encontrado"
fi

# =============================================================================
# PASSO 4: Subir containers
# =============================================================================
log_info "Subindo containers Docker..."
docker-compose up -d
log_success "Containers iniciados"

# =============================================================================
# PASSO 5: Aguardar serviços ficarem prontos
# =============================================================================
log_info "Aguardando PostgreSQL ficar pronto..."
TIMEOUT=60
ELAPSED=0
until docker exec bca-postgres pg_isready -U bca_user >/dev/null 2>&1; do
    if [ $ELAPSED -ge $TIMEOUT ]; then
        log_error "PostgreSQL não ficou pronto em ${TIMEOUT}s. Verifique: docker-compose logs postgres"
    fi
    sleep 2
    ELAPSED=$((ELAPSED + 2))
done
log_success "PostgreSQL pronto"

log_info "Aguardando Redis ficar pronto..."
ELAPSED=0
until docker exec bca-redis redis-cli ping >/dev/null 2>&1; do
    if [ $ELAPSED -ge 30 ]; then
        log_error "Redis não ficou pronto em 30s."
    fi
    sleep 1
    ELAPSED=$((ELAPSED + 1))
done
log_success "Redis pronto"

# =============================================================================
# PASSO 6: Instalar dependências
# =============================================================================
log_info "Instalando dependências PHP (Composer)..."
docker exec bca-php composer install --no-interaction --prefer-dist
log_success "Dependências PHP instaladas"

log_info "Instalando dependências NPM..."
docker exec bca-php npm install
log_success "Dependências NPM instaladas"

# =============================================================================
# PASSO 7: Configurar Laravel
# =============================================================================
log_info "Gerando chave da aplicação..."
docker exec bca-php php artisan key:generate --no-interaction
log_success "Chave gerada"

log_info "Executando migrations e seeders..."
docker exec bca-php php artisan migrate --seed --no-interaction
log_success "Banco de dados configurado"

# =============================================================================
# PASSO 8: Build dos assets
# =============================================================================
log_info "Compilando assets (Tailwind CSS + Alpine.js)..."
docker exec bca-php npm run build
log_success "Assets compilados"

# =============================================================================
# PASSO 9: Publicar assets do Livewire
# =============================================================================
log_info "Publicando assets do Livewire..."
docker exec bca-php php artisan livewire:publish --assets 2>/dev/null || true
log_success "Assets Livewire publicados"

# =============================================================================
# PASSO 10: Configurar permissões
# =============================================================================
log_info "Configurando permissões de storage..."
docker exec bca-php chown -R www-data:www-data storage bootstrap/cache
docker exec bca-php chmod -R 775 storage bootstrap/cache
log_success "Permissões configuradas"

# =============================================================================
# CONCLUÍDO
# =============================================================================
echo ""
echo "=============================================="
log_success "Setup completo!"
echo ""
echo "📋 Próximos passos:"
echo ""
echo "  1. Iniciar o Horizon (filas/jobs):"
echo "     docker exec -d bca-php php artisan horizon"
echo ""
echo "  2. Acessar o sistema:"
echo "     🌐 Aplicação:  http://localhost:8080"
echo "     📊 Horizon:    http://localhost:8080/horizon"
echo "     🗄️  pgAdmin:    http://localhost:5050"
echo "         Login:     admin@gacpac.fab.mil.br / admin123"
echo ""
echo "  3. Rodar os testes:"
echo "     docker exec bca-php php artisan test"
echo ""
echo "  4. Modo dev (watch Tailwind):"
echo "     docker exec bca-php npm run dev"
echo ""

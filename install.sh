#!/usr/bin/env bash
# ============================================================
# BCA Scrap v2 — Instalador Oficial
# Versão: 1.0.0
# Stack: Laravel 12 + PostgreSQL 16 + Redis 7
#
# Uso:
#   Interativo:    curl -fsSL https://raw.githubusercontent.com/nandinhos/bca_scrap_v2/main/install.sh | bash
#   Não-interativo: BCA_OM_NAME="<<INSIRA_NOME_OM>>" BCA_ADMIN_EMAIL="<<INSIRA_EMAIL_ADMIN>>" \
#                   BCA_ADMIN_PASSWORD="senha-segura" \
#                   BCA_DB_PASSWORD="$(openssl rand -base64 24)" \
#                   curl -fsSL ... | bash -s -- --non-interactive
#
# Variáveis de ambiente:
#   BCA_OM_NAME          - Nome da Organização Militar (ex: "OM-ALFA")
#   BCA_OM_SIGLA         - Sigla (ex: "OMA")                 [opcional, default: $BCA_OM_NAME]
#   BCA_OM_CODE          - Código ICAO/SISCOMEX              [opcional, default: gerado]
#   BCA_ADMIN_NAME       - Nome do admin                     [opcional, default: "Administrador"]
#   BCA_ADMIN_EMAIL      - Email do admin                    [obrigatório em modo non-interactive]
#   BCA_ADMIN_PASSWORD   - Senha do admin                    [obrigatório em modo non-interactive]
#   BCA_DB_PASSWORD      - Senha do PostgreSQL               [obrigatório em modo non-interactive]
#   BCA_HTTP_PORT        - Porta do nginx (default 18080)
#   BCA_INSTALL_DIR      - Diretório de instalação           [opcional, default: ./bca_scrap_v2]
#   BCA_SKIP_PREREQ      - Pular checagem de pré-requisitos [opcional, default: false]
# ============================================================

set -euo pipefail

# Cores
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m'

# URLs (configure via env para apontar para o seu fork/org)
readonly REPO_URL="${BCA_REPO_URL:-https://github.com/nandinhos/bca_scrap_v2.git}"
readonly REPO_BRANCH="${BCA_REPO_BRANCH:-main}"
readonly COMPOSE_URL="${BCA_COMPOSE_URL:-https://raw.githubusercontent.com/nandinhos/bca_scrap_v2/${REPO_BRANCH}/docker-compose.yml}"

# Defaults
INSTALL_DIR="${BCA_INSTALL_DIR:-./bca_scrap_v2}"
HTTP_PORT="${BCA_HTTP_PORT:-18080}"
SKIP_PREREQ="${BCA_SKIP_PREREQ:-false}"
MAIL_MAILER="${BCA_MAIL_MAILER:-}"
BCA_BASE_URL="${BCA_BASE_URL:-}"
BCA_ICEA_URL="${BCA_ICEA_URL:-}"
NON_INTERACTIVE=false
REUSING_EXISTING=false
COMPOSE_PROJECT_NAME="${BCA_COMPOSE_PROJECT_NAME:-$(basename "$INSTALL_DIR")}"

# Detectar modo
[[ "${1:-}" == "--non-interactive" || "${1:-}" == "-y" ]] && NON_INTERACTIVE=true
[[ -n "${BCA_ADMIN_EMAIL:-}${BCA_ADMIN_PASSWORD:-}${BCA_DB_PASSWORD:-}" ]] && NON_INTERACTIVE=true

# ============================================================
# Helpers
# ============================================================
log()    { echo -e "${BLUE}[BCA-INSTALL]${NC} $1"; }
ok()     { echo -e "${GREEN}[OK]${NC} $1"; }
warn()   { echo -e "${YELLOW}[WARN]${NC} $1"; }
err()    { echo -e "${RED}[ERRO]${NC} $1" >&2; }
fatal()  { err "$1"; exit 1; }
installation_banner() {
    local title="$1"
    local left_padding=$(( (62 - ${#title}) / 2 ))
    local right_padding=$(( 62 - left_padding - ${#title} ))

    printf '\n╔══════════════════════════════════════════════════════════════╗\n'
    printf '║%*s%s%*s║\n' "$left_padding" "" "$title" "$right_padding" ""
    printf '╚══════════════════════════════════════════════════════════════╝\n'
}
read_env_value() {
    local env_file="$1" key="$2" line value=""

    while IFS= read -r line; do
        [[ "$line" == "$key="* ]] || continue
        value="${line#*=}"
        value="${value%$'\r'}"
        if [[ "$value" == \"*\" && "$value" == *\" ]]; then
            value="${value:1:${#value}-2}"
        fi
        break
    done < "$env_file"

    printf '%s' "$value"
}
load_existing_config() {
    local env_file="$INSTALL_DIR/.env" var_name env_key value
    [[ -f "$env_file" ]] || return 0

    log "Configuração existente encontrada; preservando credenciais e dados da instalação."

    while read -r var_name env_key; do
        [[ -n "${!var_name:-}" ]] && continue
        value="$(read_env_value "$env_file" "$env_key")"
        [[ -n "$value" ]] && printf -v "$var_name" '%s' "$value"
    done << 'EXISTING_CONFIG'
OM_NAME OM_NAME
OM_SIGLA OM_SIGLA
OM_CODE OM_CODE
ADMIN_NAME ADMIN_NAME
ADMIN_EMAIL ADMIN_EMAIL
ADMIN_PASSWORD ADMIN_PASSWORD
SAD_EMAIL BCA_SAD_EMAIL
DB_DATABASE DB_DATABASE
DB_USERNAME DB_USERNAME
DB_PASSWORD DB_PASSWORD
APP_KEY APP_KEY
COMPOSE_PROJECT_NAME COMPOSE_PROJECT_NAME
MAIL_MAILER MAIL_MAILER
BCA_BASE_URL BCA_BASE_URL
BCA_ICEA_URL BCA_ICEA_URL
EXISTING_CONFIG
}
wait_for_postgres() {
    log "Aguardando PostgreSQL ficar pronto..."

    for i in {1..30}; do
        if docker compose exec -T --interactive=false postgres pg_isready -U "$DB_USERNAME" -d "$DB_DATABASE" >/dev/null 2>&1; then
            ok "PostgreSQL pronto"
            return 0
        fi
        sleep 1
    done

    fatal "PostgreSQL não ficou pronto em 30s"
}
database_credentials_valid() {
    docker compose exec -T --interactive=false postgres sh -lc \
        'PGPASSWORD="$POSTGRES_PASSWORD" psql -h 127.0.0.1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -tAc "SELECT 1"' \
        >/dev/null 2>&1
}
prompt() {
    local var_name="$1" prompt_text="$2" default_value="${3:-}" is_secret="${4:-false}"
    # Primeiro tenta a versão com prefixo BCA_ (modo não-interativo)
    local prefixed="BCA_${var_name}"
    if [[ -n "${!prefixed:-}" ]]; then
        eval "$var_name=\"${!prefixed}\""
        return 0
    fi
    # Depois tenta o nome sem prefixo (compatibilidade)
    if [[ -n "${!var_name:-}" ]]; then
        return 0
    fi
    if $NON_INTERACTIVE; then
        if [[ -z "$default_value" ]]; then
            fatal "Variável $var_name (ou BCA_$var_name) é obrigatória em modo não-interativo"
        fi
        eval "$var_name=\"$default_value\""
        return 0
    fi
    # O script pode ser recebido pelo stdin (`curl | bash`); nesse caso,
    # os valores interativos devem continuar vindo do terminal.
    local input=""
    if $is_secret; then
        if ! read -rs -p "$prompt_text${default_value:+ [$default_value]}: " input < /dev/tty; then
            fatal "Não foi possível ler $var_name do terminal. Use --non-interactive com BCA_$var_name definido."
        fi
    else
        if ! read -r -p "$prompt_text${default_value:+ [$default_value]}: " input < /dev/tty; then
            fatal "Não foi possível ler $var_name do terminal. Use --non-interactive com BCA_$var_name definido."
        fi
    fi
    echo
    eval "$var_name=\"${input:-$default_value}\""
}

generate_random() {
    openssl rand -base64 "$1" | tr -dc 'a-zA-Z0-9' | head -c "$1"
}

# ============================================================
# 1. Banner
# ============================================================
cat << 'BANNER'
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║         BCA Scrap v2 — Instalador Oficial v1.0.0            ║
║         Sistema de Busca e Análise de BCA                    ║
║         Stack: Laravel 12 + PostgreSQL 16 + Redis 7          ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
BANNER
echo

# ============================================================
# 2. Pré-requisitos
# ============================================================
check_prereqs() {
    log "Verificando pré-requisitos..."

    local missing=()

    command -v docker >/dev/null 2>&1 || missing+=("docker")
    command -v git >/dev/null 2>&1 || missing+=("git")
    command -v openssl >/dev/null 2>&1 || missing+=("openssl")

    # docker compose (plugin) ou docker-compose (legado)
    if ! docker compose version >/dev/null 2>&1 && ! command -v docker-compose >/dev/null 2>&1; then
        missing+=("docker-compose")
    fi

    if [[ ${#missing[@]} -gt 0 ]]; then
        err "Dependências faltando: ${missing[*]}"
        err "Instale-as antes de continuar:"
        err "  - Docker:    https://docs.docker.com/engine/install/"
        err "  - Git:       apt install git  /  yum install git"
        err "  - OpenSSL:   apt install openssl"
        exit 1
    fi

    # Checar Docker daemon
    if ! docker info >/dev/null 2>&1; then
        err "Docker daemon não está respondendo. Inicie o serviço:"
        err "  sudo systemctl start docker"
        exit 1
    fi

    # Checar porta
    if ss -ltn 2>/dev/null | grep -q ":$HTTP_PORT "; then
        warn "Porta $HTTP_PORT já está em uso. Defina BCA_HTTP_PORT= outra porta."
    fi

    ok "Pré-requisitos OK"
}

if [[ "$SKIP_PREREQ" != "true" ]]; then
    check_prereqs
fi

# ============================================================
# 3. Coletar inputs
# ============================================================
load_existing_config
MAIL_MAILER="${MAIL_MAILER:-log}"
BCA_BASE_URL="${BCA_BASE_URL:-http://www.cendoc.intraer/sisbca/consulta_bca/}"
BCA_ICEA_URL="${BCA_ICEA_URL:-http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/}"

log "Coletando informações da OM..."

prompt OM_NAME     "Nome da Organização Militar" "MINHA-OM"
prompt OM_SIGLA    "Sigla da OM" "${OM_NAME}"
prompt OM_CODE     "Código da OM (opcional)" "$(echo "$OM_SIGLA" | tr '[:lower:]' '[:upper:]' | tr -dc 'A-Z0-9')"

prompt ADMIN_NAME  "Nome do administrador" "Administrador"
prompt ADMIN_EMAIL "Email do administrador" ""
prompt ADMIN_PASSWORD "Senha do administrador (mínimo 8 caracteres)" "$(generate_random 16)" true

# SAD (Secretaria / Sec.Exec.) — recebe o compilado diário
prompt SAD_EMAIL    "Email da SAD/Secretaria que recebe o compilado diário (deixe vazio para desativar envio)" "${ADMIN_EMAIL}"

# DB
prompt DB_DATABASE "Nome do banco de dados" "bca_db"
prompt DB_USERNAME "Usuário do banco" "bca_user"
prompt DB_PASSWORD "Senha do PostgreSQL" "$(generate_random 24)" true

# Validar email em modo interativo
if ! $NON_INTERACTIVE && [[ -z "$ADMIN_EMAIL" ]]; then
    fatal "Email do administrador é obrigatório"
fi

# Validar senha mínima
if [[ ${#ADMIN_PASSWORD} -lt 8 ]]; then
    fatal "Senha do administrador deve ter no mínimo 8 caracteres"
fi

# ============================================================
# 4. Clonar repositório
# ============================================================
log "Clonando repositório em '$INSTALL_DIR'..."

if [[ -d "$INSTALL_DIR" ]]; then
    if [[ -d "$INSTALL_DIR/.git" ]]; then
        REUSING_EXISTING=true
        warn "Diretório $INSTALL_DIR já contém um repositório git."
        prompt USE_EXISTING "Reutilizar existente? (s/N)" "N"
        if [[ ! "$USE_EXISTING" =~ ^[sSyY]$ ]]; then
            fatal "Instalação abortada pelo usuário."
        fi
        log "Atualizando repositório existente para origin/$REPO_BRANCH..."
        git -C "$INSTALL_DIR" pull --ff-only origin "$REPO_BRANCH" \
            || fatal "Não foi possível atualizar o repositório existente. Verifique alterações locais ou divergências."
    else
        fatal "Diretório $INSTALL_DIR já existe mas não é um repositório git."
    fi
else
    git clone --depth 1 --branch "$REPO_BRANCH" "$REPO_URL" "$INSTALL_DIR" \
        || fatal "Falha ao clonar repositório"
fi

cd "$INSTALL_DIR"
ok "Repositório clonado"

# ============================================================
# 5. Gerar .env
# ============================================================
log "Gerando arquivo .env..."

# Gerar APP_KEY (32 bytes -> base64) se não foi fornecida.
# NÃO truncar: AES-256-CBC exige exatamente 32 bytes decodificados.
APP_KEY="${APP_KEY:-base64:$(openssl rand -base64 32)}"

cat > .env << EOF
COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME}

APP_NAME="BCA Scrap v2"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=http://localhost:${HTTP_PORT}

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# OM Config
OM_NAME="${OM_NAME}"
OM_SIGLA="${OM_SIGLA}"
OM_CODE="${OM_CODE}"

# Admin inicial (criado pelo seeder)
ADMIN_NAME="${ADMIN_NAME}"
ADMIN_EMAIL="${ADMIN_EMAIL}"
ADMIN_PASSWORD="${ADMIN_PASSWORD}"

# Mail (configurar depois)
MAIL_MAILER=${MAIL_MAILER}
MAIL_FROM_ADDRESS="${ADMIN_EMAIL}"
MAIL_FROM_NAME="\${APP_NAME}"

# Fontes BCA da intranet (podem ser substituídas no .env)
BCA_BASE_URL=${BCA_BASE_URL}
BCA_ICEA_URL=${BCA_ICEA_URL}

# SAD — e-mail da secretaria que recebe o compilado diario.
# Se vazio, o compilado NAO sera enviado (apenas e-mails individuais).
BCA_SAD_EMAIL="${SAD_EMAIL}"

# Sessão
SESSION_LIFETIME=120
EOF

ok ".env gerado (DB: $DB_DATABASE, Admin: $ADMIN_EMAIL)"

# Uma pasta removida não apaga os volumes Docker. Em uma instalação nova,
# um pgdata antigo usaria a senha anterior e impediria a autenticação.
PGDATA_VOLUME="${COMPOSE_PROJECT_NAME}_pgdata"
if ! $REUSING_EXISTING && docker volume inspect "$PGDATA_VOLUME" >/dev/null 2>&1; then
    warn "Foi encontrado um banco PostgreSQL de instalação anterior: $PGDATA_VOLUME"
    warn "A senha desse volume pode ser diferente da nova configuração."
    warn "A remoção abaixo apaga somente o banco antigo; arquivos BCA permanecem preservados."

    prompt RESET_DATABASE "Remover o banco PostgreSQL antigo e continuar? (s/N)" "N"
    if [[ ! "$RESET_DATABASE" =~ ^[sSyY]$ ]]; then
        fatal "Instalação interrompida para preservar o banco existente."
    fi

    docker compose down --remove-orphans \
        || fatal "Falha ao parar os containers da instalação anterior"
    docker volume rm "$PGDATA_VOLUME" >/dev/null \
        || fatal "Falha ao remover o volume $PGDATA_VOLUME"

    ok "Banco PostgreSQL antigo removido; um volume novo será criado"
fi

# ============================================================
# 6. Subir containers
# ============================================================
log "Subindo containers Docker (pode demorar 2-5 min na primeira vez)..."

docker compose up -d --build \
    || fatal "Falha ao subir containers. Rode 'docker compose logs' para detalhes."

ok "Containers em execução"

# ============================================================
# 7. Aguardar e validar PostgreSQL
# ============================================================
wait_for_postgres

if ! database_credentials_valid; then
    warn "O PostgreSQL está saudável, mas rejeitou a senha configurada."
    warn "Isso normalmente indica um volume criado por uma instalação anterior."
    warn "A recriação apaga somente o banco PostgreSQL; arquivos BCA permanecem preservados."

    prompt RESET_DATABASE "Recriar o banco PostgreSQL e continuar? (s/N)" "N"
    if [[ ! "$RESET_DATABASE" =~ ^[sSyY]$ ]]; then
        fatal "Instalação interrompida para preservar o banco existente."
    fi

    docker compose down --remove-orphans \
        || fatal "Falha ao parar os containers para recriar o banco"
    docker volume rm "$PGDATA_VOLUME" >/dev/null \
        || fatal "Falha ao remover o volume $PGDATA_VOLUME"
    docker compose up -d \
        || fatal "Falha ao reiniciar os containers após recriar o banco"

    wait_for_postgres
    database_credentials_valid \
        || fatal "O PostgreSQL continuou rejeitando as credenciais após recriar o banco"
fi

ok "Credenciais do PostgreSQL validadas"

# ============================================================
# 8. Aguardar PHP healthy
# ============================================================
log "Aguardando PHP-FPM ficar pronto..."

for i in {1..60}; do
    if docker compose exec -T --interactive=false php php -r 'exit(0);' >/dev/null 2>&1; then
        ok "PHP-FPM pronto"
        break
    fi
    [[ $i -eq 60 ]] && fatal "PHP-FPM não ficou pronto em 60s"
    sleep 1
done

# ============================================================
# 9. Dependências da aplicação
# ============================================================
log "Instalando dependências PHP..."

docker compose exec -T --interactive=false php composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    || fatal "Falha ao instalar dependências PHP"

ok "Dependências PHP instaladas"

log "Instalando e compilando assets do frontend..."

docker compose exec -T --interactive=false php npm ci --no-audit --no-fund \
    || fatal "Falha ao instalar dependências do frontend"

docker compose exec -T --interactive=false php npm run build \
    || fatal "Falha ao compilar assets do frontend"

ok "Frontend compilado"

# ============================================================
# 10. Migrations + Seed
# ============================================================
log "Rodando migrations..."

docker compose exec -T --interactive=false php php artisan migrate --force \
    || fatal "Falha nas migrations"

log "Rodando seeders (criando admin e unidades exemplo)..."

docker compose exec -T --interactive=false php php artisan db:seed --force \
    || fatal "Falha nos seeders"

ok "Banco configurado"

# ============================================================
# 10.5 Aviso sobre configuracao pendente
# ============================================================
if [[ -z "${SAD_EMAIL}" ]]; then
    warn "BCA_SAD_EMAIL esta vazio — compilado diario NAO sera enviado."
    warn "Edite o .env e adicione: BCA_SAD_EMAIL=sua-sad@suaom.fab.mil.br"
    warn "Depois reinicie: docker compose exec -T --interactive=false php php artisan config:clear && docker compose restart queue"
fi

if [[ "$MAIL_MAILER" == "log" ]]; then
    warn "MAIL_MAILER=log — e-mails serao gravados em storage/logs/laravel.log (nao enviados)."
    warn "Para envio real, edite o .env e configure: MAIL_MAILER=smtp + MAIL_HOST/USER/PASSWORD."
fi

# ============================================================
# 11. Storage link
# ============================================================
log "Criando link simbólico de storage..."

docker compose exec -T --interactive=false php php artisan storage:link --force >/dev/null 2>&1 || true

# ============================================================
# 12. Health check final
# ============================================================
log "Verificando saúde da aplicação..."

sleep 3
APP_URL="http://localhost:${HTTP_PORT}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL/login" || true)
HTTP_CODE="${HTTP_CODE:-000}"
APP_HEALTHY=false

if [[ "$HTTP_CODE" == "200" || "$HTTP_CODE" == "302" ]]; then
    APP_HEALTHY=true
    ok "Aplicação respondendo (HTTP $HTTP_CODE)"
else
    warn "Aplicação retornou HTTP $HTTP_CODE. Verifique os logs."
fi

# ============================================================
# 13. Resumo
# ============================================================
if $APP_HEALTHY; then
    INSTALLATION_STATUS="INSTALAÇÃO CONCLUÍDA!"
    ACCESS_MESSAGE="A aplicação está ativa e pronta para acesso:"
else
    INSTALLATION_STATUS="INSTALAÇÃO FINALIZADA COM AVISOS"
    ACCESS_MESSAGE="A aplicação subiu, mas o health check ainda não respondeu:"
fi

installation_banner "$INSTALLATION_STATUS"

cat << EOF

  Acesso:
    ${ACCESS_MESSAGE}
    URL:          ${APP_URL}
    Email:        ${ADMIN_EMAIL}
    Senha:        ${ADMIN_PASSWORD}

  Estrutura:
    Diretório:    $(pwd)
    OM cadastrada: ${OM_NAME} (${OM_SIGLA})

  Comandos úteis:
    cd $(pwd)
    docker compose ps              # Ver status dos containers
    docker compose logs -f php     # Logs do PHP
    docker compose exec php bash   # Shell dentro do container

  Próximos passos:
    1. Acesse http://localhost:${HTTP_PORT}
    2. Faça login com as credenciais acima
    3. Cadastre o efetivo da sua OM em /efetivo (ou importar CSV)
    4. Configure SMTP em .env se quiser envio real de e-mails
    5. Edite BCA_SAD_EMAIL em .env com o e-mail da SAD da sua OM
    6. Se usar fontes BCA diferentes, sobrescreva BCA_BASE_URL e BCA_ICEA_URL

  IMPORTANTE:
    - Salve a senha do admin em local seguro
    - As credenciais do banco estão em .env (não commitar)
    - Para produção, use HTTPS (configure um proxy reverso)

EOF

ok "Instalação finalizada em $(date '+%Y-%m-%d %H:%M:%S')"

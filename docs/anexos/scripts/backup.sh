#!/bin/bash
# =============================================================================
# backup.sh — Backup automatizado do BCA Scrap v2
# =============================================================================
# Uso:
#   ./backup.sh                         # Criar backup
#   ./backup.sh --restore <arquivo>     # Restaurar backup
#   ./backup.sh --list                  # Listar backups disponíveis
#
# Cron (backup diário às 02:00):
#   0 2 * * * /path/to/backup.sh >> /var/log/bca_backup.log 2>&1
# =============================================================================

set -e

# =============================================================================
# Configuração
# =============================================================================
BACKUP_DIR="${BACKUP_DIR:-/backups/bca_scrap}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/bca_backup_${DATE}.tar.gz"

# Container names (ajustar se necessário)
POSTGRES_CONTAINER="${POSTGRES_CONTAINER:-bca-postgres}"
PHP_CONTAINER="${PHP_CONTAINER:-bca-php}"
DB_NAME="${DB_DATABASE:-bca_db}"
DB_USER="${DB_USERNAME:-bca_user}"
APP_PATH="${APP_PATH:-/var/www/html}"

# =============================================================================
# Funções auxiliares
# =============================================================================
log()  { echo "[$(date '+%H:%M:%S')] $1"; }
fail() { echo "[$(date '+%H:%M:%S')] ERRO: $1" >&2; exit 1; }

verificar_container() {
    docker inspect "$1" >/dev/null 2>&1 || fail "Container '$1' não encontrado."
}

# =============================================================================
# Modo: Listar backups
# =============================================================================
if [ "$1" = "--list" ]; then
    log "Backups disponíveis em ${BACKUP_DIR}:"
    if ls "${BACKUP_DIR}"/bca_backup_*.tar.gz 2>/dev/null | head -20; then
        echo ""
        TOTAL=$(du -sh "${BACKUP_DIR}" 2>/dev/null | cut -f1)
        log "Espaço total usado: ${TOTAL}"
    else
        log "Nenhum backup encontrado."
    fi
    exit 0
fi

# =============================================================================
# Modo: Restaurar backup
# =============================================================================
if [ "$1" = "--restore" ]; then
    if [ -z "$2" ]; then
        fail "Especifique o arquivo de backup: ./backup.sh --restore /path/backup.tar.gz"
    fi

    RESTORE_FILE="$2"
    [ -f "$RESTORE_FILE" ] || fail "Arquivo não encontrado: ${RESTORE_FILE}"

    echo ""
    echo "⚠️  ATENÇÃO: OPERAÇÃO DESTRUTIVA"
    echo "   Arquivo: ${RESTORE_FILE}"
    echo "   Banco:   ${DB_NAME} no container ${POSTGRES_CONTAINER}"
    echo ""
    read -p "   Confirmar restore? (sim/não): " confirm
    [ "$confirm" = "sim" ] || { log "Cancelado."; exit 0; }

    log "Iniciando restore..."

    # Descomprimir
    TEMP_DIR=$(mktemp -d)
    tar xzf "$RESTORE_FILE" -C "$TEMP_DIR"

    # Restaurar banco
    log "Restaurando banco de dados..."
    docker exec "$POSTGRES_CONTAINER" psql -U "$DB_USER" -c "DROP DATABASE IF EXISTS ${DB_NAME};"
    docker exec "$POSTGRES_CONTAINER" psql -U "$DB_USER" -c "CREATE DATABASE ${DB_NAME};"
    docker exec -i "$POSTGRES_CONTAINER" psql -U "$DB_USER" "$DB_NAME" < "${TEMP_DIR}/bca_db.sql"

    # Restaurar storage (PDFs)
    if [ -d "${TEMP_DIR}/storage" ]; then
        log "Restaurando arquivos de storage..."
        rsync -a "${TEMP_DIR}/storage/" "${APP_PATH}/storage/"
    fi

    rm -rf "$TEMP_DIR"

    # Recriar caches
    docker exec "$PHP_CONTAINER" php artisan cache:clear
    docker exec "$PHP_CONTAINER" php artisan config:cache

    log "✅ Restore concluído!"
    log "   Execute smoke tests: docker exec ${PHP_CONTAINER} php artisan bca:buscar-automatica --dry-run"
    exit 0
fi

# =============================================================================
# Modo: Criar backup (padrão)
# =============================================================================
log "Iniciando backup BCA Scrap v2..."
log "Destino: ${BACKUP_FILE}"

# Verificar containers
verificar_container "$POSTGRES_CONTAINER"
verificar_container "$PHP_CONTAINER"

# Criar diretório de backup
mkdir -p "$BACKUP_DIR"

TEMP_DIR=$(mktemp -d)
trap "rm -rf ${TEMP_DIR}" EXIT  # Limpar temp ao sair

# Backup do banco de dados
log "Fazendo backup do PostgreSQL..."
docker exec "$POSTGRES_CONTAINER" \
    pg_dump -U "$DB_USER" --format=plain --no-password "$DB_NAME" \
    > "${TEMP_DIR}/bca_db.sql" || fail "pg_dump falhou"

log "  Tamanho do dump: $(du -sh ${TEMP_DIR}/bca_db.sql | cut -f1)"

# Backup do storage (PDFs e outros arquivos)
log "Fazendo backup do storage (PDFs)..."
if docker exec "$PHP_CONTAINER" test -d "${APP_PATH}/storage/app/bcas" 2>/dev/null; then
    docker exec "$PHP_CONTAINER" tar czf - -C "${APP_PATH}" storage/app/bcas \
        > "${TEMP_DIR}/storage_bcas.tar.gz" 2>/dev/null || log "  Aviso: storage/app/bcas vazio"
fi

# Compactar tudo em um único arquivo
log "Compactando backup..."
tar czf "$BACKUP_FILE" -C "$TEMP_DIR" . || fail "Compactação falhou"

# Resultado
SIZE=$(du -sh "$BACKUP_FILE" | cut -f1)
log "✅ Backup criado: ${BACKUP_FILE} (${SIZE})"

# Limpeza de backups antigos
log "Removendo backups com mais de ${RETENTION_DAYS} dias..."
REMOVED=$(find "$BACKUP_DIR" -name "bca_backup_*.tar.gz" -mtime +$RETENTION_DAYS -print -delete | wc -l)
log "  ${REMOVED} backup(s) removido(s)"

# Resumo final
echo ""
log "=== Resumo do Backup ==="
log "Arquivo: ${BACKUP_FILE}"
log "Tamanho: ${SIZE}"
log "Retenção: ${RETENTION_DAYS} dias"
log "Total de backups: $(ls ${BACKUP_DIR}/bca_backup_*.tar.gz 2>/dev/null | wc -l)"

exit 0

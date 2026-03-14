#!/bin/bash

###############################################################################
# Script de Migração de Dados: MySQL (antigo) → PostgreSQL (novo)
# BCA Scrap v1 → v2
###############################################################################

set -e  # Exit on error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configurações
OLD_MYSQL_HOST="localhost"
OLD_MYSQL_PORT="3306"
OLD_MYSQL_USER="bca_user"
OLD_MYSQL_PASS="bca_pass"
OLD_MYSQL_DB="bca_db"

NEW_PGSQL_HOST="localhost"
NEW_PGSQL_PORT="5432"
NEW_PGSQL_USER="bca_user"
NEW_PGSQL_PASS="bca_pass"
NEW_PGSQL_DB="bca_db"

BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Migração BCA Scrap v1 → v2${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Criar diretório de backup
mkdir -p $BACKUP_DIR

###############################################################################
# 1. BACKUP DO BANCO MYSQL
###############################################################################

echo -e "${YELLOW}[1/6]${NC} Criando backup do banco MySQL..."

docker exec bca_scrap-mariadb-1 mysqldump \
    -u $OLD_MYSQL_USER \
    -p$OLD_MYSQL_PASS \
    --single-transaction \
    --routines \
    --triggers \
    $OLD_MYSQL_DB > $BACKUP_DIR/mysql_backup_$DATE.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Backup MySQL criado: $BACKUP_DIR/mysql_backup_$DATE.sql"
else
    echo -e "${RED}✗${NC} Erro ao criar backup MySQL"
    exit 1
fi

###############################################################################
# 2. MIGRAÇÃO COM PGLOADER
###############################################################################

echo -e "\n${YELLOW}[2/6]${NC} Migrando dados com pgloader..."

# Criar arquivo de configuração pgloader
cat > /tmp/pgloader_config.load <<EOF
LOAD DATABASE
    FROM mysql://$OLD_MYSQL_USER:$OLD_MYSQL_PASS@$OLD_MYSQL_HOST:$OLD_MYSQL_PORT/$OLD_MYSQL_DB
    INTO pgsql://$NEW_PGSQL_USER:$NEW_PGSQL_PASS@$NEW_PGSQL_HOST:$NEW_PGSQL_PORT/$NEW_PGSQL_DB

WITH include drop, create tables, create indexes, reset sequences

SET work_mem to '256MB',
    maintenance_work_mem to '512 MB'

CAST type datetime to timestamptz
     drop default drop not null using zero-dates-to-null,
     type date drop not null drop default using zero-dates-to-null

BEFORE LOAD DO
    \$\$ DROP SCHEMA IF EXISTS public CASCADE; \$\$,
    \$\$ CREATE SCHEMA public; \$\$;
EOF

docker run --rm --network=host \
    -v /tmp/pgloader_config.load:/config.load \
    dimitri/pgloader:latest \
    pgloader /config.load

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Dados migrados com sucesso"
else
    echo -e "${RED}✗${NC} Erro na migração com pgloader"
    exit 1
fi

###############################################################################
# 3. AJUSTES NO BANCO POSTGRESQL
###############################################################################

echo -e "\n${YELLOW}[3/6]${NC} Aplicando ajustes no PostgreSQL..."

docker exec -i bca-postgres psql -U $NEW_PGSQL_USER -d $NEW_PGSQL_DB <<EOF
-- Renomear tabelas (pgloader cria com nomes originais)
ALTER TABLE IF EXISTS efetivo RENAME TO efetivos;
ALTER TABLE IF EXISTS palavras_chave RENAME TO palavras_chaves;

-- Adicionar colunas novas
ALTER TABLE efetivos ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL;

-- Criar índices full-text search
CREATE INDEX IF NOT EXISTS efetivos_nome_fulltext
ON efetivos USING gin(to_tsvector('portuguese', nome_completo));

CREATE INDEX IF NOT EXISTS bcas_conteudo_fulltext
ON bcas USING gin(to_tsvector('portuguese', conteudo_texto));

-- Atualizar sequences
SELECT setval('efetivos_id_seq', (SELECT MAX(id) FROM efetivos));
SELECT setval('palavras_chaves_id_seq', (SELECT MAX(id) FROM palavras_chaves));

-- Estatísticas
\echo '✓ Ajustes aplicados'
EOF

echo -e "${GREEN}✓${NC} Ajustes PostgreSQL concluídos"

###############################################################################
# 4. COPIAR ARQUIVOS PDF
###############################################################################

echo -e "\n${YELLOW}[4/6]${NC} Copiando arquivos PDF..."

SOURCE_PDF="../bca_scrap/arcadia/busca_bca/boletim_bca"
DEST_PDF="./storage/app/bcas"

if [ -d "$SOURCE_PDF" ]; then
    mkdir -p $DEST_PDF

    # Copiar apenas PDFs (não .txt)
    find $SOURCE_PDF -name "*.pdf" -type f -exec cp {} $DEST_PDF/ \;

    PDF_COUNT=$(find $DEST_PDF -name "*.pdf" | wc -l)
    echo -e "${GREEN}✓${NC} $PDF_COUNT PDFs copiados"
else
    echo -e "${YELLOW}!${NC} Diretório de PDFs não encontrado: $SOURCE_PDF"
fi

###############################################################################
# 5. REINDEXAR E REPROCESSAR
###############################################################################

echo -e "\n${YELLOW}[5/6]${NC} Reindexando dados..."

docker exec bca-php php artisan cache:clear
docker exec bca-php php artisan config:clear

# Se usar Laravel Scout
# docker exec bca-php php artisan scout:import "App\Models\Efetivo"
# docker exec bca-php php artisan scout:import "App\Models\Bca"

echo -e "${GREEN}✓${NC} Reindexação concluída"

###############################################################################
# 6. VALIDAÇÃO
###############################################################################

echo -e "\n${YELLOW}[6/6]${NC} Validando migração..."

# Contar registros
EFETIVO_COUNT=$(docker exec -i bca-postgres psql -U $NEW_PGSQL_USER -d $NEW_PGSQL_DB -t -c "SELECT COUNT(*) FROM efetivos;")
PALAVRAS_COUNT=$(docker exec -i bca-postgres psql -U $NEW_PGSQL_USER -d $NEW_PGSQL_DB -t -c "SELECT COUNT(*) FROM palavras_chaves;")

echo ""
echo -e "Registros migrados:"
echo -e "  - Efetivos: ${GREEN}$EFETIVO_COUNT${NC}"
echo -e "  - Palavras-chave: ${GREEN}$PALAVRAS_COUNT${NC}"

###############################################################################
# RESUMO
###############################################################################

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Migração Concluída!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Próximos passos:"
echo -e "  1. Validar dados no pgAdmin (http://localhost:5050)"
echo -e "  2. Rodar testes: ${YELLOW}php artisan test${NC}"
echo -e "  3. Testar busca manual na interface"
echo -e "  4. Verificar Horizon: ${YELLOW}http://localhost:8080/horizon${NC}"
echo ""
echo -e "Backup salvo em: ${GREEN}$BACKUP_DIR/mysql_backup_$DATE.sql${NC}"
echo ""

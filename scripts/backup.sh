#!/usr/bin/env bash
# Backup do BCA Scrap: dump do Postgres + tar dos PDFs (storage/app/public/bcas).
# Retencao local de 7 dias. Restore testado manualmente (ver docs/analise-tecnica-2026-10-23.md).
# ponytail: pg_dump+tar+cron bastam; destino externo e decisao da OM (fora deste script).
set -euo pipefail

cd "$(dirname "$0")/.."
DEST="backups/$(date +%F)"
mkdir -p "$DEST"

# Credenciais do proprio .env do projeto (sem imprimir segredos)
DB_USER=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2)
DB_NAME=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2)

docker compose exec -T postgres pg_dump -U "$DB_USER" -d "$DB_NAME" | gzip > "$DEST/banco.sql.gz"
# PDFs vivem no volume bcas_storage (montado sobre storage/app/public/bcas):
# tar a partir do container php, nunca do diretorio do host (sempre vazio).
docker compose exec -T php tar -czf - -C storage/app/public bcas > "$DEST/bcas.tar.gz"

# Retencao: apaga diretorios de backup com mais de 7 dias
find backups -maxdepth 1 -type d -mtime +7 -name '20*' -exec rm -rf {} +

echo "Backup OK: $DEST ($(du -sh "$DEST" | cut -f1))"

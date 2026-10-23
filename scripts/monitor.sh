#!/usr/bin/env bash
# Monitor externo do BCA Scrap: consulta /health com token e sai com
# codigo != 0 se algo estiver errado. Rode FORA do host do sistema
# (outra maquina/servico de uptime); neste host ele e apenas interino.
# Uso: HEALTH_URL=http://10.132.64.125:18080/health HEALTH_TOKEN=... ./scripts/monitor.sh
set -u

URL="${HEALTH_URL:-http://10.132.64.125:18080/health}"
TOKEN="${HEALTH_TOKEN:-}"

BODY=$(curl -s -m 15 -H "X-Health-Token: ${TOKEN}" "$URL") || { echo "ALERTA: health inacessivel ($URL)"; exit 1; }
echo "$BODY" | grep -q '"status":"healthy"' || { echo "ALERTA: status nao saudavel (resposta truncada):"; echo "$BODY" | head -c 200; echo; exit 1; }
echo "$BODY" | grep -q '"bca_do_dia":"pendente"' && { echo "ALERTA: BCA do dia pendente apos 12h em dia util"; exit 1; }
FALHAS=$(echo "$BODY" | grep -o '"falha_fonte_24h":[0-9]*' | cut -d: -f2)
[ "${FALHAS:-0}" -ge 3 ] && { echo "ALERTA: ${FALHAS} falhas de fonte em 24h (CENDOC/ICEA/DNS)"; exit 1; }

echo "OK: $(echo "$BODY" | head -c 200)"
exit 0

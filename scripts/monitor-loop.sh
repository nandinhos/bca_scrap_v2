#!/bin/sh
# Loop do monitor (container separado): consulta /health a cada 5 min.
# Certificado autoassinado da VM => curl -k (esperado neste ambiente).
URL="${HEALTH_URL:-https://host.docker.internal:18443/health}"

while true; do
  BODY=$(curl -k -s -m 15 -H "X-Health-Token: ${HEALTH_TOKEN:-}" "$URL" 2>/dev/null)
  if [ -z "$BODY" ]; then
    echo "$(date -u +%FT%TZ) ALERTA: health inacessivel ($URL)"
  elif ! echo "$BODY" | grep -q '"status":"healthy"'; then
    echo "$(date -u +%FT%TZ) ALERTA: status nao saudavel"
  elif echo "$BODY" | grep -q '"bca_do_dia":"pendente"'; then
    echo "$(date -u +%FT%TZ) ALERTA: BCA do dia pendente apos 12h em dia util"
  else
    FALHAS=$(echo "$BODY" | grep -o '"falha_fonte_24h":[0-9]*' | cut -d: -f2)
    if [ "${FALHAS:-0}" -ge 3 ]; then
      echo "$(date -u +%FT%TZ) ALERTA: ${FALHAS} falhas de fonte em 24h (CENDOC/ICEA/DNS)"
    else
      echo "$(date -u +%FT%TZ) OK"
    fi
  fi
  sleep 300
done

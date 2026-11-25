# Runbook de Operação — BCA Scrap v2

> Criado em 23/10/2026 a partir da [analise-tecnica-2026-10-23.md](docs/analise-tecnica-2026-10-23.md) e do [plano-implementacao-robustez-2026-10-23.md](docs/plano-implementacao-robustez-2026-10-23.md).

## Se o sistema ficar fora do ar

1. `docker compose ps` — todos os serviços devem estar `Up`. Com `restart: unless-stopped` (compose) + Docker habilitado no boot, reboot/queda de energia se recupera sozinho; se algo estiver `Exited`, `docker compose up -d`.
2. `curl -H "header do health: <token>" http://localhost:18080/health` — token em `token do health` no `.env`. Olhar `failed_jobs`, `jobs_na_fila`, `bca_do_dia`, `falha_fonte_24h`, `disco_livre_pct`.
3. Logs do dia: `storage/logs/laravel-<data>.log` (canal daily, retenção 14 dias, nível info).
4. Fila travada: `docker compose logs queue --tail=50`; reiniciar com `docker compose restart queue` (obrigatório também após qualquer mudança em `app/Jobs/`).

## Se faltar BCA de dias passados

```bash
# Auditoria (nao envia e-mail):
docker compose exec php php artisan bca:retroativo --de=AAAA-MM-DD --ate=AAAA-MM-DD
```

Conferir o relatório; os envios pendentes saem pela interface (BuscaBca → Enviar) ou despachando `EnviarEmailNotificacaoJob`/`EnviarCompiladoSADJob`. Feriados FAB não têm BCA (lista em `config/bca.php` — revisar todo janeiro: fixos `feriados_md` + móveis `feriados`).

## Backup e desastre (DR)

- Backup diário 02:30 via cron do host: `scripts/backup.sh` → `backups/<data>/` (dump do banco + tar dos PDFs, retenção local 7 dias).
- **RPO:** 24h (último backup diário). **RTO:** ~30 min (subir stack + restore).
- Restore do banco (banco temporário, nunca sobre produção sem decisão):
  `CREATE DATABASE banco_temporario;` e importar `banco.sql.gz` descompactado via `psql`.
- Restore testado em 23/10/2026: 52 BCAs, 75 efetivos, 71 ocorrências, 52 PDFs íntegros.
- Backup externo dispensado por decisão de 23/10/2026: a TI já faz backup da VM inteira. O backup local acima é para restore rápido e granular do banco, sem restaurar a VM.

## Monitoramento

- `scripts/monitor.sh` consulta o `/health` com token e sai com erro se: sistema inacessível, status não saudável, BCA-do-dia pendente após 12h em dia útil, ou ≥3 `falha_fonte` em 24h.
- Interino em produção: container separado (`docker-compose.monitor.yml`, projeto `bca-monitor`), consultando a cada 5 min — derrubar a aplicação não o derruba. Ver log: `docker compose -f docker-compose.monitor.yml logs`.
- **BACKLOG (sem VM disponível para testar, decidido em 23/10/2026):** rodar o monitor fora desta VM (outro servidor/serviço de uptime). Só isso detecta queda total da VM; o container interino morre junto, em silêncio.

## Backlog e pendências operacionais (atualizado em 25/11/2026)

- **SMTP funcional definitivo (prioridade alta):** após a obrigatoriedade de 2FA no e-mail institucional, o envio está operando temporariamente com conta individual + senha de aplicativo. Providenciar conta funcional definitiva da OM com 2FA + senha de aplicativo (ou liberação de SMTP AUTH / relay interno pela STI), atualizar `MAIL_USERNAME`, `MAIL_PASSWORD` e `MAIL_FROM_ADDRESS` no `.env`, recriar os containers (`docker compose up -d php queue scheduler`) e validar envio via `tinker`.
- **Certificado TLS oficial da OM (prioridade média):** resolvido interinamente em 23/10/2026 com certificado autoassinado da VM (`docker/nginx/certs/`, válido até jan/2029, chave fora do Git). Para eliminar o aviso nos navegadores, distribuir `bca.crt` como confiável nas estações ou substituir pelo certificado oficial emitido pela CA da OM e rodar `docker compose restart nginx`.
- **Monitoramento externo fora da VM (prioridade média):** em backlog (ver Monitoramento). Cadastrar `GET /health` com header `X-Health-Token` em ferramenta de monitoramento fora desta VM, alertando para HTTP ≠ 200, `"status"` ≠ `"healthy"`, `"bca_do_dia":"pendente"`, `"failed_jobs" > 0` ou `"falha_fonte_24h" >= 3`.
- **Revisão anual de feriados (preventiva):** revisar em janeiro os feriados móveis em `config/bca.php` (2026 e 2027 já cadastrados, incluindo `11-20` Consciência Negra).
- **Concluído em 25/11/2026:** bugfixes de `/health` (`555df61`), `EnviarCompiladoSADJob` (`555df61`), fallback paralelo ICEA (`d7f1c94`), limpeza de `failed_jobs` históricos e remoção do banco temporário de restore.

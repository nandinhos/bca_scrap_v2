# Runbook de Operação — BCA Scrap v2

> Criado em 23/10/2026 a partir da [analise-tecnica-2026-10-23.md](/home/gacpac/projects/bca_scrap_v2/docs/analise-tecnica-2026-10-23.md) e do [plano-implementacao-robustez-2026-10-23.md](/home/gacpac/projects/bca_scrap_v2/docs/plano-implementacao-robustez-2026-10-23.md).

## Se o sistema ficar fora do ar

1. `docker compose ps` — todos os serviços devem estar `Up`. Com `restart: unless-stopped` (compose) + Docker habilitado no boot, reboot/queda de energia se recupera sozinho; se algo estiver `Exited`, `docker compose up -d`.
2. `curl -H "X-Health-Token: <token>" http://localhost:18080/health` — token em `HEALTH_TOKEN` no `.env`. Olhar `failed_jobs`, `jobs_na_fila`, `bca_do_dia`, `falha_fonte_24h`, `disco_livre_pct`.
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
  `CREATE DATABASE bca_restore_test;` e importar `banco.sql.gz` descompactado via `psql`.
- Restore testado em 23/10/2026: 52 BCAs, 75 efetivos, 71 ocorrências, 52 PDFs íntegros.
- Pendência conhecida: backup só neste host não protege contra perda do host — destino externo é decisão da OM.

## Monitoramento

- `scripts/monitor.sh` consulta o `/health` com token e sai com erro se: sistema inacessível, status não saudável, BCA-do-dia pendente após 12h em dia útil, ou ≥3 `falha_fonte` em 24h.
- Deve rodar **fora deste host** (outra máquina ou serviço de uptime). Neste host há um cron interino a cada 15 min gravando em `backups/monitor.log` — ele não detecta queda total do host.

## Pendências de decisão (não técnicas)

- TLS/certificado interno para sair do HTTP puro (cookie `Secure` só com HTTPS).
- Monitor externo definitivo e destino externo de backup (decisão da OM).
- Banco temporário `bca_restore_test` (criado no teste de restore) pode ser removido manualmente.

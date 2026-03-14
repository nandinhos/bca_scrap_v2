# 🔄 Plano de Rollback — BCA Scrap v2

> **Meta**: Retornar ao sistema anterior em no máximo **30 minutos** após detecção de problema crítico em produção.

---

## 📋 Quando Acionar o Rollback

| Gatilho | Severidade | Tempo de Decisão | Ação |
|---------|-----------|-----------------|------|
| Busca BCA com erro contínuo por >5 min | 🔴 Crítico | Imediato | Rollback imediato |
| Emails não sendo enviados por >1h | 🔴 Crítico | Imediato | Rollback imediato |
| Dados corrompidos ou perdidos | 🔴 Crítico | Imediato | Rollback + RCA obrigatório |
| Performance >10s por operação | 🟡 Alto | 30 min tentando corrigir | Rollback se não resolver |
| Erros em <10% das requisições | 🟢 Baixo | Investigar | Corrigir no novo sistema |
| Interface quebrada em 1 browser | 🟢 Baixo | Investigar | Corrigir no novo sistema |

---

## 🚨 Cenário 1: Bug Crítico Pós-Deploy (Sem Perda de Dados)

**Quando usar**: Sistema novo está com erros mas dados estão íntegros.

**Tempo estimado**: 10-15 minutos

```bash
# PASSO 1: Documentar o problema (30 segundos)
echo "$(date '+%Y-%m-%d %H:%M:%S'): Iniciando rollback. Motivo: [DESCREVER]" \
    >> /var/log/bca_rollback.log

# PASSO 2: Ativar modo de manutenção (usuários veem mensagem amigável)
docker exec bca-php php artisan down \
    --message="Sistema em manutenção temporária. Retorno em breve." \
    --retry=60

# PASSO 3: Preservar logs do sistema novo para análise posterior
docker exec bca-php cp storage/logs/laravel.log \
    /tmp/laravel_rollback_$(date +%Y%m%d_%H%M).log

# PASSO 4: Voltar ao código anterior
cd /home/gacpac/projects/bca-scrap-laravel
git log --oneline -10        # Identificar tag/commit anterior
git checkout <tag-anterior>  # Ex: git checkout v1.9.2

# PASSO 5: Reinstalar dependências da versão anterior
composer install --no-dev --optimize-autoloader --no-interaction

# PASSO 6: Desfazer migrations SE houver novas tabelas/colunas problemáticas
# (Verificar se há migrations novas: php artisan migrate:status)
php artisan migrate:rollback  # Desfaz apenas última batch
# Para desfazer múltiplas batches: php artisan migrate:rollback --step=3

# PASSO 7: Limpar e recriar caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# PASSO 8: Reiniciar serviços
docker-compose restart nginx php
php artisan horizon:terminate && php artisan horizon &
php artisan queue:restart

# PASSO 9: Reativar sistema
php artisan up

# PASSO 10: Smoke test
echo "Status HTTP: $(curl -s -o /dev/null -w '%{http_code}' http://localhost:8080)"
php artisan bca:buscar-automatica --dry-run 2>&1 | head -5
```

---

## 🚨 Cenário 2: Dados Corrompidos Durante Migração

**Quando usar**: Dados foram alterados/perdidos durante a migração MySQL→PostgreSQL.

**Tempo estimado**: 20-30 minutos

> ⚠️ **ESTE CENÁRIO REQUER O BASELINE** documentado na Semana 0.

```bash
# PASSO 1: PARAR TUDO IMEDIATAMENTE
docker exec bca-php php artisan down --message="Manutenção em andamento"
docker exec bca-php php artisan horizon:pause

# PASSO 2: Identificar o backup pré-migração mais recente
ls -lht /backups/bca_scrap/bca_backup_*.tar.gz | head -5
# Selecionar o backup criado ANTES da migração (data/hora anterior ao deploy)
BACKUP_FILE="/backups/bca_scrap/bca_backup_<DATA_PRE_MIGRACAO>.tar.gz"

# PASSO 3: Restaurar banco PostgreSQL
docker exec bca-postgres psql -U bca_user -c "DROP DATABASE IF EXISTS bca_db;"
docker exec bca-postgres psql -U bca_user -c "CREATE DATABASE bca_db;"
tar xzf "$BACKUP_FILE" -C /tmp/bca_restore/
docker exec -i bca-postgres psql -U bca_user bca_db < /tmp/bca_restore/bca_db.sql
rm -rf /tmp/bca_restore

# PASSO 4: Restaurar arquivos de storage (PDFs)
rsync -a /tmp/bca_restore/storage/ /home/gacpac/projects/bca-scrap-laravel/storage/

# PASSO 5: Validar integridade (comparar com baseline da Semana 0)
docker exec bca-postgres psql -U bca_user bca_db -c "
    SELECT 'efetivos'      AS tabela, COUNT(*) AS registros FROM efetivos
    UNION ALL
    SELECT 'palavras_chaves',          COUNT(*) FROM palavras_chaves
    UNION ALL
    SELECT 'bcas',                     COUNT(*) FROM bcas;
"
# Comparar com os valores registrados no baseline da Semana 0!

# PASSO 6: Voltar código anterior e limpar caches
git checkout <versao-anterior>
composer install --no-dev --optimize-autoloader --no-interaction
php artisan config:cache && php artisan route:cache && php artisan view:cache

# PASSO 7: Reativar sistema
docker exec bca-php php artisan up
docker exec bca-php php artisan horizon:continue

# PASSO 8: Notificar equipe
echo "$(date): Rollback (Cenário 2) concluído. Sistema restaurado para versão anterior." \
    | mail -s "[BCA Scrap] ROLLBACK EXECUTADO" gacpac@fab.mil.br
```

---

## 🚨 Cenário 3: Problema de Performance Grave

**Quando usar**: Sistema funcionando mas muito lento (>10s por operação).

**Tempo estimado**: 5-10 min diagnóstico + rollback se necessário

```bash
# DIAGNÓSTICO (2 minutos)

# Verificar estado do Horizon
docker exec bca-php php artisan horizon:status

# Verificar Redis
docker exec bca-redis redis-cli ping
docker exec bca-redis redis-cli info stats | grep rejected_connections

# Verificar queries lentas no PostgreSQL
docker exec bca-postgres psql -U bca_user bca_db -c "
    SELECT pid,
           now() - pg_stat_activity.query_start AS duration,
           left(query, 100) AS query_snippet
    FROM pg_stat_activity
    WHERE (now() - pg_stat_activity.query_start) > interval '5 seconds'
    ORDER BY duration DESC;
"

# Verificar uso de memória do container PHP
docker stats bca-php --no-stream --format "{{.MemUsage}} / {{.MemPerc}}"

# AÇÃO RÁPIDA (pode resolver sem rollback)
docker exec bca-php php artisan cache:clear
docker exec bca-php php artisan horizon:terminate
docker exec bca-php php artisan queue:restart
docker exec bca-php php artisan horizon &

# SE NÃO RESOLVER EM 10 MINUTOS → Executar Cenário 1
```

---

## 📊 Baseline de Validação (Preencher na Semana 0)

> Estas informações são **essenciais** para o Cenário 2. Preencher ANTES do deploy.

| Métrica | Valor Baseline | Data Medição | Responsável |
|---------|---------------|--------------|-------------|
| Registros em `efetivos` | ___ | ___ | ___ |
| Registros em `palavras_chaves` | ___ | ___ | ___ |
| Registros em `bcas` | ___ | ___ | ___ |
| PDFs em storage (quantidade) | ___ | ___ | ___ |
| PDFs em storage (tamanho total) | ___ | ___ | ___ |
| Tempo médio busca BCA (sistema antigo) | ___s | ___ | ___ |

---

## 🧪 Teste do Plano de Rollback (Obrigatório antes do Deploy)

```bash
# Executar em ambiente de STAGING (não em produção!)
# Objetivo: garantir que o rollback funciona em <30 minutos

# 1. Deploy da versão nova em staging
# 2. Simular falha (php artisan down)
# 3. Executar Cenário 1
# 4. Validar que sistema voltou ao normal
# 5. Documentar tempo total de execução
echo "Tempo de rollback em staging: ___ minutos"
echo "Executado por: ___ em ___/___/___"
```

---

## 📞 Contatos de Emergência

| Função | Responsável | Contato |
|--------|------------|---------|
| Desenvolvedor principal | 1S BMB FERNANDO | gacpac@fab.mil.br |
| TI / Infraestrutura | ___ | ___ |
| Responsável pelo banco | ___ | ___ |
| Gestão GAC-PAC | ___ | ___ |

---

## 📝 Registro de Rollbacks Executados

| Data | Versão | Cenário | Tempo Total | Causa Raiz | Responsável |
|------|--------|---------|------------|-----------|-------------|
| ___ | ___ | ___ | ___ | ___ | ___ |

---

**Última atualização**: 14/03/2026
**Testado em staging**: [ ] Sim [ ] Não — Data: ___ / Tempo: ___ min
**Aprovado por**: ___________________________

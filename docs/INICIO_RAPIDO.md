# 🚀 Início Rápido - BCA Scrap v2

## Para que você está aqui?

Este é o novo projeto **BCA Scrap v2** - migração completa para **Laravel 12 TALL Stack** em **8 semanas**.

---

## 📖 Documentação Completa

Toda a documentação de migração está em `/docs`:

### 🎯 Por onde começar?

> ⚠️ **PRIMEIRO**: Antes de qualquer código, execute a **[Semana 0 - Pré-Projeto](09_MIGRACAO_PASSO_A_PASSO.md#semana-0-pré-projeto-validações-e-preparação)** — validações de infraestrutura obrigatórias.

1. **Se você é GESTOR/PO**: Leia [01 - Visão Geral](01_VISAO_GERAL.md) (10 min)
2. **Se você vai DESENVOLVER**: Leia [09 - Migração Passo a Passo](09_MIGRACAO_PASSO_A_PASSO.md) (guia 8 semanas)
3. **Se vai fazer DEPLOY**: Use [Checklist de Migração](anexos/checklist_migracao.md)
4. **Se ocorrer problema**: Consulte o [Plano de Rollback](ROLLBACK_PLAN.md)
5. **Referência rápida**: [10 - Guia de Comandos](10_GUIA_COMANDOS.md)

---

## 📋 Índice Completo

Consulte o **[00 - ÍNDICE](docs/00_INDICE.md)** para navegação completa.

### Documentos Principais

1. [01 - Visão Geral](01_VISAO_GERAL.md) - Por que migrar?
2. [09 - Migração Passo a Passo](09_MIGRACAO_PASSO_A_PASSO.md) - Como fazer (8 semanas)
3. [10 - Guia de Comandos](10_GUIA_COMANDOS.md) - Comandos úteis
4. [ROLLBACK_PLAN](ROLLBACK_PLAN.md) - Plano de rollback (3 cenários)

### Recursos Práticos

- **[Checklist](anexos/checklist_migracao.md)** - Lista completa de tarefas (inclui Fase 0)
- **[Script de Migração](anexos/scripts/migrate-data.sh)** - Migrar dados MySQL → PostgreSQL
- **[Script de Setup](anexos/scripts/setup-docker.sh)** - Setup inicial do ambiente Docker
- **[Script de Backup](anexos/scripts/backup.sh)** - Backup automatizado
- **[Exemplos de Config](exemplos/)** - .env, docker-compose, nginx, horizon, etc

---

## ⚡ Quick Start (5 minutos)

```bash
# 1. Clone o repositório (quando estiver pronto)
git clone https://github.com/gacpac/bca-scrap-v2.git
cd bca-scrap-v2

# 2. Configure
cp docs/exemplos/.env.example .env
cp docs/exemplos/docker-compose.yml.example docker-compose.yml

# 3. Suba containers
docker-compose up -d

# 4. Instale
docker exec bca-php composer install
docker exec bca-php php artisan key:generate
docker exec bca-php php artisan migrate --seed

# 5. Acesse
# http://localhost:8080
```

---

## 📊 Comparação Rápida

| O que muda? | Antes | Depois |
|-------------|-------|--------|
| Framework | PHP vanilla | Laravel 12 |
| Frontend | Alpine.js (CDN) | Livewire 4 |
| Database | MariaDB | PostgreSQL 16 |
| Performance | 5-15s busca | 1-3s busca |
| Testes | 0% | 80%+ |

**Resultado**: Sistema 80% mais rápido, modular e testável.

---

## 🎯 Próximos Passos

1. ✅ Leia a [Visão Geral](01_VISAO_GERAL.md)
2. ✅ Execute as validações da **[Semana 0](09_MIGRACAO_PASSO_A_PASSO.md#semana-0-pré-projeto-validações-e-preparação)**
3. ✅ Revise o [Guia de Migração](09_MIGRACAO_PASSO_A_PASSO.md)
4. ✅ Comece pela **Semana 1** (Preparação) após Semana 0 concluída

---

## 📞 Dúvidas?

- **Documentação**: [/docs](/docs)
- **Email**: gacpac@fab.mil.br
- **Sistema atual**: `/home/gacpac/projects/bca_scrap`

---

**Boa sorte com o projeto! 🚀**

# 07 — Documentação, arquitetura e dívida técnica

## Resumo

Documentos marcados como vigentes descrevem outro sistema e não existe fonte canônica de status, arquitetura ou decisões.

## Implementado

- Stack real: Laravel 12.54.1, Livewire 3.7.11, workers nativos, PostgreSQL e Redis.
- Pipeline real usa serviços BCA e componentes em `app/Livewire`.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| SSOT de arquitetura | high | Ausentes `docs/STATUS.md`, `docs/architecture/` e ADRs. | `docs/index.md` |
| Contrato multi-OM | critical | Não há decisão/documento executável de tenancy. | `README.md` |
| Runbook de produção | high | Falta processo verificável de deploy, rollback e restore. | `INSTALL.md` |

## Falhas

### ⚪ Não revalidada nesta rodada — documentação VIGENTE descreve outro sistema

**high.** Afirma Livewire 4, Horizon, Sanctum e serviços/módulos inexistentes. Evidência: `docs/architecture.md:14`, `docs/queues-and-jobs.md:1`, `composer.json:13`.

### ⚪ Não revalidada nesta rodada — abstrações e dependências mortas

**medium.** Repositories não têm consumidores; Spatie Permission não participa do fluxo de autorização. Evidência: `app/Repositories/`, `app/Models/User.php:10`.

### ⚪ Não revalidada nesta rodada — FTS prometido, não usado

**high.** Índices GIN existem, mas análise percorre texto em PHP. Evidência: `app/Services/BcaAnalysisService.php:44`.

# 04 — Filas, notificações e scheduler

## Resumo

O pipeline assíncrono funciona em caminho simples, mas não possui idempotência, coordenação de compilado ou isolamento de entrega por OM.

## Implementado

- Jobs declaram tentativas, timeout e backoff.
- `failed_jobs` existe e workers reiniciam: `database/migrations/2026_03_14_000002_create_jobs_table.php:34`.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| Outbox e estado de entrega | high | Não distingue enfileirado, aceito, falho, suprimido ou entregue. | `app/Models/BcaOcorrencia.php:12` |
| Coordenação do compilado | high | Sem batch/chain que espere os e-mails individuais. | `app/Services/BcaAnalysisService.php:136` |

## Falhas

### ✅ Confirmada — SAD mistura todas as OMs

**critical.** O job carrega todas as ocorrências enviadas do BCA e usa um único `BCA_SAD_EMAIL`. Evidência: `app/Jobs/EnviarCompiladoSADJob.php:33`, `config/bca.php:12`.

### ✅ Confirmada — resultados de palavras/cache são globais

**high.** A chave é `bca:analise:{data}` e `keywords_encontradas` vive no BCA único por data. A última análise vence. Evidência: `app/Services/BcaAnalysisService.php:22`, `app/Livewire/BuscaBca.php:164`.

### ⚪ Não revalidada nesta rodada — duplicidade e compilado parcial

**high.** `retry_after=90` é menor que timeouts de até 300s; jobs e e-mails não são únicos. Evidência: `config/queue.php:67`, `app/Jobs/AnalisarEfetivoJob.php:20`.

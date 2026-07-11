# 03 — Dados, PII e integridade

## Resumo

Há FKs e unicidade básica, porém operações administrativas alteram pertencimento histórico e podem destruir dados sem reversão.

## Implementado

- Ocorrência tem unicidade BCA/efetivo: `database/migrations/2026_03_14_000005_create_bca_ocorrencias_table.php:19`.
- Modelos ocultam senha e token: `app/Models/User.php:14`.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| Retenção/auditoria | medium | Sem expurgo, anonimização ou trilha de acesso/exportação. | `app/Models/BcaExecucao.php:13` |
| Modelo temporal de lotação | high | Ocorrência não conserva unidade do momento do fato. | `database/migrations/2026_03_14_000005_create_bca_ocorrencias_table.php:13` |

## Falhas

### ✅ Confirmada — CSV corrompe associação de unidade

**critical.** Export omite Unidade; import usa fallback do admin e `updateOrCreate` global por SARAM, sem transação. Evidência: `app/Livewire/ListagemEfetivo.php:186`, `app/Livewire/ListagemEfetivo.php:257`, `app/Livewire/ListagemEfetivo.php:266`.

### ✅ Confirmada — reanálise destrói histórico

**critical.** Ocorrências são apagadas antes de haver substituto; em redownload, a flag de suprimir e-mail não atravessa a fila. Evidência: `app/Console/Commands/ReanalisarBcasCommand.php:67`, `app/Jobs/BaixarBcaJob.php:27`.

### ⚪ Não revalidada nesta rodada — transferência/exclusão retroativas

**high.** Trocar unidade reatribui histórico e hard delete propaga cascade. Evidência: `app/Livewire/ListagemEfetivo.php:104`, `database/migrations/2026_03_14_000005_create_bca_ocorrencias_table.php:14`.

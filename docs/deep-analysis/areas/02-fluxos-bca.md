# 02 — Fluxos BCA, download e processamento

## Resumo

O pipeline existe, mas não assegura autenticidade da fonte, isolamento de resultado, recuperação de falha ou segurança do artefato.

## Implementado

- Jobs separam download, processamento e análise: `app/Jobs/BaixarBcaJob.php:56`.
- Caminho do `pdftotext` é escapado: `app/Services/BcaProcessingService.php:53`.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| Quarentena de PDF | high | Sem magic bytes, antivírus, sandbox ou limite de CPU/memória. | `app/Services/BcaProcessingService.php:53` |
| Máquina de estados/correlação | high | Falta versão, hash, etapa, tentativa e correlation ID. | `app/Models/BcaExecucao.php:13` |

## Falhas

### ✅ Confirmada — fontes HTTP não autenticadas alimentam o pipeline

**critical.** CENDOC/ICEA usam HTTP; redirects, destino final e autenticidade não são validados antes de persistir e chamar `pdftotext`. Evidência: `config/bca.php:4`, `app/Services/BcaDownloadService.php:184`, `app/Services/BcaProcessingService.php:42`.

### ✅ Confirmada — PDFs são públicos

**critical.** Download grava em disk public e Nginx serve arquivo existente antes do Laravel. Evidência: `app/Services/BcaDownloadService.php:202`, `config/filesystems.php:41`, `docker/nginx/default.conf:18`.

### ⚪ Não revalidada nesta rodada — fallback/cache e escala

**high.** Fallback sequencial pode exceder timeout; cache de URL positiva impede redescoberta; análise PHP é linear. Evidência: `app/Services/BcaDownloadService.php:105`, `app/Services/BcaAnalysisService.php:52`.

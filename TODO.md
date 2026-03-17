# Plano de Implementação - Auditoria BCA v2

## Legenda
- [ ] Pendente
- 🔴 Em Progresso
- ✅ Concluído
- ❌ Bloqueado

---

## SPRINT 1: Correções Críticas de Segurança (1 dia)

### Objetivo: Eliminar vulnerabilidades críticas antes de qualquer outra tarefa

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 1.1 | Remover/excluir .env do repositório | Arquivo não existe ou está no .gitignore | [x] |
| 1.2 | Corrigir XSS em BcaAnalysisService.php (linha 160-164) | Usar e() antes de aplicar highlight | [x] |
| 1.3 | Validar APP_DEBUG=false em produção | Verificar config e .env | [x] |
| 1.4 | Adicionar EnsureRole logging de acessos negados | Log presente ao testar acesso negado | [x] |

---

## SPRINT 2: Consistência e Error Handling (1 dia)

### Objetivo: Padronizar comportamento de Jobs e обработка de erros

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 2.1 | Adicionar logging em ProcessarBcaJob | Log de início presente | [x] |
| 2.2 | Adicionar BcaExecucao no failed() de ProcessarBcaJob | Falha persistida no DB | [x] |
| 2.3 | Corrigir cache behavior em BcaDownloadService (linha 35-45) | Comportamento consistente | [x] |
| 2.4 | Adicionar transaction em BaixarBcaJob:52-58 | Consistência garantida | [x] |

---

## SPRINT 3: Validação e UX (1 dia)

### Objetivo: Melhorar validação de entrada e experiência do usuário

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 3.1 | Fortalecer validação de data em BuscaBca.php | Validar Y-m-d e datas futuras | [ ] |
| 3.2 | Adicionar rate limiting na busca | Máximo 5 requisições/min | [ ] |
| 3.3 | Melhorar mensagem de timeout (6min → 3min) | Usuário entende melhor | [ ] |
| 3.4 | Adicionar loading state visual | Feedback claro durante loading | [ ] |

---

## SPRINT 4: Performance (1 dia)

### Otimizar consultas e processamento

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 4.1 | Implementar chunking em BcaAnalysisService | Processar efetivos em batches de 100 | [ ] |
| 4.2 | Adicionar índice no campo saram de Efetivo | Query mais rápida | [ ] |
| 4.3 | Adicionar índice composto em BcaOcorrencia (bca_id, efetivo_id) | Query mais rápida | [ ] |

---

## SPRINT 5: Testes - Cobertura Básica (2 dias)

### Objetivo: Garantir testes fundamentais para as correções

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 5.1 | Criar factory BcaOcorrencia | Factory utilizável em testes | [ ] |
| 5.2 | Criar factory BcaExecucao | Factory utilizável em testes | [ ] |
| 5.3 | Testar EnsureRole middleware | 403 retornado corretamente | [ ] |
| 5.4 | Testar validação de data em BuscaBca | Dados inválidos rejeitados | [ ] |
| 5.5 | Corrigir teste skipado (BcaDownloadServiceTest:59) | Teste executável | [ ] |

---

## SPRINT 6: Testes - Serviços (2 dias)

### Objetivo: Cobertura de serviços principais

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 6.1 | Testar BcaProcessingService - PDF não encontrado | Retorna null corretamente | [ ] |
| 6.2 | Testar BcaProcessingService - Texto vazio | Log de warning presente | [ ] |
| 6.3 | Testar BcaAnalysisService - Race condition simulada | Comportamento consistente | [ ] |
| 6.4 | Testar BcaAnalysisService - Keywords inativas | Ignoradas corretamente | [ ] |
| 6.5 | Testar Jobs - falha e retry | Comportamento esperado | [ ] |

---

## SPRINT 7: Monitoramento e Observabilidade (1 dia)

### Objetivo: Melhorar visibilidade do sistema

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 7.1 | Adicionar health check endpoint | Retorna status dos serviços | [ ] |
| 7.2 | Adicionar métricas de execução (tempo médio) | Dados coletáveis | [ ] |
| 7.3 | Dashboard básico de execuções | Visualização de成功率 | [ ] |

---

## SPRINT 8: Refinamento (1 dia)

### Objetivo: Limpeza técnica e melhorias menores

| # | Tarefa | Critério de Aceite | Status |
|---|--------|-------------------|--------|
| 8.1 | Padronizar retornos de EfetivoRepository | Tipos consistentes | [ ] |
| 8.2 | Adicionar comentários em código complexo | Código auto-documentado | [ ] |
| 8.3 | Configurar PHPStan/Laravel Pint | Zero erros/warnings | [ ] |
| 8.4 | Limpar código morto (unreachable code) | Código limpo | [ ] |

---

## Métricas de Progresso

```
SPRINT 1 ██████████████████ 100% ✅
SPRINT 2 ██████████████████ 100% ✅
SPRINT 3 ░░░░░░░░░░░░░░░░░░░░░░  0%
SPRINT 4 ░░░░░░░░░░░░░░░░░░░░░░  0%
SPRINT 5 ░░░░░░░░░░░░░░░░░░░░░░  0%
SPRINT 6 ░░░░░░░░░░░░░░░░░░░░░░  0%
SPRINT 7 ░░░░░░░░░░░░░░░░░░░░░░  0%
SPRINT 8 ░░░░░░░░░░░░░░░░░░░░░░  0%
```

---

## Notas
- Total estimado: 10 dias (2 semanas)
- Sprint = 1 dia útil
- Critério de aceite deve ser verificado antes de marcar como concluído
- Ao final de cada sprint, executar testes: `php artisan test`

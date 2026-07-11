# Mapa de fluxos

| Fluxo | Status | Trigger | Chamadas | Callbacks | Estado | Eventos | Notas |
|---|---|---|---|---|---|---|---|
| Busca manual | 🔴 flawed | dashboard | CENDOC/ICEA | polling Livewire | Bca/timestamps | BaixarBcaJob | `executarBusca` público contorna validação/limite. |
| Busca agendada | 🔴 flawed | scheduler | CENDOC/ICEA | nenhum | jobs | BaixarBcaJob | janela declarada em UTC, não Brasília. |
| Download PDF | 🔴 flawed | BaixarBcaJob | HTTP | redirects | disk public | ProcessarBcaJob | fonte sem autenticidade, limite tardio e PDF público. |
| Extração | 🟡 partial | ProcessarBcaJob | pdftotext | nenhum | texto/cache | AnalisarEfetivoJob | caminho escapado, mas sem sandbox/limites de recurso. |
| Análise | 🔴 flawed | AnalisarEfetivoJob | nenhum | nenhum | ocorrência/cache | MilitarEncontradoEvent | palavras e cache globais por data. |
| E-mail individual | 🔴 flawed | evento/UI | SMTP | nenhum | enviado_em | job | IDOR e falta de idempotência. |
| Compilado SAD | 🔴 flawed | pós-análise | SMTP | nenhum | execução | nenhum | mistura todas as OMs e pode ser parcial. |
| Reanálise | 🔴 flawed | comando | opcional HTTP | nenhum | delete/reset | jobs | apaga histórico antes do substituto. |

Os fluxos não completos devem ser corrigidos antes da liberação; detalhes e âncoras estão nas áreas 02 e 04.

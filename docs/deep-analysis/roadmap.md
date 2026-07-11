# Roadmap de prontidão

> **Correção de escopo — 2026-07-11.** Não haverá multi-tenancy: uma OM por VM, Docker, banco, Redis, storage, firewall, administrador e SAD. Itens deste roadmap que pedem segmentação entre OMs são substituídos por duas obrigações: bloquear tecnicamente uma segunda OM na instalação e manter autorização por papel/objeto para ações internas. Consulte [ADR 0001](../adr/0001-uma-instancia-por-om.md).

O sistema deve permanecer em desenvolvimento com dados sintéticos até concluir P0, comprovar instalação limpa, restore e testes da instalação única. O modelo por OM elimina riscos de mistura entre bases, mas não resolve instalação insegura, fonte HTTP, testes perigosos, reanálise, autorização interna ou confiabilidade de filas.

## P0 — antes de produção

| # | Título | Área | Esforço | Por quê |
|---|---|---|---|---|
| P0-1 | Instalação limpa e release identificável | Distribuição | L | Corrigir os defeitos do instalador, instalar dependências/build e registrar versão/commit distribuído. |
| P0-2 | Impedir o modo multi-OM | Arquitetura | M | Manter exatamente uma OM por banco e remover ou bloquear as telas/rotas de segunda unidade. |
| P0-3 | Harness de testes isolado | Testes | M | Impedir `migrate:fresh`, fila ou e-mail contra configuração operacional. |
| P0-4 | Reanálise e CSV não destrutivos | Dados | L | Gerar e validar o substituto antes da troca; preservar ocorrências/notificações ou exigir modo explícito. |
| P0-5 | Ingestão BCA proporcionalmente segura | Integrações | M | Restringir origem corporativa, redirects, tamanho e tipo de PDF; limitar o parser. |
| P0-6 | Fila/e-mail confiáveis e autorização interna | Operação | L | Propagar supressão de e-mail para jobs, deduplicar entregas e validar o objeto nas ações sensíveis. |

## P1 — piloto controlado

| # | Título | Área | Esforço | Por quê |
|---|---|---|---|---|
| P1-1 | Backup, restore e RPO/RTO comprovados | Continuidade | L | PostgreSQL, PDFs e jobs não possuem recuperação validada. |
| P1-2 | CI de regressão dos seis P0 | Qualidade | M | Evita retorno dos bloqueadores já corrigidos. |
| P1-3 | Monitoramento prático do pipeline | Observabilidade | M | Alertar falha de fila, fonte, e-mail, disco e backup, sem criar uma plataforma paralela. |
| P1-4 | Perfil Docker de produção | Infra | M | Definir exposição de portas, reinício, recursos e permissões conforme a rede da OM. |
| P1-5 | Runbooks e documentação vigente | Documentação | M | Instalação, atualização, backup/restore e incidentes precisam refletir o software real. |
| P1-6 | Ciclo de vida de contas e auditoria | Identidade | M | Desativação, revogação de sessões e trilha administrativa; MFA/SSO apenas se a política da OM exigir. |

## P2 — evolução

| # | Título | Área | Esforço | Por quê |
|---|---|---|---|---|
| P2-1 | Decompor componentes e serviços grandes | Arquitetura | XL | Facilita teste, autorização e manutenção. |
| P2-2 | Decidir FTS e metas de escala reais | Performance | L | Índices FTS existem, mas análise varre texto em PHP. |
| P2-3 | Remover código/configuração morta | Dívida técnica | M | Repositories e Spatie não são usados pelo caminho real. |
| P2-4 | Acessibilidade e responsividade | UX | L | Interface não suporta bem celular, teclado ou leitor de tela. |

## Ganhos rápidos

- Usar `force="true"` no phpunit e bloquear testes fora de `testing`.
- Corrigir `MAIL_MAILER` no shell e não escrever URLs BCA vazias no `.env`.
- Transportar a supressão de e-mail da reanálise para os jobs enfileirados.
- Bloquear criação/seleção de uma segunda OM na instalação.

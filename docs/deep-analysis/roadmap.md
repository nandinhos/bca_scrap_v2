# Roadmap de prontidão

> **Correção de escopo — 2026-07-11.** Não haverá multi-tenancy: uma OM por VM, Docker, banco, Redis, storage, firewall, administrador e SAD. Itens deste roadmap que pedem segmentação entre OMs são substituídos por duas obrigações: bloquear tecnicamente uma segunda OM na instalação e manter autorização por papel/objeto para ações internas. Consulte [ADR 0001](../adr/0001-uma-instancia-por-om.md).

O sistema deve permanecer em desenvolvimento com dados sintéticos até concluir P0, comprovar instalação limpa, restore e testes da instalação única. O modelo por OM elimina riscos de mistura entre bases, mas não resolve instalação insegura, fonte HTTP, testes perigosos, reanálise, autorização interna ou confiabilidade de filas.

## P0 — antes de produção

| # | Título | Área | Esforço | Por quê |
|---|---|---|---|---|
| P0-1 | Isolamento fail-closed e autorização por objeto | Segurança/tenancy | XL | Elimina visão global de operador sem OM e IDOR de ocorrência. |
| P0-2 | PDFs privados por rota autorizada | Dados/armazenamento | M | URL previsível no disk público contorna autenticação. |
| P0-3 | Estado, cache, palavras e SAD por OM | Arquitetura multi-OM | XL | Evita resultados e PII cruzados entre unidades. |
| P0-4 | Harness de testes isolado | Testes | M | Impede `migrate:fresh` e efeitos externos contra infraestrutura operacional. |
| P0-5 | Release imutável e instalação limpa reproduzível | Supply chain | L | O fluxo atual executa código mutável e não instala dependências/assets. |
| P0-6 | TLS e segredos obrigatórios | Infra | M | Evita credenciais/cookies em HTTP e senha em terminal. |
| P0-7 | Ingestão segura de PDF | Integrações | L | Fonte HTTP e redirects não validados podem adulterar boletins e explorar parser. |
| P0-8 | Idempotência de jobs e compilado SAD consistente | Filas/e-mail | L | Evita duplicatas, relatório parcial e reenvio indevido. |
| P0-9 | Atualizar dependências vulneráveis | Dependências | M | Há advisories runtime e lock divergente. |
| P0-10 | Preservar unidade em CSV e reanálise | Dados | M | Operações administrativas atuais podem reatribuir ou apagar histórico. |

## P1 — piloto controlado

| # | Título | Área | Esforço | Por quê |
|---|---|---|---|---|
| P1-1 | Backup, restore e RPO/RTO comprovados | Continuidade | L | PostgreSQL, PDFs e jobs não possuem recuperação validada. |
| P1-2 | CI de regressão e segurança multi-OM | Qualidade | L | Sem gate, os P0 podem retornar. |
| P1-3 | Monitoramento de fila, fontes, e-mail e armazenamento | Observabilidade | L | Health atual não verifica o pipeline real. |
| P1-4 | Perfis Docker de produção endurecidos | Infra | L | Redis, portas, restart, recursos e imagens precisam de política. |
| P1-5 | Reescrever runbooks e documentação vigente | Documentação | M | Docs descrevem Livewire 4/Horizon e componentes inexistentes. |
| P1-6 | Ciclo de vida de contas e auditoria | Identidade | M | Falta desativação, revogação de sessões, MFA/SSO e trilha administrativa. |

## P2 — evolução

| # | Título | Área | Esforço | Por quê |
|---|---|---|---|---|
| P2-1 | Decompor componentes e serviços grandes | Arquitetura | XL | Facilita teste, autorização e manutenção. |
| P2-2 | Decidir FTS e metas de escala reais | Performance | L | Índices FTS existem, mas análise varre texto em PHP. |
| P2-3 | Remover código/configuração morta | Dívida técnica | M | Repositories e Spatie não são usados pelo caminho real. |
| P2-4 | Acessibilidade e responsividade | UX | L | Interface não suporta bem celular, teclado ou leitor de tela. |

## Ganhos rápidos

- Bloquear login/operação de não-admin sem `unidade_id`.
- Aplicar `authorize()`/escopo por unidade em todas as actions com `ocorrenciaId`.
- Desabilitar links públicos de BCA até existir download autorizado.
- Usar `force="true"` no phpunit e bloquear testes fora de `testing`.
- Definir `retry_after` acima do maior timeout.
- Parar de imprimir senha e fixar temporariamente o instalador em commit conhecido.
- Adicionar Unidade ao CSV, validar existência e importar em transação.

# ADR 0001 — Uma instalação Docker por Organização Militar

- **Status:** accepted
- **Date:** `2026-07-11`
- **Owner:** Responsável do produto

## Context

Cada OM operará uma VM segregada, com firewall e intranet próprios. A instalação Docker será totalmente local à OM e o BCA é ostensivo, acessível pela infraestrutura corporativa. A cópia local reduz a latência para o militar notificado.

O código atual oferece múltiplas unidades no mesmo banco, mas essa capacidade não é requisito e não deve ser usada como fronteira de segurança.

## Options considered

| Option | Pros | Cons |
|---|---|---|
| **A — uma instalação por OM** | Isola falha, dados, segredos, backup e operação; simples para o contexto atual. | Atualizações e monitoramento precisam ser repetidos por OM. |
| B — uma instalação compartilhada multi-tenant | Centraliza operação. | Exige isolamento lógico, administração central e controles que não são necessários agora. |
| C — serviços centrais distribuídos | Escala e consolidação potenciais. | Complexidade operacional sem requisito atual. |

## Decision

Adotar **A — uma instalação Docker por OM**. Não haverá multi-tenancy, compartilhamento de VM, banco, Redis, volumes, APP_KEY, usuários, SMTP/SAD ou firewall entre OMs.

Recursos globais dentro da instalação, como um BCA por data, cache de análise e uma SAD, são permitidos desde que representem a configuração institucional da OM. Isso não autoriza ações sem controle de papel/objeto nem exposição de dados derivados.

## Consequences

- O modo multi-OM existente no código fica fora de escopo e deve ser desabilitado, removido ou limitado a uma única OM antes da distribuição.
- Riscos de mistura de SAD/cache/palavras entre OMs são rebaixados, pois não há banco compartilhado.
- Continuam obrigatórios: segurança da instalação, integridade da fonte, testes isolados, backups, autorização interna, idempotência de jobs e proteção de PII derivada.
- Microserviços, banco central multi-tenant e object storage compartilhado não são adotados.

## Trigger to revisit

Revisar esta decisão antes de qualquer OM compartilhar VM, banco, Redis, storage, identidade, SAD ou monitoramento com outra OM; ou quando RTO exigir múltiplos nós, o restore não cumprir RPO/RTO, o storage local exceder capacidade, ou SSO/consolidação central se tornar requisito formal.

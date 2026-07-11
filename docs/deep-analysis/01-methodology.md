# Metodologia e cobertura

## Escopo

| Área | Escopo |
|---|---|
| auth-tenancy | sessão, RBAC, Livewire e isolamento por OM |
| fluxos-bca | download, PDF, processamento, análise, reanálise e scheduler |
| dados-pii | schema, histórico, CSV, PII, retenção e integridade |
| jobs-notificacoes | filas, retries, e-mail, SAD e testes de jobs |
| infra-config | Docker, instalação, segredos, rede, TLS, backup e monitoramento |
| frontend-ux | jornadas, feedback, responsividade e acessibilidade |
| docs-arquitetura | aderência entre documentação e implementação, dívida e arquitetura |
| testes-qualidade | harness, CI, build, estilo e dependências |

## Evidências executadas

- Laravel Framework 12.54.1, Livewire 3.7.11 e Spatie Permission 6.24.1 identificados no ambiente.
- Docker Compose subiu PostgreSQL, Redis, PHP, queue, scheduler e Nginx; `/health` respondeu saudável e `schedule:list` listou `buscar-bca-diario`.
- Playwright 1.61.1 validou login e sete rotas autenticadas com HTTP 200 e sem erros de console/página. O Chromium programático precisou rodar fora do sandbox; trata-se de restrição do ambiente, não falha do navegador.
- `npm run build` passou. `composer validate --strict` falhou por lock desatualizado e `vendor/bin/pint --test` apontou sete arquivos.
- `composer audit --locked` retornou 20 advisories em 11 pacotes. `npm audit` retornou 9 pacotes; `npm audit --omit=dev` retornou zero, portanto riscos de toolchain foram separados de runtime.

## Limitações e cuidado com os testes

A execução local fora de Docker falhou por autenticação PostgreSQL. No Docker, 40 testes passaram, 4 foram ignorados e 2 falharam porque a fila/e-mail reais prevaleceram. A refutação confirmou que `php artisan test` limpa variáveis do `.env` pelo Collision, mas `./vendor/bin/pest` continua um caminho documentado que pode combinar variáveis operacionais com `RefreshDatabase` e `migrate:fresh`. Não se deve repetir a suíte direta até isolar o banco.

Foram publicados todos os bloqueadores críticos/altos confirmados e as lacunas estruturais relevantes. Achados médios/baixos foram consolidados por área, sem pretensão de ser uma enumeração de conformidade formal.

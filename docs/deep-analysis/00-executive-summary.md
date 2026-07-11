# Resumo executivo

> **Correção de escopo — 2026-07-11.** A topologia oficial confirmada pelo responsável é uma VM Docker segregada por OM, com firewall, intranet, banco, Redis, volumes, administrador e SAD próprios. Não existe multi-tenancy nem compartilhamento de runtime entre OMs. Portanto, as afirmações deste relatório sobre vazamento *entre OMs* só se aplicariam ao modo multi-OM existente no código, que está fora de escopo e deve ser bloqueado. Cache de BCA, palavras institucionais e SAD únicos passam a ser compatíveis com a instalação local. Continuam válidos os riscos independentes: instalação, testes, TLS, integridade da fonte, reanálise, filas, backups e autorização interna. A decisão é registrada em [ADR 0001](../adr/0001-uma-instancia-por-om.md).

## Veredito

**Não está pronto para produção nem para piloto multi-OM com dados reais.** O risco dominante é quebra de isolamento entre unidades: operadores sem OM recebem consultas globais, ações Livewire aceitam IDs de ocorrências de outras OMs, resultados de palavras-chave e o compilado SAD são globais. A distribuição também expõe PDFs e instala por um canal mutável, sem TLS obrigatório ou build reproduzível.

## Números

| Indicador | Resultado |
|---|---:|
| Áreas auditadas | 8 |
| Leituras especializadas | 8 |
| Alegações críticas/altas refutadas independentemente | 11 |
| Alegações críticas/altas confirmadas | 11 |
| Testes no Docker | 40 aprovados, 4 ignorados, 2 falhas de harness |
| Vulnerabilidades Composer | 20 advisories em 11 pacotes |
| Vulnerabilidades npm completas | 9 pacotes; 2 críticas e 4 altas |

## Cinco riscos principais

1. **Isolamento fail-open e IDOR:** um operador sem unidade vê dados globais; IDs Livewire permitem pré-visualizar PII e enviar e-mails de outra OM. [Evidência](areas/01-auth-tenancy.md)
2. **Artefatos e comunicação cross-OM:** PDFs ficam públicos e o compilado SAD reúne todas as unidades para um e-mail global. [Evidência](areas/02-fluxos-bca.md)
3. **Destruição de dados:** reanálise apaga ocorrências antes do substituto; CSV pode reassociar o efetivo de várias OMs. [Evidência](areas/03-dados-pii.md)
4. **Cadeia de entrega insegura:** instalação executa `main` remoto, expõe senha, usa HTTP e não consegue montar um clone limpo. [Evidência](areas/05-infra-config.md)
5. **Confiabilidade e testes insuficientes:** caminho documentado de Pest direto pode atingir banco operacional; jobs podem duplicar e-mails e compilados. [Evidência](areas/04-jobs-notificacoes.md)

## Pontos sólidos

- Login regenera a sessão e logout invalida sessão/token.
- Rotas administrativas têm middleware de papel.
- O pipeline BCA, scheduler, worker, healthcheck e sete rotas autenticadas responderam no ambiente Docker.
- Há limites configuráveis de tamanho de PDF, FKs básicas e unicidade de ocorrência por BCA/efetivo.
- O build Vite concluiu e o workflow de PII evita alguns vazamentos acidentais.

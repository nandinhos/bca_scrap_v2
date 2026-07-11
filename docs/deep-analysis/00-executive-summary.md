# Resumo executivo

> **Correção de escopo — 2026-07-11.** A topologia oficial confirmada pelo responsável é uma VM Docker segregada por OM, com firewall, intranet, banco, Redis, volumes, administrador e SAD próprios. Não existe multi-tenancy nem compartilhamento de runtime entre OMs. Portanto, as afirmações deste relatório sobre vazamento *entre OMs* só se aplicariam ao modo multi-OM existente no código, que está fora de escopo e deve ser bloqueado. Cache de BCA, palavras institucionais e SAD únicos passam a ser compatíveis com a instalação local. Continuam válidos os riscos independentes: instalação, testes, TLS, integridade da fonte, reanálise, filas, backups e autorização interna. A decisão é registrada em [ADR 0001](../adr/0001-uma-instancia-por-om.md).

## Veredito

**Ainda não está pronto para distribuição a outras OMs.** No modelo confirmado, não existe mistura de dados entre OMs em runtime. Os bloqueadores reais são: instalação limpa que hoje pode abortar ou desconfigurar as fontes BCA, operações destrutivas de reanálise, testes que podem usar configuração operacional, confiabilidade da fila/e-mail e recuperação comprovada. O modo multi-OM que ainda existe no código deve ser indisponibilizado para evitar uso fora do desenho aprovado.

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

1. **Instalação e release:** o instalador atual pode abortar por `MAIL_MAILER` não definido e grava URLs BCA vazias, além de ainda não prover uma instalação limpa reproduzível. [Evidência](02-reconsideracao-pos-integracao.md)
2. **Reanálise destrutiva:** ocorrências são apagadas antes de haver substituto válido e o caminho `--redownload` pode reenviar notificações. [Evidência](02-reconsideracao-pos-integracao.md)
3. **Testes seguros:** a execução direta de Pest pode herdar configuração operacional; é necessário travar banco, fila e e-mail de teste. [Evidência](areas/08-testes-qualidade.md)
4. **Fluxo BCA e PDF:** validar a origem corporativa, redirects, tamanho e assinatura do arquivo antes do parser. [Evidência](areas/02-fluxos-bca.md)
5. **Fila e autorização interna:** jobs precisam transportar a supressão de e-mail e as ações de prévia/envio devem validar o objeto e o papel do operador. [Evidência](02-reconsideracao-pos-integracao.md)

## Pontos sólidos

- Login regenera a sessão e logout invalida sessão/token.
- Rotas administrativas têm middleware de papel.
- O pipeline BCA, scheduler, worker, healthcheck e sete rotas autenticadas responderam no ambiente Docker.
- Há limites configuráveis de tamanho de PDF, FKs básicas e unicidade de ocorrência por BCA/efetivo.
- O build Vite concluiu e o workflow de PII evita alguns vazamentos acidentais.

# Estado atual

## Produto e implantação

O BCA Scrap v2 é instalado como uma solução Docker local em **uma única VM segregada por Organização Militar**. Cada instalação possui firewall, intranet, banco, fila, storage, credenciais, administrador e SAD próprios. Não há operação multi-tenant nem compartilhamento de runtime, banco, Redis, volumes ou identidades entre OMs.

O BCA é um boletim ostensivo disponível na infraestrutura corporativa. A instalação mantém uma cópia local para que o militar notificado consiga abrir rapidamente a publicação a partir da intranet da própria OM.

## Estado de conformidade

- A implementação atual ainda contém telas e campos para múltiplas unidades no mesmo banco; esse modo está **fora de escopo e não suportado** pela decisão [ADR 0001](adr/0001-uma-instancia-por-om.md).
- A auditoria de 2026-07-10 foi reclassificada em 2026-07-11 para a topologia de uma OM por instalação. Consulte [docs/deep-analysis](deep-analysis/README.md).
- Ainda permanecem bloqueadores independentes de multi-tenancy: instalação reproduzível, testes isolados, integridade da fonte BCA/PDF, reanálise, filas/e-mails, backups, segredos e atualização de dependências.

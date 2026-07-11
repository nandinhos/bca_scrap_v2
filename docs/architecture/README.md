# Arquitetura atual e alvo

## Visão

O sistema é um monólito Laravel executado por Docker em uma VM local de uma única OM. PostgreSQL, Redis, storage de BCA, workers, scheduler e Nginx pertencem exclusivamente àquela instalação.

Não há multi-tenancy lógico. A fronteira de segurança é a instalação, sua VM, firewall e intranet.

- [Limites e gatilhos de evolução](boundaries.md)
- [Dados e propriedade](data-model.md)
- [Interfaces operacionais](interfaces.md)
- [Decisão de uma OM por instalação](../adr/0001-uma-instancia-por-om.md)

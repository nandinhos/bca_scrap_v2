# Limites e gatilhos de evolução

| Limite | Forma atual | Regra | Gatilho de revisão |
|---|---|---|---|
| Instalação | Uma VM/Docker por OM | Exatamente uma OM por banco, Redis, storage e SMTP/SAD. | Qualquer requisito de hospedar duas OMs na mesma VM, banco, Redis, storage ou identidade. |
| Aplicação | Monólito Laravel | Web, scheduler e workers usam a mesma base de código, com filas para trabalho demorado. | Necessidade de escalar/deployar processamento de BCA independentemente ou RTO exigir mais de um nó. |
| Banco | PostgreSQL local | Dados de efetivo, ocorrências e configurações pertencem à OM da instalação. | Restore não cumprir RPO/RTO, manutenção saturar host ou política exigir serviço gerenciado. |
| Arquivos BCA | Storage local | PDF ostensivo pode ser servido somente na intranet da OM; dados derivados permanecem autenticados. | Acesso fora da intranet, retenção exceder disco local ou mais de um nó precisar compartilhar arquivos. |
| Identidade | Sessão local | Usuários pertencem à OM da instalação; autorização é por papel e objeto sensível. | SSO/MFA corporativo ou identidade compartilhada entre aplicações. |

O código atual possui estruturas de `unidade_id` e tela de Unidades. Elas não definem uma fronteira suportada; devem ser removidas, bloqueadas ou fixadas à única OM da instalação antes da distribuição.

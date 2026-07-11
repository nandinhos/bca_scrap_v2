# Interfaces operacionais

| Interface | Contrato |
|---|---|
| Instalação | Recebe parâmetros exclusivos da OM: nome/sigla, banco, APP_KEY, SMTP e SAD. Não reutiliza volumes ou segredos de outra instalação. |
| Fonte BCA | Baixa apenas de fontes corporativas permitidas; valida host, redirects, tamanho e tipo antes de persistir/processar. |
| Link de e-mail | Usa URL alcançável na intranet da OM, nunca `localhost`; o acesso ao PDF segue a política de conteúdo ostensivo e rede local. |
| Notificação | Somente papéis autorizados podem pré-visualizar, enviar ou reenviar e-mails; jobs são idempotentes. |
| Backup | Inclui PostgreSQL e arquivos BCA, com restauração testada na própria OM. |

# Quick Install — Para Agentes de IA

> **Objetivo**: instalar o BCA Scrap v2 em 1 comando, sem prompts.
> **Pré-requisito**: o agente já conhece o nome da OM, email e senhas.

## Comando Único (copy-paste)

```bash
export BCA_OM_NAME="<<INSIRA_NOME_DA_OM>>" \
       BCA_OM_SIGLA="<<INSIRA_SIGLA>>" \
       BCA_ADMIN_EMAIL="<<INSIRA_EMAIL_ADMIN>>" \
       BCA_ADMIN_PASSWORD="<<INSIRA_SENHA_ADMIN>>" \
       BCA_DB_PASSWORD="<<INSIRA_SENHA_POSTGRES>>" \
       BCA_HTTP_PORT=18080

curl -fsSL https://raw.githubusercontent.com/nandinhos/bca_scrap_v2/main/install.sh | bash -s -- --non-interactive
```

## Validação Pós-instalação

Após o instalador terminar, o agente deve:

```bash
# 1. Checar containers
docker compose ps

# 2. Checar HTTP
curl -sI http://localhost:18080/login | head -1
# Esperado: HTTP/1.1 200 OK

# 3. Checar admin user foi criado
docker compose exec -T php php artisan tinker --execute="
use App\\Models\\User;
\$u = User::where('email', getenv('BCA_ADMIN_EMAIL'))->first();
echo \$u ? 'admin_exists=true' : 'admin_exists=false';
echo PHP_EOL;
echo \$u->unidade_id ? 'has_unidade=true' : 'has_unidade=false';
echo PHP_EOL;
"
# Esperado: admin_exists=true, has_unidade=true
```

## Resultado Esperado

```
[OK] Pré-requisitos OK
[OK] Repositório clonado
[OK] .env gerado
[OK] Containers em execução
[OK] PostgreSQL pronto
[OK] PHP-FPM pronto
[OK] Banco configurado
[OK] Aplicação respondendo (HTTP 200)

INSTALAÇÃO CONCLUÍDA!
URL: http://localhost:18080
Email: <<INSIRA_EMAIL_ADMIN>>
```

## Próximos Passos (após install bem-sucedido)

1. Importar efetivo via CSV/tinker
2. Configurar SMTP no `.env`
3. Configurar `BCA_PDF_URL`
4. Configurar backup automatizado

## Erros Comuns

| Erro | Causa | Solução |
|------|-------|---------|
| `Porta 18080 em uso` | Outra app usando | `export BCA_HTTP_PORT=19000` |
| `Docker daemon down` | Docker não iniciou | `sudo systemctl start docker` |
| `git clone falhou` | Sem internet | Verificar proxy/firewall |
| `Permission denied` storage | Permissões | `docker compose exec -T php chmod -R 775 storage` |
| `APP_KEY inválida` | Bug raro | `docker compose exec -T php php artisan key:generate --force` |

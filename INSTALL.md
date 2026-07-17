# Guia de Instalação — BCA Scrap v2

> Como instalar o **BCA Scrap v2** em uma Organização Militar (OM) nova.
> Documento destinado a **sysadmins** ou **agentes de IA** que vão fazer o deploy.

---

## 📋 Visão Geral

O **BCA Scrap v2** é um sistema Laravel 12 que busca, processa e analisa Boletins de Comando da Aeronáutica (BCA), identificando militares da sua OM que aparecem nas publicações.

Cada OM tem:
- **Efetivo próprio** (militares cadastrados)
- **Palavras-chave próprias** (para alertas)
- **Histórico isolado** (não vê dados de outras OMs)

---

## 🚀 Instalação Rápida (1 comando)

```bash
curl -fsSL https://raw.githubusercontent.com/nandinhos/bca_scrap_v2/main/install.sh | bash
```

O instalador vai pedir:
- Nome da OM
- Sigla
- Email e senha do administrador
- **Email da SAD/Secretaria** que recebe o compilado diário
- Senha do banco PostgreSQL

Para tudo o resto, há defaults seguros. Você pode ajustar depois no `.env`.

> **Dica:** se você não souber o e-mail da SAD agora, deixe vazio (o sistema
> irá mostrar um aviso e o compilado diário NÃO será enviado até você preencher).
> Apenas os e-mails individuais para os militares continuarão funcionando.

---

## 🛠 Instalação Manual (passo a passo)

Se preferir controle total ou se o `curl | bash` não for permitido no seu ambiente:

### 1. Pré-requisitos

| Software | Versão mínima | Comando de checagem |
|----------|---------------|---------------------|
| Docker | 24+ | `docker --version` |
| Docker Compose | 2.20+ | `docker compose version` |
| Git | 2.30+ | `git --version` |
| OpenSSL | 1.1+ | `openssl version` |
| Bash | 4+ | `bash --version` |

**Porta 18080** precisa estar livre (ou defina outra via `BCA_HTTP_PORT`).

### 2. Clonar repositório

```bash
git clone https://github.com/nandinhos/bca_scrap_v2.git
cd bca_scrap_v2
git checkout main
```

### 3. Configurar `.env`

```bash
cp .env.example .env
```

Edite o `.env` e preencha:

```dotenv
APP_NAME="BCA Scrap - MINHA OM"
APP_URL=http://localhost:18080
APP_KEY=base64:<<INSIRA_32_BYTES_EM_BASE64>>

# Banco de dados
DB_DATABASE=bca_db
DB_USERNAME=bca_user
DB_PASSWORD=<<INSIRA_SUA_SENHA_POSTGRES>>  # Gerar uma senha forte (ex: openssl rand -base64 24)

# Admin inicial (criado pelo seeder)
ADMIN_NAME=Administrador
ADMIN_EMAIL=<<INSIRA_SEU_EMAIL_ADMIN>>
ADMIN_PASSWORD=<<INSIRA_SUA_SENHA_ADMIN>>  # Senha forte (mín. 8 chars)

# Fontes corporativas do BCA
BCA_BASE_URL=http://www.cendoc.intraer/sisbca/consulta_bca/
BCA_ICEA_URL=http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/
```

Para gerar `APP_KEY`, execute `openssl rand -base64 32` e prefixe o resultado com `base64:`.

### 4. Subir containers

```bash
docker compose up -d --build postgres redis storage-init php
```

Aguarde PostgreSQL, Redis e PHP ficarem `healthy`. O serviço `storage-init` deve terminar com status `Exited (0)`; isso é esperado.

### 5. Instalar dependências e compilar a aplicação

```bash
docker compose exec -T php composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
docker compose exec -T php npm ci --no-audit --no-fund
docker compose exec -T php npm run build
```

### 6. Rodar migrations, seeders e demais serviços

```bash
docker compose exec -T php php artisan migrate --force
docker compose exec -T php php artisan db:seed --force
docker compose up -d
```

### 7. Validar storage

```bash
docker compose logs storage-init
docker compose exec -T queue php -r 'var_export(is_writable("/var/www/html/storage/app/public/bcas")); echo PHP_EOL;'
readlink public/storage
```

O resultado esperado é `true` e o link deve apontar para `../storage/app/public`. Não execute `storage:link` sem a opção `--relative`, pois o projeto usa um symlink relativo válido no host e nos contêineres.

### 8. Acessar

Abra `http://localhost:18080` no navegador. Faça login com `ADMIN_EMAIL` e `ADMIN_PASSWORD`.

---

## 🤖 Instalação Automatizada (Agentes de IA / Deploy em massa)

Se você é um agente de IA configurando o BCA para uma OM, use **modo não-interativo**:

```bash
export BCA_OM_NAME="<<INSIRA_NOME_DA_OM>>"
export BCA_OM_SIGLA="<<INSIRA_SIGLA>>"
export BCA_ADMIN_EMAIL="<<INSIRA_EMAIL_ADMIN>>"
export BCA_ADMIN_PASSWORD="<<INSIRA_SENHA_ADMIN_FORTE>>"
export BCA_DB_PASSWORD="<<INSIRA_SENHA_POSTGRES_FORTE>>"

curl -fsSL https://raw.githubusercontent.com/nandinhos/bca_scrap_v2/main/install.sh | bash -s -- --non-interactive
```

O instalador vai:
1. Detectar todas as variáveis já setadas
2. Pular todos os prompts
3. Instalar sem interação

### Variáveis de ambiente aceitas

| Variável | Default | Descrição |
|----------|---------|-----------|
| `BCA_OM_NAME` | — | Nome da OM (obrigatório) |
| `BCA_OM_SIGLA` | = OM_NAME | Sigla curta (ex: OMA) |
| `BCA_OM_CODE` | gerado | Código ICAO/SISCOMEX |
| `BCA_ADMIN_NAME` | "Administrador" | Nome do admin |
| `BCA_ADMIN_EMAIL` | — | Email do admin (obrigatório) |
| `BCA_ADMIN_PASSWORD` | gerado | Senha (mín. 8 chars) |
| `BCA_DB_PASSWORD` | gerado | Senha do PostgreSQL |
| `BCA_HTTP_PORT` | 18080 | Porta do nginx |
| `BCA_INSTALL_DIR` | ./bca_scrap_v2 | Onde instalar |
| `BCA_SKIP_PREREQ` | false | Pular checagem de pré-requisitos |
| `BCA_BASE_URL` | CENDOC | Fonte principal do BCA |
| `BCA_ICEA_URL` | ICEA | Fonte alternativa do BCA |

---

## 🔧 Pós-instalação

### Cadastrar efetivo da OM

1. Acesse `http://localhost:18080/efetivo`
2. Clique em **+ Novo**
3. Preencha:
   - SARAM (7 dígitos, ex: 1234567)
   - Nome de Guerra
   - Nome Completo
   - Posto/Graduação
   - Email institucional (opcional)
4. Marque **Ativo** (default) e desmarque **Oculto**

> **Dica para importação em massa:** via `php artisan tinker` ou comando customizado, importe via CSV. Estrutura: `saram,nome_guerra,nome_completo,posto,email,ativo,oculto`.

### Configurar SMTP (opcional)

Edite `.env`:

```dotenv
# Exemplo para Gmail com Senha de App (recomendado):
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME="<<INSIRA_SEU_EMAIL_SMTP>>"
MAIL_PASSWORD="<<INSIRA_SUA_SENHA_DE_APP>>"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="<<INSIRA_EMAIL_REMETENTE>>"
MAIL_FROM_NAME="<<INSIRA_NOME_REMETENTE>>"
# IMPORTANTE: substitua os placeholders <<...>> pelos seus valores reais.
# NAO commitar o .env preenchido - ele ja esta no .gitignore.
```

Reinicie o queue:
```bash
docker compose restart queue
```

### Configurar URL do BCA (opcional)

As URLs corporativas CENDOC/ICEA são gravadas no `.env` pelo instalador. Se sua OM usa endpoints diferentes, sobrescreva:

```dotenv
BCA_BASE_URL=http://www.cendoc.intraer/sisbca/consulta_bca/
BCA_ICEA_URL=http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/
```

Após qualquer alteração no `.env`, recrie os serviços que consomem essas variáveis:

```bash
docker compose up -d --force-recreate php queue scheduler
docker compose exec -T php php artisan config:clear
```

O sistema vai baixar e processar automaticamente nos horários agendados.

---

## 📊 Estrutura Multi-OM

Cada instalação do BCA Scrap é **isolada por OM**:

```
┌─────────────────────────────────────────────┐
│  Instalação A — OM-ALFA                     │
│  ┌──────────────┐                            │
│  │ Efetivo: 58  │                            │
│  │ Palavras: 9  │                            │
│  │ Usuários: 3  │                            │
│  └──────────────┘                            │
│  DB: bca_om_alfa                             │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│  Instalação B — OM-BRAVO                     │
│  ┌──────────────┐                            │
│  │ Efetivo: 230 │                            │
│  │ Palavras: 15 │                            │
│  │ Usuários: 8  │                            │
│  └──────────────┘                            │
│  DB: bca_om_bravo                            │
└─────────────────────────────────────────────┘
```

**Isolamento:**
- Cada OM tem sua própria OM no `users.unidade_id`
- Operadores só veem efetivo e ocorrências da sua OM
- Admins podem ver todas as OMs (se houver mais de uma cadastrada)

---

## 🔄 Atualização

```bash
cd bca_scrap_v2
git pull origin main
docker compose up -d --build
docker compose exec -T php composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
docker compose exec -T php npm ci --no-audit --no-fund
docker compose exec -T php npm run build
docker compose exec -T php php artisan migrate --force
docker compose exec -T php php artisan config:clear
```

---

## 🆘 Troubleshooting

### Containers não sobem

```bash
docker compose logs -f
```

Procure por erros de porta, permissão, ou falta de memória. Mínimo recomendado: **2 GB RAM**.

### "Permission denied" em storage

```bash
docker compose run --rm storage-init
docker compose up -d
docker compose exec -T queue php -r 'var_export(is_writable("/var/www/html/storage/app/public/bcas")); echo PHP_EOL;'
```

Não use `chown` dentro de `php` ou `queue`: esses serviços executam sem privilégios. O `storage-init` é o único responsável por ajustar o volume para UID/GID `1000:1000`.

### PDF encontrado, mas processamento falha

Consulte o erro real:

```bash
docker compose logs --tail=200 queue
tail -n 200 storage/logs/laravel.log
docker compose exec -T php php artisan queue:failed
```

Se houver `PDF file not found`, execute a correção de storage acima e repita a busca. A gravação agora é verificada; uma falha de filesystem faz o job falhar explicitamente em vez de registrar “BCA não encontrado”.

### Esqueci a senha do admin

```bash
docker compose exec -T php php artisan tinker
>>> User::where('email', '<<INSIRA_EMAIL_ADMIN>>')->first()->update(['password' => bcrypt('<<INSIRA_NOVA_SENHA>>')])
```

### Resetar banco (APAGA TUDO)

```bash
docker compose exec -T php php artisan migrate:fresh --seed --force
```

### Mudar porta do nginx

Edite `docker-compose.yml`:
```yaml
nginx:
  ports:
    - "8080:80"  # era 18080
```

E `APP_URL` no `.env`:
```dotenv
APP_URL=http://localhost:8080
```

Depois:
```bash
docker compose down && docker compose up -d
```

---

## 📞 Suporte

- **Issues**: https://github.com/nandinhos/bca_scrap_v2/issues
- **Guia de configuração**: [docs/configuration.md](docs/configuration.md)
- **Documentação completa**: [docs/index.md](docs/index.md)

---

## 📝 Próximos Passos Recomendados

1. ✅ Trocar senha padrão do admin
2. ✅ Configurar SMTP para notificações
3. ✅ Cadastrar efetivo da OM
4. ✅ Configurar backup do banco (cron + pg_dump)
5. ✅ Configurar HTTPS com proxy reverso (nginx/Caddy)
6. ✅ Documentar procedimentos internos da OM

# Guia de Configuração — BCA Scrap v2

Referência completa das variáveis de ambiente e da configuração necessária para colocar o **BCA Scrap v2** em operação em uma nova Organização Militar (OM).

Toda a configuração vive no arquivo `.env` (copie de `.env.example`). O instalador `install.sh` gera esse arquivo automaticamente; este guia serve para ajustes finos e instalação manual.

---

## 1. Variáveis de ambiente

### Aplicação

| Variável | Default | Obrigatória | Descrição |
|---|---|---|---|
| `APP_NAME` | `Laravel` | Recomendada | Nome exibido na interface, no título e nos e-mails. Ex.: `"BCA Scrap - OM-ALFA"`. |
| `APP_ENV` | `local` | Sim | Ambiente: `local`, `production`, `testing`. Em produção use `production`. |
| `APP_KEY` | — | Sim | Chave de criptografia. Gerada pelo `install.sh` ou via `php artisan key:generate`. |
| `APP_DEBUG` | `true` | Sim | **`false` em produção.** |
| `APP_URL` | `http://localhost:18080` | Sim | URL base da aplicação. |

### Administrador inicial (lido pelo `UserSeeder`)

| Variável | Default | Obrigatória | Descrição |
|---|---|---|---|
| `ADMIN_NAME` | `Administrador` | Não | Nome do usuário admin criado no primeiro seed. |
| `ADMIN_EMAIL` | `admin@fab.mil.br` | Sim | E-mail de login do admin. |
| `ADMIN_PASSWORD` | `changeme123` | Sim | **Troque por uma senha forte.** |

### Organização Militar inicial (lido pelo `UnidadeSeeder`)

| Variável | Default | Obrigatória | Descrição |
|---|---|---|---|
| `OM_NAME` | `EXEMPLO1` | Sim | Nome da OM. Ex.: `"OM-ALFA"`. |
| `OM_SIGLA` | `EXEMPLO1` | Sim | Sigla **única** da OM. |
| `OM_CODE` | `00001` | Não | Código da OM (ICAO/SISCOMEX, se aplicável). |

### Fontes do BCA e envio do compilado

| Variável | Default | Obrigatória | Descrição |
|---|---|---|---|
| `BCA_BASE_URL` | `http://www.cendoc.intraer/sisbca/consulta_bca/` | Não | Fonte principal do BCA (intranet FAB). |
| `BCA_ICEA_URL` | `http://www.icea.intraer/app/arcadia/busca_bca/boletim_bca/` | Não | Fonte alternativa (fallback). |
| `BCA_SAD_EMAIL` | *(vazio)* | Não | E-mail que recebe o compilado diário. **Vazio = envio desativado.** |
| `BCA_SEARCH_CHUNK_SIZE` | `10` | Não | Requisições por lote na busca paralela. |
| `BCA_SEARCH_TIMEOUT` | `10` | Não | Timeout (s) por requisição. |
| `BCA_SEARCH_RETRY` | `2` | Não | Tentativas em caso de falha. |
| `BCA_MAX_PDF_SIZE_MB` | `50` | Não | Tamanho máximo do PDF do BCA. |

### E-mail (SMTP)

| Variável | Default | Descrição |
|---|---|---|
| `MAIL_MAILER` | `smtp` | `smtp` (produção), `log` (dev), `array` (testes). |
| `MAIL_HOST` | `smtp.fab.mil.br` | Servidor SMTP. |
| `MAIL_PORT` | `587` | Porta. |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | — | Credenciais SMTP. |
| `MAIL_ENCRYPTION` | `tls` | Criptografia. |
| `MAIL_FROM_ADDRESS` | — | Remetente. |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Nome do remetente (segue `APP_NAME`). |

### Banco de dados e infraestrutura

| Variável | Default (Docker) | Descrição |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | O sistema depende de PostgreSQL (FTS/`unaccent`). |
| `DB_HOST` | `postgres` | Nome do serviço no Docker. |
| `DB_PORT` | `5432` | Porta. |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `bca_db` / `bca_user` / — | Credenciais. |
| `REDIS_HOST` | `redis` | Cache e filas. |
| `BCA_HTTP_PORT` | `18080` | Porta HTTP do nginx (usada pelo `install.sh`). |

---

## 2. Rede corporativa e VPN

O download dos BCA depende de serviços internos da FAB que **não são acessíveis pela internet pública**:

- `http://www.cendoc.intraer/...` (CENDOC)
- `http://www.icea.intraer/...` (ICEA)

Para o sistema baixar os boletins, a máquina que roda os containers precisa estar **dentro da rede corporativa da FAB** ou conectada por **VPN corporativa**.

**Valide o acesso antes de operar:**

```bash
# De dentro da rede/VPN, o host precisa resolver e responder:
curl -I http://www.cendoc.intraer/sisbca/consulta_bca/
# Se não resolver o nome ou der timeout, a máquina não está na rede FAB/VPN.
```

A interface web (cadastro de efetivo, palavras-chave, histórico) funciona **sem** acesso à intranet — apenas o download/processamento automático dos BCA exige a rede corporativa. É possível, portanto, operar a gestão em qualquer rede e reservar a máquina de processamento para a rede FAB/VPN.

---

## 3. Como a configuração vira a primeira OM

A cadeia de inicialização é:

```
install.sh  →  .env (OM_*, ADMIN_*)  →  php artisan migrate --seed
                                              │
                                              ├─ UnidadeSeeder → cria a OM inicial a partir de OM_NAME/OM_SIGLA/OM_CODE
                                              └─ UserSeeder    → cria o admin a partir de ADMIN_EMAIL/ADMIN_PASSWORD
```

- O `UnidadeSeeder` é **idempotente** (`firstOrCreate` por sigla): rodar o seed de novo não duplica a OM.
- O `AdminDevSeeder` cria um admin de conveniência (`admin@bca.local` / `password`) **apenas em ambiente `local`/`testing`** — nunca em produção.
- Para adicionar novas OMs depois, entre como admin e use a tela **Unidades** (`/unidades`).

---

## 4. Pós-instalação

### Cadastrar o efetivo da OM

1. Faça login como admin e acesse `/efetivo`.
2. **+ Novo** e preencha: SARAM, Nome de Guerra, Nome Completo, Posto/Graduação, e-mail institucional (opcional).
3. Marque **Ativo**; use **Oculto** para militares que não devem receber notificação.

> **Importação em massa:** via `php artisan tinker` ou comando customizado, importe de um CSV com as colunas `saram,nome_guerra,nome_completo,posto,email,ativo,oculto`.

### Cadastrar palavras-chave

Acesse `/palavras-chave` e cadastre termos de interesse (programas, sistemas, assuntos). Marque como **ativa** as que devem participar do matching. As palavras são isoladas por OM.

### Configurar o envio do compilado para a SAD

Defina `BCA_SAD_EMAIL` no `.env` com o e-mail real da SAD da sua OM e reinicie a fila:

```bash
docker compose restart queue
```

Se `BCA_SAD_EMAIL` ficar vazio, o compilado diário simplesmente não é enviado (o resto do sistema continua funcionando).

---

## 5. Checklist de produção

1. `APP_ENV=production` e `APP_DEBUG=false`.
2. Senha forte no admin e no PostgreSQL.
3. `APP_KEY` gerada (nunca reutilizar a de outra instalação).
4. SMTP configurado (ou `MAIL_MAILER=log` se ainda não houver e-mail).
5. Acesso à rede FAB/VPN validado (seção 2).
6. Backup do banco agendado (`pg_dump`).
7. HTTPS via proxy reverso (nginx/Caddy) à frente da porta 18080.

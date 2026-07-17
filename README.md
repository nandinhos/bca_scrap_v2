# BCA Scrap v2

Sistema web para **buscar, processar e analisar os Boletins de Comando da Aeronáutica (BCA)** e alertar automaticamente quando um militar do efetivo da sua Organização Militar (OM) é citado numa publicação.

Construído em **Laravel 12 + TALL Stack** (Tailwind, Alpine.js, Livewire) sobre PostgreSQL e Redis, empacotado em Docker. Cada OM instala, configura e opera a sua própria instância isolada.

---

## ✨ O que o sistema faz

- **Baixa e processa os BCA** publicados na rede corporativa da FAB (CENDOC / ICEA), extraindo o texto do PDF.
- **Casa o texto contra o efetivo** cadastrado, por SARAM e por nome (busca full-text com `unaccent`), com alta precisão.
- **Notifica por e-mail** o militar citado e envia um **compilado diário para a SAD**.
- **Palavras-chave** configuráveis para destacar assuntos de interesse (ex.: nomes de programas, sistemas de armas).
- **Isolamento por instalação**: banco, Redis, PDFs, credenciais e usuários pertencem a uma única OM.
- Processamento **assíncrono** (filas) para manter a interface fluida.

---

## 🧱 Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 12 · PHP 8.2+ |
| Frontend | Livewire · Tailwind CSS · Alpine.js |
| Banco | PostgreSQL 16 (FTS com `unaccent`) |
| Cache/Filas | Redis 7 |
| Infra | Docker + Docker Compose |
| Testes | Pest / PHPUnit |

---

## 🚀 Instalação rápida (1 comando)

Em uma máquina com **Docker** e **Git**, dentro da rede corporativa (ou via VPN):

```bash
curl -fsSL https://raw.githubusercontent.com/nandinhos/bca_scrap_v2/main/install.sh | bash
```

O instalador pergunta o nome/sigla da OM, o e-mail e a senha do administrador e a senha do banco, gera o `.env`, prepara permissões e symlink do storage, compila a aplicação e roda migrations e seeders. Ao final, o sistema está no ar em `http://localhost:18080`.

> Guia completo (manual, não-interativo e troubleshooting): **[INSTALL.md](INSTALL.md)**
> Referência de todas as variáveis de configuração: **[docs/configuration.md](docs/configuration.md)**

---

## ⚙️ Configuração essencial

Toda a configuração é feita por variáveis de ambiente no `.env` (veja `.env.example`). As principais:

| Variável | Para quê | Obrigatória |
|---|---|---|
| `APP_NAME` | Nome exibido na interface e nos e-mails (ex.: `"BCA Scrap - OM-ALFA"`) | Recomendada |
| `OM_NAME` / `OM_SIGLA` / `OM_CODE` | OM inicial criada no primeiro seed | Sim (via install.sh) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Administrador inicial | Sim |
| `BCA_BASE_URL` / `BCA_ICEA_URL` | Fontes do BCA na intranet FAB | Default fornecido |
| `BCA_SAD_EMAIL` | E-mail que recebe o compilado diário (vazio = desativado) | Não |
| `MAIL_*` | Servidor SMTP para as notificações | Para enviar e-mails |
| `DB_*` | Credenciais do PostgreSQL | Sim (via install.sh) |

> **Rede corporativa / VPN:** as fontes do BCA (`cendoc.intraer`, `icea.intraer`) só são acessíveis dentro da rede da FAB ou por VPN corporativa. Detalhes e como validar o acesso: [docs/configuration.md](docs/configuration.md#rede-corporativa-e-vpn).

---

## 🔀 Modelo operacional

O modo suportado é **uma instalação Docker por OM**, conforme a [ADR 0001](docs/adr/0001-uma-instancia-por-om.md). Cada VM possui seus próprios:

- PostgreSQL e Redis;
- volume de PDFs dos BCA;
- credenciais, administrador e SAD;
- efetivo, palavras-chave e histórico.

O código ainda contém estruturas de `unidade_id`, mas hospedar duas OMs na mesma VM ou banco está fora do escopo suportado. Admin e operador são papéis internos da OM instalada.

Principais telas: `/dashboard` (busca), `/historico`, `/palavras-chave`, e — restritas a admin — `/efetivo`, `/usuarios` e `/execucoes`.

---

## 🧪 Testes

```bash
docker compose exec -T php php artisan test
```

Detalhes de estratégia e exemplos: [docs/testing.md](docs/testing.md).

---

## 📚 Documentação

- **[INSTALL.md](INSTALL.md)** — instalação passo a passo, modo não-interativo e troubleshooting
- **[docs/configuration.md](docs/configuration.md)** — todas as variáveis de ambiente e rede corporativa/VPN
- **[docs/index.md](docs/index.md)** — índice da documentação técnica
- **[docs/architecture.md](docs/architecture.md)** — arquitetura (Services, Repositories, Events)
- **[docs/database.md](docs/database.md)** — modelo de dados e full-text search
- **[docs/docker-infrastructure.md](docs/docker-infrastructure.md)** — containers e infraestrutura
- **[docs/queues-and-jobs.md](docs/queues-and-jobs.md)** — filas e processamento assíncrono
- **[docs/livewire-components.md](docs/livewire-components.md)** — componentes de interface
- **[docs/performance-optimization.md](docs/performance-optimization.md)** — cache e desempenho
- **[docs/commands-guide.md](docs/commands-guide.md)** — referência de comandos

---

## 🆘 Suporte

Dúvidas, bugs ou sugestões: abra uma **issue** em https://github.com/nandinhos/bca_scrap_v2/issues.

---

## 📄 Licença

Uso interno / proprietário. Distribua apenas dentro do contexto autorizado.

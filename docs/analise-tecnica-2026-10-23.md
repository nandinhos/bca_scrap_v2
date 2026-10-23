# Análise Técnica Completa — BCA Scrap v2

> Data: 23/10/2026 · Método: 8 frentes read-only em paralelo (infra, pipeline BCA, e-mail/fila, dados, web/auth, testes/CI, segurança, observabilidade) + crítico adversarial que reabriu as fontes e reclassificou severidades.
> Status da análise: **parcial** — as evidências detalhadas por frente foram retidas nos resultados do workflow; este documento consolida os achados confirmados nas notas de handoff de cada frente e na síntese. Nenhum arquivo de código foi modificado por esta análise.

---

## 1. Veredito executivo

A espinha dorsal é sólida (pipeline de jobs, defesa anti-reenvio, agendamento, integridade referencial básica), mas o sistema é **cego operacionalmente**: ficou 13 dias fora do ar (32 dias de sinais ignorados, ~230 falhas em `failed_jobs`) sem nenhum alerta ativo. Os maiores riscos não são de código do dia a dia — são **panes silenciosas** (tudo vira `sem_bca` ou só `Log::error`) e **fronteiras fail-open** (autorização por unidade, PDFs públicos, IDOR).

---

## 2. Achados por severidade

### ALTO

| # | Frente | Achado | Correção sugerida |
|---|---|---|---|
| A1 | Infra | `install.sh:404` executa `docker compose down -v` em reexecução — apaga volumes (banco + PDFs) | Remover o `down -v` ou exigir flag/confirmação explícita |
| A2 | Web/Auth | Autorização por unidade é **fail-open** para `unidade_id` nula, agravado porque a UI de usuários nunca atribui unidade — usuário sem unidade enxerga tudo | Fail-closed: sem unidade = sem acesso a dados de outras unidades; atribuir unidade na gestão de usuários |
| A3 | Web/Auth | **IDOR** em `BuscaBca`: preview e envio de e-mail por `ocorrenciaId` sem checar unidade do usuário | Autorizar ocorrência contra a unidade do usuário antes de exibir/enviar |
| A4 | Pipeline | `sem_bca` é indistinguível de pane (CENDOC/ICEA fora, DNS Intraer, credencial): `BaixarBcaJob` (handle, ~l.43-55) registra o mesmo status para "não publicado" e "fonte inacessível" | Status `falha_fonte` separado + alerta após `sem_bca` consecutivo em dia útil |
| A5 | Email/Fila | `suppress_emails` é `config()` em memória no comando retroativo — não serializa para o worker da fila; o desenho seguro (parâmetro do Job) foi perdido | Virar parâmetro serializado do Job ou flag persistida, nunca config em memória |
| A6 | Email/Fila | Compilado SAD pode sair parcial/vazio: despachado ao fim da análise, sem encadeamento com os individuais; sem unicidade por BCA | `Bus::chain` ou despacho pelo último individual; `ShouldBeUnique` por BCA |
| A7 | Segurança | PDFs de BCA em disco público, servidos pelo NGINX sem autenticação | Disco privado + download via rota autenticada |
| A8 | Segurança | pgAdmin com senha padrão no perfil `dev` (ALTO condicional: só sobe com `--profile dev`) | Senha forte obrigatória via env, sem default utilizável |
| A9 | Testes/CI | CI só tem `pii-guard`; nenhum workflow roda a suíte (o gate local CEH supre, mas não protege PRs de terceiros) | Workflow de CI com `php artisan test` + Pint + `composer audit` |
| A10 | Dados | Deletes em cascata sem soft-delete: apagar efetivo/unidade apaga ocorrências e histórico sem auditoria | Soft-delete + trilha de auditoria nas entidades críticas |
| A11 | Observabilidade | Detecção 100% passiva: log em arquivo, `/health` que ignora fila/scheduler/BCA-do-dia e ninguém chama, tela `/execucoes` atrás de login admin | Health completo + monitor externo (ver §4) |

### MÉDIO

| # | Frente | Achado | Correção sugerida |
|---|---|---|---|
| M1 | Web/Auth | `/health` e `/metrics` públicos vazando mensagens de exceção | Atrás de autenticação/token |
| M2 | Web/Auth | Login sem throttle (força bruta) + HTTP sem cookie `Secure` (sem TLS) | Throttle no login; TLS + `SESSION_SECURE_COOKIE` |
| M3 | Pipeline | Sem calendário de feriados FAB: feriado e "não publicado" têm o mesmo tratamento; BCA publicado após 17h só entra no dia seguinte (e para a data errada) | Calendário de feriados + lookback de 1 dia útil na rotina |
| M4 | Pipeline | Republicação/correção de BCA pela ICEA não poda ocorrências antigas | Ao reprocessar, reconciliar ocorrências do BCA |
| M5 | Email/Fila | Ocorrência de militar oculto/sem e-mail fica "pendente" eternamente, sem status terminal; `SAD_EMAIL` nulo gera só warning | Status terminal explícito + alerta de configuração |
| M6 | Email/Fila | `EnviarEmailNotificacaoJob` não tem guarda `foiEnviado()` no `handle()` nem `ShouldBeUnique` — dispatch duplicado = e-mail duplicado | Guarda no `handle()` + `ShouldBeUnique` por ocorrência |
| M7 | Dados | Palavra-chave com `UNIQUE` global contradiz o modelo multi-OM | `UNIQUE(palavra, unidade_id)` |
| M8 | Dados/Infra | Sem retenção: `texto_completo` + `tsvector` gerado + PDFs crescem sem TTL; `laravel.log` chegou a 256 MB sem rotação (canal `daily` existe e não é usado) | Política de retenção + `LOG_LEVEL=info` + canal diário com `days` |
| M9 | Infra | Sem limites de CPU/memória nos serviços; imagens `postgres:16-alpine`, `redis:7-alpine`, `alpine:3.20`, `pgadmin:latest` flutuantes | Pinar versões + `mem_limit`/reservas |
| M10 | Infra | Sem backup automatizado comprovado de `pgdata` + `bcas_storage` (restore nunca testado) | `pg_dump` agendado + cópia do storage + restore testado |
| M11 | Testes | Descoberta de URL do BCA (CENDOC/ICEA, brute-force, cache negativo) sem teste — justamente o código que já quebrou por formato de data | Testes com `Http::fake()` para os cenários de descoberta (regra já existe no AGENTS.md) |
| M12 | Web/Auth | `spatie/laravel-permission` instalado e sem uso — autorização é caseira | Adotar o pacote ou removê-lo |
| M13 | Pipeline | Relógio do host é pane silenciosa: `paraHoje()` depende de `America/Sao_Paulo`; host em UTC sem NTP vigiado | Clock-skew no health + NTP monitorado |

### BAIXO / MELHORIA

- B1 | Testes: 19 arquivos / ~1.371 linhas — cobertura razoável em jobs/mail/livewire, fraca em download/scheduler/CSV. Adicionar testes de importação CSV e de feriado no scheduler.
- B2 | Docs: `docs/STATUS.md` e guias divergem do código em pontos operacionais; runbook de incidente existe (set/2026) mas sem o cenário "sistema fora do ar por dias".
- B3 | Email: pontos fortes a manter — defesa anti-reenvio (`BcaAnalysisService` l.101-121), timeout SMTP (20s) < timeout do job (60s), adendo parametrizado com escape e testes.
- B4 | Infra: `storage-init` e healthchecks de dependência estão corretos; manter o padrão de `depends_on: service_healthy`.

---

## 3. Panes ocultas (o que quebra sem fazer barulho)

| Pane | Por que é silenciosa | Como detectar |
|---|---|---|
| Fonte CENDOC/ICEA fora ou DNS Intraer quebrado | Vira `sem_bca`, igual a "não publicado" | Status `falha_fonte` + alerta de `sem_bca` consecutivo em dia útil |
| Credencial SMTP expirada | E-mails falham no job; ninguém olha `failed_jobs` (230 falhas históricas) | Contador de `failed_jobs` no health + alerta |
| Fila ou scheduler mortos | Jobs das etapas 2–4 só gravam `Log::error` | Heartbeat de queue/scheduler no `/health` |
| Disco enchendo | log (256 MB) + tsvector + PDFs + tabelas sem TTL somam em silêncio | Health de disco/volume com limiar |
| Relógio do host dessincronizado | Agenda e "BCA de hoje" erram a data sem erro | Clock-skew no health |
| Republicação de BCA corrigido | Ocorrências velhas permanecem | Hash do PDF por data + reconciliação |

**Como se detecta hoje:** um humano sente falta do e-mail, abre `/execucoes` (admin) ou faz grep no `laravel.log`. Foi assim por 13 dias.

---

## 4. Roadmap priorizado

### Agora / esta semana
1. `install.sh`: remover `down -v` destrutivo (A1).
2. Fail-closed de unidade + correção do IDOR (A2, A3).
3. `suppress_emails` como parâmetro serializado do Job (A5).
4. Throttle no login; `/health` e `/metrics` atrás de auth (M1, M2).
5. Ligar canal `daily` com retenção + `LOG_LEVEL=info` (M8) — **aprovado pelo responsável, em aplicação**.
6. Contador de `failed_jobs` visível + backup `pg_dump`/storage com restore testado (M5, M10).

### Curto prazo (2–4 semanas)
- Chain/unique nos e-mails + status terminal de pendentes (A6, M5, M6).
- Lookback + calendário de feriados FAB (M3); reconciliação na republicação (M4).
- Health completo (fila, scheduler, disco, SMTP, CENDOC, BCA-do-dia, NTP) com monitor externo chamando (A4, A11, M13).
- CI com testes/Pint/audit (A9); testes de descoberta de URL e CSV (M11, B1).
- TLS + cookie Secure (M2); PDFs em disco privado (A7); `UNIQUE(palavra, unidade)` (M7).

### Estrutural
- Soft-delete + auditoria (A10); índices/retenção/particionamento de `bcas`/`ocorrencias`.
- Remover `tsvector` gerado ou adotar FTS de verdade com consulta que o use.
- HA/DR: segundo host ou restore ensaiado com RPO/RTO definidos; segmentação de rede; imagem imutável.
- Reescrever `docs/STATUS.md`/runbooks a partir desta análise; coverage + PHPStan no CI.

---

## 5. O que está bom e deve ser mantido

Pipeline em jobs com retry/backoff, `depends_on` com healthcheck, `restart: unless-stopped` (corrigido em 23/10), defesa anti-reenvio na análise, timeout de SMTP abaixo do timeout do job, testes de jobs/mail/livewire com `RefreshDatabase` e guarda anti-banco-de-produção (`tests/TestCase.php`), e o comando `bca:retroativo` com supressão de e-mails por padrão.

## 6. Escopo não verificado nesta análise

Evidências detalhadas linha a linha de cada frente ficaram nos resultados do workflow (submissões grandes, retidas na sessão); as severidades do crítico foram adotadas onde ele reclassificou (pgAdmin, CI, login/health, `sem_bca`, compilado SAD). Nenhum teste foi executado pelas frentes (read-only); a suíte (75 testes) foi validada separadamente no mesmo dia.

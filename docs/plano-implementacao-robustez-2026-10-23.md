This is a plan, not a special mode; I haven’t started implementation. Reply `go` to execute this plan, or tell me what to change.

## Goal

Fechar os GAPs, débitos e panes ocultas da [analise-tecnica-2026-10-23.md](docs/analise-tecnica-2026-10-23.md) no `bca_scrap_v2`, em fatias pequenas, cada uma com commit próprio, teste focado e rollback — sem reescrever o que já funciona.

## Metodologia Ponytail (regras que valem para todas as unidades)

- **Preguiça sênior:** a solução mais curta que resolve é a certa. Reusar stdlib/Laravel/existente antes de criar qualquer coisa nova. Nada de interface com uma implementação só, factory sem necessidade ou dependência nova sem prova.
- **Um diff por unidade**, na ordem das dependências. Tocar só os arquivos da unidade; nada de refatoração oportunista vizinha.
- **Simplificação deliberada** que possa quebrar em escala ganha comentário `ponytail: <limite>, <caminho de upgrade>`.
- **Teste antes do verde:** para bug, primeiro o teste que falha pelo motivo certo; depois a menor correção. Config pura valida com o comando direto, sem teste novo.
- **Gate por unidade:** suíte focada verde + `git diff` auditado antes do commit. Suíte completa uma vez ao fim de cada fase.
- **Produção:** nenhuma unidade muda comportamento de e-mail/agendamento sem janela combinada; Jobs alterados exigem `docker compose restart queue` (regra do AGENTS.md).

## Success Criteria

1. Nenhum `down -v` acidental possível; nenhum dado entre unidades vazando por UI ou API.
2. Pane de fonte, fila, scheduler, disco, SMTP ou relógio gera alerta ativo em até 1 ciclo de health — nunca mais 13 dias de silêncio.
3. E-mail duplicado impossível por construção (guarda + unicidade), compilado sempre completo ou explicitamente pendente.
4. Feriado FAB e BCA tardio (após 17h) tratados sem intervenção manual.
5. CI rodando a suíte a cada push/PR; backup com restore comprovado.

## Legenda

- **Criticidade:** CRÍTICA (perda de dados, vazamento ou pane silenciosa total) · ALTA (dano provável em produção) · MÉDIA (degradação/custo) · BAIXA (higiene).
- **Prioridade:** P0 (esta semana) · P1 (2–4 semanas) · P2 (estrutural).
- **Esforço:** PP (< ½ dia) · P (½–1 dia) · M (2–3 dias) · G (> 3 dias).

---

## Fase 0 — Já feito (não replanejar)

`LOG_LEVEL=info` aplicado e validado em 23/10; pins PHP 8.4.27/NGINX 1.30.6; `restart: unless-stopped` + teste; log zerado com arquivo compactado.

## Fase 1 — P0: parar de sangrar (ordem obrigatória)

| ID | Unidade (esforço) | Criticidade | Dependências |
|---|---|---|---|
| 1.1 | Remover `down -v` do `install.sh` (PP) | CRÍTICA | — |
| 1.2 | Fail-closed de unidade + IDOR (P) | CRÍTICA | — |
| 1.3 | `suppress_emails` serializado no Job (P) | ALTA | — |
| 1.4 | Throttle login + health protegido (PP) | ALTA | — |
| 1.5 | Contador `failed_jobs` visível (PP) | ALTA | 1.4 |
| 1.6 | Backup + restore testado (P) | CRÍTICA | — |

### 1.1 `install.sh` sem destruição
- **Mudança:** trocar o `down -v` por `down` simples; destruição de volumes só com flag explícita `--destruir` + confirmação digitada. Ponytail: nenhum framework de "modo seguro", uma condicional basta.
- **Validação:** `bash -n install.sh`; grep sem `down -v` fora da flag; rodar o trecho de parada em ambiente de teste sem tocar volumes (`docker volume ls` antes/depois).
- **Rollback:** reverter o commit.

### 1.2 Fail-closed de unidade + IDOR
- **Mudança:** usuário sem `unidade_id` não lista ocorrências de unidade alguma (fail-closed); UI de usuários passa a exigir unidade (exceto admin); `BuscaBca::enviarEmail/forcarEnvioEmail/abrirPreview` autorizam a ocorrência contra a unidade do usuário antes de agir. Reusar o middleware/policy existente — sem pacote novo (avaliar `spatie/laravel-permission` fica para a 3.4; agora, não).
- **Validação:** testes Livewire novos — usuário sem unidade vê zero ocorrências; usuário da unidade A não envia e-mail de ocorrência da unidade B (falha antes do fix, passa depois).
- **Rollback:** reverter o commit (comportamento anterior volta; risco conhecido e documentado).

### 1.3 `suppress_emails` serializado
- **Mudança:** `AnalisarEfetivoJob` e `EnviarCompiladoSADJob` ganham `bool $suppressEmails = false` no construtor (serializa na fila); `BcaAnalysisService` lê do parâmetro, não de `config()` em memória; comandos `bca:retroativo`/`bca:reanalisar` passam `true` explicitamente. É o desenho que o AGENTS.md já descrevia — restaurar, não inventar.
- **Validação:** teste de job provando supressão mesmo com worker separado (`QUEUE_CONNECTION=database` no teste ou dispatch + processamento posterior); suíte de Jobs/Mail verde; `docker compose restart queue` após deploy.
- **Rollback:** reverter o commit + restart da queue.

### 1.4 Login e endpoints
- **Mudança:** `throttle:5,1` na rota de login (mecanismo nativo); `/health` e `/metrics` atrás de `auth` ou token simples em header. Ponytail: token estático em `.env`, sem OAuth nem pacote.
- **Validação:** teste HTTP — 6ª tentativa de login recebe 429; `/health` sem credencial recebe 401/302, com credencial 200.
- **Rollback:** reverter o commit.

### 1.5 `failed_jobs` visível
- **Mudança:** expor `failed_jobs` count no `/health` (já protegido em 1.4) e um badge/contagem na tela de execuções existente. Sem tabela nova, sem dashboard novo.
- **Validação:** inserir falha sintética em ambiente de teste e ver o contador subir; zerar ao limpar.
- **Rollback:** reverter o commit.

### 1.6 Backup com restore testado
- **Mudança:** script `backup.sh` simples (cron do host): `pg_dump` + tar do volume de BCAs, retenção de 7 dias locais. Ponytail: sem ferramenta de backup nova; `pg_dump` + `tar` + `cron`.
- **Validação (a prova que importa):** restaurar o dump num banco temporário e contar `bcas`/`efetivos`; abrir 1 PDF do tar. Backup sem restore testado não conta como feito.
- **Rollback:** remover o cron e o script.
- **Decisão sua:** destino externo (outro host/mídia) fica como pendência — retenção só-local não protege contra perda do host.

## Fase 2 — P1: panes que não avisam (2–4 semanas)

| ID | Unidade (esforço) | Criticidade | Dependências |
|---|---|---|---|
| 2.1 | Health completo + monitor externo (M) | CRÍTICA | 1.4, 1.5 |
| 2.2 | `falha_fonte` ≠ `sem_bca` + alerta (P) | ALTA | 2.1 |
| 2.3 | Guarda + `ShouldBeUnique` e-mail (PP) | ALTA | — |
| 2.4 | Compilado encadeado/único (P) | ALTA | 2.3 |
| 2.5 | Status terminal pendentes (PP) | MÉDIA | 2.4 |
| 2.6 | Feriados FAB + lookback (P) | ALTA | — |
| 2.7 | Reconciliação na republicação (P) | MÉDIA | 2.6 |
| 2.8 | CI testes + Pint + audit (P) | ALTA | — |
| 2.9 | Testes descoberta URL e CSV (P) | MÉDIA | — |
| 2.10 | Retenção log/PDFs/tabelas (P) | MÉDIA | — |
| 2.11 | Pinar imagens + limites (PP) | MÉDIA | — |

### Notas por unidade (só o que não é óbvio da tabela)
- **2.1** Health checa: fila (jobs pendentes/atraso), heartbeat do scheduler, disco (limiar 80%), SMTP (conexão), fonte CENDOC (HEAD), BCA-do-dia em dia útil após 12h, clock-skew. Monitor externo = um `curl` agendado fora do host ou serviço gratuito de uptime — **decisão sua** (ver Open Questions). Sem health completo, 2.2 não tem onde pendurar o alerta.
- **2.2** `BaixarBcaJob` grava `falha_fonte` quando CENDOC+ICEA estão inacessíveis, distinto de `sem_bca` (fonte respondeu, nada publicado). Alerta após 2 ciclos consecutivos em dia útil. Ponytail: sem classificador elaborado — a distinção é "houve resposta HTTP da fonte ou não".
- **2.3** Guarda no `handle()` (`foiEnviado()` → return) + `ShouldBeUnique` por `ocorrenciaId`/`bcaId`. É a unidade mais barata com maior redução de risco de duplicidade; por isso vem antes da 2.4.
- **2.4** Compilado via `Bus::chain` após os individuais do mesmo BCA, ou despacho pelo último individual concluído; `ShouldBeUnique` por BCA. Acaba o compilado parcial/vazio silencioso.
- **2.6** Tabela simples de feriados FAB no banco (ou array versionado em `config/bca.php` — Ponytail prefere o array: sem migration, sem CRUD, revisão por commit) + scheduler com lookback: às 08h, se o dia útil anterior não tem BCA nem registro `sem_bca`/`falha_fonte`, tenta ele antes do de hoje. Resolve o BCA publicado após 17h.
- **2.7** Ao reprocessar um BCA cujo PDF mudou (hash diferente), apagar e recriar as ocorrências daquele BCA antes de reanalisar. Sem hash hoje — adicionar coluna `pdf_hash` na mesma migration.
- **2.8** Um workflow `ci.yml` com checkout + PHP 8.4 + `composer install` + `php artisan test` (serviço Postgres no runner) + Pint `--test` + `composer audit`. Sem matriz de versões: o projeto roda uma versão só.
- **2.9** `Http::fake()` simulando CENDOC ok, CENDOC fora com ICEA ok, ambos fora, e data sem zero-leading (regressão de 08/05/2026); importação CSV com SARAM duplicado e linha inválida.
- **2.10** `LOG_CHANNEL=daily` com `LOG_DAILY_DAYS=14` já suportado; retenção de PDFs/texto: **decisão sua** (manter tudo é o padrão seguro; poda só com aceite formal).
- **2.11** Pinar `postgres`, `redis`, `alpine`, `pgadmin` nas versões atuais e `mem_limit` modesto por serviço. Sem orquestrador, sem swarm.

## Fase 3 — P2: estrutural (quando houver janela)

| ID | Unidade (esforço) | Criticidade | Dependências |
|---|---|---|---|
| 3.1 | PDFs privados + download auth (M) | ALTA | 1.2 |
| 3.2 | TLS + cookie `Secure` (P) | ALTA | certificado |
| 3.3 | Soft-delete + auditoria (M) | MÉDIA | — |
| 3.4 | `spatie/permission`: usar ou remover (P) | BAIXA | 1.2 |
| 3.5 | `UNIQUE(palavra,unidade)` + tsvector (P) | MÉDIA | — |
| 3.6 | Índices/retenção/partição (M) | MÉDIA | 2.10 |
| 3.7 | Runbooks + STATUS reescritos (P) | BAIXA | todas |
| 3.8 | PHPStan + coverage no CI (P) | BAIXA | 2.8 |
| 3.9 | DR ensaiado ou 2º host (G) | ALTA | 1.6 |

## Validation Plan (por fase)

- **Fase 1:** testes focados novos por unidade (Livewire de autorização, job de supressão, HTTP de throttle/health) falhando antes e passando depois; suíte completa verde (hoje: 75 testes) ao fechar a fase; prova manual do restore (1.6).
- **Fase 2:** suíte completa + testes novos de descoberta/feriado/lookback; ensaio manual: derrubar a fila por 5 min e ver o health acusar; simular `sem_bca` em dia útil e ver o alerta.
- **Fase 3:** suíte completa + teste de download autenticado (401 sem login, 200 com) e de soft-delete preservando histórico.
- **Sempre:** `docker compose exec php php artisan test` dentro do container (nunca contra `bca_db` — regra do AGENTS.md) e certificado CEH antes de cada push.

## Risks / Rollback geral

- Cada unidade é um commit isolado: rollback = `git revert` da unidade, sem desfazer as vizinhas.
- Unidades 1.2 e 3.1 mudam quem vê o quê: comunicar os usuários antes (podem "perder" acessos que hoje têm por fail-open — é a correção, mas precisa de aviso).
- Unidade 2.6 muda o comportamento do scheduler: validar em dia útil real acompanhando os logs do primeiro ciclo.

## Open Questions (decisão sua, não bloqueiam as Fases 1–2 exceto onde indicado)

1. **Monitor externo (2.1):** existe algum serviço de monitoramento da OM (Zabbix/Nagios/e-mail de plantão)? Se não, uso um `curl` cron de outra máquina + e-mail para a SAD, ou serviço gratuito de uptime?
2. **Backup (1.6):** retenção só neste host basta por agora, ou já defino destino externo nesta fase?
3. **Certificado TLS (3.2):** a OM emite certificado interno para `<IP_DA_VM>`/hostname, ou o acesso continuará HTTP na Intraer por decisão formal?
4. **Retenção (2.10):** BCAs/PDFs antigos têm prazo legal de guarda? Sem prazo definido, o plano mantém tudo.

Reply `go` to execute this plan, or tell me what to change.

# AGENTS.md — Regras Operacionais para Agentes de IA

Este arquivo documenta lições aprendidas e regras operacionais críticas para quem trabalha neste repositório, incluindo agentes de IA (Claude Code, Gemini CLI, etc.).

---

## Operações com a Fila (Queue)

### Regra crítica: reiniciar o container `queue` após modificar Jobs

> **Após modificar qualquer classe de Job (`app/Jobs/*.php`), o container `queue` DEVE ser reiniciado antes de executar operações que dependam das mudanças.**

**Por quê:** O container `queue` roda como processo PHP persistente (`queue:work --sleep=3 --tries=3 --max-time=3600`). O OPcache do PHP guarda versões compiladas dos arquivos em memória. Editar o arquivo fonte no disco **não afeta o processo já em execução** — o novo código só é carregado após reinicialização do container.

**Comando para reiniciar:**
```bash
docker compose restart queue
```

**Verificação após restart:**
```bash
docker compose logs queue --tail=5
```

**Exemplo real (30/04/2026):** O flag `suppressEmails=true` foi adicionado a `AnalisarEfetivoJob` para suprimir emails durante reprocessamento. O container `queue` não foi reiniciado. Resultado: 7 emails foram enviados indevidamente (4 para o BCA de 10/04 + 3 para o BCA de 29/04).

### Padrão de trabalho seguro ao modificar Jobs

```
1. Editar a classe do Job (app/Jobs/*.php)
2. docker compose restart queue
3. Verificar os logs: docker compose logs queue -f
4. Despachar a operação
```

Nunca pule o passo 2. Nenhuma outra ação substitui o restart.

---

## Verificação de Ambiente

Antes de despachar Jobs via tinker ou interface, confirme que o container está rodando com o código atual:

```bash
docker compose ps          # todos os containers UP
docker compose logs queue --tail=20   # sem erros de boot
```

# 08 — Testes, qualidade e dependências

## Resumo

Há testes e build funcional, mas o harness não cria uma barreira segura para dados operacionais; CI e atualização de dependências são insuficientes.

## Implementado

- 46 testes descobertos; no Docker, 40 passaram e 4 foram ignorados.
- `npm run build` passou.
- Workflow de PII existe: `.github/workflows/pii-guard.yml`.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| CI abrangente | high | Não executa testes, build, Pint, auditoria ou scanner. | `.github/workflows/pii-guard.yml:16` |
| Casos multi-OM | critical | Sem testes de IDOR, cache, SAD, CSV ou usuário sem unidade. | `tests/Feature/Livewire/` |
| Cobertura confiável | high | Sem relatório versionado e vários cenários críticos ignorados. | `docs/testing.md:7` |

## Falhas

### ✅ Confirmada — Pest direto pode atingir banco operacional

**critical.** `phpunit.xml` não força variáveis e `RefreshDatabase` pode executar `migrate:fresh`; `php artisan test` mitiga via Collision, mas `./vendor/bin/pest` continua documentado. Evidência: `phpunit.xml:21`, `docker-compose.yml:35`, `docs/testing.md:350`.

### ⚪ Não revalidada nesta rodada — dependências vulneráveis

**high.** `composer audit --locked` encontrou 20 advisories em 11 pacotes e lock está divergente; npm completo retornou 9 pacotes vulneráveis, principalmente toolchain. Evidência: `composer.json`, `composer.lock`, `package-lock.json`.

### ⚪ Não revalidada nesta rodada — estilo e lock

**medium.** `composer validate --strict` falha e Pint aponta sete arquivos. Evidência: `composer.json`.

# 01 — Autenticação, autorização e tenancy

## Resumo

Sessão e RBAC básico existem, mas a fronteira multi-OM falha aberta e ações Livewire autorizam somente a tela, não o objeto.

## Implementado

- Login regenera sessão e logout invalida sessão/token: `app/Http/Controllers/Auth/LoginController.php:16`.
- Rotas administrativas usam `EnsureRole`: `routes/web.php:32`.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| Revogação e offboarding | high | Sem estado de conta, MFA/SSO, invalidação de sessões ou trilha administrativa. | `app/Livewire/GestorUsuarios.php:47` |
| Modelo tenant central | high | Policies, scopes ou contexto de tenant não existem. | `app/Models/User.php:10` |

## Falhas

### ✅ Confirmada — operador sem unidade recebe acesso global

**critical.** O cadastro normal omite `unidade_id`, login aceita a conta e filtros só rodam se o ID for truthy. Não há scope/policy compensatório. Evidência: `app/Livewire/GestorUsuarios.php:47`, `app/Livewire/BuscaBca.php:248`, `app/Livewire/HistoricoOcorrencias.php:33`.

### ✅ Confirmada — IDOR por ocorrência

**critical.** `enviarEmail`, `forcarEnvioEmail` e `previsualizarEmail` fazem `find/findOrFail` global com ID controlável pelo cliente. Evidência: `app/Livewire/BuscaBca.php:280`, `app/Livewire/BuscaBca.php:296`, `app/Livewire/BuscaBca.php:379`.

### ⚪ Não revalidada nesta rodada — login sem rate limit e contas padrão

**medium/high.** `POST /login` não usa throttle e o seeder possui fallback conhecido. Evidência: `routes/web.php:19`, `database/seeders/UserSeeder.php:14`.

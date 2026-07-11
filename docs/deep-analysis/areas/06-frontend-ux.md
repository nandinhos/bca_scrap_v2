# 06 — Frontend, UX e acessibilidade

## Resumo

O smoke autenticado passou, mas a interface não protege bem operações administrativas nem atende operação móvel/acessível.

## Implementado

- Playwright validou login e sete telas com HTTP 200 e sem erro de console.
- Busca, histórico, efetivo, palavras, unidades e execuções têm estados básicos.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| Responsividade | high | Sidebar/tabelas não possuem estratégia móvel. | `resources/views/layouts/app.blade.php:11` |
| Acessibilidade | high | Modais, labels, toasts e controles não têm semântica/foco/ARIA. | `resources/views/livewire/busca-bca.blade.php:219` |
| Contexto operacional | high | Shell não mostra OM/papel ativo. | `resources/views/layouts/app.blade.php:112` |

## Falhas

### ⚪ Não revalidada nesta rodada — administração de usuários não escala

**high.** UI não atribui OM e não renderiza paginação de `paginate(20)`. Evidência: `app/Livewire/GestorUsuarios.php:59`, `resources/views/livewire/gestor-usuarios.blade.php:35`.

### ⚪ Não revalidada nesta rodada — feedback de e-mail enganoso

**high.** A tela anuncia envio ao apenas enfileirar. Evidência: `app/Livewire/BuscaBca.php:287`.

### ⚪ Não revalidada nesta rodada — identidade e locale inconsistentes

**medium/high.** Login/footer exibem Laravel, while shell mostra BCA Scrap v2; locale padrão é en. Evidência: `.env.example:1`, `.env.example:7`.

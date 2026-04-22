# LIÇÃO APRENDIDA #1 - Busca BCA Não Exibia Resultados

**Data:** 22/04/2026
**Autor:** 1S BMB Fernando
**Sistema:** BCA Scrap v2

---

## Problema

Usuário buscava o BCA do dia 10/04/2026 pelo dashboard, o sistema processava corretamente (encontrava 4 militares, incluindo o SARAM 4112695 do próprio usuário), mas **nenhum resultado era exibido na interface**.

## Sintomas

- Job executava com sucesso no queue worker
- BCA era baixado, processado e analisado corretamente
- Ocorrências eram criadas no banco (`bca_ocorrencias`)
- Porém a lista de ocorrências permanecia vazia no frontend

## Root Cause

O problema estava em **três pontos distintos**:

### 1. Bug no `BcaAnalysisService::encontraNoBca()` (Linha 142)

O texto do BCA é extraído em **maiúsculas** (via `pdftotext`), mas a busca por nome usava `$textoBca` (case-sensitive) enquanto `$efetivo->nome_completo` do banco estava em **mix-case** (ex: `FERNANDO dos Santos Souza`).

```php
// ANTES (BUG):
if (mb_stripos($textoBca, $efetivo->nome_completo) !== false)

// DEPOIS (CORRIGIDO):
if (mb_stripos($textoUpper, $nomeCompleto) !== false)
```

### 2. Filtro de Unidade em `BcaAnalysisService::analisar()` (Linha 41-43)

Todos os 50 efetivos do sistema tinham `unidade_id = NULL`. O filtro `whereHas('unidade', ...)` excluía esses registros da análise.

```php
// ANTES (BUG):
Efetivo::ativo()
    ->whereHas('unidade', fn ($q) => $q->whereIn('unidade_id', $unidadesAtivas))

// DEPOIS (CORRIGIDO):
Efetivo::ativo()
    ->where(function ($q) use ($unidadesAtivas) {
        $q->whereHas('unidade', fn ($q2) => $q2->whereIn('id', $unidadesAtivas))
          ->orWhereDoesntHave('unidade');
    })
```

### 3. Double Dispatch na Busca Manual (`BuscaBca::executarBusca()`)

Quando um BCA já tinha `analisado_em` (análise anterior), o código **chamava `analisar()` novamente** antes de `finalizarBusca()`, o que duplicava o processamento e causava re-despacho de emails.

```php
// ANTES (BUG):
$bca = Bca::where('data', $this->data)->whereNotNull('analisado_em')->first();
if ($bca) {
    app(BcaAnalysisService::class)->analisar($bca, 'manual', $this->palavrasSelecionadas);
    $this->finalizarBusca($bca);
    return;
}

// DEPOIS (CORRIGIDO):
$bca = Bca::where('data', $this->data)->whereNotNull('analisado_em')->first();
if ($bca) {
    $this->finalizarBusca($bca);
    return;
}
```

---

## Impacto

- Usuário não conseguia visualizar resultados de buscas já processadas
- Parecia que o sistema não funcionava (apesar de funcionar corretamente nos bastidores)
- Causou desconfiança na ferramenta

---

## Correções Aplicadas

| Arquivo | Linha | Correção |
|---------|-------|----------|
| `app/Services/BcaAnalysisService.php` | 142 | Usar `$textoUpper` para consistência de case |
| `app/Services/BcaAnalysisService.php` | 41-43 | Incluir efetivos sem unidade no filtro |
| `app/Livewire/BuscaBca.php` | 109-115 | Remover double dispatch desnecessário |

---

## Como Identificar Problemas Semelhantes

1. **Verificar jobs pendentes/executando:**
   ```bash
   docker-compose exec php php artisan tinker --execute="echo 'Pending: ' . \Illuminate\Support\Facades\DB::table('jobs')->count();"
   ```

2. **Verificar ocorrências no banco:**
   ```bash
   docker-compose exec php php artisan tinker --execute="
   \$bca = \App\Models\Bca::where('data', '2026-04-10')->first();
   echo 'Ocorrencias: ' . \App\Models\BcaOcorrencia::where('bca_id', \$bca->id)->count();
   "
   ```

3. **Verificar logs do queue worker:**
   ```bash
   docker-compose logs --tail=50 queue
   ```

4. **Testar diretamente no tinker:**
   ```bash
   docker-compose exec php php artisan tinker --execute="
   \$bca = \App\Models\Bca::where('data', '2026-04-10')->first();
   \$service = app(\App\Services\BcaAnalysisService::class);
   \$result = \$service->encontraNoBca(
       \App\Models\Efetivo::where('saram', '4112695')->first(),
       \$bca->texto_completo
   );
   echo 'Match: ' . (\$result ?: 'NULL');
   "
   ```

---

## Prevenção

1. **Adicionar teste** para `encontraNoBca()` com texto em maiúsculas
2. **Padronizar case** do nome_completo no seeder (tudo maiúsculo)
3. **Considerar unidade_id DEFAULT** como NULLAllowed no filtro
4. **Verificar dados vazios no frontend** com debug em tinker

---

## Referências

- Commit: Correção do bug de busca BCA
- Arquivos: `app/Services/BcaAnalysisService.php`, `app/Livewire/BuscaBca.php`

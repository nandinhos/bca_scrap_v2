# Comparação de Performance — Sistema Atual vs v2

## 📊 Benchmarks por Operação

> **Ambiente de teste**: 47 militares no efetivo, 1 BCA do dia

| Operação | Sistema Atual (v1) | Meta v2 | Método de Medição |
|----------|-------------------|---------|-------------------|
| **Busca BCA — cache hit** | 5-15s | <1s | `Cache::get()` — Redis |
| **Busca BCA — API CENDOC** | 5-15s | <3s | Laravel Telescope |
| **Busca BCA — paralela** | 5-15s | <6s | `Http::pool()` c/ 10 chunks |
| **Extração texto PDF** | 2s | <500ms | `pdftotext` + cache Redis |
| **Análise de efetivo (47 mil.)** | 3-5s | <100ms | PostgreSQL FTS + GIN index |
| **Envio de email** | 2-5s (bloqueia) | <10ms (assíncrono) | Queue dispatch |
| **Carregamento da página** | 1-2s | <300ms | Chrome DevTools Network |
| **Uptime** | ~95% | ≥99.5% | Monitoring de saúde |

---

## 📈 Ganhos por Técnica

| Técnica | Operação Impactada | Ganho Estimado |
|---------|-------------------|----------------|
| Cache Redis (query + PDF) | Buscas repetidas | **10x mais rápido** |
| `Http::pool()` paralelo | Download BCA | **85% mais rápido** |
| PostgreSQL FTS + GIN index | Análise efetivo | **75% mais rápido** |
| Queue assíncrona (Horizon) | Email notification | **Não bloqueia UI** |
| Lazy loading Livewire | Carregamento página | **60% mais rápido** |
| OPcache PHP | Todas as operações | **30% mais rápido** |

---

## 🔬 Metodologia de Medição

### Antes do Deploy (Baseline — Sistema Atual)

```php
// Executar no sistema atual antes da migração
// Salvar resultados como baseline oficial

$inicio = microtime(true);

// Operação a medir (ex: busca BCA)
buscarBca(date('d-m-Y'));

$tempo = round((microtime(true) - $inicio) * 1000);
echo "Baseline busca BCA: {$tempo}ms";
```

### Após o Deploy (Verificação das Metas)

```bash
# Medir via Laravel Tinker
docker exec -it bca-php php artisan tinker

>>> $start = microtime(true);
>>> app(App\Services\BcaDownloadService::class)->buscarBca('14-03-2026');
>>> $ms = round((microtime(true) - $start) * 1000);
>>> echo "Busca BCA: {$ms}ms (meta: <3000ms)";
```

```sql
-- Medir query de análise de efetivo no PostgreSQL
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT e.id, e.nome_completo,
       ts_headline('portuguese', 'TEXTO DO BCA AQUI',
                   to_tsquery('portuguese', 'FERNANDO & SILVA')) AS snippet
FROM efetivos e
WHERE to_tsvector('portuguese', 'TEXTO DO BCA AQUI')
      @@ to_tsquery('portuguese', translate(e.nome_completo, ' ', ' & '));

-- Verificar: "Execution Time: X ms" deve ser < 100ms
```

---

## 💰 Análise de ROI

### Investimento

| Item | Custo | Observação |
|------|-------|------------|
| Desenvolvimento (8 semanas) | ~R$ 22.400 | 1 dev × 40h/sem × R$ 70/h |
| Infraestrutura adicional (ano) | ~R$ 0 | Mesmo servidor, apenas containers |
| **Total** | **~R$ 22.400** | |

> **Nota**: O desenvolvimento é realizado internamente pela equipe GAC-PAC. O valor acima representa o custo de oportunidade do tempo investido.

### Retorno

| Benefício | Cálculo | Valor Anual |
|-----------|---------|-------------|
| Tempo economizado em buscas | 10 min/dia × 220 dias úteis = 36,7h/ano | R$ 2.933 |
| Redução de bugs em produção | 5→2 bugs/mês × 2h investigação × 12 = 72h/ano | R$ 5.760 |
| Não-bloqueio da interface (email async) | 3 min/dia × 220 dias = 11h/ano | R$ 880 |
| Onboarding mais rápido (documentação) | 3 dias → 1 dia × 2 novos devs/ano = 4 dias | R$ 2.240 |
| **Total estimado** | | **~R$ 11.813/ano** |

> **Valores baseados em**: 220 dias úteis/ano, custo/hora de R$ 80 (dev)

### Payback

- **ROI 1º ano**: (R$ 11.813 - R$ 22.400) / R$ 22.400 = **-53%** (investimento)
- **ROI 2º ano**: (R$ 23.626 - R$ 22.400) / R$ 22.400 = **+5%** (break-even)
- **ROI 3º ano**: **+58%** (retorno positivo consolidado)

### Benefícios Intangíveis (Não Quantificados)

- ✅ **Qualidade**: 0% → 80%+ cobertura de testes (reduz risco de regressões)
- ✅ **Manutenibilidade**: Código modular (SOLID) vs PHP vanilla monolítico
- ✅ **Segurança**: Eloquent ORM previne SQL injection; CSRF/XSS automático
- ✅ **Escalabilidade**: Queue system suporta crescimento do efetivo
- ✅ **Documentação**: 0 → 100% de documentação técnica (onboarding eficiente)
- ✅ **Confiabilidade**: Retry automático de jobs + plano de rollback

---

## 📋 Checklist de Medição Pós-Deploy

Medir e documentar em até 7 dias após o go-live:

- [ ] Tempo médio de busca BCA (10 amostras) → meta: <3s
- [ ] Tempo de análise de efetivo → meta: <100ms
- [ ] Taxa de entrega de emails (7 dias) → meta: ≥98%
- [ ] Uptime do sistema (7 dias) → meta: ≥99.5%
- [ ] Satisfação dos usuários (pesquisa informal) → meta: ≥4.5/5

---

**Última atualização**: 14/03/2026

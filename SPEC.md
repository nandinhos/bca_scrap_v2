# SPEC: Botão Manual de Email com Preview — BCA Scrap v2

**Versão:** 1.0.0 | **Data:** 2026-04-23 | **Autor:** Fernando (Nando)
**Projeto:** BCA Scrap v2 | **Status:** PLANEJAMENTO

---

## 1. Visão

**O que é:** Funcionalidade de envio manual de emails com preview para o sistema BCA Scrap v2.

**O que não é:** Um sistema de email completo — é apenas um painel de teste e disparo manual.

**Problema resolvido:**
- Atualmente o sistema envia emails automaticamente via cron/queue
- Não há como testar envio manualmente
- Não há preview do email antes de enviar
- Usuário não tem controle sobre envios individuais

**Benefícios:**
- Teste manual antes de ativar automação
- Visualização do email antes do envio real
- Controle granular por militar
- Log de envios manuais vs automáticos

---

## 2. Arquitetura da Solução

### Fluxo Proposto

```
┌──────────────────────────────────────────────────────────────┐
│                    DASHBOARD BCA                             │
│  [Preview] ──→ Modal                                        │
│                    │                                         │
│                    ├── Renderiza email (mock)                │
│                    │   - Nome militar                        │
│                    │   - Número BCA                          │
│                    │   - Snippet                             │
│                    │   - Email destino                       │
│                    │                                         │
│                    └── [Enviar Email]                        │
│                             │                               │
│                             ▼                               │
│                    EnviarEmailNotificacaoJob::dispatch()     │
│                             │                               │
│                             ▼                               │
│                    Job executa + atualiza enviado_em          │
│                             │                               │
│                             ▼                               │
│                    Modal fecha + Card atualiza → "✓ Enviado"│
└──────────────────────────────────────────────────────────────┘
```

### Componentes a Modificar

| Arquivo | Modificação |
|---------|-------------|
| `app/Livewire/BuscaBca.php` | Adicionar `previsualizarEmail(int $ocorrenciaId)` |
| `resources/views/livewire/busca-bca.blade.php` | Adicionar botão Preview + Modal |
| `app/Jobs/EnviarEmailNotificacaoJob.php` | Já existe, usar como está |
| `app/Mail/NotificacaoBcaMail.php` | Já existe, usar como está |

---

## 3. Estados da Ocorrência

```
[PENDENTE]              [ENVIADO]
┌─────────────┐         ┌─────────────┐
│ [Preview]   │         │ ✓ Enviado   │
│ [Enviar]    │         │             │
└─────────────┘         └─────────────┘
     │                        │
     ▼                        │
Job executa                   │
     │                        │
     ▼                        │
Atualiza                      │
enviado_em ───────────────────┘
```

### Lógica de Exibição

```php
if ($ocorrencia->enviado_em !== null) {
    echo "<span>✓ Enviado</span>";
} else {
    echo "<button Preview>";
    echo "<button Enviar>";
}
```

---

## 4. Backend — BuscaBca.php

### Método previsualizarEmail

```php
public array $previewData = [];

public function previsualizarEmail(int $ocorrenciaId): array
{
    $oc = BcaOcorrencia::with(['efetivo', 'bca'])->findOrFail($ocorrenciaId);

    if (empty($oc->efetivo->email)) {
        return ['error' => 'Email não cadastrado para este militar'];
    }

    return [
        'id' => $oc->id,
        'email_destino' => $oc->efetivo->email,
        'nome_militar' => $oc->efetivo->nome_guerra,
        'posto' => $oc->efetivo->posto,
        'saram' => $oc->efetivo->saram,
        'bca_numero' => $oc->bca->numero,
        'bca_data' => $oc->bca->data->format('d/m/Y'),
        'snippet' => $oc->snippet,
        'tipo_match' => $oc->tipo_match,
        'quantidade' => $oc->quantidade,
        'foi_enviado' => $oc->foiEnviado(),
    ];
}
```

### Propriedades do Component

```php
public bool $showPreviewModal = false;
public ?int $previewOcorrenciaId = null;
public array $previewData = [];
```

---

## 5. Frontend — busca-bca.blade.php

### Botão Preview

```blade
<button wire:click="abrirPreview({{ $oc['id'] }})"
        style="font-size:12px;font-weight:600;color:#7c3aed;background:#f5f3ff;border:1.5px solid #c4b5fd;border-radius:6px;padding:5px 12px;cursor:pointer">
    🔍 Preview
</button>
```

### Modal de Preview

```blade
@if($showPreviewModal)
<div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)">
    <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:600px">
        <h3>📧 Preview — Email para {{ $previewData['nome_militar'] ?? '' }}</h3>

        <div style="background:#f8fafc;border-radius:8px;padding:16px;margin:16px 0">
            <p><strong>Para:</strong> {{ $previewData['email_destino'] }}</p>
            <p><strong>Assunto:</strong> BCA - Você foi mencionado no Boletim {{ $previewData['bca_numero'] }}</p>
        </div>

        <div style="background:white;border:1px solid #e2e8f0;border-radius:8px;padding:20px">
            <h4>{{ $previewData['nome_militar'] }} — {{ $previewData['posto'] }}</h4>
            <p>Você foi mencionado no BCA nº {{ $previewData['bca_numero'] }} de {{ $previewData['bca_data'] }}.</p>
            <div style="background:#fef3c7;border-left:3px solid #f59e0b;padding:12px;font-family:monospace;font-size:12px">
                {!! $previewData['snippet'] !!}
            </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px">
            <button wire:click="$set('showPreviewModal', false)" style="flex:1;padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:white">Fechar</button>
            @if(!$previewData['foi_enviado'])
            <button wire:click="enviarEmail({{ $previewData['id'] }})" style="flex:1;padding:12px;background:#059669;color:white;border:none;border-radius:8px;font-weight:600">📤 Enviar Email</button>
            @else
            <button disabled style="flex:1;padding:12px;background:#d1d5db;color:#6b7280;border:none;border-radius:8px">✓ Já Enviado</button>
            @endif
        </div>
    </div>
</div>
@endif
```

---

## 6. Fluxo de Execução

```
1. USUÁRIO clica [Preview]
   └── abrirPreview(ocorrenciaId)
       ├── $previewData = previsualizarEmail()
       └── $showPreviewModal = true

2. MODAL abre com preview

3. USUÁRIO clica [Enviar Email]
   └── enviarEmail(ocorrenciaId)
       ├── $showPreviewModal = false
       └── EnviarEmailNotificacaoJob::dispatch()

4. JOB executa
   ├── Mail::to(email)->send(NotificacaoBcaMail)
   └── update ['enviado_em' => now()]

5. POLLING atualiza card
   └── Card mostra "✓ Enviado"
```

---

## 7. Riscos e Mitigações

| Risco | Prob | Impacto | Mitigação |
|-------|------|---------|-----------|
| Race condition | Baixa | Média | Lock no job |
| Email vazio | Média | Baixa | Validação no método |
| Click duplo | Baixa | Baixa | Disable button |
| Modal não fecha | Baixa | Baixa | Wire:loading |

---

## 8. Critérios de Aceitação

- [ ] Botão "Preview" visível ao lado de "Enviar" para pendentes
- [ ] Modal abre com preview (destinatário, assunto, snippet)
- [ ] Botão "Enviar Email" no modal executa job
- [ ] Após envio, modal fecha e card atualiza para "✓ Enviado"
- [ ] Militar já enviado exibe apenas "✓ Enviado"
- [ ] Militar sem email exibe erro no preview

---

## 9. Estimativa

| Tarefa | Tempo |
|--------|-------|
| Backend | 15 min |
| Frontend | 30 min |
| Testes | 20 min |
| **TOTAL** | **~65 min** |

---

**Status:** PRONTO PARA IMPLEMENTAÇÃO
**Última atualização:** 2026-04-23

<div style="max-width:860px;position:relative">

    {{-- Notificação Flutuante --}}
    @if(session('notification') || $notification)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         style="position:fixed;top:20px;right:20px;z-index:9999;padding:16px 24px;border-radius:10px;
                background:{{ (session('notification.type') ?? 'success') === 'success' ? '#059669' : ((session('notification.type') ?? 'success') === 'error' ? '#dc2626' : '#2563eb') }};
                color:white;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.2);max-width:400px"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4"
         x-transition:enter-end="opacity-100 transform translate-x-0" x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div style="display:flex;align-items:center;gap:12px">
                @if((session('notification.type') ?? 'success') === 'success')
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                @endif
                <span>{{ session('notification.message') ?? $notification }}</span>
            </div>
    </div>
    @endif

    {{-- Card de Palavras-Chave --}}
    @if(count($palavrasDisponiveis) > 0)
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;padding:18px 24px;margin-bottom:16px">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin:0 0 10px">Palavras-chave monitoradas</p>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            @foreach($palavrasDisponiveis as $pw)
                @php $ativa = in_array($pw['palavra'], $palavrasSelecionadas); @endphp
                <button wire:click="togglePalavra('{{ $pw['palavra'] }}')"
                        style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid {{ $ativa ? '#1e3a5f' : '#e2e8f0' }};background:{{ $ativa ? '#1e3a5f' : 'white' }};color:{{ $ativa ? 'white' : '#94a3b8' }};transition:all .15s">
                    {{ $pw['palavra'] }}
                </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Card de Busca --}}
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:16px">
        <form wire:submit="buscar" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.07em">Data do Boletim</label>
                <input type="date" wire:model="data"
                    style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 14px;font-size:14px;outline:none;font-family:inherit;color:#1e293b">
                @error('data')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                style="background:#1e3a5f;color:white;border:none;border-radius:8px;padding:9px 20px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:8px">
                <span wire:loading.remove wire:target="buscar">Buscar BCA</span>
                <span wire:loading wire:target="buscar">Aguarde...</span>
            </button>
            @if($pdfUrl)
                <a href="{{ $pdfUrl }}" target="_blank"
                   style="background:#0f766e;color:white;border:none;border-radius:8px;padding:9px 18px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:7px;text-decoration:none">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Baixar Boletim
                </a>
            @endif
        </form>
        @if(count($palavrasSelecionadas) > 0)
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9">
            <span style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap">Filtrando por:</span>
            @foreach($palavrasSelecionadas as $pw)
                <button wire:click="togglePalavra('{{ $pw }}')"
                        title="Remover filtro"
                        style="display:flex;align-items:center;gap:5px;padding:3px 10px 3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#1e3a5f;color:white;border:none;cursor:pointer">
                    {{ $pw }}
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:11px;height:11px;opacity:.7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Estado: Buscando --}}
    @if($buscando)
        <div wire:init="executarBusca" wire:poll.3s="checkStatus"
             style="border-radius:10px;padding:14px 20px;font-size:14px;margin-bottom:14px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;display:flex;align-items:center;gap:12px">
            <svg style="width:18px;height:18px;flex-shrink:0;animation:spin 1s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.657-5.657l1.414-1.414M4.93 19.07l1.414-1.414m0-11.314L4.93 4.93M19.07 19.07l-1.414-1.414" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Baixando e processando BCA — aguarde...</span>
        </div>
        <style>@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }</style>

    {{-- Estado: Resultado --}}
    @else
        @if($mensagem)
        <div style="background:{{ $mensagemTipo==='success' ? '#f0fdf4' : ($mensagemTipo==='warning' ? '#fffbeb' : '#fef2f2') }};
                    border:1px solid {{ $mensagemTipo==='success' ? '#bbf7d0' : ($mensagemTipo==='warning' ? '#fde68a' : '#fecaca') }};
                    color:{{ $mensagemTipo==='success' ? '#166534' : ($mensagemTipo==='warning' ? '#92400e' : '#991b1b') }};
                    padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:14px;font-weight:600;display:flex;align-items:center;justify-content:space-between;gap:12px">
            <span>{{ $mensagem }}</span>
            @if($bcaId && count(array_filter($ocorrencias, fn($o) => !$o['enviado_em'])) > 0)
                <button wire:click="enviarTodos"
                        style="background:#1e40af;color:white;padding:7px 14px;border-radius:6px;font-size:13px;font-weight:700;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap"
                        onmouseover="this.style.background='#1e3a8a'" onmouseout="this.style.background='#1e40af'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Enviar Todos ({{ count(array_filter($ocorrencias, fn($o) => !$o['enviado_em'])) }})
                </button>
            @endif
        </div>
        @endif

        {{-- Lista de Ocorrências — linha única --}}
        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:60px">
            @foreach($ocorrencias as $oc)
            <div x-data="{ open: false }" style="background:white;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden">
                {{-- Linha principal --}}
                <div style="padding:12px 16px;display:flex;align-items:center;gap:12px">

                    {{-- Avatar inicial --}}
                    <div style="width:36px;height:36px;background:#f1f5f9;color:#475569;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0">
                        {{ strtoupper(substr($oc['efetivo']['nome_guerra'], 0, 1)) }}{{ strtoupper(substr(strrchr($oc['efetivo']['nome_guerra'], ' ') ?: $oc['efetivo']['nome_guerra'], 1, 1)) }}
                    </div>

                    {{-- Nome e info --}}
                    <div style="flex:1;min-width:0">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;text-transform:uppercase">{{ $oc['efetivo']['nome_completo'] }}</span>
                        <span style="font-size:12px;color:#94a3b8;margin-left:8px">{{ $oc['efetivo']['posto'] }} · {{ $oc['efetivo']['especialidade'] }} · SARAM {{ $oc['efetivo']['saram'] }}</span>
                    </div>

                    {{-- Badges --}}
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                        <span style="font-size:11px;color:#b91c1c;font-weight:700;background:#fef2f2;padding:3px 10px;border-radius:20px">
                            {{ $oc['quantidade'] ?? 1 }}×
                        </span>
                        <span style="font-size:11px;color:#2563eb;font-weight:600;background:#eff6ff;padding:3px 10px;border-radius:20px;border:1px solid #dbeafe">
                            {{ $oc['tipo_match'] }}
                        </span>

                        {{-- Preview --}}
                        <button wire:click="abrirPreview({{ $oc['id'] }})"
                                style="font-size:12px;font-weight:600;color:#7c3aed;background:#f5f3ff;border:1.5px solid #c4b5fd;border-radius:6px;padding:5px 10px;cursor:pointer"
                                onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                            🔍
                        </button>

                        {{-- Email --}}
                        @if($oc['enviado_em'])
                            <span style="font-size:11px;color:#c2410c;font-weight:600;background:#fff7ed;padding:3px 10px;border-radius:20px;border:1px solid #ffedd5">
                                ✓ Enviado
                            </span>
                        @else
                            <button wire:click="enviarEmail({{ $oc['id'] }})"
                                    style="font-size:12px;font-weight:600;color:white;background:#059669;border:none;border-radius:6px;padding:5px 12px;cursor:pointer"
                                    onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                                Enviar
                            </button>
                        @endif

{{-- Toggle prévia --}}
                        <button @click="open = !open"
                                style="font-size:12px;font-weight:600;color:#64748b;background:white;border:1.5px solid #e2e8f0;border-radius:6px;padding:5px 10px;cursor:pointer"
                                onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#e2e8f0'">
                            <span x-show="!open">▾ Texto</span>
                            <span x-show="open">▴ Fechar</span>
                        </button>
                    </div>
                </div>

                {{-- Snippet colapsável --}}
                <div x-show="open" x-collapse
                     style="background:#f8fafc;border-top:1px dashed #e2e8f0;padding:16px 20px">
                    <pre style="margin:0;font-family:'Courier New',monospace;font-size:13px;line-height:1.6;color:#334155;white-space:pre-wrap;border-left:3px solid #3b82f6;padding-left:14px">{!! strip_tags($oc['snippet']) !!}</pre>
                    @if(!empty($oc['bca_url']))
                        <a href="{{ $oc['bca_url'] }}" target="_blank" style="color:#2563eb;font-weight:500;margin-top:8px;display:inline-block">→ Ver BCA</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- Modal de Preview --}}
    @if($showPreviewModal)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)"
         x-data="{ show: true }" x-show="show" x-transition>
        <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:600px;margin:16px;max-height:90vh;overflow-y:auto"
             @click.away="show = false; @this.set('showPreviewModal', false)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0">
                    📧 Preview — Email para {{ $previewData['nome_militar'] ?? '' }}
                </h3>
                <button wire:click="forcarEnvioEmail({{ $previewData['id'] ?? 0 }})"
                        style="padding:8px 16px;background:#059669;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px"
                        @if($enviandoEmail) disabled @endif
                        onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                    @if($enviandoEmail)
                        <span style="display:inline-block;animation:spin 1s linear infinite">⏳</span> Enviando...
                    @else
                        📤 Enviar Email
                    @endif
                </button>
            </div>

            @if(isset($previewData['error']))
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px;color:#991b1b">
                    {{ $previewData['error'] }}
                </div>
            @else
                <div style="background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:16px">
                    <p style="margin:0;font-size:13px;color:#64748b">
                        <strong>Para:</strong> {{ $previewData['email_destino'] ?? '—' }}
                    </p>
                    <p style="margin:4px 0 0;font-size:13px;color:#64748b">
                        <strong>Assunto:</strong> BCA - Você foi mencionado no Boletim {{ $previewData['bca_numero'] ?? '' }}
                    </p>
                </div>

                <div style="background:white;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:16px">
                    <h4 style="margin:0 0 12px;font-size:14px;color:#1e293b">
                        {{ $previewData['nome_militar'] ?? '' }} — {{ $previewData['posto'] ?? '' }}
                    </h4>
                    <p style="margin:0 0 16px;font-size:13px;color:#64748b">
                        Você foi mencionado no BCA nº {{ $previewData['bca_numero'] ?? '' }} de {{ $previewData['bca_data'] ?? '' }}.
                    </p>
                    <div style="background:#f1f5f9;border-left:3px solid #3b6aab;padding:14px 16px;font-family:monospace;font-size:12px;color:#334155;white-space:pre-wrap;line-height:1.7">
                        {!! strip_tags($previewData['snippet'] ?? '') !!}
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:20px">
                    <button wire:click="$set('showPreviewModal', false)"
                            style="flex:1;padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:white;font-size:14px;cursor:pointer">
                        Fechar
                    </button>
                    @if(!($previewData['foi_enviado'] ?? false))
                    <button wire:click="enviarEmail({{ $previewData['id'] ?? 0 }})"
                            style="flex:1;padding:12px;background:#059669;color:white;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer"
                            @if($enviandoEmail) disabled @endif>
                        @if($enviandoEmail)
                            <span style="display:inline-block;animation:spin 1s linear infinite">⏳</span> Enviando...
                        @else
                            📤 Enviar Email
                        @endif
                    </button>
                    @else
                    <button disabled
                            style="flex:1;padding:12px;background:#d1d5db;color:#6b7280;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:not-allowed">
                        ✓ Já Enviado
                    </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div style="position:fixed;bottom:0;left:0;width:100%;background:#1e293b;color:#94a3b8;padding:10px 0;text-align:center;font-size:12px;font-weight:500;z-index:100">
        Adaptação realizada por 1S BMB FERNANDO
    </div>
</div>

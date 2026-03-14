<div style="max-width:800px">
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);padding:24px;margin-bottom:20px">
        <h2 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 16px">Buscar Boletim do Comando da Aeronáutica</h2>
        <form wire:submit="buscar" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">Data do Boletim</label>
                <input type="date" wire:model="data"
                    style="border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;outline:none;font-family:inherit">
                @error('data')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                style="background:#1e3a5f;color:white;border:none;border-radius:8px;padding:10px 20px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:8px">
                <span wire:loading.remove wire:target="buscar">Buscar BCA</span>
                <span wire:loading wire:target="buscar">Buscando...</span>
            </button>
        </form>
    </div>

    @if($buscando)
        <div wire:init="executarBusca" wire:poll.3s="checkStatus" style="border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:16px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;display:flex;align-items:center;gap:12px">
            <svg style="width:18px;height:18px;flex-shrink:0;animation:spin 1s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.657-5.657l1.414-1.414M4.93 19.07l1.414-1.414m0-11.314L4.93 4.93M19.07 19.07l-1.414-1.414" stroke-width="2" stroke-linecap="round"/></svg>
            <span>Baixando e processando BCA — aguarde...</span>
        </div>
        <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
    @elseif($mensagem)
        <div style="border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:16px;
            {{ $mensagemTipo==='success' ? 'background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0' : ($mensagemTipo==='warning' ? 'background:#fffbeb;color:#d97706;border:1px solid #fde68a' : 'background:#fef2f2;color:#dc2626;border:1px solid #fecaca') }}">
            {{ $mensagem }}
        </div>
    @endif

    @if($pdfUrl)
        <div style="margin-bottom:20px">
            <a href="{{ $pdfUrl }}" target="_blank"
                style="display:inline-flex;align-items:center;gap:8px;background:#f8fafc;color:#1e3a5f;border:1px solid #e2e8f0;padding:10px 16px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;transition:all .2s">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h3m-3 4h6m-6 4h6"/></svg>
                Visualizar PDF Original
            </a>
        </div>
    @endif

    @foreach($ocorrencias as $oc)
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);padding:20px;margin-bottom:12px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px">
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span style="font-size:16px;font-weight:700;color:#1e3a5f">{{ $oc['efetivo']['nome_guerra'] }}</span>
                    <span style="font-size:12px;color:#94a3b8">{{ $oc['efetivo']['posto'] }}</span>
                    <span style="font-size:11px;background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:4px;font-family:monospace">{{ $oc['saram'] }}</span>
                </div>
                @if(!empty($oc['snippet']))
                <div style="background:#f1f5f9;border-left:3px solid #1e3a5f;border-radius:4px;padding:12px;margin-top:10px;font-family:monospace;font-size:12px;color:#334155;white-space:pre-wrap;line-height:1.6;letter-spacing:0.025em">{!! $oc['snippet'] !!}</div>
                @endif
            </div>
            <div style="flex-shrink:0">
                @if($oc['enviado_em'])
                    <span style="font-size:12px;background:#f0fdf4;color:#16a34a;padding:4px 10px;border-radius:20px;font-weight:500;border:1px solid #bbf7d0">✓ Enviado</span>
                @else
                    <button wire:click="enviarEmail({{ $oc['id'] }})"
                        style="background:#1e3a5f;color:white;border:none;border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer;font-family:inherit">
                        Enviar email
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

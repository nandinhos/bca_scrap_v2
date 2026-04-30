<div style="max-width:700px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <div>
            <h2 style="font-size:15px;font-weight:600;color:#1e293b;margin:0">Palavras-chave</h2>
            <p style="font-size:13px;color:#94a3b8;margin:2px 0 0">Ative as palavras que devem ser buscadas no BCA</p>
        </div>
        <div style="display:flex;gap:8px">
            @if(auth()->user()->isAdmin())
            <button wire:click="toggleAll(true)" style="background:#16a34a;color:white;border:none;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Ativar todos</button>
            <button wire:click="toggleAll(false)" style="background:#64748b;color:white;border:none;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Desativar todos</button>
            <button wire:click="openCreate" style="background:#1e3a5f;color:white;border:none;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">+ Nova</button>
            @endif
        </div>
    </div>

    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden">
        @forelse($palavras as $p)
        <div style="display:flex;align-items:center;gap:16px;padding:14px 20px;border-bottom:1px solid #f1f5f9">
            <div style="width:12px;height:12px;border-radius:50%;flex-shrink:0;background:#{{ $p['cor'] }}"></div>
            <span style="flex:1;font-size:14px;font-weight:500;color:#334155">{{ $p['palavra'] }}</span>
            <div style="display:flex;align-items:center;gap:12px">
                <!-- Toggle -->
                <button wire:click="toggleAtiva({{ $p['id'] }})"
                    style="position:relative;width:36px;height:20px;border-radius:10px;border:none;cursor:pointer;transition:background .2s;background:{{ $p['ativa'] ? '#22c55e' : '#cbd5e1' }}">
                    <span style="position:absolute;top:3px;width:14px;height:14px;background:white;border-radius:50%;transition:left .2s;{{ $p['ativa'] ? 'left:19px' : 'left:3px' }}"></span>
                </button>
                <span style="font-size:12px;font-weight:500;color:{{ $p['ativa'] ? '#16a34a' : '#94a3b8' }}">{{ $p['ativa'] ? 'Ativa' : 'Inativa' }}</span>
                @if(auth()->user()->isAdmin())
                <button wire:click="openEdit({{ $p['id'] }})" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button wire:click="delete({{ $p['id'] }})" wire:confirm="Excluir '{{ $p['palavra'] }}'?" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div style="padding:40px;text-align:center;color:#94a3b8;font-size:14px">Nenhuma palavra-chave cadastrada.</div>
        @endforelse
    </div>

    @if($showModal)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)">
        <div style="background:white;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);padding:24px;width:100%;max-width:400px;margin:16px">
            <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 20px">{{ $editingId ? 'Editar' : 'Nova' }} Palavra-chave</h3>
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:6px;text-transform:uppercase">Palavra</label>
                <input wire:model="palavra" type="text" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;box-sizing:border-box;text-transform:uppercase">
                @error('palavra')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
            </div>
            <div style="margin-bottom:20px">
                <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:6px;text-transform:uppercase">Cor (hex sem #)</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input wire:model.live="cor" type="text" maxlength="6" style="flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:monospace;box-sizing:border-box;text-transform:uppercase">
                    <div style="width:40px;height:40px;border-radius:8px;border:1px solid #e2e8f0;flex-shrink:0;background:#{{ $cor }}"></div>
                </div>
                @error('cor')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
            </div>
            <div style="display:flex;gap:8px">
                <button wire:click="$set('showModal',false)" style="flex:1;padding:10px;border:1px solid #e2e8f0;border-radius:8px;background:white;font-size:14px;cursor:pointer;font-family:inherit">Cancelar</button>
                <button wire:click="save" style="flex:1;padding:10px;background:#1e3a5f;color:white;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">Salvar</button>
            </div>
        </div>
    </div>
    @endif
</div>

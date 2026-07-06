<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2 style="font-size:15px;font-weight:600;color:#1e293b;margin:0">Unidades</h2>
        <button wire:click="openCreate" style="background:#1e3a5f;color:white;border:none;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">+ Nova Unidade</button>
    </div>

    @if (session()->has('message'))
        <div style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:10px 14px;border-radius:8px;font-size:14px;margin-bottom:16px">
            {{ session('message') }}
        </div>
    @endif

    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Nome</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Sigla</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Código</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Status</th>
                    <th style="padding:12px 20px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($unidades as $u)
                <tr style="border-bottom:1px solid #f1f5f9">
                    <td style="padding:12px 20px;font-weight:600;color:#1e3a5f">{{ $u->nome }}</td>
                    <td style="padding:12px 20px;font-family:monospace;font-size:12px;color:#64748b">{{ $u->sigla }}</td>
                    <td style="padding:12px 20px;font-family:monospace;font-size:12px;color:#64748b">{{ $u->codigo }}</td>
                    <td style="padding:12px 20px">
                        <button wire:click="toggleAtivo({{ $u->id }})"
                                style="font-size:11px;padding:3px 8px;border-radius:20px;font-weight:500;border:none;cursor:pointer;transition:opacity .1s;{{ $u->ativo ? 'background:#f0fdf4;color:#16a34a' : 'background:#f1f5f9;color:#94a3b8' }}"
                                title="Clique para alternar">
                            {{ $u->ativo ? 'Ativo' : 'Inativo' }}
                        </button>
                    </td>
                    <td style="padding:12px 20px;text-align:right">
                        <button wire:click="openEdit({{ $u->id }})" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px" title="Editar">
                            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8">Nenhuma unidade cadastrada. Clique em "+ Nova Unidade" para começar.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($unidades->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9">{{ $unidades->links() }}</div>
        @endif
    </div>

    @if($showModal)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)">
        <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:480px;margin:16px;max-height:90vh;overflow-y:auto">
            <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 20px">{{ $editingId ? 'Editar' : 'Nova' }} Unidade</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Nome da Unidade</label>
                    <input wire:model="nome" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;box-sizing:border-box" placeholder="Ex: MINHA-OM">
                    @error('nome') <p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Sigla</label>
                    <input wire:model="sigla" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:monospace;box-sizing:border-box" placeholder="MINHA-OM">
                    @error('sigla') <p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Código</label>
                    <input wire:model="codigo" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:monospace;box-sizing:border-box" placeholder="12000">
                    @error('codigo') <p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p> @enderror
                </div>
                <div style="grid-column:1/-1">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:#475569">
                        <input type="checkbox" wire:model="ativo"> Unidade ativa
                    </label>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:24px">
                <button wire:click="$set('showModal', false)" style="flex:1;padding:10px;border:1px solid #e2e8f0;border-radius:8px;background:white;font-size:14px;cursor:pointer;font-family:inherit">Cancelar</button>
                <button wire:click="save" style="flex:1;padding:10px;background:#1e3a5f;color:white;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">Salvar</button>
            </div>
        </div>
    </div>
    @endif
</div>

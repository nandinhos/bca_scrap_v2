<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2 style="font-size:15px;font-weight:600;color:#1e293b;margin:0">Efetivo</h2>
        <div style="display:flex;gap:12px">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar nome ou SARAM..."
                style="border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;font-size:14px;font-family:inherit;width:220px">
            <button wire:click="openCreate" style="background:#1e3a5f;color:white;border:none;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">+ Novo</button>
        </div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:12px;flex-wrap:wrap">
        <div style="display:flex;gap:10px;align-items:center">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#64748b;cursor:pointer">
                <input type="checkbox" wire:click="toggleSelectAll" style="width:16px;height:16px"> Selecionar todos
            </label>
            @if(count($selectedIds) > 0)
            <span style="font-size:12px;color:#64748b">{{ count($selectedIds) }} selecionado(s)</span>
            <button wire:click="bulkDelete" wire:confirm="Excluir {{ count($selectedIds) }} militar(es)?"
                style="background:#dc2626;color:white;border:none;border-radius:6px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer">
                🗑 Excluir selecionados
            </button>
            @endif
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('efetivo.export') }}" download style="text-decoration:none;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px">
                📥 Exportar CSV
            </a>
            <button wire:click="$set('showImportModal', true)" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px">
                📤 Importar CSV
            </button>
            <a href="/sample_efetivo.csv" download style="text-decoration:none;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px">
                📄 Baixar modelo
            </a>
        </div>
    </div>

    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <th style="width:40px;text-align:left;padding:12px 16px"></th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">SARAM</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Nome Guerra</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Posto</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Email</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Status</th>
                    <th style="padding:12px 20px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($efetivos as $e)
                <tr style="border-bottom:1px solid #f1f5f9;{{ $e->oculto ? 'opacity:.5' : '' }}">
                    <td style="padding:12px 16px;text-align:center">
                        <input type="checkbox" value="{{ $e->id }}" wire:model.live="selectedIds" style="width:16px;height:16px;cursor:pointer">
                    </td>
                    <td style="padding:12px 20px;font-family:monospace;font-size:12px;color:#64748b">{{ $e->saram }}</td>
                    <td style="padding:12px 20px;font-weight:600;color:#1e3a5f">{{ $e->nome_guerra }}</td>
                    <td style="padding:12px 20px;color:#64748b;font-size:12px">{{ $e->posto }}</td>
                    <td style="padding:12px 20px;color:#94a3b8;font-size:12px">{{ $e->email ?? '—' }}</td>
                    <td style="padding:12px 20px">
                        <span style="font-size:11px;padding:3px 8px;border-radius:20px;font-weight:500;{{ $e->ativo ? 'background:#f0fdf4;color:#16a34a' : 'background:#f1f5f9;color:#94a3b8' }}">{{ $e->ativo ? 'Ativo' : 'Inativo' }}</span>
                        @if($e->oculto) <span style="font-size:11px;padding:3px 8px;border-radius:20px;background:#fffbeb;color:#d97706;font-weight:500;margin-left:4px">Oculto</span> @endif
                    </td>
                    <td style="padding:12px 20px;text-align:right">
                        <button wire:click="openEdit({{ $e->id }})" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px">
                            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="padding:40px;text-align:center;color:#94a3b8">Nenhum militar encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($efetivos->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9">{{ $efetivos->links() }}</div>
        @endif
    </div>

    @if($showImportLog)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)">
        <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:700px;margin:16px;max-height:80vh;overflow-y:auto">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0">Log de Importação</h3>
                <button wire:click="$set('showImportLog', false)" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:20px">×</button>
            </div>
            <textarea readonly style="width:100%;height:300px;font-family:monospace;font-size:12px;border:1px solid #e2e8f0;border-radius:8px;padding:12px;background:#f8fafc;resize:none">{{ implode("\n", $importLog) }}</textarea>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button type="button" onclick="navigator.clipboard.writeText(document.querySelector('textarea').value)" style="flex:1;padding:10px;border:1px solid #e2e8f0;border-radius:8px;background:white;font-size:14px;cursor:pointer">📋 Copiar log</button>
                <button wire:click="$set('showImportLog', false)" style="flex:1;padding:10px;background:#1e3a5f;color:white;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer">Fechar</button>
            </div>
        </div>
    </div>
    @endif

    @if($showImportModal)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)">
        <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:500px;margin:16px">
            <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 20px">Importar Efetivo via CSV</h3>
            <div style="margin-bottom:16px">
                <input type="file" wire:model="uploadedFile" accept=".csv" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px;font-size:14px">
                <p style="font-size:12px;color:#94a3b8;margin:6px 0 0">Formato: saram,nome_completo,nome_guerra,posto,especialidade,email,om_origem,unidade_id</p>
            </div>
            <div style="display:flex;gap:8px">
                <button wire:click="$set('showImportModal', false)" style="flex:1;padding:10px;border:1px solid #e2e8f0;border-radius:8px;background:white;font-size:14px;cursor:pointer">Cancelar</button>
                <button wire:click="importCsv" wire:loading.attr="disabled" style="flex:1;padding:10px;background:#1e3a5f;color:white;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer">
                    <span wire:loading.remove wire:target="importCsv">Importar</span>
                    <span wire:loading wire:target="importCsv">Processando...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($showModal)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)">
        <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:560px;margin:16px;max-height:90vh;overflow-y:auto">
            <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 20px">{{ $editingId ? 'Editar' : 'Novo' }} Militar</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">SARAM</label>
                    <input wire:model="saram" maxlength="8" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:monospace;box-sizing:border-box">
                    @error('saram')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Nome Guerra</label>
                    <input wire:model="nomeGuerra" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;text-transform:uppercase;box-sizing:border-box">
                    @error('nomeGuerra')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Nome Completo</label>
                    <input wire:model="nomeCompleto" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;text-transform:uppercase;box-sizing:border-box">
                    @error('nomeCompleto')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Posto</label>
                    <input wire:model="posto" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Email</label>
                    <input wire:model="email" type="email" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;box-sizing:border-box">
                    @error('email')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column:1/-1;display:flex;gap:20px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:#475569">
                        <input type="checkbox" wire:model="ativo"> Ativo
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:#475569">
                        <input type="checkbox" wire:model="oculto"> Oculto (excluir de buscas)
                    </label>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:24px">
                <button wire:click="$set('showModal',false)" style="flex:1;padding:10px;border:1px solid #e2e8f0;border-radius:8px;background:white;font-size:14px;cursor:pointer;font-family:inherit">Cancelar</button>
                <button wire:click="save" style="flex:1;padding:10px;background:#1e3a5f;color:white;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">Salvar</button>
            </div>
        </div>
    </div>
    @endif
</div>

<div style="max-width:700px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2 style="font-size:15px;font-weight:600;color:#1e293b;margin:0">Usuários</h2>
        <button wire:click="openCreate" style="background:#1e3a5f;color:white;border:none;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">+ Novo usuário</button>
    </div>
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Nome</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Email</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Perfil</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $u)
                <tr style="border-bottom:1px solid #f1f5f9">
                    <td style="padding:12px 20px;font-weight:500;color:#334155">{{ $u->name }}</td>
                    <td style="padding:12px 20px;color:#64748b;font-size:12px">{{ $u->email }}</td>
                    <td style="padding:12px 20px">
                        <span style="font-size:11px;padding:3px 8px;border-radius:20px;font-weight:500;{{ $u->role==='admin' ? 'background:#f5f3ff;color:#7c3aed' : 'background:#eff6ff;color:#2563eb' }}">{{ ucfirst($u->role) }}</span>
                    </td>
                    <td style="padding:12px 20px;text-align:right">
                        <button wire:click="openEdit({{ $u->id }})" style="background:none;border:none;cursor:pointer;color:#94a3b8">
                            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="padding:40px;text-align:center;color:#94a3b8">Nenhum usuário.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showModal)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5)">
        <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:400px;margin:16px">
            <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 20px">{{ $editingId ? 'Editar' : 'Novo' }} Usuário</h3>
            <div style="display:flex;flex-direction:column;gap:16px">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Nome</label>
                    <input wire:model="name" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;box-sizing:border-box">
                    @error('name')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Email</label>
                    <input wire:model="email" type="email" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;box-sizing:border-box">
                    @error('email')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Senha {{ $editingId ? '(em branco = sem alteração)' : '' }}</label>
                    <input wire:model="password" type="password" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;box-sizing:border-box">
                    @error('password')<p style="font-size:12px;color:#dc2626;margin:4px 0 0">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Perfil</label>
                    <select wire:model="role" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;background:white">
                        <option value="operador">Operador</option>
                        <option value="admin">Admin</option>
                    </select>
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

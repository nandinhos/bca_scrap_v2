<div>
    <h2 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 16px">Histórico de Ocorrências</h2>
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;display:flex;gap:16px;flex-wrap:wrap">
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Data BCA</label>
            <input type="date" wire:model.live="filtroData" style="border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:14px;font-family:inherit">
        </div>
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;text-transform:uppercase">Militar</label>
            <input type="text" wire:model.live.debounce.300ms="filtroMilitar" placeholder="Nome..." style="border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:14px;font-family:inherit;width:200px">
        </div>
    </div>
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Data</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em">BCA nº</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Militar</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Posto</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Email</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ocorrencias as $oc)
                <tr style="border-bottom:1px solid #f1f5f9">
                    <td style="padding:12px 20px;color:#64748b">{{ $oc->bca->data->format('d/m/Y') }}</td>
                    <td style="padding:12px 20px;color:#64748b">{{ $oc->bca->numero }}</td>
                    <td style="padding:12px 20px;font-weight:600;color:#1e3a5f">{{ $oc->efetivo->nome_guerra }}</td>
                    <td style="padding:12px 20px;color:#94a3b8;font-size:12px">{{ $oc->efetivo->posto }}</td>
                    <td style="padding:12px 20px">
                        @if($oc->enviado_em)
                            <span style="font-size:11px;background:#f0fdf4;color:#16a34a;padding:3px 8px;border-radius:20px;font-weight:500">✓ {{ $oc->enviado_em->format('d/m H:i') }}</span>
                        @else
                            <span style="font-size:11px;color:#94a3b8">Pendente</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8">Nenhuma ocorrência encontrada.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($ocorrencias->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9">{{ $ocorrencias->links() }}</div>
        @endif
    </div>
</div>

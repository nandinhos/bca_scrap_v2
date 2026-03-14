<div>
    <h2 style="font-size:15px;font-weight:600;color:#1e293b;margin:0 0 16px">Log de Execuções</h2>
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Data/Hora</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Tipo</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Status</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Registros</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">Mensagem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($execucoes as $ex)
                <tr style="border-bottom:1px solid #f1f5f9">
                    <td style="padding:12px 20px;font-family:monospace;font-size:12px;color:#64748b">{{ $ex->data_execucao->format('d/m/Y H:i:s') }}</td>
                    <td style="padding:12px 20px"><span style="font-size:11px;background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:4px">{{ $ex->tipo }}</span></td>
                    <td style="padding:12px 20px">
                        @if($ex->status==='sucesso') <span style="font-size:11px;background:#f0fdf4;color:#16a34a;padding:3px 8px;border-radius:20px;font-weight:500">✓ sucesso</span>
                        @elseif($ex->status==='falha') <span style="font-size:11px;background:#fef2f2;color:#dc2626;padding:3px 8px;border-radius:20px;font-weight:500">✗ falha</span>
                        @else <span style="font-size:11px;background:#f1f5f9;color:#94a3b8;padding:3px 8px;border-radius:20px">sem BCA</span>
                        @endif
                    </td>
                    <td style="padding:12px 20px;color:#475569">{{ $ex->registros_processados }}</td>
                    <td style="padding:12px 20px;color:#94a3b8;font-size:12px;max-width:300px">
                        @if($ex->mensagem)
                            @php $msg = json_decode($ex->mensagem, true); @endphp
                            @if(isset($msg['keywords_encontradas'])) Keywords: {{ implode(', ', $msg['keywords_encontradas']) }}
                            @else {{ Str::limit($ex->mensagem, 80) }} @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8">Nenhuma execução registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($execucoes->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9">{{ $execucoes->links() }}</div>
        @endif
    </div>
</div>

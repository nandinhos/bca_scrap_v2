<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
body{margin:0;padding:0;background:#f8fafc;font-family:'Helvetica Neue',Arial,sans-serif}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.hd{background:#1e3a5f;padding:24px 32px}
.hd h1{margin:0;color:#fff;font-size:18px;font-weight:600}
.hd p{margin:4px 0 0;color:rgba(255,255,255,.6);font-size:13px}
.bd{padding:28px 32px}
.title{font-size:15px;color:#1e293b;font-weight:600;margin:0 0 12px}
.text{font-size:14px;color:#475569;line-height:1.6;margin:0 0 16px}
.table-wrap{margin:0 0 20px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#1e3a5f;color:#fff;padding:10px 12px;text-align:left;font-weight:600}
td{padding:10px 12px;border-bottom:1px solid #e2e8f0;color:#334155}
tr:last-child td{border-bottom:none}
.btn{display:inline-block;background:#1e3a5f;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-size:14px;font-weight:600}
.ft{background:#f8fafc;padding:16px 32px;border-top:1px solid #e2e8f0;font-size:11px;color:#94a3b8}
</style>
</head>
<body>
<div class="wrap">
    <div class="hd">
        <h1>📋 Compilado Diário - BCA Scrap v2</h1>
        <p>GAC-PAC · Força Aérea Brasileira</p>
    </div>
    <div class="bd">
        <p class="title">BCA nº {{ $bca->numero }} de {{ $bca->data->format('d/m/Y') }}</p>
        <p class="text">Total de militares encontrados: <strong>{{ $ocorrencias->count() }}</strong></p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SARAM</th>
                        <th>Nome de Guerra</th>
                        <th>Posto</th>
                        <th>Unidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ocorrencias as $oc)
                    <tr>
                        <td>{{ $oc->efetivo->saram }}</td>
                        <td>{{ $oc->efetivo->nome_guerra }}</td>
                        <td>{{ $oc->efetivo->posto }}</td>
                        <td>{{ $oc->efetivo->unidade->sigla ?? 'GAC-PAC' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($bcaDownloadUrl)
        <a href="{{ $bcaDownloadUrl }}" class="btn">Baixar BCA completo →</a>
        @endif
    </div>
    <div class="ft">Email automático — BCA Scrap v2 · GAC-PAC · FAB</div>
</div>
</body>
</html>
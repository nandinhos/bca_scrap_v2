<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
body{margin:0;padding:0;background:#f8fafc;font-family:'Helvetica Neue',Arial,sans-serif}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.hd{background:#1e3a5f;padding:24px 32px}
.hd h1{margin:0;color:#fff;font-size:18px;font-weight:600}
.hd p{margin:4px 0 0;color:#94a3b8;font-size:13px}
.bd{padding:28px 32px}
.greeting{font-size:15px;color:#1e293b;font-weight:600;margin:0 0 12px}
.text{font-size:14px;color:#475569;line-height:1.6;margin:0 0 16px}
.snippet{background:#f1f5f9;border-left:3px solid #3b6aab;border-radius:4px;padding:14px 16px;font-family:monospace;font-size:12px;color:#334155;white-space:pre-wrap;line-height:1.7;margin:0 0 20px}
.btn{display:inline-block;background:#1e3a5f;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-size:14px;font-weight:600}
.ft{background:#f8fafc;padding:16px 32px;border-top:1px solid #e2e8f0;font-size:11px;color:#94a3b8}
</style>
</head>
<body>
<div class="wrap">
    <div class="hd">
        <h1>🛡 BCA Scrap v2</h1>
        <p>GAC-PAC · Força Aérea Brasileira</p>
    </div>
    <div class="bd">
        <p class="greeting">Olá, {{ $ocorrencia->efetivo->nome_guerra }}!</p>
        <p class="text">Você foi mencionado no <strong>BCA nº {{ $ocorrencia->bca->numero }}</strong> de {{ $ocorrencia->bca->data->format('d/m/Y') }}.</p>
        @if($ocorrencia->snippet)
        <div class="snippet">{!! strip_tags($ocorrencia->snippet) !!}</div>
        @endif
        @if($bcaDownloadUrl)
        <a href="{{ $bcaDownloadUrl }}" class="btn">Baixar BCA completo →</a>
        @endif
    </div>
    <div class="ft">Email automático — BCA Scrap v2 · GAC-PAC · FAB</div>
</div>
</body>
</html>

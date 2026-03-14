<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'BCA Scrap v2' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
</head>
<body style="font-family:'Inter',sans-serif;background:#f8fafc" x-data="{ open: true }">
<div style="display:flex;height:100vh;overflow:hidden">

    <!-- Sidebar -->
    <aside style="background:#1e3a5f;display:flex;flex-direction:column;transition:width .2s" :style="{ width: open ? '240px' : '64px' }">
        <!-- Logo -->
        <div style="height:64px;display:flex;align-items:center;padding:0 16px;border-bottom:1px solid rgba(255,255,255,.1)">
            <div style="width:32px;height:32px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg style="width:18px;height:18px;color:white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <span x-show="open" x-transition style="color:white;font-weight:600;font-size:14px;margin-left:12px;white-space:nowrap">BCA Scrap v2</span>
        </div>

        <!-- Nav -->
        <nav style="flex:1;padding:16px 8px;overflow-y:auto">
            @php
            $nav = [
                ['route'=>'dashboard','label'=>'Busca BCA','icon'=>'search'],
                ['route'=>'historico','label'=>'Histórico','icon'=>'clock'],
                ['route'=>'palavras-chave','label'=>'Palavras-chave','icon'=>'tag'],
            ];
            $admin = [
                ['route'=>'efetivo','label'=>'Efetivo','icon'=>'users'],
                ['route'=>'usuarios','label'=>'Usuários','icon'=>'user-cog'],
                ['route'=>'execucoes','label'=>'Execuções','icon'=>'activity'],
            ];
            @endphp
            @foreach($nav as $item)
            <a href="{{ route($item['route']) }}" style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;text-decoration:none;margin-bottom:2px;transition:background .1s;{{ request()->routeIs($item['route']) ? 'background:rgba(255,255,255,.2);color:white' : 'color:rgba(255,255,255,.6)' }}"
               onmouseover="if(!this.classList.contains('active'))this.style.background='rgba(255,255,255,.1)'"
               onmouseout="if(!this.classList.contains('active'))this.style.background='transparent'">
                <x-nav-icon :type="$item['icon']" style="width:20px;height:20px;flex-shrink:0"/>
                <span x-show="open" x-transition style="font-size:14px;font-weight:500;white-space:nowrap">{{ $item['label'] }}</span>
            </a>
            @endforeach

            @if(auth()->user()?->isAdmin())
            <div x-show="open" style="padding:16px 12px 4px;font-size:11px;font-weight:600;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.05em">Admin</div>
            <div x-show="!open" style="margin:8px 0;border-top:1px solid rgba(255,255,255,.1)"></div>
            @foreach($admin as $item)
            <a href="{{ route($item['route']) }}" style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;text-decoration:none;margin-bottom:2px;{{ request()->routeIs($item['route']) ? 'background:rgba(255,255,255,.2);color:white' : 'color:rgba(255,255,255,.6)' }}">
                <x-nav-icon :type="$item['icon']" style="width:20px;height:20px;flex-shrink:0"/>
                <span x-show="open" x-transition style="font-size:14px;font-weight:500;white-space:nowrap">{{ $item['label'] }}</span>
            </a>
            @endforeach
            @endif
        </nav>

        <!-- Bottom -->
        <div style="border-top:1px solid rgba(255,255,255,.1);padding:12px 8px">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="width:100%;display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;background:none;border:none;cursor:pointer;color:rgba(255,255,255,.5)">
                    <svg style="width:18px;height:18px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span x-show="open" x-transition style="font-size:13px;white-space:nowrap">Sair</span>
                </button>
            </form>
            <button @click="open=!open" style="width:100%;display:flex;align-items:center;padding:8px;border-radius:8px;background:none;border:none;cursor:pointer;color:rgba(255,255,255,.3);transition:all .2s;justify-content:center" :style="{ gap: open ? '12px' : '0' }">
                <div style="width:16px;height:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="16" height="16" style="transition:transform .2s" :style="{ transform: open ? 'none' : 'rotate(180deg)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </div>
                <span x-show="open" style="font-size:12px;white-space:nowrap">Recolher</span>
            </button>
        </div>
    </aside>

    <!-- Main -->
    <div style="flex:1;display:flex;flex-direction:column;min-width:0;overflow:hidden">
        <header style="height:56px;background:white;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;padding:0 24px;flex-shrink:0">
            <h1 style="font-size:14px;font-weight:600;color:#475569;margin:0">{{ $title ?? 'Dashboard' }}</h1>
            <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
                <span style="font-size:12px;color:#475569">{{ auth()->user()?->name }}</span>
                <span style="font-size:11px;background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:20px;font-weight:500">● ativo</span>
            </div>
        </header>
        <main style="flex:1;overflow-y:auto;padding:24px">
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>

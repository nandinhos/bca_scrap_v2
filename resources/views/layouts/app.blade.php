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
<body class="min-h-screen bg-slate-50 text-zinc-900" style="font-family:'Inter',sans-serif" x-data="{ open: true }">
<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="bg-primary-700 text-white flex flex-col transition-all duration-200" :class="open ? 'w-60' : 'w-16'">
        {{-- Logo --}}
        <div class="h-16 flex items-center px-4 border-b border-white/10 shrink-0">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <span x-show="open" x-transition class="font-semibold text-sm ml-3 whitespace-nowrap">BCA Scrap v2</span>
        </div>

        {{-- Nav --}}
        @php
            $nav = [
                ['route' => 'dashboard',      'label' => 'Busca BCA',     'icon' => 'search'],
                ['route' => 'historico',      'label' => 'Histórico',     'icon' => 'clock'],
                ['route' => 'palavras-chave', 'label' => 'Palavras-chave','icon' => 'tag'],
            ];
            $admin = [
                ['route' => 'efetivo',   'label' => 'Efetivo',  'icon' => 'users'],
                ['route' => 'usuarios',  'label' => 'Usuários', 'icon' => 'user-cog'],
                ['route' => 'unidades',  'label' => 'Unidades', 'icon' => 'building'],
                ['route' => 'execucoes', 'label' => 'Execuções','icon' => 'activity'],
            ];
        @endphp

        <nav class="flex-1 px-2 py-4 overflow-y-auto">
            @foreach($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 text-sm font-medium transition-colors
                          {{ request()->routeIs($item['route'])
                                ? 'bg-white/20 text-white'
                                : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        @if($item['icon'] === 'search')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        @elseif($item['icon'] === 'clock')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @elseif($item['icon'] === 'tag')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        @endif
                    </svg>
                    <span x-show="open" x-transition class="whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach

            @if(auth()->user()?->isAdmin())
                <div x-show="open" class="px-3 py-3 mt-4 text-[11px] font-semibold text-white/40 uppercase tracking-wider">Admin</div>
                <div x-show="!open" class="my-2 border-t border-white/10"></div>
                @foreach($admin as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 text-sm font-medium transition-colors
                              {{ request()->routeIs($item['route'])
                                    ? 'bg-white/20 text-white'
                                    : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            @if($item['icon'] === 'users')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            @elseif($item['icon'] === 'user-cog')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            @elseif($item['icon'] === 'building')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            @elseif($item['icon'] === 'activity')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            @endif
                        </svg>
                        <span x-show="open" x-transition class="whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        {{-- Bottom: Sair + Recolher --}}
        <div class="border-t border-white/10 p-2 space-y-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg bg-transparent border-0 cursor-pointer text-white/50 hover:text-white hover:bg-white/10 transition-colors text-sm">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span x-show="open" x-transition class="text-sm whitespace-nowrap">Sair</span>
                </button>
            </form>
            <button @click="open=!open"
                    class="w-full flex items-center justify-center px-3 py-2 rounded-lg bg-transparent border-0 cursor-pointer text-white/30 hover:text-white hover:bg-white/10 transition-colors"
                    :class="open ? 'gap-3' : 'gap-0'">
                <svg class="w-4 h-4 transition-transform duration-200" :class="open ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <span x-show="open" class="text-xs whitespace-nowrap">Recolher</span>
            </button>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="h-14 bg-white border-b border-zinc-200 flex items-center px-6 shrink-0">
            <h1 class="text-sm font-semibold text-zinc-700 m-0">{{ $title ?? 'Dashboard' }}</h1>
            <div class="ml-auto flex items-center gap-2">
                <span class="text-xs text-zinc-600">{{ auth()->user()?->name }}</span>
                <span class="text-[11px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-medium">● ativo</span>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>

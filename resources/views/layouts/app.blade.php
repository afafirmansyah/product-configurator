<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Product Configurator') — Product Configurator</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full flex flex-col" style="background:#f8fafc">

    {{-- ── Navigation ──────────────────────────────────────────────────────────── --}}
    <header class="bg-white border-b sticky top-0 z-40" style="border-color:#e2e8f0">
        <div class="max-w-screen-xl mx-auto px-6 h-14 flex items-center justify-between">

            {{-- Brand --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('sales.catalog') }}" class="flex items-center">
                    <img src="/logo_black.png" alt="Product Configurator" class="h-5 object-contain">
                </a>
                @auth
                    <nav class="hidden md:flex items-center gap-1">
                        <a href="{{ route('sales.catalog') }}"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('sales.catalog*') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                            Catalog
                        </a>
                        <a href="{{ route('sales.configurations') }}"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('sales.configurations*') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                            My Configurations
                        </a>
                    </nav>
                @endauth
            </div>

            {{-- Right --}}
            <div class="flex items-center gap-2">
                @auth
                    @if (auth()->user()->hasRole(['super_admin', 'product_manager', 'engineering']))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-ghost text-slate-500">
                            <x-ui.icon name="settings" class="w-3.5 h-3.5" />
                            Admin
                        </a>
                    @endif
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border transition-colors hover:bg-slate-50"
                            style="border-color:#e2e8f0">

                            <div class="w-7 h-7 ml-2 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                style="background:#2563eb">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>

                            <span class="text-sm font-medium text-slate-700 hidden sm:block max-w-32 truncate">
                                {{ auth()->user()->name }}
                            </span>

                            <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-2" />
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak
                            class="absolute right-0 mt-1.5 w-44 bg-white rounded-xl border shadow-md py-1 z-50"
                            style="border-color:#e2e8f0">
                            <div class="px-3 py-2 border-b" style="border-color:#f1f5f9">
                                <div class="text-xs font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</div>
                            </div>
                            <a href="{{ route('profile.edit') }}"
                                class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                <x-ui.icon name="user-circle" class="w-3.5 h-3.5 text-slate-400" />
                                Profile
                            </a>
                            <div class="border-t my-1" style="border-color:#f1f5f9"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                    <x-ui.icon name="logout" class="w-3.5 h-3.5 text-slate-400" />
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-primary">Sign in</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- ── Flash ────────────────────────────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="max-w-screen-xl mx-auto px-6 pt-4">
            <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
        </div>
    @endif
    @if (session('error'))
        <div class="max-w-screen-xl mx-auto px-6 pt-4">
            <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
        </div>
    @endif

    {{-- ── Content ──────────────────────────────────────────────────────────────── --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ── Footer ───────────────────────────────────────────────────────────────── --}}
    <footer class="bg-white border-t mt-auto" style="border-color:#e2e8f0">
        <div class="max-w-screen-xl mx-auto px-6 py-4 flex items-center justify-between">
            <span class="text-xs text-slate-400">© {{ date('Y') }} Hectronic India Pvt Ltd. All rights
                reserved.</span>
            <span class="text-xs text-slate-400">v1.0</span>
        </div>
    </footer>

    @livewireScripts
</body>

</html>

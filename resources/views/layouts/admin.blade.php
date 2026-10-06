<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Product Configurator</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full bg-slate-50" x-data="{ sidebarOpen: false }">

    <div class="flex h-full">

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <aside class="sidebar fixed inset-y-0 left-0 z-40 w-60 flex flex-col" style="background:#0f172a">

            {{-- Logo --}}
            <div class="flex items-center gap-2.5 px-5 h-14 border-b" style="border-color:#1e293b">
                <img src="/logo_white.png" alt="Product Configurator" class="h-6">
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">

                <a href="{{ route('admin.dashboard') }}"
                    class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <x-ui.icon name="dashboard" class="w-4 h-4 flex-shrink-0" />
                    <span>Dashboard</span>
                </a>

                <div class="sidebar-section-label">Catalog</div>

                <a href="{{ route('admin.products.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <x-ui.icon name="products" class="w-4 h-4 flex-shrink-0" />
                    <span>Products</span>
                </a>
                <a href="{{ route('admin.categories.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <x-ui.icon name="category" class="w-4 h-4 flex-shrink-0" />
                    <span>Categories</span>
                </a>
                <a href="{{ route('admin.families.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.families.*') ? 'active' : '' }}">
                    <x-ui.icon name="family" class="w-4 h-4 flex-shrink-0" />
                    <span>Families</span>
                </a>

                <div class="sidebar-section-label">Configuration</div>

                <a href="{{ route('admin.attributes.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.attributes.*') ? 'active' : '' }}">
                    <x-ui.icon name="attributes" class="w-4 h-4 flex-shrink-0" />
                    <span>Attributes</span>
                </a>
                <a href="{{ route('admin.rules.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.rules.*') ? 'active' : '' }}">
                    <x-ui.icon name="rules" class="w-4 h-4 flex-shrink-0" />
                    <span>Rules</span>
                </a>
                <a href="{{ route('admin.compatibility.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.compatibility.*') ? 'active' : '' }}">
                    <x-ui.icon name="compatibility" class="w-4 h-4 flex-shrink-0" />
                    <span>Compatibility</span>
                </a>

                <div class="sidebar-section-label">Engineering</div>

                <a href="{{ route('admin.components.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.components.*') ? 'active' : '' }}">
                    <x-ui.icon name="components" class="w-4 h-4 flex-shrink-0" />
                    <span>Components</span>
                </a>
                <a href="{{ route('admin.bom.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.bom.*') ? 'active' : '' }}">
                    <x-ui.icon name="bom" class="w-4 h-4 flex-shrink-0" />
                    <span>BOM Rules</span>
                </a>
                <a href="{{ route('admin.part-numbers.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.part-numbers.*') ? 'active' : '' }}">
                    <x-ui.icon name="part-number" class="w-4 h-4 flex-shrink-0" />
                    <span>Part Numbers</span>
                </a>

                <div class="sidebar-section-label">Commercial</div>

                <a href="{{ route('admin.pricing.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                    <x-ui.icon name="pricing" class="w-4 h-4 flex-shrink-0" />
                    <span>Pricing</span>
                </a>
                <a href="{{ route('admin.configurations.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.configurations.*') ? 'active' : '' }}">
                    <x-ui.icon name="configurations" class="w-4 h-4 flex-shrink-0" />
                    <span>Configurations</span>
                </a>
                <a href="{{ route('admin.quotations.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.quotations.*') ? 'active' : '' }}">
                    <x-ui.icon name="quotations" class="w-4 h-4 flex-shrink-0" />
                    <span>Quotations</span>
                </a>

                <div class="sidebar-section-label">Access</div>

                <a href="{{ route('admin.users.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <x-ui.icon name="users" class="w-4 h-4 flex-shrink-0" />
                    <span>Users</span>
                </a>
                <a href="{{ route('admin.roles.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <x-ui.icon name="roles" class="w-4 h-4 flex-shrink-0" />
                    <span>Roles</span>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <x-ui.icon name="audit" class="w-4 h-4 flex-shrink-0" />
                    <span>Audit Logs</span>
                </a>
                <a href="{{ route('admin.settings.index') }}"
                    class="sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <x-ui.icon name="settings" class="w-4 h-4 flex-shrink-0" />
                    <span>Settings</span>
                </a>
            </nav>

            {{-- User footer --}}
            <div class="border-t px-3 py-3 space-y-1" style="border-color:#1e293b">
                <a href="{{ route('catalog.index') }}" class="sidebar-item text-xs">
                    <x-ui.icon name="external-link" class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>View Main App</span>
                </a>
                @auth
                    <div class="flex items-center gap-2.5 px-3 py-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                            style="background:#2563eb">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-slate-200 truncate">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-500 hover:text-slate-300 transition-colors"
                                title="Logout">
                                <x-ui.icon name="logout" class="w-4 h-4" />
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </aside>

        {{-- ── Main ─────────────────────────────────────────────────────────── --}}
        <div class="flex-1 flex flex-col min-w-0" style="margin-left:15rem">

            {{-- Top bar --}}
            <header class="page-header bg-white">
                <div class="flex items-center gap-2 min-w-0">
                    @hasSection('breadcrumb')
                        <nav class="flex items-center gap-1.5 text-sm text-slate-400">
                            @yield('breadcrumb')
                        </nav>
                    @else
                        <h1 class="text-sm font-semibold text-slate-800">@yield('heading', 'Dashboard')</h1>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @yield('header-actions')
                    @auth
                        <a href="{{ route('profile.edit') }}"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                            style="background:#2563eb">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </a>
                    @endauth
                </div>
            </header>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mx-6 mt-4">
                    <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
                </div>
            @endif
            @if (session('error'))
                <div class="mx-6 mt-4">
                    <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
                </div>
            @endif

            {{-- Page content --}}
            <main class="flex-1 p-6 overflow-auto">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    <script>
        // Auto-dismiss flash messages
        setTimeout(() => {
            document.querySelectorAll('[data-flash]').forEach(el => el.remove());
        }, 4000);
    </script>
</body>

</html>

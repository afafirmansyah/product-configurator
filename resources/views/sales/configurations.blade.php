@extends('layouts.app')
@section('title', 'My Configurations')

@section('content')
    <div class="max-w-screen-xl mx-auto px-6 py-8" x-data="{ deleteModal: false, deleteId: null, deleteNum: '' }">

        {{-- Delete Confirmation Modal --}}
        <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center"
            style="background:rgba(15,23,42,0.4)">
            <div @click.outside="deleteModal = false"
                class="bg-white rounded-2xl shadow-xl border w-full max-w-sm mx-4 overflow-hidden"
                style="border-color:#e2e8f0">
                <div class="px-6 pt-6 pb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-4" style="background:#fee2e2">
                        <x-ui.icon name="trash" class="w-5 h-5" style="color:#dc2626" />
                    </div>
                    <h3 class="text-base font-semibold text-slate-900 mb-1">Delete Configuration</h3>
                    <p class="text-sm text-slate-500">
                        Are you sure you want to delete <span class="font-semibold text-slate-700"
                            x-text="deleteNum"></span>?
                        This action cannot be undone.
                    </p>
                </div>
                <div class="px-6 pb-6 flex gap-3 justify-end">
                    <button @click="deleteModal = false" class="btn btn-sm btn-ghost">Cancel</button>
                    <form method="POST" :action="'/sales/configurations/' + deleteId">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">My Configurations</h1>
                <p class="text-sm text-slate-500 mt-0.5">Manage, edit, and export your product configurations</p>
            </div>
            <a href="{{ route('sales.catalog') }}" class="btn btn-sm btn-primary">
                <x-ui.icon name="plus" class="w-3.5 h-3.5" />
                New Configuration
            </a>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
        @endif
        @if (session('error'))
            <x-ui.alert type="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
        @endif

        {{-- Status Tabs --}}
        <div class="flex gap-1 mb-5 border-b" style="border-color:#e2e8f0">
            @foreach (['all' => 'All', 'draft' => 'Draft', 'validated' => 'Validated', 'finalized' => 'Finalized'] as $val => $label)
                <a href="{{ route('sales.configurations', array_merge(request()->except('status', 'page'), $val !== 'all' ? ['status' => $val] : [])) }}"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                      {{ request('status', 'all') === $val ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" class="flex gap-3 mb-5">
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search by name, customer, project..." class="form-input text-sm" style="width:360px">
            <button type="submit" class="btn btn-sm btn-primary">Search</button>
            @if (request('search'))
                <a href="{{ route('sales.configurations', request()->except('search', 'page')) }}"
                    class="btn btn-sm btn-ghost">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        @if ($configurations->isEmpty())
            <x-ui.empty-state icon="document" title="No configurations yet"
                description="Start by selecting a product from the catalog." action-url="{{ route('sales.catalog') }}"
                action-label="Browse Catalog" />
        @else
            <div class="card" style="overflow:visible">
                <table class="w-full text-sm" style="border-collapse:collapse">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">
                                Configuration</th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">Product
                            </th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">Customer
                                / Project</th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">Status
                            </th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">BOM</th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">Value
                            </th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">Updated
                            </th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-4 py-3 whitespace-nowrap">Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($configurations as $cfg)
                            <tr class="border-b hover:bg-slate-50 transition-colors" style="border-color:#f1f5f9">

                                {{-- Configuration --}}
                                <td class="px-4 py-3">
                                    <a href="{{ route('sales.configuration.detail', $cfg) }}"
                                        class="text-sm font-semibold text-blue-600 hover:text-blue-700 font-mono">
                                        {{ $cfg->configuration_number }}
                                    </a>
                                    @if ($cfg->name)
                                        <div class="text-xs text-slate-500 mt-0.5">{{ $cfg->name }}</div>
                                    @endif
                                    @if ($cfg->configuration_code)
                                        <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $cfg->configuration_code }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Product --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm text-slate-800 font-medium">{{ $cfg->product->name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $cfg->product->code }}</div>
                                </td>

                                {{-- Customer / Project --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm text-slate-700">{{ $cfg->customer ?? '—' }}</div>
                                    @if ($cfg->project)
                                        <div class="text-xs text-slate-400">{{ $cfg->project }}</div>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    @php $statusColors = ['draft'=>'gray','validated'=>'blue','finalized'=>'green']; @endphp
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="flex items-center gap-1 group">
                                            <x-ui.badge color="{{ $statusColors[$cfg->status] ?? 'gray' }}">
                                                {{ ucfirst($cfg->status) }}
                                            </x-ui.badge>
                                            <x-ui.icon name="chevron-down"
                                                class="w-3 h-3 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </button>
                                        <div x-show="open" @click.outside="open = false" x-cloak
                                            class="absolute left-0 mt-1 w-36 bg-white rounded-xl border shadow-md py-1 z-20"
                                            style="border-color:#e2e8f0">
                                            @foreach (['draft' => 'Draft', 'validated' => 'Validated', 'finalized' => 'Finalized'] as $s => $sl)
                                                @if ($s !== $cfg->status)
                                                    <form method="POST"
                                                        action="{{ route('sales.configuration.status', $cfg) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ $s }}">
                                                        <button type="submit"
                                                            class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                                            → {{ $sl }}
                                                        </button>
                                                    </form>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </td>

                                {{-- BOM --}}
                                <td class="px-4 py-3">
                                    @php $bomColors = ['pending'=>'gray','generated'=>'blue','validated'=>'green','failed'=>'red']; @endphp
                                    <x-ui.badge color="{{ $bomColors[$cfg->bom_status] ?? 'gray' }}">
                                        {{ ucfirst($cfg->bom_status) }}
                                    </x-ui.badge>
                                    @if ($cfg->bom_revision > 0)
                                        <div class="text-xs text-slate-400 mt-0.5">Rev {{ $cfg->bom_revision }}</div>
                                    @endif
                                </td>

                                {{-- Value --}}
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if ($cfg->total_price > 0)
                                        {{ number_format($cfg->total_price, 2) }}
                                        <span class="text-xs text-slate-400">{{ $cfg->currency }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Updated --}}
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    {{ $cfg->updated_at->diffForHumans() }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5" x-data="{ open: false }">
                                        <a href="{{ route('sales.configuration.detail', $cfg) }}"
                                            class="btn btn-sm btn-ghost">View</a>

                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="btn btn-sm btn-secondary">
                                                Actions
                                                <x-ui.icon name="chevron-down" class="w-3 h-3" />
                                            </button>
                                            <div x-show="open" @click.outside="open = false" x-cloak
                                                class="absolute right-0 mt-1 w-48 bg-white rounded-xl border shadow-md py-1 z-[9999]"
                                                style="border-color:#e2e8f0">

                                                <a href="{{ route('configurator.configure', $cfg->product->slug) }}"
                                                    class="flex items-center px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 whitespace-nowrap">
                                                    Edit
                                                </a>

                                                <div class="border-t my-1" style="border-color:#f1f5f9"></div>

                                                <form method="POST"
                                                    action="{{ route('sales.configuration.generate-bom', $cfg) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 whitespace-nowrap">
                                                        Generate BOM
                                                    </button>
                                                </form>

                                                @if ($cfg->bom_status !== 'pending')
                                                    <a href="{{ route('sales.bom.pdf', $cfg) }}"
                                                        class="flex items-center px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 whitespace-nowrap">
                                                        Download PDF
                                                    </a>
                                                    <a href="{{ route('sales.bom.excel', $cfg) }}"
                                                        class="flex items-center px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 whitespace-nowrap">
                                                        Export Excel
                                                    </a>
                                                @endif

                                                <div class="border-t my-1" style="border-color:#f1f5f9"></div>

                                                <button type="button"
                                                    @click="deleteModal = true; deleteId = '{{ $cfg->id }}'; deleteNum = '{{ $cfg->configuration_number }}'; open = false"
                                                    class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 whitespace-nowrap">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $configurations->links() }}</div>
        @endif
    </div>
@endsection

@extends('layouts.app')
@section('title', 'Product Catalog')

@section('content')
    <div class="max-w-screen-xl mx-auto px-6 py-8">

        {{-- Page Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Product Catalog</h1>
                <p class="text-sm text-slate-500 mt-0.5">Select a product to start a new configuration</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <x-ui.icon name="shield-check" class="w-4 h-4" />
                Internal Sales Workspace
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-6" id="filter-form">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                class="form-input w-64 text-sm">

            {{-- Family dropdown --}}
            @php $selectedFamily = $families->firstWhere('slug', request('family')); @endphp
            <div class="relative" x-data="{ open: false }">
                <input type="hidden" name="family" value="{{ request('family') }}" x-ref="familyInput">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border bg-white text-sm transition-colors hover:bg-slate-50"
                    style="border-color:#e2e8f0; min-width:200px">
                    <span class="flex-1 text-left px-3 {{ $selectedFamily ? 'text-slate-800' : 'text-slate-400' }}">
                        {{ $selectedFamily?->name ?? 'All Families' }}
                    </span>
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-2" />
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                    class="absolute left-0 mt-1.5 w-56 bg-white rounded-xl border shadow-md py-1 z-50"
                    style="border-color:#e2e8f0">
                    <button type="button" @click="$refs.familyInput.value = ''; open = false; $el.closest('form').submit()"
                        class="w-full text-left px-3 py-2 text-sm {{ !request('family') ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                        All Families
                    </button>
                    @foreach ($families as $f)
                        <button type="button"
                            @click="$refs.familyInput.value = '{{ $f->slug }}'; open = false; $el.closest('form').submit()"
                            class="w-full text-left px-3 py-2 text-sm {{ request('family') === $f->slug ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                            {{ $f->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Category dropdown --}}
            @php $selectedCategory = $categories->firstWhere('slug', request('category')); @endphp
            <div class="relative" x-data="{ open: false }">
                <input type="hidden" name="category" value="{{ request('category') }}" x-ref="categoryInput">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border bg-white text-sm transition-colors hover:bg-slate-50"
                    style="border-color:#e2e8f0; min-width:200px">
                    <span class="flex-1 text-left px-3 {{ $selectedCategory ? 'text-slate-800' : 'text-slate-400' }}">
                        {{ $selectedCategory?->name ?? 'All Categories' }}
                    </span>
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-2" />
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                    class="absolute left-0 mt-1.5 w-56 bg-white rounded-xl border shadow-md py-1 z-50"
                    style="border-color:#e2e8f0">
                    <button type="button"
                        @click="$refs.categoryInput.value = ''; open = false; $el.closest('form').submit()"
                        class="w-full text-left px-3 py-2 text-sm {{ !request('category') ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                        All Categories
                    </button>
                    @foreach ($categories as $c)
                        <button type="button"
                            @click="$refs.categoryInput.value = '{{ $c->slug }}'; open = false; $el.closest('form').submit()"
                            class="w-full text-left px-3 py-2 text-sm {{ request('category') === $c->slug ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                            {{ $c->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            @if (request()->hasAny(['search', 'family', 'category']))
                <a href="{{ route('sales.catalog') }}" class="btn btn-sm btn-ghost">Clear</a>
            @endif
        </form>

        {{-- Products Grid --}}
        @if ($products->isEmpty())
            <x-ui.empty-state icon="cube" title="No products found" description="Try adjusting your filters." />
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($products as $product)
                    <div class="card hover:shadow-md transition-shadow">
                        {{-- Product Header --}}
                        <div class="card-header flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-ui.badge color="{{ $product->status === 'published' ? 'green' : 'gray' }}">
                                        {{ ucfirst($product->status) }}
                                    </x-ui.badge>
                                    @if ($product->family)
                                        <x-ui.badge color="blue">{{ $product->family->name }}</x-ui.badge>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-slate-900 text-sm leading-tight">{{ $product->name }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ $product->code }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 ml-3"
                                style="background:#eff6ff">
                                <x-ui.icon name="cube" class="w-5 h-5" style="color:#2563eb" />
                            </div>
                        </div>

                        <div class="card-body">
                            @if ($product->description)
                                <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ $product->description }}</p>
                            @endif

                            {{-- Key Specs --}}
                            <div class="grid grid-cols-2 gap-2 mb-4 text-xs">
                                <div class="bg-slate-50 rounded-lg p-2">
                                    <div class="text-slate-400 mb-0.5">Family</div>
                                    <div class="font-medium text-slate-700">{{ $product->family?->name ?? '—' }}</div>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-2">
                                    <div class="text-slate-400 mb-0.5">Category</div>
                                    <div class="font-medium text-slate-700">{{ $product->category?->name ?? '—' }}</div>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-2">
                                    <div class="text-slate-400 mb-0.5">Version</div>
                                    <div class="font-medium text-slate-700">{{ $product->version ?? '1.0' }}</div>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-2">
                                    <div class="text-slate-400 mb-0.5">Attributes</div>
                                    <div class="font-medium text-slate-700">{{ $product->productAttributes->count() }}
                                        options</div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex gap-2">
                                <a href="{{ route('configurator.configure', $product->slug) }}"
                                    class="btn btn-sm btn-primary flex-1 justify-center">
                                    <x-ui.icon name="settings" class="w-3.5 h-3.5" />
                                    Configure
                                </a>
                                <a href="{{ route('sales.product.detail', $product->slug) }}"
                                    class="btn btn-sm btn-ghost">
                                    <x-ui.icon name="eye" class="w-3.5 h-3.5" />
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $products->links() }}</div>
        @endif
    </div>
@endsection

@extends('layouts.app')
@section('title', $product->name)

@section('content')
<div class="max-w-screen-xl mx-auto px-6 py-8">

    <div class="flex items-center gap-2 mb-6 text-sm">
        <a href="{{ route('sales.catalog') }}" class="text-slate-500 hover:text-slate-700">Catalog</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-700">{{ $product->name }}</span>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-5">

            {{-- Product Header --}}
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                @if($product->family)
                                    <x-ui.badge color="blue">{{ $product->family->name }}</x-ui.badge>
                                @endif
                                @if($product->category)
                                    <x-ui.badge color="gray">{{ $product->category->name }}</x-ui.badge>
                                @endif
                                <x-ui.badge color="{{ $product->status === 'published' ? 'green' : 'gray' }}">
                                    {{ ucfirst($product->status) }}
                                </x-ui.badge>
                            </div>
                            <h1 class="text-xl font-bold text-slate-900">{{ $product->name }}</h1>
                            <p class="text-sm font-mono text-slate-400 mt-1">{{ $product->code }}</p>
                        </div>
                        <a href="{{ route('configurator.configure', $product->slug) }}"
                           class="btn btn-md btn-primary">
                            <x-ui.icon name="settings" class="w-4 h-4"/>
                            Configure Now
                        </a>
                    </div>
                    @if($product->description)
                        <p class="text-sm text-slate-600 mt-4">{{ $product->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Configurable Attributes --}}
            @if($product->productAttributes->count())
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Configurable Attributes</h2>
                </div>
                <div class="card-body p-0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Attribute</th>
                                <th>Type</th>
                                <th>Available Options</th>
                                <th>Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->productAttributes->sortBy('sort_order') as $pa)
                            <tr>
                                <td>
                                    <div class="font-medium text-slate-800 text-sm">{{ $pa->attribute->name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $pa->attribute->code }}</div>
                                </td>
                                <td><x-ui.badge color="gray">{{ ucfirst($pa->attribute->type) }}</x-ui.badge></td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($pa->attribute->options->take(6) as $opt)
                                            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{{ $opt->label }}</span>
                                        @endforeach
                                        @if($pa->attribute->options->count() > 6)
                                            <span class="text-xs text-slate-400">+{{ $pa->attribute->options->count() - 6 }} more</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($pa->is_required)
                                        <x-ui.badge color="red">Required</x-ui.badge>
                                    @else
                                        <x-ui.badge color="gray">Optional</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-5">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Product Details</h2>
                </div>
                <div class="card-body space-y-3 text-sm">
                    <div>
                        <div class="text-xs text-slate-400 mb-0.5">Product Code</div>
                        <div class="font-mono font-semibold text-slate-800">{{ $product->code ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 mb-0.5">Family</div>
                        <div class="text-slate-700">{{ $product->family?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 mb-0.5">Category</div>
                        <div class="text-slate-700">{{ $product->category?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 mb-0.5">Version</div>
                        <div class="text-slate-700">{{ $product->version ?? '1.0' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 mb-0.5">Configurable Attributes</div>
                        <div class="text-slate-700">{{ $product->productAttributes->count() }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <a href="{{ route('configurator.configure', $product->slug) }}"
                       class="btn btn-md btn-primary w-full justify-center">
                        <x-ui.icon name="settings" class="w-4 h-4"/>
                        Start Configuration
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

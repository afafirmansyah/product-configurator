@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('catalog.index') }}" class="hover:text-blue-600">Catalog</a>
        <span>/</span>
        <a href="{{ route('catalog.category', $product->family->category->slug) }}" class="hover:text-blue-600">{{ $product->family->category->name }}</a>
        <span>/</span>
        <a href="{{ route('catalog.family', $product->family->slug) }}" class="hover:text-blue-600">{{ $product->family->name }}</a>
        <span>/</span>
        <span class="text-gray-900">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="text-sm text-blue-600 font-medium mb-1">{{ $product->family->name }}</div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                        <div class="text-sm text-gray-400 font-mono mt-1">{{ $product->code }}</div>
                    </div>
                    <span class="text-5xl">📦</span>
                </div>

                @if($product->short_description)
                    <p class="text-gray-600 mb-4">{{ $product->short_description }}</p>
                @endif

                @if($product->long_description)
                    <div class="prose prose-sm max-w-none text-gray-600">
                        {!! nl2br(e($product->long_description)) !!}
                    </div>
                @endif
            </div>

            @if($product->features)
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="font-semibold text-gray-900 mb-3">Features</h2>
                    <div class="text-sm text-gray-600">{!! nl2br(e($product->features)) !!}</div>
                </div>
            @endif

            @if($product->applications)
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="font-semibold text-gray-900 mb-3">Applications</h2>
                    <div class="text-sm text-gray-600">{!! nl2br(e($product->applications)) !!}</div>
                </div>
            @endif

            {{-- Configurable Attributes Preview --}}
            @if($product->productAttributes->count() > 0)
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="font-semibold text-gray-900 mb-3">Configuration Options</h2>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($product->productAttributes as $pa)
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <span class="text-green-500">✓</span>
                                {{ $pa->attribute->name }}
                                @if($pa->is_required)
                                    <span class="text-xs text-red-500">*</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5 sticky top-20">
                <div class="text-2xl font-bold text-gray-900 mb-1">
                    {{ $product->currency }} {{ number_format($product->base_price, 0) }}
                </div>
                <div class="text-sm text-gray-500 mb-5">Starting price</div>

                <a href="{{ route('configurator.configure', $product->slug) }}"
                   class="block w-full text-center bg-blue-700 text-white py-3 rounded-md font-medium hover:bg-blue-800 transition-colors">
                    Configure Product →
                </a>

                <div class="mt-4 space-y-2 text-sm text-gray-500">
                    @if($product->manufacturer)
                        <div class="flex justify-between">
                            <span>Manufacturer</span>
                            <span class="text-gray-900">{{ $product->manufacturer }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span>Version</span>
                        <span class="text-gray-900">{{ $product->version }}</span>
                    </div>
                    @if($product->sku)
                        <div class="flex justify-between">
                            <span>SKU</span>
                            <span class="text-gray-900 font-mono text-xs">{{ $product->sku }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

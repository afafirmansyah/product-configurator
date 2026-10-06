@extends('layouts.app')
@section('title', $category->name)
@section('content')
<div class="max-w-screen-xl mx-auto px-6 py-8">
    <nav class="flex items-center gap-1.5 text-sm text-slate-400 mb-6">
        <a href="{{ route('catalog.index') }}" class="hover:text-slate-600">Catalog</a>
        <x-ui.icon name="chevron-right" class="w-3.5 h-3.5"/>
        <span class="text-slate-700">{{ $category->name }}</span>
    </nav>

    <div class="mb-8">
        <h1 class="text-h1 text-slate-900">{{ $category->name }}</h1>
        @if($category->description)
            <p class="text-slate-500 mt-2">{{ $category->description }}</p>
        @endif
    </div>

    @foreach($category->families as $family)
        @if($family->products->count() > 0)
            <div class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-h3 text-slate-800">{{ $family->name }}</h2>
                    <a href="{{ route('catalog.family', $family->slug) }}" class="text-sm text-blue-600 hover:text-blue-700">
                        View all {{ $family->products->count() }} →
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($family->products as $product)
                        <a href="{{ route('catalog.product', $product->slug) }}"
                           class="card p-5 hover:border-blue-300 hover:shadow-md transition-all group" style="text-decoration:none">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="text-xs font-medium text-blue-600 mb-1">{{ $family->name }}</div>
                                    <h3 class="font-semibold text-slate-900 group-hover:text-blue-700">{{ $product->name }}</h3>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $product->code }}</div>
                                </div>
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#eff6ff">
                                    <x-ui.icon name="products" class="w-5 h-5 text-blue-600"/>
                                </div>
                            </div>
                            @if($product->short_description)
                                <p class="text-sm text-slate-500 line-clamp-2 mb-3">{{ $product->short_description }}</p>
                            @endif
                            <div class="flex items-center justify-between pt-2 border-t" style="border-color:#f1f5f9">
                                <span class="text-sm font-semibold text-slate-900">
                                    {{ $product->currency }} {{ number_format($product->base_price, 0) }}
                                </span>
                                <span class="text-xs text-blue-600 font-medium">Configure →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection

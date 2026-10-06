@extends('layouts.app')
@section('title', 'Configure ' . $product->name)
@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('catalog.product', $product->slug) }}" class="hover:text-blue-600">{{ $product->name }}</a>
        <span>/</span>
        <span class="text-gray-900">Configure</span>
    </div>
    @livewire('configurator.product-configurator', ['product' => $product])
</div>
@endsection

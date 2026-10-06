@extends('layouts.admin')
@section('title', 'Products')
@section('heading', 'Products')
@section('header-actions')
    <x-ui.button href="{{ route('admin.products.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Product
    </x-ui.button>
@endsection
@section('content')
    @livewire('admin.product-list')
@endsection

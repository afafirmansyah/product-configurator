@extends('layouts.admin')
@section('title', 'Edit Product')
@section('heading', 'Edit Product: ' . $product->name)
@section('content')
    @livewire('admin.product-builder', ['product' => $product])
@endsection

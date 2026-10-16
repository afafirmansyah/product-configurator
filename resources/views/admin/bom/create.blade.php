@extends('layouts.admin')
@section('title', 'Create BOM Rule')
@section('heading', 'Create BOM Rule for: ' . $product->name)
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-body">
            @include('admin.bom._form', [
                'action' => route('admin.bom.store', $product),
                'method' => 'POST',
                'rule' => null,
            ])
        </div>
    </div>
</div>
@endsection

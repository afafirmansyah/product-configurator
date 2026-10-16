@extends('layouts.admin')
@section('title', 'Edit BOM Rule')
@section('heading', 'Edit BOM Rule: ' . $rule->name)
@section('content')
<div class="max-w-2xl space-y-4">
    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-semibold text-slate-800">Rule Details</h3>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-400 text-xs">Product</dt><dd class="font-medium">{{ $rule->product->name }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Code</dt><dd class="text-data text-xs">{{ $rule->code }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @include('admin.bom._form', [
                'action' => route('admin.bom.update', $rule),
                'method' => 'PUT',
                'rule' => $rule,
            ])
        </div>
    </div>

    {{-- Kept outside the main form so its fields are never submitted with an update. --}}
    <form method="POST" action="{{ route('admin.bom.destroy', $rule) }}" onsubmit="return confirm('Delete this BOM rule?')">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-md">Delete Rule</button>
    </form>
</div>
@endsection

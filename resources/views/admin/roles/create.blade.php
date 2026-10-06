@extends('layouts.admin')
@section('title', 'Create Role')
@section('heading', 'Create Role')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="form-label">Role Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. product_manager">
                    <p class="form-hint">Use lowercase with underscores. e.g. product_manager</p>
                </div>
                <div>
                    <label class="form-label mb-3">Permissions</label>
                    <div class="space-y-4">
                        @foreach($permissions as $group => $perms)
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ ucfirst($group) }}</div>
                                <div class="grid grid-cols-2 gap-1.5">
                                    @foreach($perms as $perm)
                                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                                   {{ in_array($perm->name, old('permissions', [])) ? 'checked' : '' }}
                                                   class="w-4 h-4 rounded" style="accent-color:#2563eb">
                                            {{ $perm->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary btn-md">Create Role</button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Roles')
@section('heading', 'Roles & Permissions')
@section('header-actions')
    <x-ui.button href="{{ route('admin.roles.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Role
    </x-ui.button>
@endsection
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse($roles as $role)
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ $role->users_count }} user(s)</div>
                </div>
                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-ghost btn-sm">
                    <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                </a>
            </div>
            <div class="card-body py-3">
                <div class="flex flex-wrap gap-1">
                    @foreach($role->permissions->take(8) as $perm)
                        <span class="text-xs px-1.5 py-0.5 rounded" style="background:#f1f5f9;color:#475569">{{ $perm->name }}</span>
                    @endforeach
                    @if($role->permissions->count() > 8)
                        <span class="text-xs text-slate-400">+{{ $role->permissions->count() - 8 }} more</span>
                    @endif
                    @if($role->permissions->isEmpty())
                        <span class="text-xs text-slate-400">No permissions assigned</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-3">
            <x-ui.empty-state icon="roles" title="No roles defined" description="Create roles to manage user access."/>
        </div>
    @endforelse
</div>
@endsection

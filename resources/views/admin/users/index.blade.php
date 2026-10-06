@extends('layouts.admin')
@section('title', 'Users')
@section('heading', 'User Management')
@section('header-actions')
    <x-ui.button href="{{ route('admin.users.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New User
    </x-ui.button>
@endsection
@section('content')

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="flex flex-wrap gap-3" id="filter-form">
            <div class="relative flex-1 min-w-48">
                <x-ui.icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search name, email, company..."
                       class="form-input" style="padding-left:2.25rem">
            </div>

            @php $selectedRole = request('role'); @endphp
            <div class="relative" x-data="{ open: false }">
                <input type="hidden" name="role" value="{{ $selectedRole }}" x-ref="roleInput">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border bg-white text-sm transition-colors hover:bg-slate-50"
                    style="border-color:#e2e8f0; min-width:160px">
                    <span class="flex-1 text-left px-1 {{ $selectedRole ? 'text-slate-800' : 'text-slate-400' }}">
                        {{ $selectedRole ? ucfirst(str_replace('_', ' ', $selectedRole)) : 'All Roles' }}
                    </span>
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-1"/>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                    class="absolute left-0 mt-1.5 w-48 bg-white rounded-xl border shadow-md py-1 z-50"
                    style="border-color:#e2e8f0">
                    <button type="button" @click="$refs.roleInput.value = ''; open = false; $el.closest('form').submit()"
                        class="w-full text-left px-3 py-2 text-sm {{ !$selectedRole ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                        All Roles
                    </button>
                    @foreach($roles as $role)
                        <button type="button"
                            @click="$refs.roleInput.value = '{{ $role->name }}'; open = false; $el.closest('form').submit()"
                            class="w-full text-left px-3 py-2 text-sm {{ $selectedRole === $role->name ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                        </button>
                    @endforeach
                </div>
            </div>

            @php $selectedStatus = request('status'); @endphp
            <div class="relative" x-data="{ open: false }">
                <input type="hidden" name="status" value="{{ $selectedStatus }}" x-ref="statusInput">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border bg-white text-sm transition-colors hover:bg-slate-50"
                    style="border-color:#e2e8f0; min-width:140px">
                    <span class="flex-1 text-left px-1 {{ $selectedStatus !== '' && $selectedStatus !== null ? 'text-slate-800' : 'text-slate-400' }}">
                        @if($selectedStatus === '1') Active
                        @elseif($selectedStatus === '0') Inactive
                        @else All Status
                        @endif
                    </span>
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-1"/>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                    class="absolute left-0 mt-1.5 w-40 bg-white rounded-xl border shadow-md py-1 z-50"
                    style="border-color:#e2e8f0">
                    @foreach(['' => 'All Status', '1' => 'Active', '0' => 'Inactive'] as $val => $label)
                        <button type="button"
                            @click="$refs.statusInput.value = '{{ $val }}'; open = false; $el.closest('form').submit()"
                            class="w-full text-left px-3 py-2 text-sm {{ $selectedStatus === (string)$val ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if(request()->hasAny(['search','role','status']))
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-md">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Company</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background:{{ $user->is_active ? '#2563eb' : '#94a3b8' }}">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                <div class="text-xs text-slate-400">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="text-slate-600">{{ $user->company ?? '—' }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <x-ui.badge color="blue">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</x-ui.badge>
                        @endforeach
                    </td>
                    <td>
                        <x-ui.badge color="{{ $user->is_active ? 'green' : 'red' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td class="text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-sm {{ $user->is_active ? 'text-red-500 hover:text-red-600' : 'text-green-600 hover:text-green-700' }}"
                                    title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                    <x-ui.icon name="{{ $user->is_active ? 'x' : 'check' }}" class="w-3.5 h-3.5"/>
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            @if($user->id !== auth()->id())
                                <button type="button"
                                    @click="$dispatch('open-modal', 'del-user-{{ $user->id }}')"
                                    class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                    <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                    Delete
                                </button>
                                <x-ui.confirm-modal
                                    id="del-user-{{ $user->id }}"
                                    title="Delete User"
                                    name="{{ $user->name }}"
                                    action="{{ route('admin.users.destroy', $user) }}"
                                />
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state icon="users" title="No users found"
                            description="No users match your current filters."
                            actionLabel="Create User" actionHref="{{ route('admin.users.create') }}"/>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">
        {{ $users->links() }}
    </div>
</div>
@endsection

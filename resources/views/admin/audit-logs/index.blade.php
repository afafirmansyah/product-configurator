@extends('layouts.admin')
@section('title', 'Audit Logs')
@section('heading', 'Audit Logs')
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Entity ID</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="text-slate-400 whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td class="text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                    <td>
                        @php $actionColors = ['created'=>'green','updated'=>'blue','deleted'=>'red','published'=>'purple']; @endphp
                        <x-ui.badge color="{{ $actionColors[$log->action] ?? 'gray' }}">{{ $log->action }}</x-ui.badge>
                    </td>
                    <td class="text-data text-slate-500">{{ class_basename($log->entity_type) }}</td>
                    <td class="text-slate-500">{{ $log->entity_id }}</td>
                    <td class="text-slate-400">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6">
                    <x-ui.empty-state icon="audit" title="No audit logs yet" description="Actions will be logged here automatically."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $logs->links() }}</div>
</div>
@endsection

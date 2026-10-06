@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('content')

{{-- KPI Row --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $kpis = [
        ['label'=>'Published Products','value'=>$stats['active_products'],'sub'=>$stats['total_products'].' total','color'=>'#2563eb','icon'=>'products'],
        ['label'=>'Active Rules','value'=>$stats['active_rules'],'sub'=>$stats['total_rules'].' total','color'=>'#7c3aed','icon'=>'rules'],
        ['label'=>'Configurations','value'=>$stats['saved_configurations'],'sub'=>'all time','color'=>'#0891b2','icon'=>'configurations'],
        ['label'=>'Quotation Requests','value'=>$stats['quotation_requests'],'sub'=>'pending review','color'=>'#d97706','icon'=>'quotations'],
    ];
    @endphp
    @foreach($kpis as $kpi)
        <div class="stat-card">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:{{ $kpi['color'] }}1a">
                    <x-ui.icon name="{{ $kpi['icon'] }}" class="w-5 h-5" style="color:{{ $kpi['color'] }}"/>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mb-0.5">{{ number_format($kpi['value']) }}</div>
            <div class="text-sm font-medium text-slate-600">{{ $kpi['label'] }}</div>
            <div class="text-xs text-slate-400 mt-0.5">{{ $kpi['sub'] }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    {{-- Product Status --}}
    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-semibold text-slate-800">Product Status</h3>
            <a href="{{ route('admin.products.index') }}" class="text-xs text-blue-600 hover:text-blue-700">View all</a>
        </div>
        <div class="card-body py-3">
            @php
            $statuses = [
                ['label'=>'Published','value'=>$stats['active_products'],'color'=>'green'],
                ['label'=>'Draft','value'=>$stats['draft_products'],'color'=>'gray'],
                ['label'=>'Total','value'=>$stats['total_products'],'color'=>'blue'],
                ['label'=>'Components','value'=>$stats['total_components'],'color'=>'purple'],
            ];
            @endphp
            <div class="space-y-2.5">
                @foreach($statuses as $s)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full badge-{{ $s['color'] }}" style="background:currentColor"></div>
                            <span class="text-sm text-slate-600">{{ $s['label'] }}</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-900">{{ $s['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent Configurations --}}
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h3 class="text-sm font-semibold text-slate-800">Recent Configurations</h3>
            <a href="{{ route('admin.configurations.index') }}" class="text-xs text-blue-600 hover:text-blue-700">View all</a>
        </div>
        <div class="divide-y" style="border-color:#f1f5f9">
            @forelse($recentConfigurations as $cfg)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#eff6ff">
                            <x-ui.icon name="configurations" class="w-3.5 h-3.5 text-blue-600"/>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-slate-900">{{ $cfg->configuration_number }}</div>
                            <div class="text-xs text-slate-400">{{ $cfg->product->name }} · {{ $cfg->user?->name ?? 'Guest' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-medium text-slate-600">{{ $cfg->currency }} {{ number_format($cfg->total_price, 0) }}</span>
                        <x-ui.badge color="{{ $cfg->status === 'finalized' ? 'green' : ($cfg->status === 'validated' ? 'blue' : 'gray') }}">
                            {{ ucfirst($cfg->status) }}
                        </x-ui.badge>
                    </div>
                </div>
            @empty
                <x-ui.empty-state icon="configurations" title="No configurations yet" description="Configurations will appear here once users start configuring products."/>
            @endforelse
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    {{-- Recent Quotations --}}
    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-semibold text-slate-800">Recent Quotation Requests</h3>
            <a href="{{ route('admin.quotations.index') }}" class="text-xs text-blue-600 hover:text-blue-700">View all</a>
        </div>
        <div class="divide-y" style="border-color:#f1f5f9">
            @forelse($recentQuotations as $q)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-900">{{ $q->reference_number }}</div>
                        <div class="text-xs text-slate-400">{{ $q->company }} · {{ $q->contact_person }}</div>
                    </div>
                    <x-ui.badge color="yellow">{{ ucfirst($q->status) }}</x-ui.badge>
                </div>
            @empty
                <x-ui.empty-state icon="quotations" title="No quotation requests" description="Quotation requests will appear here."/>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-semibold text-slate-800">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 gap-2">
                @php
                $actions = [
                    ['href'=>route('admin.products.create'),'label'=>'New Product','icon'=>'products','color'=>'#2563eb'],
                    ['href'=>route('admin.categories.create'),'label'=>'New Category','icon'=>'category','color'=>'#7c3aed'],
                    ['href'=>route('admin.attributes.create'),'label'=>'New Attribute','icon'=>'attributes','color'=>'#0891b2'],
                    ['href'=>route('admin.components.create'),'label'=>'New Component','icon'=>'components','color'=>'#d97706'],
                    ['href'=>route('admin.users.create'),'label'=>'New User','icon'=>'users','color'=>'#16a34a'],
                    ['href'=>route('admin.pricing.index'),'label'=>'Manage Pricing','icon'=>'pricing','color'=>'#dc2626'],
                ];
                @endphp
                @foreach($actions as $action)
                    <a href="{{ $action['href'] }}"
                       class="flex items-center gap-2.5 p-3 rounded-lg border hover:border-blue-200 hover:bg-blue-50 transition-colors group"
                       style="border-color:#e2e8f0">
                        <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0"
                             style="background:{{ $action['color'] }}1a">
                            <x-ui.icon name="{{ $action['icon'] }}" class="w-3.5 h-3.5" style="color:{{ $action['color'] }}"/>
                        </div>
                        <span class="text-sm font-medium text-slate-700 group-hover:text-blue-700">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

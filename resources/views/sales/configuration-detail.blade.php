@extends('layouts.app')
@section('title', $configuration->configuration_number)

@section('content')
<div class="max-w-screen-xl mx-auto px-6 py-8">

    {{-- Page Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('sales.configurations') }}" class="text-sm text-slate-500 hover:text-slate-700">My Configurations</a>
                <span class="text-slate-300">/</span>
                <span class="text-sm text-slate-700 font-mono">{{ $configuration->configuration_number }}</span>
            </div>
            <h1 class="text-xl font-semibold text-slate-900">
                {{ $configuration->name ?? $configuration->configuration_number }}
            </h1>
        </div>
        <div class="flex items-center gap-2">
            {{-- Generate BOM --}}
            <form method="POST" action="{{ route('sales.configuration.generate-bom', $configuration) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-secondary">
                    <x-ui.icon name="refresh" class="w-3.5 h-3.5"/>
                    Generate BOM
                </button>
            </form>
            @if($configuration->bom_status !== 'pending')
                <a href="{{ route('sales.bom.pdf', $configuration) }}" class="btn btn-sm btn-primary">
                    <x-ui.icon name="document" class="w-3.5 h-3.5"/>
                    BOM PDF
                </a>
                <a href="{{ route('sales.bom.excel', $configuration) }}" class="btn btn-sm btn-ghost">
                    <x-ui.icon name="table" class="w-3.5 h-3.5"/>
                    Excel
                </a>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert type="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    {{-- BOM Validation Errors --}}
    @if($configuration->validation_errors && !empty($configuration->validation_errors['errors']))
        <div class="mb-4 p-4 rounded-xl border" style="background:#fef2f2;border-color:#fecaca">
            <div class="flex items-center gap-2 mb-2">
                <x-ui.icon name="exclamation" class="w-4 h-4 text-red-600"/>
                <span class="text-sm font-semibold text-red-800">BOM cannot be finalized — {{ count($configuration->validation_errors['errors']) }} issue(s) found</span>
            </div>
            <ul class="text-xs text-red-700 space-y-1 ml-6 list-disc">
                @foreach($configuration->validation_errors['errors'] as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- Left Column --}}
        <div class="col-span-2 space-y-5">

            {{-- Configuration Info --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Configuration</h2>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Configuration ID</dt>
                            <dd class="font-mono font-semibold text-slate-800">{{ $configuration->configuration_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Part Number</dt>
                            <dd class="font-mono text-slate-700">{{ $configuration->configuration_code ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Customer</dt>
                            <dd class="text-slate-700">{{ $configuration->customer ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Project</dt>
                            <dd class="text-slate-700">{{ $configuration->project ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Sales Person</dt>
                            <dd class="text-slate-700">{{ $configuration->user?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Status</dt>
                            <dd>
                                @php $statusColors = ['draft'=>'gray','validated'=>'blue','finalized'=>'green']; @endphp
                                <x-ui.badge color="{{ $statusColors[$configuration->status] ?? 'gray' }}">{{ ucfirst($configuration->status) }}</x-ui.badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Created</dt>
                            <dd class="text-slate-600 text-xs">{{ $configuration->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Last Updated</dt>
                            <dd class="text-slate-600 text-xs">{{ $configuration->updated_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Product --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Product</h2>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-3 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Product Name</dt>
                            <dd class="font-semibold text-slate-800">{{ $configuration->product->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Product Code</dt>
                            <dd class="font-mono text-slate-700">{{ $configuration->product->code ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 mb-0.5">Version</dt>
                            <dd class="text-slate-700">{{ $configuration->product->version ?? '1.0' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Selected Configuration --}}
            @if($result && !empty($result->options))
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Selected Configuration</h2>
                </div>
                <div class="card-body">
                    <div class="space-y-2">
                        @foreach($result->options as $attrCode => $option)
                        <div class="flex items-center justify-between py-2 border-b last:border-0" style="border-color:#f1f5f9">
                            <span class="text-xs text-slate-500 w-40 flex-shrink-0">{{ $option['attribute_name'] ?? $attrCode }}</span>
                            <span class="text-sm font-medium text-slate-800 flex-1">{{ $option['label'] ?? $option['value'] ?? '—' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- BOM --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-700">Bill of Materials</h2>
                        @if($configuration->bom_generated_at)
                            <p class="text-xs text-slate-400 mt-0.5">
                                Rev {{ $configuration->bom_revision }} — Generated {{ $configuration->bom_generated_at->format('d M Y H:i') }}
                            </p>
                        @endif
                    </div>
                    @php $bomColors = ['pending'=>'gray','generated'=>'blue','validated'=>'green','failed'=>'red']; @endphp
                    <x-ui.badge color="{{ $bomColors[$configuration->bom_status] ?? 'gray' }}">
                        {{ ucfirst($configuration->bom_status) }}
                    </x-ui.badge>
                </div>
                <div class="card-body p-0">
                    @if(empty($bomItems))
                        <div class="p-6 text-center text-sm text-slate-400">
                            BOM not generated yet. Click "Generate BOM" to calculate.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="w-12">Item</th>
                                        <th>Part Number</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Manufacturer</th>
                                        <th class="w-16">Qty</th>
                                        <th class="w-16">Unit</th>
                                        <th class="text-right">Unit Price</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bomItems as $item)
                                    <tr>
                                        <td class="font-mono text-xs text-slate-400">{{ $item['item_no'] }}</td>
                                        <td class="font-mono text-xs font-semibold text-slate-700">{{ $item['part_number'] ?? '—' }}</td>
                                        <td>
                                            <div class="text-sm text-slate-800">{{ $item['description'] }}</div>
                                            @if($item['long_description'])
                                                <div class="text-xs text-slate-400">{{ Str::limit($item['long_description'], 60) }}</div>
                                            @endif
                                        </td>
                                        <td class="text-xs text-slate-500">{{ $item['type'] }}</td>
                                        <td class="text-xs text-slate-500">{{ $item['manufacturer'] ?? '—' }}</td>
                                        <td class="text-sm font-semibold text-slate-800">{{ $item['quantity'] }}</td>
                                        <td class="text-xs text-slate-500">{{ $item['unit'] }}</td>
                                        <td class="text-right text-sm text-slate-700">
                                            {{ $item['unit_price'] > 0 ? number_format($item['unit_price'], 2) : '—' }}
                                        </td>
                                        <td class="text-right text-sm font-semibold text-slate-800">
                                            {{ $item['total_price'] > 0 ? number_format($item['total_price'], 2) : '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50">
                                        <td colspan="7" class="text-xs font-semibold text-slate-600 px-4 py-2">
                                            {{ count($bomItems) }} items
                                        </td>
                                        <td class="text-right text-xs font-semibold text-slate-600 px-4 py-2">Total</td>
                                        <td class="text-right text-sm font-bold text-slate-900 px-4 py-2">
                                            {{ number_format(array_sum(array_column($bomItems, 'total_price')), 2) }}
                                            {{ $configuration->currency }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right Column --}}
        <div class="space-y-5">

            {{-- Commercial --}}
            @if($result && isset($result->pricing))
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Commercial</h2>
                </div>
                <div class="card-body space-y-2 text-sm">
                    @php $pricing = $result->pricing; @endphp
                    <div class="flex justify-between">
                        <span class="text-slate-500">Base Price</span>
                        <span class="text-slate-800">{{ number_format($pricing['base_price'] ?? 0, 2) }}</span>
                    </div>
                    @if(!empty($pricing['option_adjustments']))
                        @foreach($pricing['option_adjustments'] as $adj)
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">+ {{ $adj['label'] ?? 'Option' }}</span>
                            <span class="text-slate-600">{{ number_format($adj['adjustment'] ?? 0, 2) }}</span>
                        </div>
                        @endforeach
                    @endif
                    @if(($pricing['discount'] ?? 0) > 0)
                    <div class="flex justify-between text-xs text-green-600">
                        <span>Discount</span>
                        <span>-{{ number_format($pricing['discount'], 2) }}</span>
                    </div>
                    @endif
                    <div class="border-t pt-2 flex justify-between font-semibold" style="border-color:#e2e8f0">
                        <span class="text-slate-700">Total</span>
                        <span class="text-slate-900">{{ number_format($pricing['total'] ?? 0, 2) }} {{ $pricing['currency'] ?? 'EUR' }}</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Documents --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Documents</h2>
                </div>
                <div class="card-body space-y-2">
                    <form method="POST" action="{{ route('sales.configuration.generate-bom', $configuration) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary w-full justify-center">
                            <x-ui.icon name="refresh" class="w-3.5 h-3.5"/>
                            Generate BOM
                        </button>
                    </form>
                    @if($configuration->bom_status !== 'pending')
                        <a href="{{ route('sales.bom.pdf', $configuration) }}" class="btn btn-sm btn-primary w-full justify-center">
                            <x-ui.icon name="document" class="w-3.5 h-3.5"/>
                            Download BOM PDF
                        </a>
                        <a href="{{ route('sales.bom.excel', $configuration) }}" class="btn btn-sm btn-ghost w-full justify-center">
                            <x-ui.icon name="table" class="w-3.5 h-3.5"/>
                            Export BOM Excel
                        </a>
                    @else
                        <p class="text-xs text-slate-400 text-center">Generate BOM first to enable exports.</p>
                    @endif
                </div>
            </div>

            {{-- Submit for Review --}}
            @if($configuration->status !== 'finalized')
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Submit for Review</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sales.configuration.submit', $configuration) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="form-label">Customer *</label>
                            <input type="text" name="customer" value="{{ $configuration->customer }}"
                                   class="form-input text-sm" required placeholder="Customer name">
                        </div>
                        <div>
                            <label class="form-label">Project</label>
                            <input type="text" name="project" value="{{ $configuration->project }}"
                                   class="form-input text-sm" placeholder="Project name">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-full justify-center">
                            <x-ui.icon name="check" class="w-3.5 h-3.5"/>
                            Submit for Review
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-700">Actions</h2>
                </div>
                <div class="card-body space-y-2">
                    <a href="{{ route('configurator.configure', $configuration->product->slug) }}"
                       class="btn btn-sm btn-ghost w-full justify-start">
                        <x-ui.icon name="pencil" class="w-3.5 h-3.5"/>
                        Edit Configuration
                    </a>
                    <form method="POST" action="{{ route('sales.configuration.duplicate', $configuration) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-ghost w-full justify-start">
                            <x-ui.icon name="duplicate" class="w-3.5 h-3.5"/>
                            Duplicate
                        </button>
                    </form>
                    <form method="POST" action="{{ route('sales.configuration.destroy', $configuration) }}"
                          onsubmit="return confirm('Delete this configuration?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-ghost w-full justify-start text-red-600 hover:bg-red-50">
                            <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                            Delete
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

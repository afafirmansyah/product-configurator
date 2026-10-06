@extends('layouts.app')
@section('title', $session->configuration_number)
@section('content')
<div class="max-w-screen-xl mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
                <a href="{{ route('catalog.index') }}" class="hover:text-slate-600">Catalog</a>
                <x-ui.icon name="chevron-right" class="w-3.5 h-3.5"/>
                <a href="{{ route('catalog.product', $session->product->slug) }}" class="hover:text-slate-600">{{ $session->product->name }}</a>
                <x-ui.icon name="chevron-right" class="w-3.5 h-3.5"/>
                <span class="text-slate-700">Configuration</span>
            </div>
            <h1 class="text-h2 text-slate-900">{{ $session->configuration_number }}</h1>
            <p class="text-slate-500 text-sm mt-1">{{ $session->product->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.badge color="{{ $session->status === 'finalized' ? 'green' : ($session->status === 'validated' ? 'blue' : 'gray') }}">
                {{ ucfirst($session->status) }}
            </x-ui.badge>
            <a href="{{ route('configurator.configure', $session->product->slug) }}" class="btn btn-secondary btn-sm">
                <x-ui.icon name="refresh" class="w-3.5 h-3.5"/>
                Reconfigure
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Configuration Details --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Configuration Code --}}
            @if($session->configuration_code)
                <div class="card p-4" style="background:#eff6ff;border-color:#bfdbfe">
                    <div class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-1">Configuration Code</div>
                    <div class="text-data text-xl font-bold text-blue-900">{{ $session->configuration_code }}</div>
                </div>
            @endif

            {{-- Selected Options --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-800">Selected Configuration</h2>
                </div>
                <div class="divide-y" style="border-color:#f8fafc">
                    @foreach($session->items as $item)
                        <div class="px-5 py-2.5 flex items-center justify-between">
                            <span class="text-sm text-slate-500">{{ $item->attribute->name }}</span>
                            <span class="text-sm font-medium text-slate-900">{{ $item->value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- BOM --}}
            @if($session->bomItems->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-sm font-semibold text-slate-800">Bill of Materials</h2>
                        <span class="text-xs text-slate-400">{{ $session->bomItems->count() }} items</span>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th>Code</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($session->bomItems as $item)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $item->component->name }}</td>
                                    <td class="text-data text-xs text-slate-500">{{ $item->component->code }}</td>
                                    <td class="text-right text-slate-700">{{ $item->quantity }}</td>
                                    <td class="text-right text-slate-600">{{ $session->currency }} {{ number_format($item->unit_price, 0) }}</td>
                                    <td class="text-right font-medium text-slate-900">{{ $session->currency }} {{ number_format($item->unit_price * $item->quantity, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Right: Pricing + Quotation --}}
        <div class="space-y-4">

            {{-- Price Summary --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="text-sm font-semibold text-slate-800">Price Summary</h3>
                </div>
                <div class="card-body">
                    <div class="text-3xl font-bold text-slate-900 mb-0.5">
                        {{ $session->currency }} {{ number_format($session->total_price, 0) }}
                    </div>
                    <div class="text-xs text-slate-400 mb-4">Indicative price</div>
                    <div class="text-xs text-slate-400">
                        Created {{ $session->created_at->format('d M Y, H:i') }}
                    </div>
                </div>
            </div>

            {{-- Quotation Request --}}
            @if(!$session->quotationRequest)
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-slate-800">Request Quotation</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-slate-500 mb-4">Get a formal quotation with delivery terms and final pricing.</p>
                        <form method="POST" action="{{ route('configurator.quotation.store', $session->ulid) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="form-label">Company *</label>
                                <input type="text" name="company" value="{{ old('company', auth()->user()?->company) }}" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Contact Person *</label>
                                <input type="text" name="contact_person" value="{{ old('contact_person', auth()->user()?->name) }}" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Project Name</label>
                                <input type="text" name="project_name" value="{{ old('project_name') }}" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Quantity *</label>
                                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Required Delivery Date</label>
                                <input type="date" name="required_delivery_date" value="{{ old('required_delivery_date') }}" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" rows="2" class="form-textarea">{{ old('remarks') }}</textarea>
                            </div>
                            @guest
                                <x-ui.alert type="info">
                                    <a href="{{ route('login') }}" class="font-medium underline">Sign in</a> to submit a quotation request.
                                </x-ui.alert>
                            @else
                                <button type="submit" class="btn btn-primary btn-md w-full">
                                    <x-ui.icon name="quotations" class="w-4 h-4"/>
                                    Submit Quotation Request
                                </button>
                            @endguest
                        </form>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <x-ui.alert type="success">
                            Quotation request <strong>{{ $session->quotationRequest->reference_number }}</strong> submitted successfully.
                            Our team will contact you shortly.
                        </x-ui.alert>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

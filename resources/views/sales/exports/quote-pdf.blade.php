<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
    .header { background: #0f2d5e; color: white; padding: 16px 20px; margin-bottom: 14px; }
    .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .company { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
    .doc-title { font-size: 12px; opacity: 0.85; margin-top: 2px; }
    .doc-meta { text-align: right; font-size: 9px; opacity: 0.85; }
    .doc-number { font-size: 13px; font-weight: bold; }
    .info-grid { display: flex; flex-wrap: wrap; gap: 0; margin-bottom: 14px; }
    .info-box { width: 33.3%; border: 1px solid #e2e8f0; padding: 8px 12px; }
    .info-label { font-size: 7px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .info-value { font-size: 10px; font-weight: bold; color: #1e293b; }
    .section-title { font-size: 10px; font-weight: bold; color: #0f2d5e; text-transform: uppercase;
                     letter-spacing: 0.5px; border-bottom: 2px solid #0f2d5e; padding-bottom: 3px; margin-bottom: 8px; margin-top: 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    thead tr { background: #1e3a5f; color: white; }
    thead th { padding: 6px 8px; text-align: left; font-size: 8px; font-weight: bold; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    tfoot tr { background: #ebf0f7; }
    tfoot td { padding: 6px 8px; font-weight: bold; font-size: 10px; border-top: 2px solid #1e3a5f; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
    .remarks { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; margin-bottom: 12px; font-size: 9px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #f8fafc;
              border-top: 1px solid #e2e8f0; padding: 6px 20px;
              display: flex; justify-content: space-between; font-size: 7px; color: #94a3b8; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-top">
        <div>
            <div class="company">HECONOMY</div>
            <div class="doc-title">QUOTATION</div>
        </div>
        <div class="doc-meta">
            <div class="doc-number mono">{{ $quotation->reference_number }}</div>
            <div>Status: {{ ucfirst($quotation->status) }}</div>
            <div>{{ now()->format('d M Y') }}</div>
        </div>
    </div>
</div>

{{-- Customer / project details --}}
<div class="section-title">Customer Details</div>
<div class="info-grid">
    <div class="info-box">
        <div class="info-label">Company</div>
        <div class="info-value">{{ $quotation->company ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Contact</div>
        <div class="info-value">{{ $quotation->contact_person ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Email</div>
        <div class="info-value">{{ $quotation->email ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Phone</div>
        <div class="info-value">{{ $quotation->phone ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Country</div>
        <div class="info-value">{{ $quotation->country ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Quantity</div>
        <div class="info-value">{{ $quotation->quantity }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Project</div>
        <div class="info-value">{{ $quotation->project_name ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Location</div>
        <div class="info-value">{{ $quotation->project_location ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Required Delivery</div>
        <div class="info-value">{{ $quotation->required_delivery_date?->format('d M Y') ?? '—' }}</div>
    </div>
</div>

{{-- Configuration summary --}}
<div class="section-title">Configuration</div>
<div class="info-grid">
    <div class="info-box">
        <div class="info-label">Product</div>
        <div class="info-value">{{ $configuration?->product?->name ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Configuration No.</div>
        <div class="info-value mono">{{ $configuration?->configuration_number ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Part Number</div>
        <div class="info-value mono">{{ $configuration?->configuration_code ?? '—' }}</div>
    </div>
</div>

{{-- Line items --}}
@if(!empty($bomItems))
<div class="section-title">Line Items — {{ count($bomItems) }}</div>
<table>
    <thead>
        <tr>
            <th style="width:6%">Item</th>
            <th style="width:18%">Part Number</th>
            <th style="width:36%">Description</th>
            <th style="width:8%" class="text-center">Qty</th>
            @if($showPricing)
            <th style="width:16%" class="text-right">Unit Price</th>
            <th style="width:16%" class="text-right">Total</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($bomItems as $item)
        <tr>
            <td class="mono text-center">{{ $item['item_no'] ?? '—' }}</td>
            <td class="mono" style="font-size:8px">{{ $item['part_number'] ?? '—' }}</td>
            <td><strong>{{ $item['description'] ?? '—' }}</strong></td>
            <td class="text-center">{{ $item['quantity'] ?? '—' }}</td>
            @if($showPricing)
            <td class="text-right">{{ isset($item['unit_price']) && $item['unit_price'] > 0 ? number_format($item['unit_price'], 2) : '—' }}</td>
            <td class="text-right"><strong>{{ isset($item['total_price']) && $item['total_price'] > 0 ? number_format($item['total_price'], 2) : '—' }}</strong></td>
            @endif
        </tr>
        @endforeach
    </tbody>
    @if($showPricing)
    <tfoot>
        <tr>
            <td colspan="{{ 4 }}" class="text-right">TOTAL ESTIMATED VALUE</td>
            <td colspan="2" class="text-right">
                {{ number_format(array_sum(array_column($bomItems, 'total_price')), 2) }}
                {{ $configuration?->currency ?? 'EUR' }}
            </td>
        </tr>
    </tfoot>
    @endif
</table>
@else
<div class="remarks">No bill of materials has been generated for this configuration yet.</div>
@endif

{{-- Remarks --}}
@if($quotation->remarks)
<div class="section-title">Remarks</div>
<div class="remarks">{{ $quotation->remarks }}</div>
@endif

{{-- Footer --}}
<div class="footer">
    <div>
        Generated: {{ now()->format('d M Y H:i') }}
        &nbsp;|&nbsp; {{ $quotation->reference_number }}
    </div>
    <div>ConfigPro — Heconomy &copy; {{ date('Y') }}</div>
</div>

</body>
</html>

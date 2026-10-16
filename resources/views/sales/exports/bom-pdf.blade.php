<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
    .header { background: #0f2d5e; color: white; padding: 14px 20px; margin-bottom: 12px; }
    .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .company { font-size: 14px; font-weight: bold; letter-spacing: 1px; }
    .doc-title { font-size: 11px; opacity: 0.8; margin-top: 2px; }
    .doc-meta { text-align: right; font-size: 8px; opacity: 0.8; }
    .doc-number { font-size: 12px; font-weight: bold; }
    .info-grid { display: flex; gap: 0; margin-bottom: 12px; }
    .info-box { flex: 1; border: 1px solid #e2e8f0; padding: 8px 12px; }
    .info-box:not(:last-child) { border-right: none; }
    .info-label { font-size: 7px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .info-value { font-size: 9px; font-weight: bold; color: #1e293b; }
    .section-title { font-size: 9px; font-weight: bold; color: #0f2d5e; text-transform: uppercase;
                     letter-spacing: 0.5px; border-bottom: 2px solid #0f2d5e; padding-bottom: 3px; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    thead tr { background: #1e3a5f; color: white; }
    thead th { padding: 6px 8px; text-align: left; font-size: 8px; font-weight: bold; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 8px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    tfoot tr { background: #ebf0f7; }
    tfoot td { padding: 6px 8px; font-weight: bold; font-size: 9px; border-top: 2px solid #1e3a5f; }
    .warning-box { background: #fef2f2; border: 1px solid #fecaca; padding: 8px 12px; margin-bottom: 10px; border-radius: 4px; }
    .warning-title { font-weight: bold; color: #dc2626; font-size: 9px; margin-bottom: 4px; }
    .warning-item { color: #b91c1c; font-size: 8px; margin-left: 10px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #f8fafc;
              border-top: 1px solid #e2e8f0; padding: 6px 20px;
              display: flex; justify-content: space-between; font-size: 7px; color: #94a3b8; }
    .confidential { color: #dc2626; font-weight: bold; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7px; font-weight: bold; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-gray { background: #f1f5f9; color: #475569; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-top">
        <div>
            <div class="company">HECONOMY</div>
            <div class="doc-title">BILL OF MATERIALS</div>
        </div>
        <div class="doc-meta">
            <div class="doc-number mono">{{ $configuration->configuration_number }}</div>
            <div>BOM Rev {{ str_pad($configuration->bom_revision, 2, '0', STR_PAD_LEFT) }}</div>
            <div>{{ now()->format('d M Y') }}</div>
        </div>
    </div>
</div>

{{-- Info Grid --}}
<div class="info-grid">
    <div class="info-box">
        <div class="info-label">Product</div>
        <div class="info-value">{{ $configuration->product->name }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Product Code</div>
        <div class="info-value mono">{{ $configuration->product->code ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Customer</div>
        <div class="info-value">{{ $configuration->customer ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Project</div>
        <div class="info-value">{{ $configuration->project ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Sales Person</div>
        <div class="info-value">{{ $configuration->user?->name ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="info-label">Part Number</div>
        <div class="info-value mono">{{ $configuration->configuration_code ?? '—' }}</div>
    </div>
</div>

{{-- Validation Warnings --}}
@if(!empty($validation['errors']))
<div class="warning-box">
    <div class="warning-title">⚠ BOM Validation Issues ({{ count($validation['errors']) }})</div>
    @foreach($validation['errors'] as $err)
        <div class="warning-item">• {{ $err }}</div>
    @endforeach
</div>
@endif

{{-- BOM Table --}}
<div class="section-title">Bill of Materials — {{ count(array_filter($bomItems, fn ($row) => ! ($row['is_assembly'] ?? false))) }} Items</div>

<table>
    <thead>
        <tr>
            <th style="width:5%">Item</th>
            <th style="width:4%" class="text-center">Level</th>
            <th style="width:14%">Part Number</th>
            <th style="width:24%">Description</th>
            <th style="width:12%">Type</th>
            <th style="width:14%">Manufacturer</th>
            <th style="width:10%">Mfr Part No.</th>
            <th style="width:5%" class="text-center">Qty</th>
            <th style="width:5%" class="text-center">Unit</th>
            @if($showPricing)
            <th style="width:8%" class="text-right">Unit Price</th>
            <th style="width:9%" class="text-right">Total</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($bomItems as $item)
        <tr>
            <td class="mono text-center">{{ $item['item_no'] }}</td>
            <td class="text-center">{{ $item['level'] ?? 0 }}</td>
            <td class="mono" style="font-size:7.5px">{{ $item['part_number'] ?? '—' }}</td>
            <td style="padding-left: {{ 4 + ($item['level'] ?? 0) * 10 }}px">
                <strong>{{ $item['description'] }}</strong>
                @if($item['is_assembly'] ?? false)
                    <span style="color:#1d4ed8;font-size:7px">[ASSEMBLY]</span>
                @endif
                @if($item['long_description'])
                    <br><span style="color:#64748b;font-size:7px">{{ Str::limit($item['long_description'], 80) }}</span>
                @endif
            </td>
            <td>{{ $item['type'] }}</td>
            <td style="font-size:7.5px">{{ $item['manufacturer'] ?? '—' }}</td>
            <td class="mono" style="font-size:7px">{{ $item['manufacturer_part_no'] ?? '—' }}</td>
            <td class="text-center"><strong>{{ $item['quantity'] }}</strong></td>
            <td class="text-center">{{ $item['unit'] }}</td>
            @if($showPricing)
            <td class="text-right">{{ $item['unit_price'] > 0 ? number_format($item['unit_price'], 2) : '—' }}</td>
            <td class="text-right"><strong>{{ $item['total_price'] > 0 ? number_format($item['total_price'], 2) : '—' }}</strong></td>
            @endif
        </tr>
        @endforeach
    </tbody>
    @if($showPricing)
    <tfoot>
        <tr>
            <td colspan="9" class="text-right">TOTAL ESTIMATED VALUE</td>
            <td colspan="2" class="text-right">
                {{ number_format(array_sum(array_column($bomItems, 'total_price')), 2) }}
                {{ $configuration->currency }}
            </td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Footer --}}
<div class="footer">
    <div>
        <span class="confidential">CONFIDENTIAL — INTERNAL USE ONLY</span>
        &nbsp;|&nbsp; Generated: {{ now()->format('d M Y H:i') }}
        &nbsp;|&nbsp; {{ $configuration->configuration_number }} / BOM Rev {{ str_pad($configuration->bom_revision, 2, '0', STR_PAD_LEFT) }}
    </div>
    <div>ConfigPro — Heconomy &copy; {{ date('Y') }}</div>
</div>

</body>
</html>

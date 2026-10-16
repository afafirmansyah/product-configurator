{{--
    Nested BOM preview. Pass the flat, depth-first BOM rows as `rows`; the component
    nests them with BomTree::nest() and renders itself recursively through `nodes`.
--}}
@props(['rows' => [], 'showPricing' => true, 'nodes' => null, 'depth' => 0, 'label' => 'BOM structure'])

@php
    $nodes ??= \App\Services\Bom\BomTree::nest(array_values($rows));
@endphp

<ul @if ($depth === 0) role="tree" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'space-y-1 text-sm']) }} @else role="group" class="mt-1 ml-3 space-y-1 border-l border-slate-200 pl-3" @endif>
    @foreach ($nodes as $node)
        @php
            $isAssembly = (bool) ($node['is_assembly'] ?? false);
            $hasChildren = !empty($node['children']);
            $amount = $isAssembly ? ($node['rollup_total'] ?? null) : ($node['total_price'] ?? null);
        @endphp
        <li role="treeitem" aria-level="{{ $depth + 1 }}" @if ($hasChildren) aria-expanded="true" @endif>
            <div @class([
                'flex items-center justify-between gap-3 rounded-md px-2 py-1.5',
                'bg-slate-50' => $isAssembly,
            ])>
                <div class="flex min-w-0 items-center gap-2">
                    <span class="shrink-0 font-mono text-xs text-slate-400">{{ $node['item_no'] ?? '' }}</span>
                    <span @class(['truncate text-slate-800', 'font-semibold' => $isAssembly, 'font-medium' => !$isAssembly])>
                        {{ (float) $node['quantity'] }} × {{ $node['description'] ?? '' }}
                    </span>
                    @if ($isAssembly)
                        <x-ui.badge color="blue">Assembly</x-ui.badge>
                    @endif
                    @if (!empty($node['part_number']))
                        <span class="shrink-0 font-mono text-xs text-slate-400">{{ $node['part_number'] }}</span>
                    @endif
                </div>
                @if ($showPricing && $amount !== null)
                    <span @class(['shrink-0 tabular-nums', 'text-slate-500 text-xs' => $isAssembly, 'text-slate-700' => !$isAssembly])>
                        @if ($isAssembly)
                            <span class="sr-only">Rolled-up total</span>
                        @endif
                        {{ number_format((float) $amount, 2) }}
                    </span>
                @endif
            </div>
            @if ($hasChildren)
                <x-bom.tree :nodes="$node['children']" :depth="$depth + 1" :show-pricing="$showPricing" />
            @endif
        </li>
    @endforeach
</ul>

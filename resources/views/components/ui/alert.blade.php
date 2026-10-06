@props(['type' => 'info', 'dismissible' => false])
@php
$types = [
    'success' => ['cls' => 'alert-success', 'icon' => 'check'],
    'warning' => ['cls' => 'alert-warning', 'icon' => 'warning'],
    'danger'  => ['cls' => 'alert-danger',  'icon' => 'warning'],
    'info'    => ['cls' => 'alert-info',    'icon' => 'info'],
];
$t = $types[$type] ?? $types['info'];
@endphp
<div {{ $attributes->merge(['class' => 'alert ' . $t['cls']]) }} role="alert">
    <div class="flex items-start gap-2.5">
        <x-ui.icon name="{{ $t['icon'] }}" class="w-4 h-4 flex-shrink-0 mt-0.5"/>
        <div class="flex-1 text-sm">{{ $slot }}</div>
    </div>
</div>

@props(['variant' => 'secondary', 'size' => 'md', 'href' => null, 'type' => 'button'])
@php
$variants = [
    'primary'   => 'btn-primary',
    'secondary' => 'btn-secondary',
    'ghost'     => 'btn-ghost',
    'danger'    => 'btn-danger',
    'success'   => 'btn-success',
];
$sizes = ['sm' => 'btn-sm', 'md' => 'btn-md', 'lg' => 'btn-lg'];
$cls = 'btn ' . ($variants[$variant] ?? 'btn-secondary') . ' ' . ($sizes[$size] ?? 'btn-md');
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif

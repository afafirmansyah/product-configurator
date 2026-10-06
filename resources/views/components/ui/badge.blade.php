@props(['color' => 'gray', 'size' => 'sm'])
@php
$colors = [
    'gray'   => 'badge-gray',
    'blue'   => 'badge-blue',
    'green'  => 'badge-green',
    'yellow' => 'badge-yellow',
    'red'    => 'badge-red',
    'purple' => 'badge-purple',
    'orange' => 'badge-orange',
    'teal'   => 'badge-teal',
];
$cls = $colors[$color] ?? 'badge-gray';
@endphp
<span {{ $attributes->merge(['class' => "badge $cls"]) }}>{{ $slot }}</span>

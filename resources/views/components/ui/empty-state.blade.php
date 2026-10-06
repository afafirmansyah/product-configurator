@props(['icon' => 'info', 'title', 'description' => null, 'actionLabel' => null, 'actionHref' => null])
<div class="flex flex-col items-center justify-center py-16 px-4 text-center">
    <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
        <x-ui.icon name="{{ $icon }}" class="w-7 h-7 text-slate-400"/>
    </div>
    <h3 class="text-sm font-semibold text-slate-700 mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-slate-400 max-w-xs mb-4">{{ $description }}</p>
    @endif
    @if($actionLabel && $actionHref)
        <x-ui.button href="{{ $actionHref }}" variant="primary" size="sm">
            <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
            {{ $actionLabel }}
        </x-ui.button>
    @endif
    {{ $slot }}
</div>

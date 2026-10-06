@props(['id', 'title' => 'Confirm Delete', 'name' => '', 'action' => null, 'confirmLabel' => 'Delete', 'footer' => null])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    style="background:rgba(15,23,42,0.45)"
    @keydown.escape.window="open = false"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="open = false"
        class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 overflow-hidden"
    >
        {{-- Header --}}
        <div class="flex items-start gap-4 px-6 pt-6 pb-4">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background:#fff1f2">
                <x-ui.icon name="trash" class="w-5 h-5" style="color:#e11d48"/>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-slate-900">{{ $title }}</div>
                <p class="text-sm text-slate-500 mt-1">
                    Are you sure you want to delete
                    @if($name) <span class="font-medium text-slate-700">"{{ $name }}"</span>@endif?
                    This action cannot be undone.
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t" style="border-color:#f1f5f9">
            @if($footer && $footer->isNotEmpty())
                {{ $footer }}
            @else
                <button type="button" @click="open = false" class="btn btn-secondary btn-sm">
                    Cancel
                </button>
                @if($action)
                    <form method="POST" action="{{ $action }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                            {{ $confirmLabel }}
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>

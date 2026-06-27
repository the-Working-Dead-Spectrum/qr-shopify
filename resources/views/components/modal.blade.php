@props([
    'name',
    'title' => '',
    'maxWidth' => '2xl', // sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
])

@php
$maxWidthClasses = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

<div 
    x-data="{
        show: false,
        name: '{{ $name }}'
    }" 
    x-on:open-modal.window="
        if ($event.detail === name) {
            show = true;
        }
    "
    x-on:close-modal.window="
        if ($event.detail === name) {
            show = false;
        }
    "
    x-on:open-modal.window="
        if ($event.detail === name) {
            show = true;
            $nextTick(() => $refs.dialog.focus());
        }
    "
    x-on:close-modal.window="
        if ($event.detail === name) {
            show = false;
        }
    "
    x-on:keydown.escape.window="
        if (show && $event.target.tagName !== 'INPUT' && $event.target.tagName !== 'TEXTAREA' && $event.target.tagName !== 'SELECT') {
            show = false;
        }
    "
    x-show="show"
    x-transition
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
    style="display: none;"
>
    <div 
        x-ref="dialog"
        x-on:click.away="show = false"
        tabindex="-1"
        class="w-full {{ $maxWidthClasses }} bg-white rounded-xl shadow-2xl dark:bg-slate-800 outline-none"
    >
        @if($title)
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ $title }}
                </h3>
                <button 
                    x-on:click="show = false"
                    class="p-1 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>
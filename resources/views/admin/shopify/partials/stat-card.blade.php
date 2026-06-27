{{-- Carte KPI réutilisable pour le dashboard Shopify --}}
@props(['label', 'value', 'color' => 'blue', 'icon' => null])

@php
    $colorClasses = match($color) {
        'blue'   => 'bg-blue-50 text-blue-700',
        'green'  => 'bg-green-50 text-green-700',
        'red'    => 'bg-red-50 text-red-700',
        'indigo' => 'bg-indigo-50 text-indigo-700',
        'orange' => 'bg-orange-50 text-orange-700',
        default  => 'bg-gray-50 text-gray-700',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white shadow rounded-lg p-5']) }}>
    <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
    <div class="mt-1 text-3xl font-semibold {{ $colorClasses }}">
        {{ $value }}
    </div>
</div>
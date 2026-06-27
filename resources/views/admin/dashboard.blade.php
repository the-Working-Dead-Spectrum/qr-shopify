@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $kpis = $kpis ?? null;
        $alerts = $kpis?->alerts ?? [];
    @endphp

    {{-- Header --}}
    <div class="mb-6 lg:mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-sm text-slate-500 mt-1">
            Vue d'ensemble &middot; actualisé
            @if($kpis?->computedAt)
                le {{ $kpis->computedAt->format('d/m/Y à H:i:s') }}
            @endif
        </p>
    </div>

    {{-- Alertes --}}
    @if(count($alerts) > 0)
        <div class="space-y-3 mb-6">
            @foreach($alerts as $alert)
                @php
                    $colors = [
                        'red'    => 'bg-rose-50 border-rose-200 text-rose-800',
                        'orange' => 'bg-orange-50 border-orange-200 text-orange-800',
                        'yellow' => 'bg-amber-50 border-amber-200 text-amber-800',
                    ];
                    $color = $colors[$alert['level']] ?? 'bg-slate-50 border-slate-200 text-slate-800';
                @endphp
                <div class="border rounded-lg px-4 py-3 {{ $color }} flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span class="flex-1 text-sm font-medium">{{ $alert['message'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php
            $cards = [
                ['label' => 'QR générés aujourd\'hui', 'value' => $kpis?->qrGeneratedToday ?? 0, 'icon' => 'M12 4v16m8-8H4', 'color' => 'blue'],
                ['label' => 'QR utilisés aujourd\'hui', 'value' => $kpis?->qrUsedToday ?? 0, 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald'],
                ['label' => 'QR expirés non scannés', 'value' => $kpis?->qrExpiredUnscanned ?? 0, 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber'],
                ['label' => 'Commandes en attente de QR', 'value' => $kpis?->ordersAwaitingQr ?? 0, 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', 'color' => 'rose'],
            ];
        @endphp

        @foreach($cards as $card)
            @php
                $bgColors = [
                    'blue' => 'bg-blue-50 text-blue-600',
                    'emerald' => 'bg-emerald-50 text-emerald-600',
                    'amber' => 'bg-amber-50 text-amber-600',
                    'rose' => 'bg-rose-50 text-rose-600',
                ];
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-slate-500">{{ $card['label'] }}</span>
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg {{ $bgColors[$card['color']] }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                        </svg>
                    </span>
                </div>
                <div class="text-3xl font-bold text-slate-900">{{ number_format($card['value'], 0, ',', ' ') }}</div>
            </div>
        @endforeach
    </div>

    {{-- Métriques secondaires --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Taux d'utilisation global</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-bold text-slate-900">{{ number_format($kpis?->usageRate ?? 0, 1, ',', ' ') }}</span>
                <span class="text-lg text-slate-500">%</span>
            </div>
            <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-blue-600 transition-all" style="width: {{ min(100, $kpis?->usageRate ?? 0) }}%"></div>
            </div>
            <p class="text-xs text-slate-500 mt-2">QR Codes utilisés sur total généré.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Délai moyen email → QR</h3>
            <div class="flex items-baseline gap-2">
                @if($kpis?->avgEmailDeliverySeconds !== null)
                    <span class="text-4xl font-bold text-slate-900">{{ number_format($kpis->avgEmailDeliverySeconds, 0, ',', ' ') }}</span>
                    <span class="text-lg text-slate-500">secondes</span>
                @else
                    <span class="text-2xl text-slate-400">—</span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-4">Temps entre la commande Shopify et la génération du QR.</p>
        </div>
    </div>

    {{-- Actions rapides --}}
    <div class="mt-8 bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Actions rapides</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.orders.index') }}"
               class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Voir les commandes
            </a>
            <a href="{{ route('admin.partners.index') }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                Gérer les partenaires
            </a>
            <a href="{{ route('admin.validations.index') }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                Historique des scans
            </a>
            <a href="{{ route('admin.reports.export', ['type' => 'validations']) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                Exporter CSV
            </a>
        </div>
    </div>
@endsection
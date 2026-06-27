{{--
    Dashboard admin Shopify
    Affiche les KPIs webhooks + santé queue + sync commandes.
    Polling AJAX toutes les 30s pour rafraîchir les stats.
--}}
@extends('layouts.admin')

@section('title', 'Shopify — Dashboard')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8">
    {{-- En-tête --}}
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Shopify</h1>
            <p class="mt-1 text-sm text-gray-500">
                Santé de l'intégration Shopify — Webhooks, files d'attente, synchronisation
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 gap-2">
            <button
                type="button"
                hx-post="{{ route('admin.shopify.test-connection') }}"
                hx-swap="none"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Tester la connexion
            </button>
            <button
                type="button"
                onclick="window.location.reload()"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Actualiser
            </button>
        </div>
    </div>

    {{-- KPIs 24h --}}
    <h2 class="text-lg font-medium text-gray-900 mb-3">Dernières 24 heures</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @include('admin.shopify.partials.stat-card', [
            'label' => 'Webhooks reçus',
            'value' => $stats24h['received'] ?? 0,
            'color' => 'blue',
            'icon'  => 'inbox',
        ])
        @include('admin.shopify.partials.stat-card', [
            'label' => 'Traités avec succès',
            'value' => $stats24h['processed'] ?? 0,
            'color' => 'green',
            'icon'  => 'check',
        ])
        @include('admin.shopify.partials.stat-card', [
            'label' => 'En échec',
            'value' => $stats24h['failed'] ?? 0,
            'color' => 'red',
            'icon'  => 'x',
        ])
        @include('admin.shopify.partials.stat-card', [
            'label' => 'Latence moyenne',
            'value' => ($stats24h['avg_latency_ms'] ?? 0) . ' ms',
            'color' => 'indigo',
            'icon'  => 'clock',
        ])
    </div>

    {{-- Par topic --}}
    @if (! empty($stats24h['by_topic']))
        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <h3 class="text-base font-medium text-gray-900 mb-4">Répartition par topic</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($stats24h['by_topic'] as $topic => $count)
                    <div class="bg-gray-50 px-4 py-3 rounded">
                        <dt class="text-xs font-medium text-gray-500 truncate">{{ $topic }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    {{-- 7 jours --}}
    <h2 class="text-lg font-medium text-gray-900 mb-3">7 derniers jours</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white shadow rounded-lg p-5">
            <div class="text-sm font-medium text-gray-500">Taux de succès</div>
            <div class="mt-1 text-3xl font-semibold {{ ($stats7d['success_rate'] ?? 0) >= 95 ? 'text-green-600' : 'text-orange-600' }}">
                {{ $stats7d['success_rate'] ?? 100 }}%
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <div class="text-sm font-medium text-gray-500">Total traités</div>
            <div class="mt-1 text-3xl font-semibold text-gray-900">
                {{ $stats7d['processed'] ?? 0 }}
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <div class="text-sm font-medium text-gray-500">Total échoués</div>
            <div class="mt-1 text-3xl font-semibold text-red-600">
                {{ $stats7d['failed'] ?? 0 }}
            </div>
        </div>
    </div>

    {{-- Top échecs --}}
    @if (! empty($stats7d['top_failures']))
        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <h3 class="text-base font-medium text-gray-900 mb-4">Top échecs par topic</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Topic</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Occurrences</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($stats7d['top_failures'] as $row)
                        <tr>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $row['topic'] }}</td>
                            <td class="px-3 py-2 text-sm text-red-600 text-right font-medium">{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Santé queue --}}
    <h2 class="text-lg font-medium text-gray-900 mb-3">File d'attente</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <div class="bg-white shadow rounded-lg p-5">
            <div class="text-sm font-medium text-gray-500">Jobs échoués (7j)</div>
            <div class="mt-1 text-3xl font-semibold {{ ($queueHealth['failed_jobs_count'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $queueHealth['failed_jobs_count'] ?? 0 }}
            </div>
            @if (! empty($queueHealth['last_failure_at']))
                <div class="mt-2 text-xs text-gray-500">
                    Dernier échec : {{ \Carbon\Carbon::parse($queueHealth['last_failure_at'])->diffForHumans() }}
                </div>
            @endif
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <div class="text-sm font-medium text-gray-500">Commandes synchronisées (24h)</div>
            <div class="mt-1 text-3xl font-semibold text-gray-900">
                {{ $ordersHealth['orders_paid_24h'] ?? 0 }} <span class="text-base font-normal text-gray-500">payées</span>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                {{ $ordersHealth['orders_cancelled_24h'] ?? 0 }} annulée(s) ·
                {{ $ordersHealth['orders_created_24h'] ?? 0 }} créée(s)
            </div>
        </div>
    </div>

    {{-- Note --}}
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Logs détaillés : <code>storage/logs/shopify-YYYY-MM-DD.log</code>.
                    Pour les échecs définitifs, une alerte email est envoyée à
                    <code>{{ config('mail.admin_email', env('ADMIN_EMAIL', 'admin@app.com')) }}</code>.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Polling AJAX --}}
<script>
    setInterval(() => {
        fetch('{{ route('admin.shopify.stats.json') }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(() => {
            // On rafraîchit la page plutôt que de patcher le DOM (simple, robuste)
            window.location.reload();
        })
        .catch(() => {});
    }, 30000);
</script>
@endsection
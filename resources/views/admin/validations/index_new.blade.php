@extends('layouts.admin_new')

@section('title', 'Historique des scans')

@section('content')
<section class="p-lg flex-1">
    <div class="max-w-7xl mx-auto flex flex-col gap-lg">
        <!-- Header Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface">Historique des scans</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Suivi en temps réel des validations de codes QR par les partenaires.</p>
            </div>
            <div class="flex items-center gap-xs">
                <a href="{{ route('admin.reports.export') }}" class="flex items-center gap-xs px-md py-xs bg-white border border-outline-variant rounded-lg text-button-text font-button-text text-secondary hover:bg-surface-variant transition-colors">
                    <span class="material-symbols-outlined text-[20px]">download</span>
                    Exporter CSV
                </a>
            </div>
        </div>
        
        <!-- Filter Bar (Bento style integration) -->
        <form method="GET" action="{{ route('admin.validations.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-md p-md bg-white border border-outline-variant rounded-xl shadow-sm">
            <div class="flex flex-col gap-xs">
                <label class="text-label-md text-on-surface-variant uppercase tracking-wider">Partenaire</label>
                <select name="partner_id" class="p-xs bg-surface-container-lowest border border-outline-variant rounded-lg text-body-md focus:ring-primary focus:border-primary">
                    <option value="">Tous les partenaires</option>
                    @foreach(\App\Models\Partner::orderBy('name')->get() as $partner)
                        <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                            {{ $partner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex flex-col gap-xs">
                <label class="text-label-md text-on-surface-variant uppercase tracking-wider">Statut</label>
                <select name="status" class="p-xs bg-surface-container-lowest border border-outline-variant rounded-lg text-body-md focus:ring-primary focus:border-primary">
                    <option value="">Tous les statuts</option>
                    <option value="valid" {{ request('status') === 'valid' ? 'selected' : '' }}>Validé</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                </select>
            </div>
            
            <div class="flex flex-col gap-xs">
                <label class="text-label-md text-on-surface-variant uppercase tracking-wider">À partir du</label>
                <input type="date" name="date_from" class="p-xs bg-surface-container-lowest border border-outline-variant rounded-lg text-body-md focus:ring-primary" value="{{ request('date_from') }}">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full h-[38px] flex items-center justify-center gap-xs px-md bg-surface-container-highest text-on-surface rounded-lg text-button-text font-button-text hover:bg-outline-variant transition-colors">
                    <span class="material-symbols-outlined text-[20px]">filter_list</span>
                    Appliquer les filtres
                </button>
            </div>
        </form>
        
        <!-- Table Section -->
        <div class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant">
                        <tr>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Date</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">QR Code</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Commande</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Partenaire</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase text-center">Statut</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        @forelse($validations as $validation)
                            @php
                                $statusColor = $validation->status === 'valid' ? 'bg-[#008060]/10 text-[#008060]' : 'bg-error/10 text-error';
                                $statusText = ucfirst($validation->status);
                                $statusIcon = $validation->status === 'valid' ? 'check_circle' : 'cancel';
                            @endphp
                            <tr class="hover:bg-surface-container-lowest transition-colors group">
                                <td class="px-lg py-md text-body-md text-on-surface whitespace-nowrap">
                                    {{ $validation->scanned_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-lg py-md">
                                    <div class="text-sm font-mono text-on-surface break-all">{{ $validation->qrCode?->uuid }}</div>
                                    <div class="text-xs text-on-surface-variant">ID: {{ $validation->qrCode?->id }}</div>
                                </td>
                                <td class="px-lg py-md">
                                    @if($validation->qrCode?->order)
                                        <a href="{{ route('admin.orders.show', $validation->qrCode->order) }}"
                                           class="text-body-md text-primary hover:underline font-medium">
                                            #{{ $validation->qrCode->order->shopify_order_id }}
                                        </a>
                                    @else
                                        <span class="text-body-md text-on-surface-variant italic">Inconnu</span>
                                    @endif
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface">
                                    {{ $validation->partner?->name ?? 'Inconnu' }}
                                </td>
                                <td class="px-lg py-md text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" style="background-color: currentColor;"></span>
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant">
                                    {{ $validation->ip_address }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-lg py-md text-center text-secondary">
                                    Aucun scan trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="px-lg py-md border-t border-outline-variant flex items-center justify-between bg-surface-container-lowest">
                <span class="text-body-md text-on-surface-variant">
                    Affichage de {{ $validations->firstItem() }} à {{ $validations->lastItem() }} sur {{ $validations->total() }} scans
                </span>
                <div class="flex items-center gap-xs">
                    @if($validations->onFirstPage())
                        <button class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary opacity-30 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                    @else
                        <a href="{{ $validations->previousPageUrl() }}" class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    @endif
                    
                    @for($i = 1; $i <= min($validations->lastPage(), 3); $i++)
                        @if($i === $validations->currentPage())
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-on-primary text-body-md font-bold">{{ $i }}</button>
                        @else
                            <a href="{{ $validations->url($i) }}" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-surface-container-high text-on-surface text-body-md transition-colors">{{ $i }}</a>
                        @endif
                    @endfor
                    
                    @if($validations->lastPage() > 3)
                        <span class="text-secondary">...</span>
                        <a href="{{ $validations->url($validations->lastPage()) }}" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-surface-container-high text-on-surface text-body-md transition-colors">{{ $validations->lastPage() }}</a>
                    @endif
                    
                    @if($validations->hasMorePages())
                        <a href="{{ $validations->nextPageUrl() }}" class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    @else
                        <button class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary opacity-30 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards (Bento style) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-lg">
            <div class="p-lg bg-white border border-outline-variant rounded-xl shadow-sm">
                <span class="text-label-md text-secondary uppercase tracking-wider">Taux de succès</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold text-on-surface">
                        {{ $validations->count() > 0 ? min(100, round(($validations->where('status', 'valid')->count() / $validations->count()) * 100)) : 0 }}%
                    </span>
                    <span class="text-label-md {{ $validations->where('status', 'valid')->count() > $validations->where('status', 'failed')->count() ? 'text-[#008060]' : 'text-error' }} font-bold">
                        {{ $validations->where('status', 'valid')->count() }} validés
                    </span>
                </div>
            </div>
            
            <div class="p-lg bg-white border border-outline-variant rounded-xl shadow-sm">
                <span class="text-label-md text-secondary uppercase tracking-wider">Scans aujourd'hui</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold text-on-surface">{{ $validations->where('scanned_at', '>=', now()->startOfDay())->count() }}</span>
                    <span class="text-label-md {{ $validations->where('scanned_at', '>=', now()->startOfDay())->count() > $validations->where('scanned_at', '>=', now()->subDay()->startOfDay())->where('scanned_at', '<', now()->startOfDay())->count() ? 'text-[#008060]' : 'text-error' }} font-bold">
                        {{ $validations->where('scanned_at', '>=', now()->startOfDay())->count() - $validations->where('scanned_at', '>=', now()->subDay()->startOfDay())->where('scanned_at', '<', now()->startOfDay())->count() > 0 ? '+' : '' }}
                        {{ $validations->where('scanned_at', '>=', now()->startOfDay())->count() - $validations->where('scanned_at', '>=', now()->subDay()->startOfDay())->where('scanned_at', '<', now()->startOfDay())->count() }}
                    </span>
                </div>
            </div>
            
            <div class="p-lg bg-white border border-outline-variant rounded-xl shadow-sm">
                <span class="text-label-md text-secondary uppercase tracking-wider">Scans échoués</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold text-on-surface">{{ $validations->where('status', 'failed')->count() }}</span>
                    <span class="text-label-md text-secondary">
                        {{ $validations->count() > 0 ? round(($validations->where('status', 'failed')->count() / $validations->count()) * 100) : 0 }}%
                    </span>
                </div>
            </div>
            
            <div class="p-lg bg-primary text-on-primary border border-primary-container rounded-xl shadow-md">
                <span class="text-label-md text-on-primary-container uppercase tracking-wider">Partenaire le plus actif</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold">
                        {{ $validations->groupBy('partner_id')->sortDesc()->keys()->first() ? \App\Models\Partner::find($validations->groupBy('partner_id')->sortDesc()->keys()->first())->name : 'Aucun' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Micro-interactions for table rows */
    tbody tr {
        transition: opacity 0.1s ease;
    }
    
    tbody tr:hover {
        cursor: pointer;
    }
    
    /* Pagination styles */
    .pagination-container {
        display: flex;
        gap: 4px;
    }
    
    .pagination-container span {
        padding: 8px 12px;
        border-radius: 8px;
    }
    
    .pagination-container a {
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .pagination-container a:hover {
        background-color: #f2f4f6;
    }
</style>

<script>
    // Simple Micro-interactions for table rows
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a') || e.target.closest('button')) return;
            row.classList.add('opacity-70');
            setTimeout(() => {
                row.classList.remove('opacity-70');
            }, 100);
        });
    });
</script>
@endsection
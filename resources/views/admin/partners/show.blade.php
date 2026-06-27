@extends('layouts.admin_new')

@section('title', 'Détails Partenaire - ' . $partner->name)

@section('content')
<div class="p-lg max-w-7xl mx-auto w-full space-y-lg">
    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-md mb-xl">
        <div>
            <nav class="flex items-center gap-xs text-on-surface-variant font-label-md mb-xs">
                <a href="{{ route('admin.partners.index') }}" class="hover:text-primary cursor-pointer">Partenaires</a>
                <span class="material-symbols-outlined text-[14px]" data-icon="chevron_right">chevron_right</span>
                <span class="text-primary">Détails Partenaire</span>
            </nav>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $partner->name }}</h2>
            <div class="flex items-center gap-sm mt-xs">
                @php
                    $statusClasses = 'px-sm py-1 rounded-full text-label-md font-bold ';
                    if($partner->status->value === 'active') {
                        $statusClasses .= 'bg-[#0080601a] text-[#008060]';
                        $statusText = 'Validé';
                    } elseif($partner->status->value === 'inactive') {
                        $statusClasses .= 'bg-amber-100 text-amber-800';
                        $statusText = 'En attente';
                    } else {
                        $statusClasses .= 'bg-error-container text-error';
                        $statusText = 'Suspendu';
                    }
                @endphp
                <span class="{{ $statusClasses }}">{{ $statusText }}</span>
                @if($partner->user?->email)
                    <span class="text-body-md text-on-surface-variant flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]" data-icon="verified_user">verified_user</span>
                        Compte vérifié
                    </span>
                @endif
            </div>
        </div>
        <div class="flex gap-sm">
            <button class="flex items-center gap-xs px-lg py-sm bg-white border border-outline-variant text-secondary rounded-lg font-button-text hover:bg-surface-container-low transition-colors active:opacity-70">
                <span class="material-symbols-outlined" data-icon="mail">mail</span>
                Contacter
            </button>
            <button onclick="openEditModal()" class="flex items-center gap-xs px-lg py-sm bg-primary text-on-primary rounded-lg font-button-text hover:brightness-90 transition-all active:scale-95 shadow-sm">
                <span class="material-symbols-outlined" data-icon="edit">edit</span>
                Modifier Partenaire
            </button>
        </div>
    </div>
    
    <!-- Bento Grid Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-lg">
        <!-- Stats Cards (Large Columns) -->
        <div class="md:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-md">
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Total Scans</span>
                    <div class="bg-primary-container/10 p-xs rounded-lg">
                        <span class="material-symbols-outlined text-primary" data-icon="qr_code_scanner">qr_code_scanner</span>
                    </div>
                </div>
                <div class="mt-md">
                    <p class="font-headline-lg text-headline-lg">{{ $partner->validations_count ?? 0 }}</p>
                    <p class="text-body-md text-[#008060] flex items-center gap-xs mt-1">
                        <span class="material-symbols-outlined text-[18px]" data-icon="trending_up">trending_up</span>
                        +{{ $partner->validations_count_7d ?? 0 }}% ce mois
                    </p>
                </div>
            </div>
            
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Taux de Conversion</span>
                    <div class="bg-tertiary-container/10 p-xs rounded-lg">
                        <span class="material-symbols-outlined text-tertiary" data-icon="percent">percent</span>
                    </div>
                </div>
                <div class="mt-md">
                    <p class="font-headline-lg text-headline-lg">
                        {{ $partner->validations_count > 0 ? min(100, round(($partner->validations_count / ($partner->validations_count + 10)) * 100)) : 0 }}%
                    </p>
                    <p class="text-body-md text-[#008060] flex items-center gap-xs mt-1">
                        <span class="material-symbols-outlined text-[18px]" data-icon="check_circle">check_circle</span>
                        Au-dessus de la moyenne
                    </p>
                </div>
            </div>
            
            <!-- Recent Activity List -->
            <div class="sm:col-span-2 bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="p-lg border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Activité de Scan Récente</h3>
                    <a href="#" class="text-primary font-button-text hover:underline text-sm">Voir tout</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-surface-container-low">
                            <tr class="text-label-md text-on-surface-variant">
                                <th class="px-lg py-md">Produit / ID</th>
                                <th class="px-lg py-md">Localisation</th>
                                <th class="px-lg py-md">Statut</th>
                                <th class="px-lg py-md">Date & Heure</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse($recentScans as $scan)
                                <tr class="hover:bg-surface-container-lowest transition-colors">
                                    <td class="px-lg py-md">
                                        <p class="font-body-md font-bold">{{ $scan->qrCode?->order?->product_name ?? 'Produit inconnu' }}</p>
                                        <p class="text-[12px] text-on-surface-variant">#{{ $scan->qrCode?->token ?? 'N/A' }}</p>
                                    </td>
                                    <td class="px-lg py-md text-body-md">
                                        {{ $scan->location ?? 'Non spécifiée' }}
                                    </td>
                                    <td class="px-lg py-md">
                                        <span class="px-sm py-1 bg-[#0080601a] text-[#008060] rounded-full text-[12px] font-bold">
                                            {{ $scan->status }}
                                        </span>
                                    </td>
                                    <td class="px-lg py-md text-body-md">
                                        {{ $scan->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-lg py-md text-center text-secondary">
                                        Aucun scan récent
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Info (Small Columns) -->
        <div class="md:col-span-4 space-y-lg">
            <!-- Shopify Integration Card -->
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm">
                <div class="flex items-center gap-sm mb-lg">
                    <div class="w-10 h-10 rounded-lg bg-[#95BF47] flex items-center justify-center text-white">
                        <span class="material-symbols-outlined" data-icon="shopping_bag">shopping_bag</span>
                    </div>
                    <h3 class="font-headline-md text-[20px] text-on-surface">Intégration Shopify</h3>
                </div>
                <div class="space-y-md">
                    <div>
                        <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Boutique URL</p>
                        <p class="font-body-md font-bold">{{ $partner->user?->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Statut du Sync</p>
                        <div class="flex items-center gap-xs">
                            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                            <p class="font-body-md text-primary">Synchronisé (il y a {{ $partner->updated_at->diffInMinutes() }} min)</p>
                        </div>
                    </div>
                    <div class="pt-sm border-t border-outline-variant">
                        <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Compte depuis</p>
                        <p class="font-body-md">{{ $partner->created_at->format('d/m/Y') }}</p>
                    </div>
                    <button class="w-full py-sm border border-primary text-primary rounded-lg font-button-text hover:bg-primary-container hover:text-white transition-all">
                        Reconfigurer Webhooks
                    </button>
                </div>
            </div>
            
            <!-- Map Visualization -->
            <div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="p-lg border-b border-outline-variant">
                    <h3 class="font-headline-md text-[20px] text-on-surface">Siège Social</h3>
                    <p class="text-body-md text-on-surface-variant">
                        {{ $partner->address ?? 'Adresse non spécifiée' }}
                    </p>
                </div>
                <div class="h-48 relative bg-surface-container-high">
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shadow-lg border-2 border-white">
                            <span class="material-symbols-outlined text-white text-[16px]" data-icon="location_on" data-weight="fill">location_on</span>
                        </div>
                    </div>
                    <div class="absolute bottom-4 left-4 right-4 text-center">
                        <p class="text-sm text-secondary">Carte interactive disponible avec l'API Google Maps</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm">
                <h3 class="font-headline-md text-[20px] text-on-surface mb-md">Actions Rapides</h3>
                <div class="space-y-sm">
                    <button class="w-full flex items-center justify-between py-sm px-md hover:bg-surface-container-low rounded-lg transition-colors">
                        <span class="text-body-md">Générer un nouveau token</span>
                        <span class="material-symbols-outlined text-secondary" data-icon="chevron_right">chevron_right</span>
                    </button>
                    <button class="w-full flex items-center justify-between py-sm px-md hover:bg-surface-container-low rounded-lg transition-colors">
                        <span class="text-body-md">Exporter les données</span>
                        <span class="material-symbols-outlined text-secondary" data-icon="chevron_right">chevron_right</span>
                    </button>
                    <button class="w-full flex items-center justify-between py-sm px-md hover:bg-surface-container-low rounded-lg transition-colors">
                        <span class="text-body-md">Voir les rapports</span>
                        <span class="material-symbols-outlined text-secondary" data-icon="chevron_right">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Details Section -->
    <div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-lg border-b border-outline-variant">
            <h3 class="font-headline-md text-headline-md text-on-surface">Informations Complémentaires</h3>
        </div>
        <div class="p-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
                <div>
                    <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">ID Partenaire</p>
                    <p class="font-body-md font-bold">#{{ $partner->id }}</p>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Contact Principal</p>
                    <p class="font-body-md">{{ $partner->contact_name ?? 'Non spécifié' }}</p>
                    <p class="font-body-md text-secondary">{{ $partner->contact_email ?? 'Non spécifié' }}</p>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Téléphone</p>
                    <p class="font-body-md">{{ $partner->contact_phone ?? 'Non spécifié' }}</p>
                </div>
            </div>
            
            <div class="mt-lg">
                <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Notes Internes</p>
                <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                    <p class="text-body-md text-secondary italic">
                        {{ $partner->notes ?? 'Aucune note pour ce partenaire.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Micro-interactions */
    button, a {
        transition: transform 0.1s ease, opacity 0.1s ease;
    }
    
    button:active, a:active {
        transform: scale(0.98);
        opacity: 0.8;
    }
    
    /* Status badges */
    .partner-status-active {
        background-color: rgba(0, 128, 96, 0.1);
        color: #008060;
    }
    
    .partner-status-pending {
        background-color: rgba(245, 158, 11, 0.1);
        color: #b45309;
    }
</style>

<script>
    // Micro-interactions and hover logic
    document.querySelectorAll('button, a').forEach(el => {
        el.addEventListener('mousedown', () => {
            el.style.transform = 'scale(0.96)';
            el.style.opacity = '0.8';
        });
        el.addEventListener('mouseup', () => {
            el.style.transform = '';
            el.style.opacity = '';
        });
        el.addEventListener('mouseleave', () => {
            el.style.transform = '';
            el.style.opacity = '';
        });
    });
    
    // Open edit modal function
    function openEditModal() {
        @this.dispatch('open-modal', 'edit-partner-{{ $partner->id }}');
    }
</script>

<!-- Include edit modal -->
@include('admin.partners._edit_modal')
@endsection
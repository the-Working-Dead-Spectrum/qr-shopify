@extends('layouts.admin_new')

@section('title', 'Commande #' . $order->shopify_order_id)

@section('content')
<div class="p-lg max-w-7xl mx-auto w-full space-y-lg">
    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-xl">
        <div>
            <nav class="flex items-center gap-xs text-on-surface-variant font-label-md mb-xs">
                <a href="{{ route('admin.orders.index') }}" class="hover:text-primary cursor-pointer">Commandes</a>
                <span class="material-symbols-outlined text-[14px]" data-icon="chevron_right">chevron_right</span>
                <span class="text-primary">Détails Commande</span>
            </nav>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Commande #{{ $order->shopify_order_id }}</h2>
            <div class="flex items-center gap-sm mt-xs">
                @php
                    $statusColor = 'bg-[#008060]/10 text-[#008060]';
                    $statusText = ucfirst($order->status->value);
                    
                    switch($order->status->value) {
                        case 'pending':
                            $statusColor = 'bg-amber-100 text-amber-800';
                            break;
                        case 'cancelled':
                            $statusColor = 'bg-red-100 text-red-800';
                            break;
                    }
                @endphp
                <span class="px-sm py-1 rounded-full text-label-md font-bold {{ $statusColor }}">{{ $statusText }}</span>
                <span class="text-body-md text-on-surface-variant">
                    {{ $order->created_at->format('d/m/Y H:i') }} &middot; {{ $order->formatted_amount }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-xs">
            @if($order->qrCode && $order->qrCode->isActive())
                <form action="{{ route('admin.orders.resend-qr', $order) }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="flex items-center gap-xs px-lg py-sm bg-white border border-outline-variant text-secondary rounded-lg font-button-text hover:bg-surface-container-low transition-colors active:opacity-70">
                        <span class="material-symbols-outlined" data-icon="send">send</span>
                        Renvoyer QR
                    </button>
                </form>
            @endif
            @if($order->qrCode && $order->qrCode->isActive())
                <form action="{{ route('admin.qr.revoke', $order->qrCode) }}" method="POST" 
                      onsubmit="return confirm('Révocation irréversible. Confirmer ?')">
                    @csrf
                    <button type="submit" 
                            class="flex items-center gap-xs px-lg py-sm bg-error text-on-error rounded-lg font-button-text hover:brightness-90 transition-colors shadow-sm">
                        <span class="material-symbols-outlined" data-icon="block">block</span>
                        Révoquer QR
                    </button>
                </form>
            @endif
        </div>
    </div>
    
    <!-- Bento Grid Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-lg">
        <!-- Stats Cards (Large Columns) -->
        <div class="md:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-md">
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Montant Total</span>
                    <div class="bg-primary-container/10 p-xs rounded-lg">
                        <span class="material-symbols-outlined text-primary" data-icon="paid">paid</span>
                    </div>
                </div>
                <div class="mt-md">
                    <p class="font-headline-lg text-headline-lg">{{ $order->formatted_amount }}</p>
                    <p class="text-body-md text-[#008060] flex items-center gap-xs mt-1">
                        <span class="material-symbols-outlined text-[18px]" data-icon="check_circle">check_circle</span>
                        Paiement confirmé
                    </p>
                </div>
            </div>
            
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Statut QR</span>
                    <div class="bg-tertiary-container/10 p-xs rounded-lg">
                        <span class="material-symbols-outlined text-tertiary" data-icon="qr_code_2">qr_code_2</span>
                    </div>
                </div>
                <div class="mt-md">
                    @if($order->qrCode)
                        <p class="font-headline-lg text-headline-lg">{{ ucfirst($order->qrCode->status->value) }}</p>
                        <p class="text-body-md {{ $order->qrCode->isActive() ? 'text-[#008060]' : 'text-secondary' }} flex items-center gap-xs mt-1">
                            @if($order->qrCode->isActive())
                                <span class="material-symbols-outlined text-[18px]" data-icon="check_circle">check_circle</span>
                                Actif jusqu'au {{ $order->qrCode->expires_at->format('d/m/Y') }}
                            @else
                                <span class="material-symbols-outlined text-[18px]" data-icon="cancel">cancel</span>
                                {{ $order->qrCode->status->value }}
                            @endif
                        </p>
                    @else
                        <p class="font-headline-lg text-headline-lg">Non généré</p>
                        <p class="text-body-md text-secondary flex items-center gap-xs mt-1">
                            <span class="material-symbols-outlined text-[18px]" data-icon="info">info</span>
                            Aucun code QR associé
                        </p>
                    @endif
                </div>
            </div>
            
            <!-- Recent Activity List -->
            <div class="sm:col-span-2 bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="p-lg border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Historique des scans</h3>
                    <span class="text-sm text-secondary">{{ $order->qrCode?->validations?->count() ?? 0 }} scan(s)</span>
                </div>
                <div class="overflow-x-auto">
                    @if($order->qrCode && $order->qrCode->validations->isNotEmpty())
                        <div class="space-y-2 p-md">
                            @foreach($order->qrCode->validations as $validation)
                                <div class="flex items-start gap-3 p-3 rounded-lg border border-outline-variant hover:bg-surface-container-lowest transition-colors">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full {{ $validation->status === 'valid' ? 'bg-[#008060]/10' : 'bg-error/10' }} flex items-center justify-center">
                                        @if($validation->status === 'valid')
                                            <span class="material-symbols-outlined text-[#008060]" data-icon="check_circle" data-weight="fill">check_circle</span>
                                        @else
                                            <span class="material-symbols-outlined text-error" data-icon="cancel" data-weight="fill">cancel</span>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm font-medium text-on-surface">{{ $validation->partner?->name ?? 'Inconnu' }}</span>
                                            <span class="text-xs text-on-surface-variant">{{ $validation->scanned_at?->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $validation->status === 'valid' ? 'bg-[#008060]/10 text-[#008060]' : 'bg-error/10 text-error' }}">
                                                {{ ucfirst($validation->status) }}
                                            </span>
                                            <span class="text-xs text-on-surface-variant">{{ $validation->ip_address }}</span>
                                            @if($validation->location)
                                                <span class="text-xs text-on-surface-variant">{{ $validation->location }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <span class="material-symbols-outlined text-[48px] text-outline-variant" data-icon="qr_code_scanner">qr_code_scanner</span>
                            <p class="text-sm text-on-surface-variant mt-2">Aucun scan enregistré pour cette commande.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sidebar Info (Small Columns) -->
        <div class="md:col-span-4 space-y-lg">
            <!-- Client Information Card -->
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm">
                <div class="flex items-center gap-sm mb-lg">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined" data-icon="person">person</span>
                    </div>
                    <h3 class="font-headline-md text-[20px] text-on-surface">Informations Client</h3>
                </div>
                <div class="space-y-md">
                    <div>
                        <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Nom Complet</p>
                        <p class="font-body-md font-bold">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Email</p>
                        <p class="font-body-md">{{ $order->customer_email }}</p>
                    </div>
                    <div>
                        <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Téléphone</p>
                        <p class="font-body-md">{{ $order->customer_phone ?? 'Non spécifié' }}</p>
                    </div>
                    <div class="pt-sm border-t border-outline-variant">
                        <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Adresse</p>
                        <p class="font-body-md text-sm">
                            {{ $order->shipping_address_1 ?? 'Non spécifiée' }}<br>
                            {{ $order->shipping_address_2 ? $order->shipping_address_2 . '<br>' : '' }}
                            {{ $order->shipping_city ?? '' }} {{ $order->shipping_zip ?? '' }}<br>
                            {{ $order->shipping_country ?? '' }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- QR Code Information -->
            @if($order->qrCode)
                <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm">
                    <div class="flex items-center gap-sm mb-lg">
                        <div class="w-10 h-10 rounded-lg bg-tertiary/10 flex items-center justify-center text-tertiary">
                            <span class="material-symbols-outlined" data-icon="qr_code_2">qr_code_2</span>
                        </div>
                        <h3 class="font-headline-md text-[20px] text-on-surface">Code QR</h3>
                    </div>
                    <div class="space-y-md">
                        <div>
                            <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Token</p>
                            <p class="font-mono text-sm font-bold break-all">{{ $order->qrCode->token }}</p>
                        </div>
                        <div>
                            <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">UUID</p>
                            <p class="font-mono text-xs text-on-surface-variant break-all">{{ $order->qrCode->uuid }}</p>
                        </div>
                        <div>
                            <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Créé le</p>
                            <p class="font-body-md">{{ $order->qrCode->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Expire le</p>
                            <p class="font-body-md">{{ $order->qrCode->expires_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($order->qrCode->used_at)
                            <div>
                                <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Validé le</p>
                                <p class="font-body-md">{{ $order->qrCode->used_at->format('d/m/Y H:i') }}</p>
                            </div>
                        @endif
                        @if($order->qrCode->partner)
                            <div class="pt-sm border-t border-outline-variant">
                                <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Partenaire</p>
                                <p class="font-body-md">{{ $order->qrCode->partner->name }}</p>
                                <p class="font-body-md text-sm text-on-surface-variant">{{ $order->qrCode->partner->email }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            
            <!-- Quick Actions -->
            <div class="bg-white p-lg rounded-xl border border-outline-variant shadow-sm">
                <h3 class="font-headline-md text-[20px] text-on-surface mb-md">Actions Rapides</h3>
                <div class="space-y-sm">
                    <button class="w-full flex items-center justify-between py-sm px-md hover:bg-surface-container-low rounded-lg transition-colors">
                        <span class="text-body-md">Télécharger reçu</span>
                        <span class="material-symbols-outlined text-secondary" data-icon="chevron_right">chevron_right</span>
                    </button>
                    <button class="w-full flex items-center justify-between py-sm px-md hover:bg-surface-container-low rounded-lg transition-colors">
                        <span class="text-body-md">Envoyer notification</span>
                        <span class="material-symbols-outlined text-secondary" data-icon="chevron_right">chevron_right</span>
                    </button>
                    <button class="w-full flex items-center justify-between py-sm px-md hover:bg-surface-container-low rounded-lg transition-colors">
                        <span class="text-body-md">Voir dans Shopify</span>
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
                    <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">ID Shopify</p>
                    <p class="font-body-md font-bold">#{{ $order->shopify_order_id }}</p>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Produit</p>
                    <p class="font-body-md">{{ $order->product_name ?? 'Produit non spécifié' }}</p>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Paiement</p>
                    <p class="font-body-md">{{ ucfirst($order->payment_method ?? 'Non spécifié') }}</p>
                </div>
            </div>
            
            @if($order->notes)
                <div class="mt-lg">
                    <p class="text-label-md text-on-surface-variant mb-1 uppercase tracking-tighter">Notes</p>
                    <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                        <p class="text-body-md text-on-surface">{{ $order->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Back to list -->
    <div class="mt-lg">
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-body-md text-secondary hover:text-primary transition-colors">
            <span class="material-symbols-outlined" data-icon="arrow_back">arrow_back</span>
            Retour à la liste des commandes
        </a>
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
</script>
@endsection
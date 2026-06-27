@extends('layouts.admin')

@section('title', 'Commande #' . $order->shopify_order_id)

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Commande #{{ $order->shopify_order_id }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $order->created_at?->format('d/m/Y H:i') }} &middot; {{ $order->formatted_amount }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($order->qrCode && $order->qrCode->isActive())
                <form action="{{ route('admin.orders.resend-qr', $order) }}" method="POST" 
                      x-data x-on:submit.prevent="$el.submit(); $el.querySelector('button').disabled = true">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" 
                                  d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.879A3 3 0 0115 18.257V17.25m-6-10.5V5.25a3 3 0 01.879-2.122L16.5 3h-9l1.621.879A3 3 0 019 6.257V7.5" />
                        </svg>
                        Renvoyer QR par email
                    </button>
                </form>
            @endif
            @if($order->qrCode && $order->qrCode->isActive())
                <form action="{{ route('admin.qr.revoke', $order->qrCode) }}" method="POST" 
                      x-data x-on:submit.prevent="if(confirm('Révocation irréversible. Confirmer ?')) { $el.submit() }">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center gap-2 bg-rose-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-rose-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" 
                                  d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636M5.636 5.636l12.728 12.728" />
                        </svg>
                        Révoquer QR
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Colonne gauche : Infos client --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Client</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-slate-500">Nom</p>
                        <p class="font-medium text-slate-900">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Email</p>
                        <p class="font-medium text-slate-900">{{ $order->customer_email }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Statut commande</p>
                        @php
                            $statusColors = [
                                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $statusColors[$order->status->value] ?? 'bg-slate-50' }}">
                            {{ ucfirst($order->status->value) }}
                        </span>
                    </div>
                </div>
            </div>

            @if($order->qrCode)
                <div class="bg-white rounded-xl border border-slate-200 p-6 mt-4">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">QR Code</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-slate-500">UUID</p>
                            <p class="font-mono text-xs text-slate-900 break-all">{{ $order->qrCode->uuid }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Statut</p>
                            @php
                                $qrStatusColors = [
                                    'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'used' => 'bg-slate-50 text-slate-700 border-slate-200',
                                    'expired' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'revoked' => 'bg-rose-50 text-rose-700 border-rose-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $qrStatusColors[$order->qrCode->status->value] ?? 'bg-slate-50' }}">
                                {{ ucfirst($order->qrCode->status->value) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Expire le</p>
                            <p class="font-medium text-slate-900">{{ $order->qrCode->expires_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($order->qrCode->used_at)
                            <div>
                                <p class="text-sm text-slate-500">Validé le</p>
                                <p class="font-medium text-slate-900">{{ $order->qrCode->used_at?->format('d/m/Y H:i') }}</p>
                            </div>
                        @endif
                        @if($order->qrCode->partner_id)
                            <div>
                                <p class="text-sm text-slate-500">Validé par</p>
                                <p class="font-medium text-slate-900">{{ $order->qrCode->partner?->name }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Colonne centrale : Historique des scans --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Historique des scans</h3>
                    <span class="text-xs text-slate-500">{{ $order->qrCode?->validations?->count() ?? 0 }} scan(s)</span>
                </div>

                @if($order->qrCode && $order->qrCode->validations->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($order->qrCode->validations as $validation)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-slate-200">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                    @if($validation->status === 'valid')
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-slate-900">{{ $validation->partner?->name ?? 'Inconnu' }}</span>
                                        <span class="text-xs text-slate-500">{{ $validation->scanned_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $validation->status === 'valid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                            {{ ucfirst($validation->status) }}
                                        </span>
                                        <span class="text-xs text-slate-500">{{ $validation->ip_address }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm text-slate-500 mt-2">Aucun scan enregistré.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Retour à la liste
        </a>
    </div>
@endsection
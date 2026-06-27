@extends('layouts.admin')

@section('title', 'Commandes')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Commandes</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $orders->total() }} commande(s) au total</p>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.orders.index') }}" class="mb-6 bg-white rounded-xl border border-slate-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label for="status" class="block text-xs font-medium text-slate-600 mb-1">Statut</label>
                <select name="status" id="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach(\App\Enums\OrderStatus::cases() as $s)
                        <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ ucfirst($s->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="email" class="block text-xs font-medium text-slate-600 mb-1">Email client</label>
                <input type="text" name="email" id="email" value="{{ request('email') }}"
                       placeholder="marie@exemple.com"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="without_qr" value="1" {{ request('without_qr') ? 'checked' : '' }}
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Sans QR actif uniquement
                </label>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Filtrer
                </button>
                <a href="{{ route('admin.orders.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">
                    Réinitialiser
                </a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Commande</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">QR</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($orders as $order)
                        @php
                            $qr = $order->qrCode;
                            $statusColors = [
                                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                            ];
                            $qrStatusColors = [
                                'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'used' => 'bg-slate-50 text-slate-700 border-slate-200',
                                'expired' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'revoked' => 'bg-rose-50 text-rose-700 border-rose-200',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-sm font-medium text-blue-600 hover:underline">
                                    #{{ $order->shopify_order_id }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-900">{{ $order->customer_name }}</div>
                                <div class="text-xs text-slate-500">{{ $order->customer_email }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                {{ $order->formatted_amount }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $statusColors[$order->status->value] ?? 'bg-slate-50' }}">
                                    {{ ucfirst($order->status->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($qr)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $qrStatusColors[$qr->status->value] ?? 'bg-slate-50' }}">
                                        {{ ucfirst($qr->status->value) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Aucun</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500">
                                {{ $order->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-sm text-blue-600 hover:underline font-medium">
                                    Détail →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">
                                Aucune commande trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
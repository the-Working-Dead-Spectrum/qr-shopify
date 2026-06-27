@extends('layouts.admin')

@section('title', 'Historique des scans')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Historique des scans</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $validations->total() }} scan(s) au total</p>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.validations.index') }}" class="mb-6 bg-white rounded-xl border border-slate-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label for="partner_id" class="block text-xs font-medium text-slate-600 mb-1">Partenaire</label>
                <select name="partner_id" id="partner_id" 
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Partner::orderBy('name')->get() as $p)
                        <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="block text-xs font-medium text-slate-600 mb-1">Statut</label>
                <select name="status" id="status" 
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="valid" {{ request('status') === 'valid' ? 'selected' : '' }}>Validé</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                </select>
            </div>

            <div>
                <label for="date_from" class="block text-xs font-medium text-slate-600 mb-1">À partir du</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Filtrer
                </button>
                <a href="{{ route('admin.validations.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">QR Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Commande</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Partenaire</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($validations as $validation)
                        @php
                            $statusColors = [
                                'valid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-sm text-slate-900 whitespace-nowrap">
                                {{ $validation->scanned_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-mono text-slate-900 break-all">{{ $validation->qrCode?->uuid }}</div>
                                <div class="text-xs text-slate-500">ID: {{ $validation->qrCode?->id }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($validation->qrCode?->order)
                                    <a href="{{ route('admin.orders.show', $validation->qrCode->order) }}"
                                       class="text-sm text-blue-600 hover:underline font-medium">
                                        #{{ $validation->qrCode->order->shopify_order_id }}
                                    </a>
                                @else
                                    <span class="text-sm text-slate-400 italic">Inconnu</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-900">
                                {{ $validation->partner?->name ?? 'Inconnu' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $statusColors[$validation->status] ?? 'bg-slate-50' }}">
                                    {{ ucfirst($validation->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500">
                                {{ $validation->ip_address }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                Aucun scan trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $validations->links() }}
        </div>
    </div>
@endsection
@extends('layouts.admin')

@section('title', 'Partenaires')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Partenaires</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $partners->total() }} partenaire(s) au total</p>
        </div>
        <button type="button" 
                x-data x-on:click="
                    $dispatch('open-modal', 'create-partner');
                    $nextTick(() => $refs.name.focus())
                "
                class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Ajouter un partenaire
        </button>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.partners.index') }}" class="mb-6 bg-white rounded-xl border border-slate-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label for="status" class="block text-xs font-medium text-slate-600 mb-1">Statut</label>
                <select name="status" id="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach(\App\Enums\PartnerStatus::cases() as $s)
                        <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ ucfirst($s->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Filtrer
                </button>
                <a href="{{ route('admin.partners.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Nom</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Scans (7j)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Créé le</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($partners as $partner)
                        @php
                            $statusColors = [
                                'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'inactive' => 'bg-slate-50 text-slate-700 border-slate-200',
                                'suspended' => 'bg-rose-50 text-rose-700 border-rose-200',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-900">{{ $partner->name }}</div>
                                <div class="text-xs text-slate-500">ID: {{ $partner->id }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-900">{{ $partner->user?->email }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $statusColors[$partner->status->value] ?? 'bg-slate-50' }}">
                                    {{ ucfirst($partner->status->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-900">{{ $partner->validations_count_7d ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-slate-500">{{ $partner->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" 
                                            x-data x-on:click="
                                                $dispatch('open-modal', 'edit-partner-{{ $partner->id }}');
                                                $nextTick(() => $refs['status-{{ $partner->id }}'].focus())
                                            "
                                            class="text-sm text-blue-600 hover:underline font-medium">
                                        Modifier
                                    </button>
                                    <button type="button" 
                                            x-data x-on:click="
                                                $dispatch('open-modal', 'tokens-partner-{{ $partner->id }}')
                                            "
                                            class="text-sm text-slate-600 hover:text-slate-900">
                                        Tokens
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                Aucun partenaire trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $partners->links() }}
        </div>
    </div>

    {{-- Modals --}}
    @include('admin.partners._create_modal')
    @include('admin.partners._edit_modal')
    @include('admin.partners._tokens_modal')
@endsection
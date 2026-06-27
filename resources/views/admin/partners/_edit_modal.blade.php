@php
    use App\Enums\PartnerStatus;
@endphp

<x-modal name="edit-partner-{{ $partner->id }}" title="Modifier {{ $partner->name }}" maxWidth="md">
    <form method="POST" action="{{ route('admin.partners.update', $partner) }}" x-data="{ loading: false }" 
          x-on:submit.prevent="loading = true; $el.submit()">
        @csrf
        @method('PATCH')

        <div class="space-y-4">
            <div>
                <label for="status-{{ $partner->id }}" class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                <select name="status" id="status-{{ $partner->id }}" x-ref="status-{{ $partner->id }}" required 
                        class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                    @foreach(PartnerStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ $partner->status === $status ? 'selected' : '' }}>
                            {{ ucfirst($status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pt-2">
                <p class="text-xs text-slate-500">
                    <strong>Note :</strong> Le changement de statut est immédiat. Un partenaire suspendu ne pourra plus scanner de QR codes.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" x-on:click="$dispatch('close-modal')" 
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                Annuler
            </button>
            <button type="submit" 
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition" 
                    :disabled="loading">
                <span x-show="!loading">Mettre à jour</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Mise à jour...
                </span>
            </button>
        </div>
    </form>
</x-modal>
@php
    use App\Enums\PartnerStatus;
@endphp

<x-modal name="create-partner" title="Créer un partenaire" maxWidth="md">
    <form method="POST" action="{{ route('admin.partners.store') }}" x-data="{ loading: false }" 
          x-on:submit.prevent="loading = true; $el.submit()">
        @csrf

        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom complet</label>
                <input type="text" name="name" id="name" x-ref="name" required 
                       class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" id="email" required 
                       class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                <select name="status" id="status" required 
                        class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                    @foreach(PartnerStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4">
                <p class="text-xs text-slate-500">
                    <strong>Important :</strong> Un token Sanctum sera généré automatiquement. Il ne sera affiché qu'une seule fois après la création. Transmettez-le au partenaire par un canal sécurisé (email chiffré, SMS, etc.).
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
                <span x-show="!loading">Créer le partenaire</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Création...
                </span>
            </button>
        </div>
    </form>
</x-modal>
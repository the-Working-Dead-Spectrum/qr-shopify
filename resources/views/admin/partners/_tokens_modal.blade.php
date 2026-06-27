<x-modal name="tokens-partner-{{ $partner->id }}" title="Tokens de {{ $partner->name }}" maxWidth="md">
    <div class="space-y-4">
        <div class="bg-slate-50 rounded-lg p-4">
            <h4 class="font-semibold text-slate-800 mb-2">Tokens actifs</h4>
            @if($partner->user?->tokens->isNotEmpty())
                <div class="space-y-3">
                    @foreach($partner->user->tokens as $token)
                        <div class="flex items-center justify-between gap-3 p-3 bg-white rounded-lg border border-slate-200">
                            <div>
                                <div class="text-sm font-medium text-slate-900">{{ $token->name }}</div>
                                <div class="text-xs text-slate-500">Créé le {{ $token->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                            <form action="{{ route('api.partners.tokens.destroy', [$partner, $token->id]) }}" method="POST" 
                                  x-data x-on:submit.prevent="if(confirm('Révocation irréversible. Confirmer ?')) { $el.submit() }">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="text-sm text-rose-600 hover:text-rose-800 font-medium">
                                    Révoquer
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500 italic">Aucun token actif.</p>
            @endif
        </div>

        <div class="bg-slate-50 rounded-lg p-4">
            <h4 class="font-semibold text-slate-800 mb-2">Créer un nouveau token</h4>
            <form method="POST" action="{{ route('api.partners.tokens.store', $partner) }}" x-data="{ loading: false }" 
                  x-on:submit.prevent="loading = true; $el.submit()">
                @csrf

                <div class="space-y-3">
                    <div>
                        <label for="token-name-{{ $partner->id }}" class="block text-sm font-medium text-slate-700 mb-1">Nom du token</label>
                        <input type="text" name="name" id="token-name-{{ $partner->id }}" required 
                               placeholder="ex: PWA Mobile v2" 
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                    </div>

                    <div class="pt-2">
                        <p class="text-xs text-slate-500">
                            <strong>Important :</strong> Le token ne sera affiché qu'une seule fois après la création. Copiez-le immédiatement.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition" 
                            :disabled="loading">
                        <span x-show="!loading">Créer le token</span>
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
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="button" x-on:click="$dispatch('close-modal')" 
                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
            Fermer
        </button>
    </div>
</x-modal>
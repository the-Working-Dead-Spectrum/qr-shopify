@extends('layouts.admin_new')

@section('title', 'Paramètres')

@section('content')
    <div class="max-w-7xl mx-auto space-y-xl">
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-lg">Paramètres du compte</h2>
            
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-md">
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary mb-1">Nom</label>
                        <input type="text" id="name" name="name" 
                               value="{{ auth()->user()->name }}" 
                               class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-secondary mb-1">Email</label>
                        <input type="email" id="email" name="email" 
                               value="{{ auth()->user()->email }}" 
                               class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-secondary mb-1">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-secondary mb-1">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    
                    <div>
                        <label for="new_password_confirmation" class="block text-sm font-medium text-secondary mb-1">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" 
                               class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                </div>
                
                <div class="mt-lg flex gap-md">
                    <button type="submit" class="bg-primary text-white px-lg py-sm rounded-lg font-bold hover:bg-opacity-90 transition-all">
                        Enregistrer les modifications
                    </button>
                    
                    <a href="{{ route('admin.dashboard.new') }}" class="bg-surface-container text-secondary px-lg py-sm rounded-lg font-bold hover:bg-surface-container-high transition-all">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-lg">Paramètres de l'application</h2>
            
            <div class="space-y-md">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-on-surface">Notifications par email</h3>
                        <p class="text-secondary text-sm">Recevoir des notifications pour les nouvelles commandes et validations</p>
                    </div>
                    
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-primary peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-on-surface">Mode sombre</h3>
                        <p class="text-secondary text-sm">Activer le mode sombre pour l'interface</p>
                    </div>
                    
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-primary peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
@endsection
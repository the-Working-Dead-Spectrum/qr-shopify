@extends('layouts.admin_new')

@section('title', 'Support')

@section('content')
    <div class="max-w-7xl mx-auto space-y-xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
            <!-- Contact Form -->
            <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-lg">Contactez le support</h2>
                
                <form method="POST" action="{{ route('admin.support.send') }}">
                    @csrf
                    
                    <div class="space-y-md">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-secondary mb-1">Sujet</label>
                            <input type="text" id="subject" name="subject" 
                                   class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" 
                                   placeholder="Ex: Problème avec les validations QR">
                        </div>
                        
                        <div>
                            <label for="category" class="block text-sm font-medium text-secondary mb-1">Catégorie</label>
                            <select id="category" name="category" 
                                    class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                <option value="technical">Problème technique</option>
                                <option value="billing">Facturation</option>
                                <option value="feature">Demande de fonctionnalité</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="priority" class="block text-sm font-medium text-secondary mb-1">Priorité</label>
                            <select id="priority" name="priority" 
                                    class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                <option value="low">Basse</option>
                                <option value="medium" selected>Moyenne</option>
                                <option value="high">Haute</option>
                                <option value="critical">Critique</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="message" class="block text-sm font-medium text-secondary mb-1">Message</label>
                            <textarea id="message" name="message" rows="6" 
                                      class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" 
                                      placeholder="Décrivez votre problème ou votre question en détail..."></textarea>
                        </div>
                        
                        <div>
                            <label for="attachment" class="block text-sm font-medium text-secondary mb-1">Pièce jointe (optionnel)</label>
                            <input type="file" id="attachment" name="attachment" 
                                   class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                        </div>
                    </div>
                    
                    <div class="mt-lg flex gap-md">
                        <button type="submit" class="bg-primary text-white px-lg py-sm rounded-lg font-bold hover:bg-opacity-90 transition-all">
                            Envoyer la demande
                        </button>
                        
                        <button type="reset" class="bg-surface-container text-secondary px-lg py-sm rounded-lg font-bold hover:bg-surface-container-high transition-all">
                            Réinitialiser
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Support Info -->
            <div class="space-y-lg">
                <!-- FAQ -->
                <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
                    <h3 class="font-bold text-on-surface mb-md">Questions fréquentes</h3>
                    
                    <div class="space-y-sm">
                        <details class="border border-outline-variant rounded-lg p-sm">
                            <summary class="font-bold text-on-surface cursor-pointer">Comment configurer un nouveau partenaire ?</summary>
                            <p class="text-secondary text-sm mt-sm pl-md">Pour configurer un nouveau partenaire, allez dans la section "Partenaires" et cliquez sur "Ajouter un partenaire". Remplissez les informations nécessaires et générez un token d'API unique.</p>
                        </details>
                        
                        <details class="border border-outline-variant rounded-lg p-sm">
                            <summary class="font-bold text-on-surface cursor-pointer">Comment résoudre les problèmes de validation QR ?</summary>
                            <p class="text-secondary text-sm mt-sm pl-md">Vérifiez que le QR code n'a pas expiré et qu'il n'a pas déjà été utilisé. Assurez-vous également que le partenaire a une connexion internet stable.</p>
                        </details>
                        
                        <details class="border border-outline-variant rounded-lg p-sm">
                            <summary class="font-bold text-on-surface cursor-pointer">Comment exporter les données de ventes ?</summary>
                            <p class="text-secondary text-sm mt-sm pl-md">Vous pouvez exporter les données de ventes depuis la section "Rapports". Sélectionnez le type de rapport "Ventes" et choisissez le format souhaité (CSV, Excel ou PDF).</p>
                        </details>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="bg-primary text-white p-lg rounded-xl flex flex-col justify-between overflow-hidden relative">
                    <div class="relative z-10">
                        <h3 class="font-bold text-on-primary-container mb-xs">Besoin d'aide immédiate ?</h3>
                        <p class="text-on-primary-container opacity-90 text-sm">Notre équipe support est disponible 24/7</p>
                        
                        <div class="mt-lg space-y-md">
                            <div class="flex items-center gap-sm">
                                <span class="material-symbols-outlined">mail</span>
                                <span>support@qrvalidator.pro</span>
                            </div>
                            
                            <div class="flex items-center gap-sm">
                                <span class="material-symbols-outlined">phone</span>
                                <span>+33 1 23 45 67 89</span>
                            </div>
                            
                            <div class="flex items-center gap-sm">
                                <span class="material-symbols-outlined">schedule</span>
                                <span>Lun-Ven: 9h-18h</span>
                            </div>
                        </div>
                    </div>
                    
                    <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-8xl opacity-10">support_agent</span>
                </div>
            </div>
        </div>
        
        <!-- Documentation -->
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-lg">Centre de documentation</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
                <div class="bg-surface-container p-md rounded-xl border border-outline-variant">
                    <h4 class="font-bold text-on-surface mb-sm">Guide de démarrage</h4>
                    <p class="text-secondary text-sm mb-md">Apprenez à configurer et utiliser QR Validator Pro</p>
                    <a href="#" class="text-primary font-bold text-sm hover:underline">Lire le guide →</a>
                </div>
                
                <div class="bg-surface-container p-md rounded-xl border border-outline-variant">
                    <h4 class="font-bold text-on-surface mb-sm">API Documentation</h4>
                    <p class="text-secondary text-sm mb-md">Documentation complète de notre API pour les développeurs</p>
                    <a href="#" class="text-primary font-bold text-sm hover:underline">Voir la documentation →</a>
                </div>
                
                <div class="bg-surface-container p-md rounded-xl border border-outline-variant">
                    <h4 class="font-bold text-on-surface mb-sm">Vidéos tutoriels</h4>
                    <p class="text-secondary text-sm mb-md">Tutoriels vidéo pour maîtriser toutes les fonctionnalités</p>
                    <a href="#" class="text-primary font-bold text-sm hover:underline">Regarder les vidéos →</a>
                </div>
            </div>
        </div>
    </div>
@endsection
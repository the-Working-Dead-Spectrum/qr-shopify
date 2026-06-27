

<?php $__env->startSection('title', 'Rapports'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-7xl mx-auto space-y-xl">
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-lg">Rapports et Analytics</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
                <!-- Rapport Ventes -->
                <div class="bg-surface-container p-md rounded-xl border border-outline-variant">
                    <h3 class="font-bold text-on-surface mb-sm">Rapport des ventes</h3>
                    <p class="text-secondary text-sm mb-md">Analyse des ventes par période et par partenaire</p>
                    
                    <div class="flex gap-md">
                        <a href="<?php echo e(route('admin.reports.export', ['type' => 'sales'])); ?>" 
                           class="inline-flex items-center gap-xs bg-primary text-white px-md py-sm rounded-lg font-bold hover:bg-opacity-90 transition-all">
                            <span class="material-symbols-outlined">download</span>
                            Exporter
                        </a>
                        
                        <a href="#" 
                           class="inline-flex items-center gap-xs bg-surface-container text-secondary px-md py-sm rounded-lg font-bold hover:bg-surface-container-high transition-all">
                            <span class="material-symbols-outlined">visibility</span>
                            Voir
                        </a>
                    </div>
                </div>
                
                <!-- Rapport Validations -->
                <div class="bg-surface-container p-md rounded-xl border border-outline-variant">
                    <h3 class="font-bold text-on-surface mb-sm">Rapport des validations</h3>
                    <p class="text-secondary text-sm mb-md">Historique des scans et taux de validation</p>
                    
                    <div class="flex gap-md">
                        <a href="<?php echo e(route('admin.reports.export', ['type' => 'validations'])); ?>" 
                           class="inline-flex items-center gap-xs bg-primary text-white px-md py-sm rounded-lg font-bold hover:bg-opacity-90 transition-all">
                            <span class="material-symbols-outlined">download</span>
                            Exporter
                        </a>
                        
                        <a href="#" 
                           class="inline-flex items-center gap-xs bg-surface-container text-secondary px-md py-sm rounded-lg font-bold hover:bg-surface-container-high transition-all">
                            <span class="material-symbols-outlined">visibility</span>
                            Voir
                        </a>
                    </div>
                </div>
                
                <!-- Rapport Partenaires -->
                <div class="bg-surface-container p-md rounded-xl border border-outline-variant">
                    <h3 class="font-bold text-on-surface mb-sm">Rapport des partenaires</h3>
                    <p class="text-secondary text-sm mb-md">Activité et performance des partenaires</p>
                    
                    <div class="flex gap-md">
                        <a href="<?php echo e(route('admin.reports.export', ['type' => 'partners'])); ?>" 
                           class="inline-flex items-center gap-xs bg-primary text-white px-md py-sm rounded-lg font-bold hover:bg-opacity-90 transition-all">
                            <span class="material-symbols-outlined">download</span>
                            Exporter
                        </a>
                        
                        <a href="#" 
                           class="inline-flex items-center gap-xs bg-surface-container text-secondary px-md py-sm rounded-lg font-bold hover:bg-surface-container-high transition-all">
                            <span class="material-symbols-outlined">visibility</span>
                            Voir
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-lg">Générateur de rapports personnalisés</h2>
            
            <form method="POST" action="<?php echo e(route('admin.reports.custom')); ?>">
                <?php echo csrf_field(); ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-lg">
                    <div>
                        <label for="report_type" class="block text-sm font-medium text-secondary mb-1">Type de rapport</label>
                        <select id="report_type" name="report_type" 
                                class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                            <option value="sales">Ventes</option>
                            <option value="validations">Validations</option>
                            <option value="partners">Partenaires</option>
                            <option value="custom">Personnalisé</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_range" class="block text-sm font-medium text-secondary mb-1">Période</label>
                        <select id="date_range" name="date_range" 
                                class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                            <option value="7days">7 derniers jours</option>
                            <option value="30days">30 derniers jours</option>
                            <option value="90days">90 derniers jours</option>
                            <option value="custom">Personnalisé</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="format" class="block text-sm font-medium text-secondary mb-1">Format</label>
                        <select id="format" name="format" 
                                class="w-full px-md py-sm rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-md">
                    <button type="submit" class="bg-primary text-white px-lg py-sm rounded-lg font-bold hover:bg-opacity-90 transition-all">
                        Générer le rapport
                    </button>
                    
                    <button type="reset" class="bg-surface-container text-secondary px-lg py-sm rounded-lg font-bold hover:bg-surface-container-high transition-all">
                        Réinitialiser
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-lg">Historique des rapports</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-secondary bg-surface-container">
                        <tr>
                            <th class="px-md py-sm">Date</th>
                            <th class="px-md py-sm">Type</th>
                            <th class="px-md py-sm">Période</th>
                            <th class="px-md py-sm">Format</th>
                            <th class="px-md py-sm">Actions</th>
                        </tr>
                    </thead>
                    
                    <tbody class="divide-y divide-outline-variant">
                        <tr class="hover:bg-surface-container transition-colors">
                            <td class="px-md py-sm text-on-surface">27/06/2026 14:30</td>
                            <td class="px-md py-sm text-on-surface">Ventes</td>
                            <td class="px-md py-sm text-on-surface">30 derniers jours</td>
                            <td class="px-md py-sm text-on-surface">CSV</td>
                            <td class="px-md py-sm">
                                <a href="#" class="text-primary hover:underline">Télécharger</a>
                            </td>
                        </tr>
                        
                        <tr class="hover:bg-surface-container transition-colors">
                            <td class="px-md py-sm text-on-surface">26/06/2026 09:15</td>
                            <td class="px-md py-sm text-on-surface">Validations</td>
                            <td class="px-md py-sm text-on-surface">7 derniers jours</td>
                            <td class="px-md py-sm text-on-surface">Excel</td>
                            <td class="px-md py-sm">
                                <a href="#" class="text-primary hover:underline">Télécharger</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin_new', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/admin/reports.blade.php ENDPATH**/ ?>
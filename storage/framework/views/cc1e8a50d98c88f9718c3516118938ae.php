

<?php $__env->startSection('title', 'Gestion des Partenaires'); ?>

<?php $__env->startSection('content'); ?>
<div class="p-lg space-y-lg">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-background">Gestion des Partenaires</h2>
            <p class="font-body-md text-body-md text-secondary">Gérez vos relations commerciales et surveillez l'activité des scans QR Shopify.</p>
        </div>
        <button onclick="openCreatePartnerModal()" class="flex items-center gap-xs bg-primary text-on-primary px-lg py-sm rounded-lg font-button-text text-button-text hover:bg-opacity-90 active:scale-95 transition-all shadow-sm">
            <span class="material-symbols-outlined text-[18px]" data-icon="add">add</span>
            Ajouter un partenaire
        </button>
    </div>
    
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-lg">
        <div class="col-span-1 md:col-span-1 bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-sm">
                <span class="font-label-md text-label-md text-secondary uppercase">Total Partenaires</span>
                <span class="material-symbols-outlined text-primary" data-icon="group">group</span>
            </div>
            <div class="font-headline-lg text-headline-lg"><?php echo e($partners->total()); ?></div>
            <div class="mt-xs flex items-center gap-1 text-[12px] font-bold text-primary">
                <span class="material-symbols-outlined text-[14px]" data-icon="trending_up">trending_up</span>
                <span>+<?php echo e($partners->count()); ?>% vs mois dernier</span>
            </div>
        </div>
        
        <div class="col-span-1 md:col-span-1 bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-sm">
                <span class="font-label-md text-label-md text-secondary uppercase">Scans QR (24h)</span>
                <span class="material-symbols-outlined text-tertiary" data-icon="qr_code_2">qr_code_2</span>
            </div>
            <div class="font-headline-lg text-headline-lg"><?php echo e($partners->sum('validations_count_7d')); ?></div>
            <div class="mt-xs flex items-center gap-1 text-[12px] font-bold text-primary">
                <span class="material-symbols-outlined text-[14px]" data-icon="bolt">bolt</span>
                <span>Traitement en temps réel</span>
            </div>
        </div>
        
        <div class="col-span-1 md:col-span-2 bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-sm">
                    <span class="font-label-md text-label-md text-secondary uppercase">Activité de la Plateforme</span>
                    <span class="material-symbols-outlined text-secondary" data-icon="analytics">analytics</span>
                </div>
                <div class="flex items-end gap-2 h-12">
                    <?php $__currentLoopData = [100, 75, 90, 50, 80, 100, 70]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $height): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="w-4 bg-primary-fixed rounded-t h-[<?php echo e($height); ?>%] group-hover:bg-primary transition-colors"></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <p class="mt-xs text-[12px] text-secondary">Pic d'activité détecté à <?php echo e(now()->format('H')); ?>h00 CET</p>
            </div>
            <div class="absolute right-[-20px] top-[-20px] opacity-10">
                <span class="material-symbols-outlined text-[120px]" data-icon="insights">insights</span>
            </div>
        </div>
    </div>
    
    <!-- Table Filters & Search -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant flex flex-col md:flex-row justify-between items-center gap-md bg-white">
            <div class="flex items-center gap-sm w-full md:w-auto">
                <div class="flex bg-surface-container border border-outline-variant rounded-lg p-1">
                    <button class="px-md py-1.5 rounded-md bg-white shadow-sm font-button-text text-[13px] text-on-surface" onclick="filterPartners('all')">Tous</button>
                    <button class="px-md py-1.5 rounded-md font-button-text text-[13px] text-secondary hover:text-on-surface transition-colors" onclick="filterPartners('active')">Actifs</button>
                    <button class="px-md py-1.5 rounded-md font-button-text text-[13px] text-secondary hover:text-on-surface transition-colors" onclick="filterPartners('inactive')">En attente</button>
                </div>
                <form method="GET" action="<?php echo e(route('admin.partners.index')); ?>" class="flex items-center gap-sm">
                    <input type="hidden" name="status" id="status-filter-input" value="<?php echo e(request('status')); ?>">
                    <button type="submit" class="flex items-center gap-xs border border-outline-variant px-md py-2 rounded-lg font-button-text text-[13px] text-secondary hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-[18px]" data-icon="filter_list">filter_list</span>
                        Filtres
                    </button>
                </form>
            </div>
            <div class="text-label-md text-secondary">Affichage de <?php echo e($partners->firstItem()); ?> à <?php echo e($partners->lastItem()); ?> sur <?php echo e($partners->total()); ?> partenaires</div>
        </div>
        
        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-lg py-md font-label-md text-label-md text-secondary uppercase tracking-wider">Nom du Partenaire</th>
                        <th class="px-lg py-md font-label-md text-label-md text-secondary uppercase tracking-wider">Boutique Shopify</th>
                        <th class="px-lg py-md font-label-md text-label-md text-secondary uppercase tracking-wider">Nombre de scans</th>
                        <th class="px-lg py-md font-label-md text-label-md text-secondary uppercase tracking-wider">Statut</th>
                        <th class="px-lg py-md font-label-md text-label-md text-secondary uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    <?php $__empty_1 = true; $__currentLoopData = $partners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $partner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-surface-container-low/50 transition-colors partner-row" 
                            data-status="<?php echo e($partner->status->value); ?>">
                            <td class="px-lg py-lg">
                                <div class="flex items-center gap-md">
                                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold">
                                        <?php echo e(strtoupper(substr($partner->name, 0, 2))); ?>

                                    </div>
                                    <div>
                                        <a href="<?php echo e(route('admin.partners.show', $partner)); ?>" class="font-body-md text-body-md font-bold text-on-surface hover:text-primary transition-colors"><?php echo e($partner->name); ?></a>
                                        <div class="text-[12px] text-secondary">Inscrit le <?php echo e($partner->created_at->format('d/m/Y')); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-lg py-lg">
                                <div class="flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-tertiary text-[18px]" data-icon="store">store</span>
                                    <span class="font-body-md text-body-md text-on-surface-variant"><?php echo e($partner->user?->email ?? 'N/A'); ?></span>
                                </div>
                            </td>
                            <td class="px-lg py-lg">
                                <div class="font-headline-md text-[18px] text-on-surface"><?php echo e($partner->validations_count_7d ?? 0); ?></div>
                            </td>
                            <td class="px-lg py-lg">
                                <?php
                                    $statusClasses = 'px-sm py-1 rounded-full text-[12px] font-bold ';
                                    if($partner->status->value === 'active') {
                                        $statusClasses .= 'partner-status-active';
                                        $statusText = 'Validé';
                                    } elseif($partner->status->value === 'inactive') {
                                        $statusClasses .= 'partner-status-pending';
                                        $statusText = 'En attente';
                                    } else {
                                        $statusClasses .= 'bg-error/10 text-error';
                                        $statusText = 'Suspendu';
                                    }
                                ?>
                                <span class="<?php echo e($statusClasses); ?>"><?php echo e($statusText); ?></span>
                            </td>
                            <td class="px-lg py-lg text-right">
                                <div class="flex items-center justify-end gap-sm">
                                    <button class="p-2 text-secondary hover:text-primary hover:bg-primary-container/20 rounded-lg transition-all" title="Modifier" 
                                            x-data x-on:click="$dispatch('open-modal', 'edit-partner-<?php echo e($partner->id); ?>')">
                                        <span class="material-symbols-outlined" data-icon="edit">edit</span>
                                    </button>
                                    <button class="p-2 text-secondary hover:text-tertiary hover:bg-tertiary-container/20 rounded-lg transition-all" title="Voir les tokens" 
                                            x-data x-on:click="$dispatch('open-modal', 'tokens-partner-<?php echo e($partner->id); ?>')">
                                        <span class="material-symbols-outlined" data-icon="history">history</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-lg py-lg text-center text-secondary">
                                Aucun partenaire trouvé
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-lg py-md bg-surface-container-low border-t border-outline-variant flex items-center justify-between">
            <?php if($partners->onFirstPage()): ?>
                <button class="flex items-center gap-xs px-md py-2 border border-outline-variant rounded-lg font-button-text text-[13px] text-secondary opacity-50 cursor-not-allowed">
                    <span class="material-symbols-outlined text-[18px]" data-icon="chevron_left">chevron_left</span>
                    Précédent
                </button>
            <?php else: ?>
                <a href="<?php echo e($partners->previousPageUrl()); ?>" class="flex items-center gap-xs px-md py-2 border border-outline-variant rounded-lg font-button-text text-[13px] text-secondary hover:bg-surface-container transition-all active:scale-95">
                    <span class="material-symbols-outlined text-[18px]" data-icon="chevron_left">chevron_left</span>
                    Précédent
                </a>
            <?php endif; ?>
            
            <div class="flex items-center gap-xs">
                <?php for($i = 1; $i <= $partners->lastPage(); $i++): ?>
                    <?php if($i === $partners->currentPage()): ?>
                        <button class="w-8 h-8 rounded-lg bg-primary text-on-primary font-bold text-[13px]"><?php echo e($i); ?></button>
                    <?php elseif($i === 1 || $i === $partners->lastPage() || ($i >= $partners->currentPage() - 1 && $i <= $partners->currentPage() + 1)): ?>
                        <a href="<?php echo e($partners->url($i)); ?>" class="w-8 h-8 rounded-lg hover:bg-surface-container transition-colors text-secondary text-[13px] flex items-center justify-center"><?php echo e($i); ?></a>
                    <?php elseif($i === $partners->currentPage() - 2 || $i === $partners->currentPage() + 2): ?>
                        <span class="text-secondary">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            
            <?php if($partners->hasMorePages()): ?>
                <a href="<?php echo e($partners->nextPageUrl()); ?>" class="flex items-center gap-xs px-md py-2 border border-outline-variant rounded-lg font-button-text text-[13px] text-secondary hover:bg-surface-container transition-all active:scale-95">
                    Suivant
                    <span class="material-symbols-outlined text-[18px]" data-icon="chevron_right">chevron_right</span>
                </a>
            <?php else: ?>
                <button class="flex items-center gap-xs px-md py-2 border border-outline-variant rounded-lg font-button-text text-[13px] text-secondary opacity-50 cursor-not-allowed">
                    Suivant
                    <span class="material-symbols-outlined text-[18px]" data-icon="chevron_right">chevron_right</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .partner-status-active { 
        background-color: rgba(0, 128, 96, 0.1); 
        color: #008060; 
    }
    .partner-status-pending { 
        background-color: rgba(245, 158, 11, 0.1); 
        color: #b45309; 
    }
    
    /* Micro-interactions */
    button, a {
        transition: transform 0.1s ease;
    }
    
    button:active, a:active {
        transform: scale(0.98);
    }
    
    /* Row highlight */
    tbody tr:hover {
        cursor: pointer;
    }
    
    tbody tr.selected {
        background-color: rgba(0, 128, 96, 0.05);
    }
</style>

<script>
    // Micro-interactions for buttons
    document.querySelectorAll('button, a').forEach(el => {
        el.addEventListener('mousedown', () => {
            el.style.transform = 'scale(0.98)';
        });
        el.addEventListener('mouseup', () => {
            el.style.transform = 'scale(1)';
        });
        el.addEventListener('mouseleave', () => {
            el.style.transform = 'scale(1)';
        });
    });
    
    // Row highlight on click
    const tableRows = document.querySelectorAll('tbody tr.partner-row');
    tableRows.forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            tableRows.forEach(r => r.classList.remove('selected'));
            row.classList.add('selected');
        });
    });
    
    // Filter function
    function filterPartners(status) {
        document.getElementById('status-filter-input').value = status === 'all' ? '' : status;
        document.querySelector('form[action*="partners"]').submit();
    }
    
    // Open create partner modal
    function openCreatePartnerModal() {
        @this.dispatch('open-modal', 'create-partner');
    }
</script>

<!-- Include modals -->
<?php echo $__env->make('admin.partners._create_modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('admin.partners._edit_modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('admin.partners._tokens_modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin_new', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/admin/partners/index_new.blade.php ENDPATH**/ ?>


<?php $__env->startSection('title', 'Gestion des Commandes'); ?>

<?php $__env->startSection('content'); ?>
<section class="p-lg flex-1">
    <div class="max-w-7xl mx-auto flex flex-col gap-lg">
        <!-- Header Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface">Gestion des Commandes</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Suivi en temps réel des validations de codes QR et transactions B2B.</p>
            </div>
            <div class="flex items-center gap-xs">
                <a href="<?php echo e(route('admin.reports.export')); ?>" class="flex items-center gap-xs px-md py-xs bg-white border border-outline-variant rounded-lg text-button-text font-button-text text-secondary hover:bg-surface-variant transition-colors">
                    <span class="material-symbols-outlined text-[20px]">download</span>
                    Exporter CSV
                </a>
                <button class="flex items-center gap-xs px-md py-xs bg-primary text-on-primary rounded-lg text-button-text font-button-text hover:brightness-90 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Nouvelle Commande
                </button>
            </div>
        </div>
        
        <!-- Filter Bar (Bento style integration) -->
        <form method="GET" action="<?php echo e(route('admin.orders.index')); ?>" class="grid grid-cols-1 md:grid-cols-4 gap-md p-md bg-white border border-outline-variant rounded-xl shadow-sm">
            <div class="flex flex-col gap-xs">
                <label class="text-label-md text-on-surface-variant uppercase tracking-wider">Statut du Scan</label>
                <select name="status" class="p-xs bg-surface-container-lowest border border-outline-variant rounded-lg text-body-md focus:ring-primary focus:border-primary">
                    <option value="">Tous les statuts</option>
                    <?php $__currentLoopData = \App\Enums\OrderStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($status->value); ?>" <?php echo e(request('status') === $status->value ? 'selected' : ''); ?>>
                            <?php echo e(ucfirst($status->value)); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            
            <div class="flex flex-col gap-xs">
                <label class="text-label-md text-on-surface-variant uppercase tracking-wider">Période</label>
                <input type="date" name="date" class="p-xs bg-surface-container-lowest border border-outline-variant rounded-lg text-body-md focus:ring-primary" value="<?php echo e(request('date')); ?>">
            </div>
            
            <div class="flex flex-col gap-xs">
                <label class="text-label-md text-on-surface-variant uppercase tracking-wider">Partenaire</label>
                <select name="partner" class="p-xs bg-surface-container-lowest border border-outline-variant rounded-lg text-body-md focus:ring-primary">
                    <option value="">Tous les partenaires</option>
                    <?php $__currentLoopData = $partners ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $partner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($partner->id); ?>" <?php echo e(request('partner') == $partner->id ? 'selected' : ''); ?>>
                            <?php echo e($partner->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full h-[38px] flex items-center justify-center gap-xs px-md bg-surface-container-highest text-on-surface rounded-lg text-button-text font-button-text hover:bg-outline-variant transition-colors">
                    <span class="material-symbols-outlined text-[20px]">filter_list</span>
                    Appliquer les filtres
                </button>
            </div>
        </form>
        
        <!-- Table Section -->
        <div class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant">
                        <tr>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Order ID</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Client</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Produit</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Date</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase text-center">Status Scan</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase">Localisation</th>
                            <th class="px-lg py-md text-label-md font-bold text-on-surface-variant uppercase text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $qr = $order->qrCode;
                                $statusColor = 'bg-[#008060]/10 text-[#008060]';
                                $statusText = 'Validé';
                                $statusIcon = 'check_circle';
                                
                                switch($order->status->value) {
                                    case 'pending':
                                        $statusColor = 'bg-amber-100 text-amber-800';
                                        $statusText = 'En attente';
                                        $statusIcon = 'pending';
                                        break;
                                    case 'cancelled':
                                        $statusColor = 'bg-red-100 text-red-800';
                                        $statusText = 'Expiré';
                                        $statusIcon = 'cancel';
                                        break;
                                    case 'paid':
                                    default:
                                        $statusColor = 'bg-[#008060]/10 text-[#008060]';
                                        $statusText = 'Validé';
                                        $statusIcon = 'check_circle';
                                }
                            ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors group">
                                <td class="px-lg py-md font-mono text-body-md font-bold text-primary">
                                    <a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="hover:underline">
                                        #<?php echo e($order->shopify_order_id); ?>

                                    </a>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex flex-col">
                                        <span class="text-body-md font-semibold"><?php echo e($order->customer_name); ?></span>
                                        <span class="text-[12px] text-on-surface-variant"><?php echo e($order->customer_email); ?></span>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-body-md"><?php echo e($order->product_name ?? 'Produit non spécifié'); ?></td>
                                <td class="px-lg py-md text-body-md text-secondary"><?php echo e($order->created_at->format('d M Y')); ?></td>
                                <td class="px-lg py-md text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo e($statusColor); ?>">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" style="background-color: currentColor;"></span>
                                        <?php echo e($statusText); ?>

                                    </span>
                                </td>
                                <td class="px-lg py-md text-body-md text-secondary">
                                    <?php echo e($order->location ?? 'Non spécifiée'); ?>

                                </td>
                                <td class="px-lg py-md text-right">
                                    <div class="flex items-center justify-end gap-sm">
                                        <a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="p-1 hover:bg-surface-container rounded-lg text-secondary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </a>
                                        <?php if($qr): ?>
                                            <button class="p-1 hover:bg-surface-container rounded-lg text-secondary transition-colors" title="Renvoyer QR">
                                                <span class="material-symbols-outlined">send</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="px-lg py-md text-center text-secondary">
                                    Aucune commande trouvée
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="px-lg py-md border-t border-outline-variant flex items-center justify-between bg-surface-container-lowest">
                <span class="text-body-md text-on-surface-variant">
                    Affichage de <?php echo e($orders->firstItem()); ?> à <?php echo e($orders->lastItem()); ?> sur <?php echo e($orders->total()); ?> commandes
                </span>
                <div class="flex items-center gap-xs">
                    <?php if($orders->onFirstPage()): ?>
                        <button class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary opacity-30 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo e($orders->previousPageUrl()); ?>" class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= min($orders->lastPage(), 3); $i++): ?>
                        <?php if($i === $orders->currentPage()): ?>
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-on-primary text-body-md font-bold"><?php echo e($i); ?></button>
                        <?php else: ?>
                            <a href="<?php echo e($orders->url($i)); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-surface-container-high text-on-surface text-body-md transition-colors"><?php echo e($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($orders->lastPage() > 3): ?>
                        <span class="text-secondary">...</span>
                        <a href="<?php echo e($orders->url($orders->lastPage())); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-surface-container-high text-on-surface text-body-md transition-colors"><?php echo e($orders->lastPage()); ?></a>
                    <?php endif; ?>
                    
                    <?php if($orders->hasMorePages()): ?>
                        <a href="<?php echo e($orders->nextPageUrl()); ?>" class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <button class="p-1 rounded hover:bg-surface-container-high transition-colors text-secondary opacity-30 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards (Bento style) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-lg">
            <div class="p-lg bg-white border border-outline-variant rounded-xl shadow-sm">
                <span class="text-label-md text-secondary uppercase tracking-wider">Taux de Validation</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold text-on-surface">
                        <?php echo e($orders->count() > 0 ? min(100, round(($orders->where('status', 'paid')->count() / $orders->count()) * 100)) : 0); ?>%
                    </span>
                    <span class="text-label-md text-[#008060] font-bold">
                        +<?php echo e($orders->where('status', 'paid')->count()); ?>%
                    </span>
                </div>
            </div>
            
            <div class="p-lg bg-white border border-outline-variant rounded-xl shadow-sm">
                <span class="text-label-md text-secondary uppercase tracking-wider">Commandes du jour</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold text-on-surface"><?php echo e($orders->where('created_at', '>=', now()->startOfDay())->count()); ?></span>
                    <span class="text-label-md <?php echo e($orders->where('created_at', '>=', now()->startOfDay())->count() < $orders->where('created_at', '>=', now()->subDay()->startOfDay())->where('created_at', '<', now()->startOfDay())->count() ? 'text-red-600' : 'text-[#008060]'); ?> font-bold">
                        <?php echo e($orders->where('created_at', '>=', now()->startOfDay())->count() - $orders->where('created_at', '>=', now()->subDay()->startOfDay())->where('created_at', '<', now()->startOfDay())->count() > 0 ? '+' : ''); ?>

                        <?php echo e($orders->where('created_at', '>=', now()->startOfDay())->count() - $orders->where('created_at', '>=', now()->subDay()->startOfDay())->where('created_at', '<', now()->startOfDay())->count()); ?>

                    </span>
                </div>
            </div>
            
            <div class="p-lg bg-white border border-outline-variant rounded-xl shadow-sm">
                <span class="text-label-md text-secondary uppercase tracking-wider">Scans en attente</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold text-on-surface"><?php echo e($orders->where('status', 'pending')->count()); ?></span>
                    <span class="text-label-md text-secondary">stagnant</span>
                </div>
            </div>
            
            <div class="p-lg bg-primary text-on-primary border border-primary-container rounded-xl shadow-md">
                <span class="text-label-md text-on-primary-container uppercase tracking-wider">Chiffre d'affaires (24h)</span>
                <div class="flex items-baseline gap-xs mt-xs">
                    <span class="text-headline-md font-bold">
                        <?php echo e(number_format($orders->where('created_at', '>=', now()->subDay())->sum('amount') / 100, 2)); ?>€
                    </span>
                    <span class="material-symbols-outlined text-on-primary-container">trending_up</span>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Micro-interactions for table rows */
    tbody tr {
        transition: opacity 0.1s ease;
    }
    
    tbody tr:hover {
        cursor: pointer;
    }
    
    /* Pagination styles */
    .pagination-container {
        display: flex;
        gap: 4px;
    }
    
    .pagination-container span {
        padding: 8px 12px;
        border-radius: 8px;
    }
    
    .pagination-container a {
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .pagination-container a:hover {
        background-color: #f2f4f6;
    }
</style>

<script>
    // Simple Micro-interactions for table rows
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a') || e.target.closest('button')) return;
            row.classList.add('opacity-70');
            setTimeout(() => {
                row.classList.remove('opacity-70');
            }, 100);
        });
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin_new', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/admin/orders/index_new.blade.php ENDPATH**/ ?>
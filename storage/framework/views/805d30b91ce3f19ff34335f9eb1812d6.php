<div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-shadow">
    <div class="flex justify-between items-start mb-sm">
        <p class="font-label-md text-label-md text-secondary uppercase tracking-wider"><?php echo e($title); ?></p>
        <span class="material-symbols-outlined text-primary" data-icon="<?php echo e($icon); ?>"><?php echo e($icon); ?></span>
    </div>
    
    <h2 class="font-headline-lg text-headline-lg text-on-surface" <?php if(isset($dataKpi)): ?> data-kpi="<?php echo e($dataKpi); ?>" <?php endif; ?>><?php echo e($value); ?></h2>
    
    <div class="mt-md flex items-center gap-xs text-<?php echo e($trendColor); ?> font-bold text-xs">
        <span class="material-symbols-outlined text-sm" data-icon="<?php echo e($trendIcon); ?>"><?php echo e($trendIcon); ?></span>
        <span><?php echo e($trendText); ?></span>
    </div>
</div><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/components/kpi-card.blade.php ENDPATH**/ ?>
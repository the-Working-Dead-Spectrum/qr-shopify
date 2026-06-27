<div class="p-lg border-b border-outline-variant">
    <h3 class="font-headline-md text-headline-md text-on-surface">Logs en temps réel</h3>
    <p class="text-secondary text-body-md">Dernières activités de scan</p>
</div>

<div class="flex-1 overflow-y-auto p-md space-y-md" id="activity-feed">
    <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex gap-md p-sm hover:bg-surface-container transition-colors rounded-lg">
            <div class="w-10 h-10 rounded-full bg-<?php echo e($activity['color']); ?>/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-<?php echo e($activity['color']); ?>" data-icon="<?php echo e($activity['icon']); ?>"><?php echo e($activity['icon']); ?></span>
            </div>
            
            <div class="min-w-0">
                <p class="text-on-surface font-bold text-sm truncate"><?php echo e($activity['title']); ?></p>
                <p class="text-secondary text-xs truncate"><?php echo e($activity['description']); ?></p>
                <p class="text-outline text-[10px] mt-1"><?php echo e($activity['time']); ?></p>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="p-md border-t border-outline-variant text-center">
    <button class="text-primary font-bold text-xs hover:underline">Voir tous les logs</button>
</div>

<script>
    // Simulate a live scan every 8 seconds
    setInterval(() => {
        const feed = document.getElementById('activity-feed');
        const newLog = document.createElement('div');
        newLog.className = 'flex gap-md p-sm bg-primary-container/10 border border-primary/20 transition-all rounded-lg animate-pulse';
        newLog.innerHTML = `
            <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary">bolt</span>
            </div>
            <div class="min-w-0">
                <p class="text-on-surface font-bold text-sm truncate">Scan Validé #QR-LIVE</p>
                <p class="text-secondary text-xs truncate">Nouveau scan à l'instant</p>
                <p class="text-primary text-[10px] mt-1 font-bold">À L'INSTANT</p>
            </div>
        `;
        feed.insertBefore(newLog, feed.firstChild);
        
        // Remove pulse and border after 3 seconds
        setTimeout(() => {
            newLog.classList.remove('animate-pulse', 'bg-primary-container/10', 'border', 'border-primary/20');
            newLog.classList.add('hover:bg-surface-container');
        }, 3000);
        
        // Keep list length manageable
        if (feed.children.length > 8) {
            feed.removeChild(feed.lastChild);
        }
    }, 8000);
</script><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/components/activity-feed.blade.php ENDPATH**/ ?>
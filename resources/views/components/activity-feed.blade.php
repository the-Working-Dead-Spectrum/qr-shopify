<div class="p-lg border-b border-outline-variant">
    <h3 class="font-headline-md text-headline-md text-on-surface">Logs en temps réel</h3>
    <p class="text-secondary text-body-md">Dernières activités de scan</p>
</div>

<div class="flex-1 overflow-y-auto p-md space-y-md" id="activity-feed">
    @foreach($activities as $activity)
        <div class="flex gap-md p-sm hover:bg-surface-container transition-colors rounded-lg">
            <div class="w-10 h-10 rounded-full bg-{{ $activity['color'] }}/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-{{ $activity['color'] }}" data-icon="{{ $activity['icon'] }}">{{ $activity['icon'] }}</span>
            </div>
            
            <div class="min-w-0">
                <p class="text-on-surface font-bold text-sm truncate">{{ $activity['title'] }}</p>
                <p class="text-secondary text-xs truncate">{{ $activity['description'] }}</p>
                <p class="text-outline text-[10px] mt-1">{{ $activity['time'] }}</p>
            </div>
        </div>
    @endforeach
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
</script>
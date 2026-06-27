<div class="flex justify-between items-center mb-lg">
    <div>
        <h3 class="font-headline-md text-headline-md text-on-surface">Évolution des scans</h3>
        <p class="text-secondary text-body-md">Comparaison entre les scans et les commandes réelles</p>
    </div>
    
    <div class="flex bg-surface-container rounded-lg p-1">
        <button class="px-sm py-1 bg-white rounded-md shadow-sm text-xs font-bold text-primary">7 jours</button>
        <button class="px-sm py-1 text-xs font-bold text-secondary">30 jours</button>
    </div>
</div>

<div class="flex-1 relative">
    <div class="absolute inset-0 flex flex-col justify-between">
        <div class="border-b border-outline-variant h-0 w-full"></div>
        <div class="border-b border-outline-variant h-0 w-full"></div>
        <div class="border-b border-outline-variant h-0 w-full"></div>
        <div class="border-b border-outline-variant h-0 w-full"></div>
        <div class="border-b border-outline-variant h-0 w-full"></div>
    </div>
    
    <div class="absolute inset-0 flex items-end justify-between px-md">
        <!-- Simulated Bar Chart -->
        @foreach($chartData as $data)
            <div class="w-8 bg-{{ $data['color'] }} rounded-t-sm" style="height: {{ $data['height'] }}%"></div>
        @endforeach
    </div>
</div>

<div class="mt-md flex justify-center gap-xl text-xs font-bold">
    <div class="flex items-center gap-xs">
        <span class="w-3 h-3 bg-primary rounded-full"></span>
        <span>Scans effectués</span>
    </div>
    
    <div class="flex items-center gap-xs">
        <span class="w-3 h-3 bg-primary-fixed-dim rounded-full"></span>
        <span>Commandes validées</span>
    </div>
</div>
<header class="flex justify-between items-center w-full px-lg h-16 bg-surface dark:bg-surface-dim border-b border-outline-variant dark:border-outline shrink-0">
    <div class="flex items-center flex-1 max-w-xl">
        <div class="relative w-full">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary">search</span>
            <input class="w-full pl-10 pr-4 py-2 rounded-lg border border-outline-variant bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" 
                   placeholder="Rechercher une commande, un partenaire..." type="text">
        </div>
    </div>
    
    <div class="flex items-center gap-lg ml-lg">
        <button class="relative text-secondary hover:text-primary transition-colors active:scale-95 duration-150">
            <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
            @php
                $unreadNotifications = $unreadNotifications ?? 0;
            @endphp
            @if($unreadNotifications > 0)
                <span class="absolute top-0 right-0 w-2 h-2 bg-error rounded-full border-2 border-white"></span>
            @endif
        </button>
        
        <button class="text-secondary hover:text-primary transition-colors active:scale-95 duration-150">
            <span class="material-symbols-outlined" data-icon="account_circle">account_circle</span>
        </button>
    </div>
</header>
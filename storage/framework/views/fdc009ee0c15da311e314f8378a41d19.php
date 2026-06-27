<aside class="flex flex-col h-screen py-lg px-md bg-surface-container-low dark:bg-surface-container-lowest docked left-0 w-64 shrink-0 shadow-sm border-r border-outline-variant">
    <div class="mb-xl px-xs">
        <h1 class="font-headline-md text-headline-md font-bold text-primary dark:text-primary-fixed">QR Validator Pro</h1>
        <p class="text-secondary text-label-md mt-1">Administration B2B</p>
    </div>
    
    <nav class="flex-1 space-y-1">
        <!-- Tableau de bord is active -->
        <a class="flex items-center gap-sm px-md py-sm text-primary dark:text-primary-fixed font-bold border-r-4 border-primary bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors active:opacity-80" 
           href="<?php echo e(route('admin.dashboard')); ?>">
            <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
            <span class="font-body-md">Tableau de bord</span>
        </a>
        
        <a class="flex items-center gap-sm px-md py-sm text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors active:opacity-80" 
           href="<?php echo e(route('admin.partners.index')); ?>">
            <span class="material-symbols-outlined" data-icon="handshake">handshake</span>
            <span class="font-body-md">Partenaires</span>
        </a>
        
        <a class="flex items-center gap-sm px-md py-sm text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors active:opacity-80" 
           href="<?php echo e(route('admin.orders.index')); ?>">
            <span class="material-symbols-outlined" data-icon="shopping_cart">shopping_cart</span>
            <span class="font-body-md">Commandes</span>
        </a>
        
        <a class="flex items-center gap-sm px-md py-sm text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors active:opacity-80" 
           href="<?php echo e(route('admin.validations.index')); ?>">
            <span class="material-symbols-outlined" data-icon="qr_code_2">qr_code_2</span>
            <span class="font-body-md">Validations QR</span>
        </a>
        
        <a class="flex items-center gap-sm px-md py-sm text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors active:opacity-80" 
           href="<?php echo e(route('admin.settings')); ?>">
            <span class="material-symbols-outlined" data-icon="settings">settings</span>
            <span class="font-body-md">Paramètres</span>
        </a>
        
        <a class="flex items-center gap-sm px-md py-sm text-on-surface-variant dark:text-surface-variant hover:bg-surface-container-high dark:hover:bg-surface-container-highest transition-colors active:opacity-80" 
           href="<?php echo e(route('admin.logs')); ?>">
            <span class="material-symbols-outlined" data-icon="terminal">terminal</span>
            <span class="font-body-md">Logs système</span>
        </a>
    </nav>
    
    <div class="mt-auto p-md border-t border-outline-variant flex items-center gap-sm">
        <img class="w-10 h-10 rounded-full bg-surface-container" 
             src="<?php echo e(auth()->user()?->avatar ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuDSq4Owp-_kxVPOnY45pFOWjtGDnNady6U8REIwPrh7NnrEfzVEPDKhcCDbkUq2zbSu90QlwyJ5EgPHk5gb2TbxaRoo-8Anggi_-YM07TgFnAQG55VN82enjD3DDroyTAKoynnFdZaSCWcP7u0aY7-iR5tbygaOAwJq59y7r7zyT5ESotnWihRSiEDHDx2xAHScGlUGj00jqGAm7VhiqNStPTI-PWMPZvQSwLcUq8o4y09HzcX05DslSwqMm8i77xvqKmbZobMyeby9'); ?>"
             alt="Avatar de <?php echo e(auth()->user()?->name); ?>">
        <div class="overflow-hidden">
            <p class="text-on-surface font-bold truncate"><?php echo e(auth()->user()?->name ?? 'Administrateur'); ?></p>
            <p class="text-secondary text-xs truncate"><?php echo e(auth()->user()?->role ?? 'Super Admin'); ?></p>
        </div>
    </div>
</aside><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/components/sidenav_new.blade.php ENDPATH**/ ?>
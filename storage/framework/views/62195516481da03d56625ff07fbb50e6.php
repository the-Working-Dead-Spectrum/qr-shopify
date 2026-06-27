<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Page non trouvée — QR Shopify</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 text-blue-600 mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            
            <h1 class="text-4xl font-bold text-slate-900 mb-2">404</h1>
            <p class="text-xl text-slate-600 mb-4">Page non trouvée</p>
            <p class="text-slate-500 mb-8 max-w-md mx-auto">
                La page que vous cherchez n'existe pas ou a été déplacée.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?php echo e(route('home')); ?>" 
                   class="inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" />
                    </svg>
                    Retour à l'accueil
                </a>
                <?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(route('admin.dashboard')); ?>" 
                       class="inline-flex items-center justify-center gap-2 bg-white border border-slate-300 text-slate-700 px-6 py-3 rounded-lg font-medium hover:bg-slate-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h-3V1.5m3 0h3m-3 18.75h3" />
                        </svg>
                        Tableau de bord
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/errors/404.blade.php ENDPATH**/ ?>
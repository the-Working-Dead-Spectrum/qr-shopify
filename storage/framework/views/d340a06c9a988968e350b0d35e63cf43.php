<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Tableau de bord'); ?> — <?php echo e(config('app.name')); ?></title>
    
    <!-- Tailwind CSS with plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Tailwind Config -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#515f74",
                        "surface-tint": "#006c50",
                        "on-tertiary-fixed-variant": "#004c6e",
                        "surface-container": "#eceef0",
                        "surface-dim": "#d8dadc",
                        "on-secondary": "#ffffff",
                        "on-tertiary-fixed": "#001e2f",
                        "inverse-primary": "#75d9b3",
                        "outline": "#6e7a73",
                        "surface-variant": "#e0e3e5",
                        "on-background": "#191c1e",
                        "error": "#ba1a1a",
                        "on-surface": "#191c1e",
                        "on-secondary-container": "#57657a",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary-fixed-variant": "#3a485b",
                        "inverse-surface": "#2d3133",
                        "surface-bright": "#f7f9fb",
                        "on-error": "#ffffff",
                        "on-tertiary-container": "#f0f7ff",
                        "on-primary-container": "#d6ffeb",
                        "primary": "#00654b",
                        "on-primary-fixed-variant": "#00513c",
                        "tertiary-fixed-dim": "#89ceff",
                        "background": "#f7f9fb",
                        "on-surface-variant": "#3e4944",
                        "inverse-on-surface": "#eff1f3",
                        "error-container": "#ffdad6",
                        "primary-fixed": "#92f6cf",
                        "outline-variant": "#bdc9c2",
                        "surface-container-high": "#e6e8ea",
                        "on-primary": "#ffffff",
                        "surface": "#f7f9fb",
                        "primary-fixed-dim": "#75d9b3",
                        "surface-container-low": "#f2f4f6",
                        "on-secondary-fixed": "#0d1c2e",
                        "on-tertiary": "#ffffff",
                        "tertiary-fixed": "#c9e6ff",
                        "surface-container-highest": "#e0e3e5",
                        "tertiary": "#005e88",
                        "on-primary-fixed": "#002116",
                        "secondary-fixed-dim": "#b9c7df",
                        "secondary-container": "#d5e3fc",
                        "tertiary-container": "#0078ab",
                        "on-error-container": "#93000a",
                        "secondary-fixed": "#d5e3fc",
                        "primary-container": "#008060"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "base": "4px",
                        "gutter": "16px",
                        "md": "16px",
                        "xs": "8px",
                        "xl": "32px",
                        "sm": "12px",
                        "lg": "24px",
                        "container-margin": "24px"
                    },
                    "fontFamily": {
                        "button-text": ["Inter"],
                        "headline-md": ["Inter"],
                        "headline-lg": ["Inter"],
                        "headline-lg-mobile": ["Inter"],
                        "label-md": ["Inter"],
                        "body-md": ["Inter"],
                        "body-lg": ["Inter"]
                    },
                    "fontSize": {
                        "button-text": ["14px", {"lineHeight": "20px", "fontWeight": "600"}],
                        "headline-md": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "headline-lg-mobile": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "700"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}]
                    }
                },
            },
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }
        body {
            background-color: #f7f9fb;
            color: #191c1e;
        }
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid #E2E8F0;
        }
    </style>
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="font-body-md text-body-md overflow-hidden">
<?php
    $unreadNotifications = $unreadNotifications ?? 0;
?>
<div class="flex h-screen overflow-hidden">
    <!-- SideNavBar Component -->
    <?php echo $__env->make('components.sidenav_new', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    
    <main class="flex-1 flex flex-col min-w-0 bg-background overflow-hidden">
        <!-- TopNavBar Component -->
        <?php echo $__env->make('components.topnav_new', ['unreadNotifications' => $unreadNotifications], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        
        <!-- Dashboard Content -->
        <div class="flex-1 overflow-y-auto p-lg">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
        
        <!-- Footer Component -->
        <?php echo $__env->make('components.footer_new', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </main>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/layouts/admin_new.blade.php ENDPATH**/ ?>
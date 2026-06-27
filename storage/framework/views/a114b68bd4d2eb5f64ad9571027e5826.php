<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="robots" content="noindex, nofollow">

    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="h-full font-sans antialiased text-slate-900 flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <a href="<?php echo e(url('/')); ?>" class="inline-flex items-center gap-2 text-xl font-semibold text-slate-900">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white">Q</span>
            <span><?php echo e(config('app.name')); ?></span>
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <?php echo $__env->yieldContent('content'); ?>
    </div>

    <?php if (! empty(trim($__env->yieldContent('footer')))): ?>
        <p class="text-center text-xs text-slate-500 mt-6"><?php echo $__env->yieldContent('footer'); ?></p>
    <?php endif; ?>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH C:\Users\arist\Documents\websites\qr-shopify-main\resources\views/layouts/guest.blade.php ENDPATH**/ ?>
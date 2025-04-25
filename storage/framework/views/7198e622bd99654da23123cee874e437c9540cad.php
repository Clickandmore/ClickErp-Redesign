<?php if(app()->getLocale() == 'ar'): ?>
    <link rel="stylesheet" href="<?php echo e(asset('web')); ?>/assets/css/bootstrap.rtl.min.css">
<?php else: ?>
    <link rel="stylesheet" href="<?php echo e(asset('web')); ?>/assets/css/bootstrap.min.css">
<?php endif; ?>

<link rel="stylesheet" href="<?php echo e(asset('web')); ?>/assets/css/bootstrap-select.css">
<link rel="stylesheet" href="<?php echo e(asset('web')); ?>/assets/css/main.css">

<?php if(app()->getLocale() == 'ar'): ?>
    <link rel="stylesheet" href="<?php echo e(asset('web')); ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo e(asset('admin')); ?>/custom/style-rtl.css">
<?php else: ?>
    <link rel="stylesheet" href="<?php echo e(asset('web')); ?>/assets/css/styleEN.css">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo e(asset('admin')); ?>/assets/vendor/toster/css/jquery.toast.css">

<style>
    .toast-success {
        background-color: #1d70b7 !important;
    }

    .jq-icon-success {
        background-color: #1d70b7 !important;
    }
</style>

<?php echo $__env->yieldPushContent('css'); ?>
<?php /**PATH /Applications/MAMP/htdocs/asuss/resources/views/web/layout/inc/_css.blade.php ENDPATH**/ ?>
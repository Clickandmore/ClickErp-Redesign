
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- favicon -->
<link rel="shortcut icon" href="<?php echo e(isset($settings) ? showFile($settings->favicon): ''); ?>" type="image/x-icon">
<!-- *** css *** -->

<meta name="title" content="<?php if(isset($settings) && isset($settings->website_name)): ?>
            <?php echo e($settings->website_name); ?><?php endif; ?>">



<?php /**PATH /Applications/MAMP/htdocs/asuss/resources/views/web/layout/inc/_meta.blade.php ENDPATH**/ ?>
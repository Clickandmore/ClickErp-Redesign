<!-- header -->

<header>
    <div class="container">
        
        
        
        
        
        
        <nav class="navbar">
            <a class="logo" href="<?php echo e(route('web.home')); ?>">
                <img src="<?php if(isset($settings) && isset($settings->logo_header)): ?> <?php echo e(showFile($settings->logo_header)); ?>

      <?php else: ?>
      <?php echo e(url('/')); ?>/web/assets/images/logoh.svg <?php endif; ?>"
                    alt="">
            </a>
            <ul class="nav-links">
                <li>
                    <a href="<?php echo e(route('web.home')); ?>" class="active"><?php echo e(__('web.Home')); ?></a>
                </li>
                <li>
                    <div class="dropdown">
                        <button type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo e(helperTrans('web.about_us')); ?>

                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li><a class="dropdown-item"
                                    href="<?php echo e(route('web.rights-users')); ?>"><?php echo e(helperTrans('web.rights_users')); ?></a>
                            </li>
                            <li><a class="dropdown-item"
                                    href="<?php echo e(route('web.rights-maids')); ?>"><?php echo e(helperTrans('web.rights_maids')); ?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li>
                    <div class="dropdown">
                        <button type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo e(helperTrans('web.our_services')); ?>

                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <?php $__currentLoopData = $jobTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $jobType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><a class="dropdown-item"
                                        href="<?php echo e(route('web.maids.index', ['job_type_id[]' => $jobType->id])); ?>"><?php echo e($jobType->title); ?></a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            
                            
                        </ul>
                    </div>
                </li>
                <li>
                    <div class="dropdown">
                        <button type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo e(helperTrans('web.help_&_support')); ?>

                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li><a class="dropdown-item"
                                    href="<?php echo e(route('web.contact-us.index')); ?>"><?php echo e(__('web.Contact_Us')); ?></a></li>
                            <li><a class="dropdown-item"
                                    href="<?php echo e(route('web.faqs')); ?>"><?php echo e(helperTrans('web.FAQs')); ?></a></li>
                        </ul>
                    </div>
                </li>
                <li class="bttns">
                    <a href="<?php echo e(route('web.contact-us.index')); ?>"><?php echo e(__('web.Contact_Us')); ?></a>
                </li>
                <?php if(auth()->guard()->guest()): ?>
                    <li class="bttns">
                        <a href="<?php echo e(route('web.auth')); ?>"><?php echo e(helperTrans('web.register')); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
            <?php if(auth()->guard()->guest()): ?>

                <div class="btns">
                    <a href="<?php echo e(route('web.login')); ?>"><?php echo e(helperTrans('web.login')); ?></a>
                    <a href="<?php echo e(route('web.auth')); ?>"><?php echo e(helperTrans('web.register')); ?></a>
                </div>
            <?php endif; ?>
            <?php if(auth()->guard()->check()): ?>
                <div class="btns">
                    <a href="<?php echo e(route('web.profile')); ?>">
                        <i class="fa-solid fa-user"></i> <?php echo e(helperTrans('web.profile')); ?> </a>
                </div>
            <?php endif; ?>

            <div class="menu-btn">
                <div class="menu-bar"></div>
            </div>

            <?php if(\App::getLocale() == 'ar'): ?>
                <a class="btn mx-2" href="<?php echo e(url('en')); ?>"> English </a>
            <?php else: ?>
                <a class="btn mx-2" href="<?php echo e(url('ar')); ?>"> عربي </a>
            <?php endif; ?>

        </nav>
    </div>
</header>
<?php /**PATH /Applications/MAMP/htdocs/asuss/resources/views/web/layout/inc/header.blade.php ENDPATH**/ ?>
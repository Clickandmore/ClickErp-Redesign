<!-- main -->
<script src="<?php echo e(asset('web')); ?>/assets/js/main.js"></script>
<script src="<?php echo e(asset('web')); ?>/assets/js/bootstrap-select.js"></script>
<script src="<?php echo e(asset('web')); ?>/assets/js/app.js"></script>

<!-- custom -->
<script src="<?php echo e(asset('admin')); ?>/assets/vendor/toster/js/jquery.toast.js"></script>
<script src="<?php echo e(asset('admin')); ?>/assets/js/bundle/sweetalert2.bundle.js"></script>
<script src="<?php echo e(asset('admin')); ?>/assets/vendor/jquery_validator/form_validator/jquery.form-validator.min.js"></script>
<script src="<?php echo e(asset('admin')); ?>/assets/js/custom.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_N1k4WKCdiZqCIjjgO0aaKz1Y19JqYqw&libraries=places&callback=initMap">
    </script>

<script>
    let numbers = document.querySelectorAll(".num");
    let started = false;

    function startCount(element) {
        let goal = element.dataset.goal;
        let increment = Math.ceil(goal / 100); // Calculate increment based on the goal

        function updateCounter(currentValue) {
            let newValue = parseInt(currentValue) + increment;
            element.textContent = newValue <= goal ? newValue : goal; // Limit to the goal value
            if (newValue < goal) {
                requestAnimationFrame(updateCounter.bind(null, newValue)); // Continue animation
            }
        }

        requestAnimationFrame(updateCounter.bind(null, 0)); // Start animation
    }

    window.onscroll = function () {
        if (this.scrollY >= document.querySelector(".countries").offsetTop) {
            if (!started) {
                numbers.forEach((num) => startCount(num));
            }
            started = true;
        }
    };
</script>
<?php echo $__env->yieldContent('js'); ?>
<?php echo $__env->yieldPushContent('js'); ?>
<?php /**PATH /Applications/MAMP/htdocs/asuss/resources/views/web/layout/inc/_js.blade.php ENDPATH**/ ?>
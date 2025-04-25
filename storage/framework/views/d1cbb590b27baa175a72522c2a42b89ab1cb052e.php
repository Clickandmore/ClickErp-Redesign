<!DOCTYPE html>
<html>

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Primary Meta Tags -->
    <title>
        <?php if(isset($settings) && isset($settings->website_name)): ?>
            <?php echo e($settings->website_name); ?>

        <?php endif; ?>
    </title>
    <?php echo $__env->make('web.layout.inc._meta', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php echo $__env->make('web.layout.inc._css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->yieldContent('css'); ?>
    <?php echo $__env->yieldPushContent('css'); ?>
    <!-- Hotjar Tracking Code for https://ussusalenjaz.sa/ -->
    <script>
        (function (h, o, t, j, a, r) {
            h.hj = h.hj || function () {
                (h.hj.q = h.hj.q || []).push(arguments)
            };
            h._hjSettings = {
                hjid: 3895725,
                hjsv: 6
            };
            a = o.getElementsByTagName('head')[0];
            r = o.createElement('script');
            r.async = 1;
            r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
            a.appendChild(r);
        })(window, document, 'https://static.hotjar.com/c/hotjar-', '.js?sv=');
    </script>

    <!-- Google Tag Manager -->
    <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-58VWL9QZ');
    </script>
    <!-- End Google Tag Manager -->
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-58VWL9QZ" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <!-- ================ loader ================= -->
    <div class="preloader">
        <div class="preloaderImg"></div>
    </div>
    <!-- ================ Header ================= -->
    <div id="header-ajax">
        <?php echo $__env->make('web.layout.inc.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <!--((((((((((((((((((( content )))))))))))))))))))-->
    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <content>
        <?php echo $__env->yieldContent('content'); ?>
    </content>
    <!-- ================ Footer ================= -->

    <?php echo $__env->make('web.layout.inc.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div id="footer-ajax">
        <?php echo $__env->make('web.layout.inc._js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('web.layout.inc._js_custom', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <?php echo $__env->yieldContent('js'); ?>
    <?php echo $__env->yieldPushContent('js'); ?>

</body>

</html><?php /**PATH /Applications/MAMP/htdocs/asuss/resources/views/web/layout/app.blade.php ENDPATH**/ ?>
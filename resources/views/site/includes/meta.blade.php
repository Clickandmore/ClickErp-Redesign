<!-- Required meta tags -->
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, shrink-to-fit=no" />
<meta name="theme-color" content="#000000" />

<!-- SEO: Description, Keywords, Author, and Robots -->
@php
    $metaBrandName = isset($settings) ? optional($settings->translate(app()->getLocale()))->website_name : null;
    $metaBrandName = $metaBrandName ?: documentSetting('website_name', config('app.name'));
@endphp
<meta name="description"
    content="{{ $metaBrandName }} تقدم خدمات استقدام العمالة المنزلية والمهنية من مختلف الجنسيات، مع ضمان الجودة والالتزام بالمعايير." />
<meta name="keywords"
    content="{{ $metaBrandName }}, استقدام, استقدام عمالة, استقدام العمالة المنزلية, استقدام مهني, شركة استقدام, مكتب استقدام" />
<meta name="author" content="{{ $metaBrandName }}" />
<meta name="robots" content="index, follow" />

<!-- Open Graph Data for Social Media -->
<meta property="og:title" content="{{ $metaBrandName }}" />
<meta property="og:description"
    content="شركة رائدة في مجال استقدام العمالة المنزلية والمهنية، بخدمات موثوقة تلبي احتياجات الأفراد والأعمال." />
<meta property="og:image" content="assets/images/fav.svg" />
<meta property="og:url" content="https://ruknedem.com" />
<meta property="og:type" content="website" />

<!-- Twitter Card Data -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $metaBrandName }}" />
<meta name="twitter:description"
    content="نوفّر خدمات استقدام العمالة المنزلية والمهنية بأعلى معايير الجودة والموثوقية." />
<meta name="twitter:image" content="assets/images/fav.svg" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Required meta tags -->
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, shrink-to-fit=no" />
<meta name="theme-color" content="#000000" />

<!-- SEO: Description, Keywords, Author, and Robots -->
@php
    $locale = app()->getLocale();
    $settingsTranslation = isset($settings) ? $settings->translate($locale, false) : null;
    $metaBrandName = $settingsTranslation->website_name ?? null;
    $metaBrandName = $metaBrandName ?: documentSetting('website_name', config('app.name'));
    $metaTitle = trim((string) ($settingsTranslation->meta_title ?? '')) ?: $metaBrandName;
    $metaDescription = trim((string) ($settingsTranslation->meta_description ?? ''));
    $metaDescription = $metaDescription ?: $metaBrandName . ' تقدم خدمات استقدام العمالة المنزلية والمهنية من مختلف الجنسيات، مع ضمان الجودة والالتزام بالمعايير.';
    $metaKeywords = trim((string) ($settingsTranslation->meta_keywords ?? ''));
    $metaKeywords = $metaKeywords ?: trim((string) ($settingsTranslation->seo_article_tags ?? ''));
    $metaKeywords = $metaKeywords ?: $metaBrandName . ', استقدام, استقدام عمالة, استقدام العمالة المنزلية, استقدام مهني, شركة استقدام, مكتب استقدام';
    $metaImage = isset($settings) && !empty($settings->favicon) ? showFile($settings->favicon) : asset('assets/images/fav.svg');
@endphp
<meta name="description"
    content="{{ $metaDescription }}" />
<meta name="keywords"
    content="{{ $metaKeywords }}" />
<meta name="author" content="{{ $metaBrandName }}" />
<meta name="robots" content="index, follow" />

<!-- Open Graph Data for Social Media -->
<meta property="og:title" content="{{ $metaTitle }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
<meta property="og:image" content="{{ $metaImage }}" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:type" content="website" />

<!-- Twitter Card Data -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $metaTitle }}" />
<meta name="twitter:description" content="{{ $metaDescription }}" />
<meta name="twitter:image" content="{{ $metaImage }}" />
<meta name="csrf-token" content="{{ csrf_token() }}">

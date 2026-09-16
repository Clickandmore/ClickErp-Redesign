<?php

/*
|--------------------------------------------------------------------------
| سكاشن الصفحة الرئيسية في المواقع الخارجية
|--------------------------------------------------------------------------
|
| المفاتيح هنا عقد ثابت مع كل المواقع المرتبطة بالداشبورد: كل موقع يقرأ
| data.sections_visibility من GET /api/client/home ويُخفي القسم فقط عندما تكون
| قيمته false. أي مفتاح غير موجود (أو موقع لم يُحدّث) = القسم ظاهر كما هو.
|
| لا تُغيّر اسم مفتاح موجود — المواقع تعتمد عليه. أضف مفاتيح جديدة فقط.
| العناوين والتلميحات في resources/lang/{ar,en}/home_sections.php.
|
*/

return [
    'sections' => [
        'hero' => ['icon' => 'fa-panorama'],
        'worker_search' => ['icon' => 'fa-magnifying-glass'],
        'slider_gallery' => ['icon' => 'fa-images'],
        'statistics' => ['icon' => 'fa-chart-simple'],
        'trust_highlights' => ['icon' => 'fa-shield-halved'],
        'services' => ['icon' => 'fa-briefcase'],
        'other_services' => ['icon' => 'fa-layer-group'],
        'offers' => ['icon' => 'fa-percent'],
        'latest_cvs' => ['icon' => 'fa-id-card'],
        'about' => ['icon' => 'fa-circle-info'],
        'nationalities' => ['icon' => 'fa-earth-africa'],
        'musaned' => ['icon' => 'fa-medal'],
        'video' => ['icon' => 'fa-circle-play'],
        'why_us' => ['icon' => 'fa-star'],
        'steps' => ['icon' => 'fa-shoe-prints'],
        'requirements' => ['icon' => 'fa-list-check'],
        'customer_service' => ['icon' => 'fa-headset'],
        'unified_number' => ['icon' => 'fa-phone'],
        'app_download' => ['icon' => 'fa-mobile-screen'],
        'faq' => ['icon' => 'fa-circle-question'],
        'blog' => ['icon' => 'fa-newspaper'],
        'cta' => ['icon' => 'fa-bullhorn'],
        'partners' => ['icon' => 'fa-handshake'],
        'contact' => ['icon' => 'fa-envelope'],
        'map' => ['icon' => 'fa-map-location-dot'],
    ],

    /*
    | قوالب جاهزة للأقسام الموجودة فعلاً في تصميم كل موقع مرتبط. يستخدمها المطور
    | من شاشة سكاشن الصفحة الرئيسية لتعبئة «الأقسام المتاحة» بضغطة واحدة، ثم
    | يعدّل عليها إن لزم. عند تعديل تصميم موقع حدّث القائمة الخاصة به هنا.
    */
    'presets' => [
        'alrafedain' => [
            'label' => 'الرافدين',
            'sections' => ['hero', 'services', 'slider_gallery', 'musaned', 'latest_cvs', 'about', 'nationalities', 'offers', 'blog', 'why_us', 'statistics', 'requirements', 'customer_service', 'cta', 'partners', 'contact'],
        ],
        'tawasut' => [
            'label' => 'توسط',
            'sections' => ['hero', 'statistics', 'services', 'offers', 'latest_cvs', 'about', 'nationalities', 'why_us', 'requirements', 'customer_service', 'musaned', 'cta', 'partners', 'contact'],
        ],
        'rukn_adem' => [
            'label' => 'ركن آدم',
            'sections' => ['hero', 'services', 'statistics', 'video', 'about', 'nationalities', 'customer_service', 'contact', 'map'],
        ],
        'qawafel' => [
            'label' => 'قوافل',
            'sections' => ['hero', 'statistics', 'services', 'trust_highlights', 'steps', 'about', 'nationalities', 'customer_service', 'faq', 'contact', 'partners'],
        ],
        'hajar' => [
            'label' => 'هجر',
            'sections' => ['hero', 'services', 'about', 'statistics', 'requirements', 'nationalities', 'customer_service', 'partners', 'blog', 'contact'],
        ],
        'al_rahma' => [
            'label' => 'الرحمة',
            'sections' => ['hero', 'statistics', 'services', 'musaned', 'trust_highlights', 'steps', 'about', 'nationalities', 'customer_service', 'faq', 'contact', 'partners'],
        ],
        'alata' => [
            'label' => 'العطاء',
            'sections' => ['hero', 'services', 'video', 'about', 'nationalities', 'unified_number', 'statistics', 'blog', 'contact', 'map'],
        ],
        'ajeb_qortoba' => [
            'label' => 'عجب قرطبة',
            'sections' => ['hero', 'services', 'about', 'app_download', 'statistics', 'requirements', 'nationalities', 'customer_service', 'partners', 'contact'],
        ],
        'alwajhah' => [
            'label' => 'الوجهة الأولى',
            'sections' => ['hero', 'worker_search', 'statistics', 'partners', 'services', 'offers', 'latest_cvs', 'about', 'nationalities', 'why_us', 'requirements', 'customer_service', 'musaned', 'cta', 'contact'],
        ],
        'platinum' => [
            'label' => 'بلاتينيوم',
            'sections' => ['hero', 'services', 'nationalities', 'about', 'musaned', 'statistics', 'steps', 'other_services', 'requirements', 'why_us', 'cta', 'offers', 'video', 'contact', 'map'],
        ],
    ],
];

@extends('site.layouts.index')
@section('title')
    {{__("ingaz.Policies and laws")}}
@endsection
@section('content')
    <section class="policies-hero">
        <div class="overlay">
            <div class="container">
                <h2>سياسات وقوانين الاستخدام داخل المملكة</h2>
                <p>
                    الدستور والقانون السعودي يقوم على أهم المعايير والمفاهيم العامة
                    بالقوانين المتوازنة التي تضمن حقوق وواجبات كلاً من العامل وصاحب العمل،
                    ولذلك هذه أهم سياسات وقوانين الاستخدام داخل المملكة.
                </p>
                <div class="buttons">
                    <a href="{{asset('site')}}/files/employer-rights.pdf" class="btn" download>
                        <i class="fa-solid fa-file-pdf"></i>
                        حقوق وواجبات صاحب العمل
                    </a>

                    <a href="{{asset('site')}}/files/worker-rights.pdf" class="btn" download>
                        <i class="fa-solid fa-file-pdf"></i>
                        حقوق وواجبات العمالة المنزلية
                    </a>
                </div>
            </div>
        </div>
    </section>
    <section class="guidelines-section">
        <div class="container">
            <h2 class="section-title">التوجيهات والإرشادات حسب الدول</h2>
            <p class="section-desc">
                تحرص شركة <strong> بلاتينيوم الشرقية</strong> على توفير بيئة عمل آمنة وواضحة
                لكلٍ من العملاء والعاملات.
                لذلك قمنا بإعداد كتيبات <span>التوجيهات</span> و<span>الإرشادات</span>
                بشكل مبسط ومترجم إلى لغات الجنسيات المختلفة،
                حتى يتمكن الجميع من الاطلاع عليها وفهم حقوقهم وواجباتهم بسهولة.
                <br/>
                يمكنكم تحميل الملفات الخاصة بكل دولة من خلال البطاقات أدناه.
            </p>
            <div class="guidelines-wrapper">
                <!-- دولة: السعودية مثال -->
                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/saudi_Arabia.webp" alt="Saudi Arabia"
                         class="country-flag"/>
                    <h3>المملكة العربية السعودية</h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/saudi-guidelines-ar.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة العربية
                        </a>
                        <a href="{{asset('site')}}/files/saudi-instructions-ar.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة العربية
                        </a>
                    </div>
                </div>

                <!-- دولة: أثيوبيا (أمهرية) -->
                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/Ethiopia-amher.png" alt="Ethiopia"
                         class="country-flag"/>
                    <h3>إثيوبيا (أمهرية)</h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/ethiopia-guidelines-am.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة الأثيوبية – أمهرية
                        </a>
                        <a href="{{asset('site')}}/files/ethiopia-instructions-am.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة الأثيوبية – أمهرية
                        </a>
                    </div>
                </div>

                <!-- دولة: أثيوبيا (أمهرية) -->
                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/oromo.png" alt="Ethiopia" class="country-flag"/>
                    <h3>إثيوبيا (أرومية)</h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/ethiopia-guidelines-am.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة الأثيوبية – أرومية
                        </a>
                        <a href="{{asset('site')}}/files/ethiopia-instructions-am.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة الأثيوبية – أرومية
                        </a>
                    </div>
                </div>
                <!-- دولة: الفلبين -->
                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/4.jpeg" alt="Philippines" class="country-flag"/>
                    <h3>الفلبين (تغالو)</h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/philippines-guidelines-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة الفلبينية – التغالو
                        </a>
                        <a href="{{asset('site')}}/files/philippines-instructions-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة الفلبينية – التغالو
                        </a>
                    </div>
                </div>

                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/3.jpeg" alt="Philippines" class="country-flag"/>
                    <h3> بنغلادش</h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/philippines-guidelines-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة البنجلاديشية
                        </a>
                        <a href="{{asset('site')}}/files/philippines-instructions-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة البنجلاديشية
                        </a>
                    </div>
                </div>
                <!-- دولة: اندونسيا -->
                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/4.jpeg" alt="Philippines" class="country-flag"/>
                    <h3>أندونسيا </h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/philippines-guidelines-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة الاندونيسية
                        </a>
                        <a href="{{asset('site')}}/files/philippines-instructions-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة الاندونيسية
                        </a>
                    </div>
                </div>
                <!-- دولة: أوغندا -->
                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/1.png" alt="Philippines" class="country-flag"/>
                    <h3>أوغندا </h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/philippines-guidelines-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة الأوغندية
                        </a>
                        <a href="{{asset('site')}}/files/philippines-instructions-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة الأوغندية
                        </a>
                    </div>
                </div>
                <!-- دولة: كينيا  -->
                <div class="country-card">
                    <img src="{{asset('site')}}/assets/images/countries/Kenya.png" alt="Philippines"
                         class="country-flag"/>
                    <h3>كينيا </h3>
                    <div class="links">
                        <a href="{{asset('site')}}/files/philippines-guidelines-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            التوجيهات باللغة الكينية والتنزانية – السواحيلية
                        </a>
                        <a href="{{asset('site')}}/files/philippines-instructions-tl.pdf" class="btn" download>
                            <i class="fa-solid fa-file-pdf"></i>
                            الإرشادات باللغة الكينية والتنزانية – السواحيلية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection

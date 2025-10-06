@extends('site.layouts.index')
@section('title')
    {{__("ingaz.Avoid_human")}}
@endsection
@section('content')
    <section class="anti-trafficking">
        <div class="container">
            <h2 class="section-title">مكافحة الاتجار بالبشر</h2>

            <div class="anti-wrapper">
                <!-- سكشن 1: تعريف -->
                <div class="anti-card definition">
                    <h3><i class="fa-solid fa-circle-info"></i> تعريف الجريمة</h3>
                    <p>
                        استخدام شخص أو نقله أو إيواؤه أو استقباله بقصد الاستغلال (العمل
                        القسري، الاستغلال الجنسي، التسول، الاسترقاق).
                    </p>
                </div>

                <!-- سكشن 2: العقوبات -->
                <div class="anti-card punishment">
                    <h3><i class="fa-solid fa-scale-balanced"></i> العقوبات النظامية</h3>
                    <p>السجن حتى 15 سنة أو غرامة تصل إلى مليون ريال أو كليهما.</p>
                </div>

                <!-- سكشن 3: الجهات المسؤولة -->
                <div class="anti-card authority">
                    <h3><i class="fa-solid fa-building"></i> الجهات المسؤولة</h3>
                    <p>
                        وزارة الموارد البشرية والتنمية الاجتماعية (إدارة مكافحة جرائم
                        الاتجار بالأشخاص) بالتعاون مع هيئة حقوق الإنسان.
                    </p>
                </div>

                <!-- سكشن 4: التحديثات -->
                <div class="anti-card updates">
                    <h3><i class="fa-solid fa-rotate"></i> التحديثات حتى 2025</h3>
                    <p>
                        استمرار سريان النظام مع تعزيز التعاون الدولي وتشديد الملاحقة
                        القانونية للجناة.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- awareness-section  -->
    <section class="awareness-section">
        <div class="container">
            <div class="awareness-wrapper">
                <div class="awareness-image">
                    <img src="{{asset('site')}}/assets/images/awarness.jpg" alt="التوعية"/>
                </div>
                <div class="awareness-content">
                    <h2>التوعية والوقاية</h2>
                    <p>
                        التوعية المجتمعية عنصر أساسي للحد من جرائم الاتجار بالبشر، وتشمل
                        نشر المعرفة بحقوق الإنسان، وتقديم البرامج التدريبية، وحملات
                        إعلامية توضح العقوبات والأنظمة.
                    </p>
                    <ul>
                        <li>برامج تدريبية للمواطنين والمقيمين</li>
                        <li>حملات إعلامية دورية</li>
                        <li>تعاون مع المنظمات الدولية</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- policies-accordion  -->
    <section class="policies-accordion">
        <div class="container">
            <h2>سياسات مكافحة الاتجار بالأشخاص</h2>

            <div class="accordion">
                <div class="accordion-item">
                    <button class="accordion-header">الإطار القانوني</button>
                    <div class="accordion-body">
                        <p>
                            نظام مكافحة جرائم الاتجار بالأشخاص، نظام العمل السعودي، نظام
                            حماية الطفل، الخطة الوطنية لمنع عمل الأطفال (2024).
                        </p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header">السياسات العامة</button>
                    <div class="accordion-body">
                        <p>
                            عدم التسامح مطلقًا مع جميع أشكال الاتجار بالبشر أو تشغيل
                            الأطفال، وحماية العمالة من أي استغلال أو رسوم غير قانونية.
                        </p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header">الإجراءات التنفيذية</button>
                    <div class="accordion-body">
                        <p>
                            التحقق من الأهلية، مراجعة العقود، الموافقة الحرة، التدريب
                            المستمر، وآليات التبليغ عن حالات الاشتباه.
                        </p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header">آليات المراقبة</button>
                    <div class="accordion-body">
                        <p>
                            إدارة الشكاوى إلكترونيًا، متابعة أوضاع العمالة، تدقيق داخلي
                            وخارجي، دورات تدريبية سنوية.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="report-section">
        <div class="container">
            <h2 class="section-title">آليات الإبلاغ عن المخالفات</h2>

            <div class="report-cards">
                <div class="report-card">

                    <h3 class="card-title">الرقم الموحد</h3>
                    <p class="card-desc">لوزارة الموارد البشرية والتنمية الاجتماعية</p>
                    <!-- <div class="card-contact">19911</div> -->
                    <a href="tel:19911" class="card-link">
                        <span>19911</span>
                        <i class="fa-solid fa-phone"></i>
                    </a>

                </div>

                <div class="report-card">

                    <h3 class="card-title">التطبيق الإلكتروني</h3>
                    <p class="card-desc">للهيئة الوطنية لحقوق الإنسان</p>
                    <a href="#" class="card-link">
                        <span>عبر التطبيق الإلكتروني. </span>
                    </a>
                </div>

                <div class="report-card">

                    <h3 class="card-title">البريد الإلكتروني</h3>
                    <p class="card-desc">للجنة الوطنية لمكافحة الاتجار بالأشخاص</p>
                    <a href="mailto:info@ncct.gov.sa" class="card-link">
                        <span>info@ncct.gov.sa</span>
                        <i class="fa-solid fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div class="report-note">
                <p>جميع آليات الإبلاغ سرية وتضمن حماية للمبلغين وفقاً للأنظمة واللوائح</p>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection

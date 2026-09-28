@extends('web.hourly.layout')
@section('content')
<main>
    @include('web.hourly._header', ['title' => 'مساعدة تناسب وقتك'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')

            <div class="hr-intro">
                <div>
                    <span class="hr-badge"><i class="fa-light fa-clock"></i> إيجار ساعات</span>
                    <h2 class="mt-2">احجزي عاملة بالساعة في خطوات بسيطة</h2>
                    <p>اختاري عاملة متاحة، ثم حددي الخدمة والباقة والموعد المناسب لك.</p>
                </div>
                <a class="hr-btn" href="{{ route('web.hourly.catalog') }}"><i class="fa-light fa-box-open"></i> استعراض الخدمات والباقات</a>
            </div>

            <div class="hr-steps">
                <div class="hr-step"><b>1</b><div><strong>اختاري العاملة</strong><small>أو تصفحي الباقات مباشرة</small></div></div>
                <div class="hr-step"><b>2</b><div><strong>حددي الباقة</strong><small>عدد الزيارات والساعات</small></div></div>
                <div class="hr-step"><b>3</b><div><strong>اختاري الموعد</strong><small>بحسب الحي والأوقات المتاحة</small></div></div>
                <div class="hr-step"><b>4</b><div><strong>أكملي الدفع</strong><small>إلكترونيًا أو بالتحويل</small></div></div>
            </div>

            <div class="row maids-grid g-4">
            @forelse($maids as $maid)
                <div class="col-12 col-sm-6 col-xl-4">
                    <article class="hr-card hr-worker p-0">
                        <a class="hr-worker-photo" href="{{ route('web.hourly.catalog', ['maid'=>$maid->id]) }}"><img src="{{ $maid->cv_preview ? showFile($maid->cv_preview) : $maid->photo_url }}" alt="{{ $maid->name }}" loading="lazy"><span class="hr-badge">إيجار ساعات</span></a>
                        <div class="hr-card-body"><h3>{{ $maid->name }}</h3><p class="hr-muted mb-2">متاحة للحجز بالساعات بحسب المنطقة والموعد.</p>
                            <ul class="mcv-facts">
                                <li class="mcv-fact"><span class="mcv-fact__ico"><i class="fa-light fa-globe"></i></span><span class="mcv-fact__text"><small>الجنسية</small><strong>{{ $maid->nationality?->sub_title ?? $maid->nationality?->title ?? 'غير محدد' }}</strong></span></li>
                                <li class="mcv-fact"><span class="mcv-fact__ico"><i class="fa-light fa-briefcase"></i></span><span class="mcv-fact__text"><small>الخبرة</small><strong>{{ $maid->experience_status === 'has_experience' ? 'سبق لها العمل' : 'قادم جديد' }}</strong></span></li>
                            </ul>
                            <a class="hr-btn" href="{{ route('web.hourly.catalog', ['maid'=>$maid->id]) }}">احجزي الآن</a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="hr-empty"><i class="fa-light fa-user-clock"></i><p class="m-0">لا توجد عاملات متاحات للاختيار حاليًا. يمكنك تصفح الباقات لمعرفة الخدمات المتاحة للحجز.</p><a class="hr-btn" href="{{ route('web.hourly.catalog') }}">تصفح الباقات</a></div>
                </div>
            @endforelse
            </div>
            <div class="hr-pagination">{{ $maids->links() }}</div>
        </div>
    </section>
</main>
@endsection

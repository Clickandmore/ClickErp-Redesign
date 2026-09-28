@extends('web.hourly.layout')
@section('content')
<main>
    @include('web.hourly._header', ['title' => 'اختاري باقة الساعات', 'crumb' => 'الباقات'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')

            @if($maid)
                <div class="hr-notice"><i class="fa-light fa-circle-info mt-1"></i><p>تظهر الخدمات التي يمكن حجز العاملة المختارة فيها. <a href="{{ route('web.hourly.catalog') }}">عرض جميع الخدمات</a></p></div>
            @endif

            <div id="hr-catalog" aria-live="polite">
            <div class="hr-card hr-filters">
                <div class="hr-filter">
                    <div class="hr-filter-label"><i class="fa-light fa-location-dot"></i> المنطقة / المدينة</div>
                    <nav class="hr-tabs" aria-label="المنطقة">
                        @foreach($cities as $row)<a class="{{ $city === $row->id ? 'active' : '' }}" aria-current="{{ $city === $row->id ? 'page' : 'false' }}" href="{{ route('web.hourly.catalog', ['city'=>$row->id,'maid'=>$maid]) }}">{{ $row->name_ar }}</a>@endforeach
                    </nav>
                </div>
                <div class="hr-filter">
                    <div class="hr-filter-label"><i class="fa-light fa-broom"></i> نوع الخدمة</div>
                    <nav class="hr-tabs" aria-label="نوع الخدمة">
                        @foreach($categories as $row)<a class="{{ $category === $row->id ? 'active' : '' }}" aria-current="{{ $category === $row->id ? 'page' : 'false' }}" href="{{ route('web.hourly.catalog', ['city'=>$city,'category'=>$row->id,'maid'=>$maid]) }}">{{ $row->name_ar }}</a>@endforeach
                    </nav>
                </div>
            </div>

            <div class="hr-grid">
            @forelse($packages as $item)
                @php($data = app(\App\Services\Hourly\CustomerHourlyData::class)->package($item))
                <article class="hr-card hr-package">
                    <div class="hr-package-head"><h2>{{ $data['title'] }}</h2></div>
                    <div class="hr-package-body">
                        @include('web.hourly._package-details')
                        <a class="hr-btn block" href="{{ route('web.hourly.package', ['package'=>$item->id,'city'=>$city,'maid'=>$maid]) }}">تفاصيل الباقة</a>
                    </div>
                </article>
            @empty
                <div class="hr-empty"><i class="fa-light fa-box-open"></i><p class="m-0">لا توجد باقات متاحة لهذه الخيارات حاليًا.</p></div>
            @endforelse
            </div>
            </div>
        </div>
    </section>
</main>
@endsection
@push('js')
<script>
// التنقل بين المدن وأنواع الخدمة دون إعادة تحميل الصفحة: نجلب نفس الصفحة ونستبدل منطقة الفلاتر والباقات فقط
(() => {
    const load = async (url, push) => {
        const box = document.getElementById('hr-catalog');
        box.classList.add('is-loading');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok || res.redirected) throw new Error();
            const fresh = new DOMParser().parseFromString(await res.text(), 'text/html').getElementById('hr-catalog');
            if (!fresh) throw new Error();
            box.replaceWith(fresh);
            if (push) history.pushState({ hrCatalog: true }, '', url);
        } catch (e) {
            location.href = url;
        }
    };
    document.addEventListener('click', e => {
        const link = e.target.closest('#hr-catalog .hr-tabs a');
        if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        if (!link.classList.contains('active')) load(link.href, true);
    });
    history.replaceState({ hrCatalog: true }, '', location.href);
    window.addEventListener('popstate', e => { if (e.state?.hrCatalog) load(location.href, false); });
})();
</script>
@endpush

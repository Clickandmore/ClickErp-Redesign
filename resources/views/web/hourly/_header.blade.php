{{-- ترويسة الصفحة بنمط الموقع: $title عنوان الصفحة و$crumb آخر عنصر في مسار التنقل --}}
<section class="page_header">
    <h1>{{ $title }}</h1>
    <h6>
        <a href="{{ route('site.home.index') }}">{{ __('web.Home') }}</a> /
        @if (!empty($crumb))
            <a href="{{ route('web.hourly.index') }}">إيجار الساعات</a> / {{ $crumb }}
        @else
            إيجار الساعات
        @endif
    </h6>
</section>

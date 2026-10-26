@extends('admin.layout.indexs.index')
@section('page-title', 'تحديثات النظام')

@section('style')
<style>
    .su-page { --su-primary: #0f6b6e; --su-primary-soft: #e8f7f5; --su-border: #e5ebf1; --su-muted: #64748b; --su-text: #0f172a; color: var(--su-text); }
    .su-hero {
        display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap;
        padding: 24px 26px; margin-bottom: 18px; border-radius: 16px; color: #fff;
        background: linear-gradient(135deg, #0f6b6e 0%, #15959a 100%);
        box-shadow: 0 18px 40px -24px rgba(15, 107, 110, .8);
    }
    .su-hero h1 { margin: 0; color: inherit; font-size: 1.35rem; font-weight: 800; }
    .su-hero p { margin: 6px 0 0; color: rgba(255,255,255,.85); font-size: .88rem; }
    .su-hero-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .su-hero-count { padding: 8px 14px; border-radius: 12px; background: rgba(255,255,255,.16); font-weight: 700; font-size: .85rem; }
    .su-hero .btn-light { font-weight: 700; color: var(--su-primary); }

    .su-filters { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .su-chips { display: flex; gap: 6px; flex-wrap: wrap; }
    .su-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: 999px;
        border: 1px solid var(--su-border); background: #fff; color: #334155; font-size: .8rem; font-weight: 600; text-decoration: none;
    }
    .su-chip:hover { border-color: #b6c6d4; color: var(--su-text); }
    .su-chip.active { background: var(--su-primary-soft); border-color: #9fd3cb; color: var(--su-primary); }
    .su-chip .su-chip-count { min-width: 20px; padding: 0 6px; border-radius: 999px; background: #ef4444; color: #fff; font-size: .7rem; line-height: 18px; text-align: center; }

    .su-list { display: grid; gap: 16px; }
    .su-card {
        position: relative; padding: 20px 22px; border: 1px solid var(--su-border); border-radius: 16px; background: #fff;
        transition: box-shadow .2s ease, border-color .2s ease;
        scroll-margin-top: 110px;
    }
    .su-card.is-pending { border-color: #9fd3cb; box-shadow: 0 10px 30px -22px rgba(15, 107, 110, .7); }
    .su-card.is-pending::before { content: ""; position: absolute; inset-block: 18px; inset-inline-start: -1px; width: 4px; border-radius: 4px; background: var(--su-primary); }
    .su-card.is-important.is-pending { border-color: #fbd5d1; }
    .su-card.is-important.is-pending::before { background: #d92d20; }
    .su-card.is-target { box-shadow: 0 0 0 3px rgba(15, 107, 110, .25); }
    .su-card-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; font-size: .78rem; color: var(--su-muted); }
    .su-cat { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-weight: 700; font-size: .74rem; }
    .su-cat-feature { background: #e8f7f5; color: #0f6b6e; }
    .su-cat-improvement { background: #eff8ff; color: #175cd3; }
    .su-cat-fix { background: #ecfdf3; color: #067647; }
    .su-cat-important { background: #fef3f2; color: #b42318; }
    .su-new { padding: 2px 8px; border-radius: 999px; background: #ef4444; color: #fff; font-weight: 800; font-size: .68rem; }
    .su-version { direction: ltr; unicode-bidi: plaintext; font-family: ui-monospace, Consolas, monospace; }
    .su-card h2 { margin: 0; font-size: 1.08rem; font-weight: 800; line-height: 1.6; color: var(--su-text); }
    .su-summary { margin: 6px 0 0; color: #475569; font-size: .9rem; line-height: 1.8; }
    .su-body { margin-top: 12px; color: #334155; font-size: .88rem; line-height: 1.95; }
    .su-body h1, .su-body h2, .su-body h3, .su-body h4 { font-size: .95rem; font-weight: 800; margin: 14px 0 6px; }
    .su-body ul, .su-body ol { padding-inline-start: 22px; margin-bottom: 8px; }
    .su-body p:last-child { margin-bottom: 0; }
    .su-body img { max-width: 100%; }

    .su-media { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 16px; }
    .su-media figure { margin: 0; border: 1px solid var(--su-border); border-radius: 12px; overflow: hidden; background: #f8fafc; }
    .su-media figure.is-wide { grid-column: 1 / -1; }
    .su-media img { display: block; width: 100%; height: 170px; object-fit: cover; cursor: zoom-in; }
    .su-media video { display: block; width: 100%; max-height: 460px; background: #0f172a; }
    .su-media .su-embed { position: relative; padding-top: 56.25%; background: #0f172a; }
    .su-media .su-embed iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
    .su-media figcaption { padding: 8px 10px; font-size: .78rem; color: var(--su-muted); }
    .su-media figure.su-pdf { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff; }
    .su-pdf-icon { width: 40px; height: 40px; flex: 0 0 auto; display: grid; place-items: center; border-radius: 10px; background: #fef3f2; color: #d92d20; font-size: 1.2rem; }
    .su-pdf-name { flex: 1; min-width: 0; font-weight: 700; font-size: .85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .su-card-foot { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 16px; padding-top: 14px; border-top: 1px solid #eef2f6; }
    .su-try { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: .84rem; color: var(--su-primary); text-decoration: none; }
    .su-try:hover { color: #0b4f51; text-decoration: underline; }
    .su-ack-btn { font-weight: 700; }
    .su-ack-done { display: inline-flex; align-items: center; gap: 6px; color: #067647; font-size: .82rem; font-weight: 600; }

    .su-empty { padding: 50px 20px; text-align: center; color: var(--su-muted); border: 1px dashed #cbd8e2; border-radius: 16px; background: #f8fafc; }
    .su-empty i { display: block; margin-bottom: 10px; font-size: 2rem; color: #94a3b8; }

    .su-lightbox { position: fixed; inset: 0; z-index: 2000; display: none; align-items: center; justify-content: center; padding: 24px; background: rgba(15, 23, 42, .86); cursor: zoom-out; }
    .su-lightbox.is-open { display: flex; }
    .su-lightbox img { max-width: 100%; max-height: 100%; border-radius: 10px; box-shadow: 0 20px 60px rgba(0,0,0,.5); }

    @media (max-width: 575.98px) {
        .su-hero { padding: 18px; }
        .su-card { padding: 16px; }
        .su-media { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endsection

@section('content')
@php
    $filterUrl = fn (array $params) => route('system-updates.index', array_filter(array_merge(['filter' => $filter !== 'all' ? $filter : null, 'category' => $category], $params)));
@endphp
<div class="su-page">
    <div class="su-hero">
        <div>
            <h1><i class="fa fa-bullhorn ms-2"></i> تحديثات النظام</h1>
            <p>كل ما أُضيف أو تحسّن في النظام. اضغط «تم الاطلاع» على كل تحديث بعد قراءته ليختفي من شريط التحديثات.</p>
        </div>
        <div class="su-hero-actions">
            <span class="su-hero-count" id="suPendingLabel">
                {{ $pendingCount > 0 ? $pendingCount . ' بانتظار اطلاعك' : 'اطلعت على كل التحديثات' }}
            </span>
            @if ($pendingCount > 0)
                <form method="POST" action="{{ route('system-updates.acknowledge-all') }}" id="suAckAllForm" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-light btn-sm">تأكيد الاطلاع على الكل</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="su-filters">
        <div class="su-chips">
            <a class="su-chip {{ $filter === 'all' ? 'active' : '' }}" href="{{ $filterUrl(['filter' => null]) }}">الكل</a>
            <a class="su-chip {{ $filter === 'pending' ? 'active' : '' }}" href="{{ $filterUrl(['filter' => 'pending']) }}">
                بانتظار اطلاعي
                @if ($pendingCount > 0)<span class="su-chip-count" id="suPendingChip">{{ $pendingCount }}</span>@endif
            </a>
            <a class="su-chip {{ $filter === 'acknowledged' ? 'active' : '' }}" href="{{ $filterUrl(['filter' => 'acknowledged']) }}">اطلعت عليها</a>
        </div>
        <div class="su-chips">
            <a class="su-chip {{ ! $category ? 'active' : '' }}" href="{{ $filterUrl(['category' => null]) }}">كل التصنيفات</a>
            @foreach (\App\Models\SystemUpdate::CATEGORIES as $key => $meta)
                <a class="su-chip {{ $category === $key ? 'active' : '' }}" href="{{ $filterUrl(['category' => $key]) }}">
                    <i class="fa {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($updates->isEmpty())
        <div class="su-empty">
            <i class="fa fa-check-circle-o"></i>
            {{ $filter === 'pending' ? 'لا توجد تحديثات بانتظار اطلاعك.' : 'لا توجد تحديثات لعرضها حاليًا.' }}
        </div>
    @else
        <div class="su-list">
            @foreach ($updates as $update)
                @php
                    $meta = $update->categoryMeta();
                    $read = $reads->get($update->id);
                    $isAcknowledged = (bool) $read?->acknowledged_at;
                @endphp
                <article id="update-{{ $update->id }}" class="su-card {{ $isAcknowledged ? '' : 'is-pending' }} {{ $update->isImportant() ? 'is-important' : '' }}">
                    <div class="su-card-head">
                        <span class="su-cat su-cat-{{ $update->category }}"><i class="fa {{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span>
                        @unless ($isAcknowledged)<span class="su-new" data-new-badge>جديد</span>@endunless
                        @if ($update->version)<span class="su-version">v{{ $update->version }}</span>@endif
                        <span><i class="fa fa-calendar-o"></i> {{ $update->published_at?->translatedFormat('j F Y') }}</span>
                    </div>

                    <h2>{{ $update->title }}</h2>
                    @if ($update->summary)<p class="su-summary">{{ $update->summary }}</p>@endif
                    @if ($update->body)<div class="su-body">{!! $update->renderedBody() !!}</div>@endif

                    @if ($update->media->isNotEmpty())
                        <div class="su-media">
                            @foreach ($update->media as $media)
                                @php $mediaUrl = $media->publicUrl(); @endphp
                                @if ($media->type === 'image' && $mediaUrl)
                                    <figure>
                                        <img src="{{ $mediaUrl }}" alt="{{ $media->caption ?: $update->title }}" loading="lazy" data-lightbox>
                                        @if ($media->caption)<figcaption>{{ $media->caption }}</figcaption>@endif
                                    </figure>
                                @elseif ($media->type === 'video' && $mediaUrl)
                                    <figure class="is-wide">
                                        <video src="{{ $mediaUrl }}" controls preload="metadata" playsinline></video>
                                        @if ($media->caption)<figcaption>{{ $media->caption }}</figcaption>@endif
                                    </figure>
                                @elseif ($media->type === 'pdf' && $mediaUrl)
                                    <figure class="is-wide su-pdf">
                                        <span class="su-pdf-icon"><i class="fa fa-file-pdf-o"></i></span>
                                        <span class="su-pdf-name">{{ $media->caption ?: 'دليل الاستخدام (PDF)' }}</span>
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ $mediaUrl }}" target="_blank" rel="noopener noreferrer"><i class="fa fa-eye"></i> عرض</a>
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ $mediaUrl }}" download><i class="fa fa-download"></i> تحميل</a>
                                    </figure>
                                @elseif ($media->type === 'embed')
                                    <figure class="is-wide">
                                        @if ($embedUrl = $media->embedUrl())
                                            <div class="su-embed">
                                                <iframe src="{{ $embedUrl }}" title="{{ $media->caption ?: $update->title }}" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                            </div>
                                        @else
                                            <figcaption><a href="{{ $media->url }}" target="_blank" rel="noopener noreferrer"><i class="fa fa-play-circle"></i> مشاهدة الفيديو</a></figcaption>
                                        @endif
                                        @if ($media->caption)<figcaption>{{ $media->caption }}</figcaption>@endif
                                    </figure>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="su-card-foot">
                        <div>
                            @if ($update->link_url)
                                <a class="su-try" href="{{ $update->link_url }}" @if ($update->isExternalLink()) target="_blank" rel="noopener noreferrer" @endif>
                                    {{ $update->link_label ?: 'جرّبه الآن' }}
                                    <i class="fa {{ app()->getLocale() === 'ar' ? 'fa-arrow-left' : 'fa-arrow-right' }}"></i>
                                </a>
                            @endif
                        </div>
                        <div data-ack-area>
                            @if ($isAcknowledged)
                                <span class="su-ack-done"><i class="fa fa-check-circle"></i> اطلعت عليه في {{ $read->acknowledged_at->translatedFormat('j F Y - h:i a') }}</span>
                            @else
                                <form method="POST" action="{{ route('system-updates.acknowledge', $update) }}" class="m-0" data-ack-form>
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm su-ack-btn"><i class="fa fa-check"></i> تم الاطلاع</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-3">{{ $updates->links() }}</div>
    @endif
</div>

<div class="su-lightbox" id="suLightbox" role="dialog" aria-modal="true" aria-label="معاينة الصورة"><img alt=""></div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const pendingLabel = document.getElementById('suPendingLabel');
    const pendingChip = document.getElementById('suPendingChip');

    function renderPending(count) {
        if (pendingLabel) pendingLabel.textContent = count > 0 ? count + ' بانتظار اطلاعك' : 'اطلعت على كل التحديثات';
        if (pendingChip) {
            pendingChip.textContent = count;
            pendingChip.style.display = count > 0 ? '' : 'none';
        }
        if (count === 0) document.getElementById('suAckAllForm')?.remove();
        document.querySelectorAll('.cm-su-badge').forEach(function (badge) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? '' : 'none';
        });
    }

    document.querySelectorAll('[data-ack-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const button = form.querySelector('button');
            button.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.json();
                })
                .then(function (data) {
                    const card = form.closest('.su-card');
                    card?.classList.remove('is-pending');
                    card?.querySelector('[data-new-badge]')?.remove();
                    form.outerHTML = '<span class="su-ack-done"><i class="fa fa-check-circle"></i> اطلعت عليه في ' + data.acknowledged_at + '</span>';
                    renderPending(data.pending_count);
                })
                .catch(function () {
                    button.disabled = false;
                    form.submit();
                });
        });
    });

    const lightbox = document.getElementById('suLightbox');
    const lightboxImage = lightbox?.querySelector('img');
    document.querySelectorAll('[data-lightbox]').forEach(function (image) {
        image.addEventListener('click', function () {
            lightboxImage.src = image.src;
            lightboxImage.alt = image.alt;
            lightbox.classList.add('is-open');
        });
    });
    lightbox?.addEventListener('click', function () { lightbox.classList.remove('is-open'); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') lightbox?.classList.remove('is-open');
    });

    if (location.hash && location.hash.indexOf('#update-') === 0) {
        document.querySelector(location.hash)?.classList.add('is-target');
    }
});
</script>
@endsection

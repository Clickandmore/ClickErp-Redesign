{{-- لودر التصفية بدون إعادة تحميل: بطاقة عائمة في منتصف الشاشة، وتعتيم منطقة النتائج (.hx-results) أثناء الجلب.
     يضبط السكربت موضعه الأفقي على منتصف منطقة النتائج. المتغيرات: $id، و$title و$hint اختياريان. --}}
@once
@push('css')
<style>
    .hx-loader { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; display: flex; align-items: center; gap: 14px; width: max-content; max-width: calc(100vw - 32px); padding: 14px 20px 18px; border: 1px solid #e0defc; border-radius: 16px; background: rgba(255, 255, 255, .97); box-shadow: 0 18px 45px rgba(49, 46, 129, .18); overflow: hidden; animation: hx-loader-in .2s ease-out; direction: rtl; }
    .hx-loader[hidden] { display: none !important; }
    .hx-loader-ring { position: relative; width: 38px; height: 38px; flex: 0 0 38px; border-radius: 50%; background: #f0effd; display: grid; place-items: center; font-size: .72rem; color: #6861ee; }
    .hx-loader-ring::before { content: ''; position: absolute; inset: 4px; border-radius: 50%; border: 3px solid #e0defc; border-top-color: #6861ee; border-inline-start-color: #6861ee; animation: hx-spin .8s linear infinite; }
    .hx-loader-text b { display: block; font-size: .92rem; color: #1e1b4b; }
    .hx-loader-text small { display: block; font-size: .78rem; color: #6b7280; margin-top: .1rem; }
    .hx-loader-bar { position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: #eeedfd; overflow: hidden; }
    .hx-loader-bar span { position: absolute; top: 0; bottom: 0; width: 40%; border-radius: 99px; background: linear-gradient(90deg, #6861ee, #00ac9a); animation: hx-slide 1.1s ease-in-out infinite; }
    .hx-results { transition: opacity .2s ease, filter .2s ease; }
    .hx-results[aria-busy="true"] { opacity: .45; filter: grayscale(.25); pointer-events: none; user-select: none; }
    @keyframes hx-spin { to { transform: rotate(360deg); } }
    @keyframes hx-slide { from { inset-inline-start: -40%; } to { inset-inline-start: 100%; } }
    @keyframes hx-loader-in { from { opacity: 0; transform: translate(-50%, -50%) scale(.96); } to { opacity: 1; transform: translate(-50%, -50%) scale(1); } }
    @media (prefers-reduced-motion: reduce) {
        .hx-loader, .hx-loader-ring::before, .hx-loader-bar span { animation: none; }
        .hx-loader-bar span { width: 100%; inset-inline-start: 0; opacity: .6; }
    }
</style>
@endpush
@endonce
<div id="{{ $id }}" class="hx-loader" role="status" aria-live="polite" hidden>
    <span class="hx-loader-ring" aria-hidden="true"><i class="fa fa-filter"></i></span>
    <span class="hx-loader-text"><b>{{ $title ?? 'جارٍ تحديث النتائج' }}</b><small>{{ $hint ?? 'نطبّق الفلاتر المختارة، لحظات…' }}</small></span>
    <span class="hx-loader-bar" aria-hidden="true"><span></span></span>
</div>

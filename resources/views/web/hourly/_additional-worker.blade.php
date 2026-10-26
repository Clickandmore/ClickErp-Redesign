{{-- نافذة إضافة عاملة لزيارة واحدة؛ تفتحها أزرار [data-add-worker] فقط. --}}
@php
    $addWorkerConfig = [
        'preview' => route('web.hourly.visit.additional-worker.preview', [$order->id, '__VISIT__']),
        'store' => route('web.hourly.visit.additional-worker.store', [$order->id, '__VISIT__']),
    ];
@endphp
<script type="application/json" id="hca-config">@json($addWorkerConfig)</script>
<style>
    /* إعادة تعيين margin في الموقع تُلغي توسيط dialog الافتراضي، فيُعاد هنا. */
    #hca-dialog { position: fixed; inset: 0; margin: auto; height: fit-content; width: min(94vw, 520px); max-height: 92vh; border: 0; border-radius: 18px; padding: 0; box-shadow: 0 24px 60px rgba(25, 25, 54, .25); overflow: hidden; }
    #hca-dialog::backdrop { background: rgba(15, 23, 42, .5); }
    #hca-dialog form { display: flex; flex-direction: column; max-height: 92vh; margin: 0; }
    .hca-head { display: flex; align-items: center; gap: 12px; padding: 18px 20px; background: var(--hr-tint, #eef5fb); border-bottom: 1px solid var(--hr-line, #e5e9f0); }
    .hca-head-icon { flex: 0 0 44px; height: 44px; border-radius: 12px; background: #fff; color: var(--hr-blue, #1d70b7); display: grid; place-items: center; font-size: 1.2rem; }
    .hca-head h2 { margin: 0; font-size: 1.12rem; font-weight: 800; color: var(--hr-navy, #191936); }
    .hca-head p { margin: 2px 0 0; color: var(--hr-muted, #64748b); font-size: .84rem; }
    .hca-close { margin-inline-start: auto; width: 36px; height: 36px; border: 0; border-radius: 10px; background: #fff; color: var(--hr-navy, #191936); font-size: 1.1rem; cursor: pointer; }
    .hca-close:hover { background: #e2e8f0; }
    .hca-body { padding: 18px 20px; overflow-y: auto; }
    .hca-loading { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 34px 0; color: var(--hr-muted, #64748b); font-weight: 700; }
    .hca-workers { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 14px; border: 1px solid var(--hr-line, #e5e9f0); border-radius: 14px; margin-bottom: 14px; }
    .hca-count { text-align: center; }
    .hca-count b { display: block; font-size: 1.6rem; font-weight: 800; color: var(--hr-navy, #191936); line-height: 1.2; }
    .hca-count span { font-size: .76rem; color: var(--hr-muted, #64748b); font-weight: 700; }
    .hca-count.is-new b { color: var(--hr-blue, #1d70b7); }
    .hca-arrow { color: var(--hr-muted, #64748b); font-size: 1.1rem; }
    .hca-prices { margin: 0 0 14px; border: 1px solid var(--hr-line, #e5e9f0); border-radius: 14px; overflow: hidden; }
    .hca-prices > div { display: flex; justify-content: space-between; gap: 12px; padding: 10px 14px; font-size: .92rem; }
    .hca-prices > div + div { border-top: 1px solid #f1f5f9; }
    .hca-prices dt { color: var(--hr-muted, #64748b); font-weight: 600; }
    .hca-prices dd { margin: 0; font-weight: 800; color: var(--hr-navy, #191936); }
    .hca-prices .is-fee dd { color: #b45309; }
    .hca-prices .is-total { background: var(--hr-tint, #eef5fb); }
    .hca-prices .is-total dd { color: var(--hr-blue, #1d70b7); font-size: 1.05rem; }
    .hca-field { margin-bottom: 14px; }
    .hca-field label { display: block; font-weight: 700; font-size: .88rem; margin-bottom: 6px; color: var(--hr-navy, #191936); }
    .hca-field select { border-radius: 11px; height: 46px; }
    .hca-note { display: flex; gap: 8px; align-items: flex-start; font-size: .84rem; line-height: 1.7; color: var(--hr-muted, #64748b); margin-bottom: 12px; }
    .hca-note i { margin-top: 5px; color: var(--hr-blue, #1d70b7); }
    .hca-confirm { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border: 1px solid var(--hr-line, #e5e9f0); border-radius: 12px; background: #fafbfc; font-size: .9rem; cursor: pointer; }
    .hca-confirm input { width: 18px; height: 18px; margin-top: 3px; flex-shrink: 0; accent-color: var(--hr-blue, #1d70b7); }
    .hca-error { margin: 12px 0 0; padding: 10px 14px; border-radius: 11px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: .88rem; }
    .hca-foot { display: flex; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--hr-line, #e5e9f0); background: #fff; }
    .hca-foot .hr-btn { flex: 1; justify-content: center; }
    .hca-foot .hr-btn:disabled { opacity: .5; cursor: not-allowed; }
</style>
<dialog id="hca-dialog" dir="rtl" aria-labelledby="hca-title">
    <form id="hca-form" method="POST" novalidate>
        @csrf
        <input type="hidden" name="request_token">
        <input type="hidden" name="expected_workers"><input type="hidden" name="expected_price"><input type="hidden" name="expected_total">
        <div class="hca-head">
            <span class="hca-head-icon" aria-hidden="true"><i class="fa-light fa-user-plus"></i></span>
            <div>
                <h2 id="hca-title">إضافة عاملة</h2>
                <p id="hca-visit"></p>
            </div>
            <button type="button" class="hca-close" data-hca-close aria-label="إغلاق"><i class="fa-light fa-xmark"></i></button>
        </div>
        <div class="hca-body">
            <div id="hca-loading" class="hca-loading" role="status"><i class="fa-light fa-spinner fa-spin" aria-hidden="true"></i> جارٍ التحقق من توفر عاملة إضافية…</div>
            <div id="hca-content" hidden>
                <div class="hca-workers" aria-label="عدد العاملات">
                    <div class="hca-count"><b id="hca-current"></b><span>العاملات حاليًا</span></div>
                    <i class="fa-light fa-arrow-left hca-arrow" aria-hidden="true"></i>
                    <div class="hca-count is-new"><b id="hca-new"></b><span>بعد الإضافة</span></div>
                </div>
                <dl class="hca-prices">
                    <div class="is-fee"><dt>تكلفة العاملة الإضافية</dt><dd id="hca-fee"></dd></div>
                    <div><dt>سعر الزيارة الجديد</dt><dd id="hca-price"></dd></div>
                    <div class="is-total"><dt>إجمالي الطلب الجديد</dt><dd id="hca-total"></dd></div>
                </dl>
                <div class="hca-field" id="hca-choice" hidden>
                    <label for="hca-worker">اختر العاملة الإضافية</label>
                    <select name="worker_id" class="form-select" id="hca-worker"></select>
                </div>
                <p class="hca-note"><i class="fa-light fa-circle-info" aria-hidden="true"></i><span>تخص الزيادة هذه الزيارة فقط، وتبقى مدفوعاتك السابقة محفوظة.@if(($order->package_snapshot['payment_policy'] ?? null) === 'installments') تُضاف التكلفة إلى آخر قسط في الجدول، دون خصم تلقائي.@endif</span></p>
                <label class="hca-confirm"><input type="checkbox" name="confirmed" value="1" id="hca-confirmed"><span>أوافق على إضافة عاملة لهذه الزيارة بالسعر المعروض.</span></label>
            </div>
            <p id="hca-error" class="hca-error" role="alert" hidden></p>
        </div>
        <div class="hca-foot">
            <button type="submit" class="hr-btn" id="hca-submit" disabled><i class="fa-light fa-check" aria-hidden="true"></i> تأكيد الإضافة</button>
            <button type="button" class="hr-btn secondary" data-hca-close>إلغاء</button>
        </div>
    </form>
</dialog>
@push('js')<script src="{{ asset('web/assets/js/hourly-add-worker-customer.js') }}?v={{ filemtime(public_path('web/assets/js/hourly-add-worker-customer.js')) }}" defer></script>@endpush

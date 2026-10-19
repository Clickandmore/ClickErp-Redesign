@php
    $snapshot = $order->package_snapshot ?? [];
    $itemTitle = collect($snapshot['translations'] ?? [])->firstWhere('locale', app()->getLocale())['title'] ?? ($snapshot['title'] ?? $snapshot['code'] ?? '—');
    $orderNotes = app(\App\Services\Hourly\OperationalNotes::class)->forOrder($order);
    $remaining = max(0, (float) $order->total_amount - (float) $order->paid_amount);
@endphp
<section class="hs-card">
    <header class="hs-hero">
        <div class="hs-hero-copy"><div class="hs-eyebrow">ملخص الطلب — خدمات الساعات</div><div class="hs-order-number"><i class="fa fa-hashtag"></i><span>{{ str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) }}</span></div></div>
        <div class="hs-actions"><button type="button" id="copy-order"><i class="fa fa-copy"></i> نسخ تفاصيل الطلب</button><button type="button" onclick="window.print()"><i class="fa fa-print"></i> طباعة</button><a class="is-light" target="_blank" href="{{ route('admin.hourly-orders.invoice',$order) }}"><i class="fa fa-file-pdf"></i> الفاتورة PDF</a></div>
    </header>
    <div class="hs-pad" id="order-details-copy">
        <div class="hs-meta">
            <div class="hs-stat"><span>حالة الطلب</span><b>{{ __('hourly.'.$order->status) }}</b></div>
            <div class="hs-stat"><span>حالة السداد</span><b>{{ __('hourly.'.$order->payment_status) }}</b></div>
            <div class="hs-stat"><span>طريقة الدفع</span><b>{{ __('hourly.'.$order->payment_method) }}</b></div>
            <div class="hs-stat"><span>إجمالي الطلب</span><b class="hs-money">{{ number_format((float)$order->total_amount,2) }} ر.س</b></div>
            <div class="hs-stat"><span>المبلغ المحصل</span><b>{{ number_format((float)$order->paid_amount,2) }} ر.س</b></div>
            <div class="hs-stat"><span>المبلغ المتبقي</span><b>{{ number_format($remaining,2) }} ر.س</b></div>
        </div>
        <div class="hs-grid">
            <section class="hs-panel"><h5><i class="fa fa-user"></i> معلومات العميل</h5><div class="hs-lines">
                <div class="hs-line"><span>الاسم</span><b>{{ $order->user?->name ?: '—' }}</b></div>
                <div class="hs-line"><span>البريد الإلكتروني</span><b class="hs-ltr">{{ $order->user?->email ?: '—' }}</b></div>
                <div class="hs-line"><span>رقم التواصل</span><b class="hs-ltr">{{ $order->contact_phone ?: $order->user?->phone ?: '—' }}</b></div>
            </div></section>
            <section class="hs-panel"><h5><i class="fa fa-location-dot"></i> عنوان الخدمة</h5><div class="hs-lines">
                <div class="hs-line"><span>العنوان</span><b>{{ $order->address_text ?: '—' }}</b></div>
                <div class="hs-line"><span>الحي</span><b>{{ $order->district ?: '—' }}</b></div>
                <div class="hs-line"><span>المبنى / الوحدة</span><b>{{ $order->building ?: '—' }} / {{ $order->unit ?: '—' }}</b></div>
            </div></section>
            <section class="hs-panel"><h5><i class="fa fa-box-open"></i> تفاصيل الخدمة</h5><div class="hs-lines">
                <div class="hs-line"><span>الفئة</span><b>{{ $snapshot['category'] ?? $order->serviceCategory?->name ?? '—' }}</b></div>
                <div class="hs-line"><span>الباقة</span><b>{{ $itemTitle }}</b></div>
                <div class="hs-line"><span>مواد التنظيف</span><b>{{ !empty($snapshot['materials_included']) ? 'مشمولة' : 'غير مشمولة' }}</b></div>
                @if(!empty($snapshot['brand']))<div class="hs-line"><span>العلامة</span><b>{{ $snapshot['brand'] }}</b></div>@endif
            </div></section>
            <section class="hs-panel"><h5><i class="fa fa-calendar-check"></i> بيانات الطلب</h5><div class="hs-lines">
                <div class="hs-line"><span>تاريخ الإنشاء</span><b class="hs-ltr">{{ $order->created_at?->format('Y-m-d H:i') }}</b></div>
                <div class="hs-line"><span>الزيارات / ساعات الزيارة</span><b>{{ $order->visits_total }} / {{ $order->hours_per_visit }} ساعة</b></div>
                <div class="hs-line"><span>مقدمو الخدمة لكل زيارة</span><b>{{ $order->workers_per_visit }}</b></div>
                <div class="hs-line"><span>المصدر / الموظف</span><b>{{ $order->source === 'admin' ? 'موظف الفرع' : $order->source }} / {{ $creator?->name ?? '—' }}</b></div>
                <div class="hs-line"><span>الصلاحية</span><b class="hs-ltr">{{ $order->expires_at?->format('Y-m-d') }}</b></div>
            </div></section>
        </div>
        <div class="hs-balance mt-3">الزيارات المتاحة: {{ $order->balance['available'] }} — المحجوزة: {{ $order->balance['reserved'] }} — المستهلكة: {{ $order->balance['consumed'] }}</div>
        <section class="hs-panel mt-3"><h5><i class="fa fa-note-sticky"></i> ملاحظات الطلب</h5>
            @if($order->notes)<div class="hs-note">{{ $order->notes }}</div>@endif
            @forelse($orderNotes->whereNull('visit_id') as $note)<div class="hs-note">{{ $note['text'] }}<small class="d-block text-muted mt-1">{{ $note['author'] }} — {{ $note['created_at'] }}</small></div>@empty @unless($order->notes)<div class="hs-empty">لا توجد ملاحظات بعد.</div>@endunless @endforelse
            <div class="hs-note-form">@include('admin.hourly._note-form', ['visitId'=>null])</div>
        </section>
    </div>
</section>
<script>
document.getElementById('copy-order')?.addEventListener('click', async function () {
    try { await navigator.clipboard.writeText(document.getElementById('order-details-copy').innerText); this.innerHTML='<i class="fa fa-check"></i> تم النسخ'; }
    catch (_) { this.textContent='تعذر النسخ؛ حدد النص وانسخه'; }
});
</script>

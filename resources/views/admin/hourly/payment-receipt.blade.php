@php
    $printSettings = $printSettings ?? null;
    $amount = (float) $payment->amount;
    $total = (float) $order->total_amount;
    $remainingAfter = max(0, $total - $paidThrough);
    $refunded = (float) $payment->refunded_amount;
    // حصة الضريبة داخل الدفعة بنسبة ضريبة الطلب إلى إجماليه.
    $vatShare = $total > 0 ? round($amount * (float) $order->tax_amount / $total, 2) : 0;
    $customer = $order->user?->name ?: '—';
    $collectorName = $collector ?: ($payment->provider === 'moyasar' ? 'بوابة ميسر' : 'النظام');
    $debitAccount = match ($payment->method) {
        'cash' => 'الصندوق — عهدة '.$collectorName,
        'card_on_delivery' => 'نقاط البيع (مدى / بطاقة)',
        'bank_transfer' => 'البنك — تحويل وارد',
        'moyasar' => 'بوابة الدفع الإلكتروني (ميسر)',
        'wallet' => 'محفظة العميل',
        default => __('hourly.'.$payment->method),
    };
    $creditAccount = 'ذمم العملاء — '.$customer;
    $payload = $payment->provider_payload ?? [];
    $statement = trim((string) ($payload['description'] ?? '')) ?: 'تحصيل دفعة من قيمة طلب خدمات الساعات رقم #'.$order->order_number.' — فاتورة '.$order->invoice_number;
    $reference = $payment->provider_reference ?: '—';
    $companyName = documentSetting('document_company_name', documentSetting('website_name', config('app.name')));
    $voucherNo = 'RV-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    $paidAt = optional($payment->paid_at ?: $payment->created_at);
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>سند قبض {{ $voucherNo }}</title>
    <style>
        :root{--ink:#111827;--muted:#6b7280;--line:#d1d5db;--soft:#f9fafb;--brand:#0f5f8c;--brand-soft:#eef6fb;--ok:#047857;--ok-soft:#ecfdf5;--red:#b91c1c}
        *{box-sizing:border-box}
        body{margin:0;background:#eef1f5;color:var(--ink);font:14px/1.6 Tahoma,"Segoe UI",Arial,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact}
        .toolbar{max-width:900px;margin:20px auto 0;padding:0 16px;display:flex;gap:8px}
        .toolbar button{border:0;border-radius:8px;background:var(--brand);color:#fff;padding:9px 20px;font:inherit;font-weight:700;cursor:pointer}
        .toolbar button.ghost{background:#fff;color:var(--brand);border:1px solid var(--line)}
        .voucher{max-width:900px;margin:12px auto 24px;background:#fff;border:1px solid var(--line);padding:22px 26px;position:relative}
        .voucher:before{content:"";position:absolute;inset:6px;border:1px solid #e5e7eb;pointer-events:none}
        .head{display:flex;justify-content:space-between;align-items:center;gap:16px;padding-bottom:14px;border-bottom:2px solid var(--brand)}
        .company{display:flex;align-items:center;gap:12px;min-width:0}
        .company img{max-width:110px;max-height:70px;object-fit:contain}
        .company h2{margin:0;font-size:18px;color:var(--brand)}
        .company p{margin:2px 0 0;color:var(--muted);font-size:12px}
        .doc{text-align:center;border:2px solid var(--brand);padding:6px 18px;white-space:nowrap}
        .doc h1{margin:0;font-size:22px;color:var(--brand)}
        .doc span{display:block;font-size:12px;color:var(--muted);letter-spacing:1px}
        .meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:16px 0}
        .meta div{border:1px solid var(--line);padding:8px 12px;background:var(--soft)}
        .meta small{display:block;color:var(--muted);font-size:12px}
        .meta b{font-size:15px}
        .meta .no b{color:var(--red);font-size:18px}
        .amount{display:flex;align-items:stretch;border:1px solid var(--ok);margin-bottom:16px}
        .amount .fig{background:var(--ok-soft);color:var(--ok);padding:10px 18px;text-align:center;min-width:210px;border-left:1px solid var(--ok)}
        .amount .fig small{display:block;color:var(--muted);font-size:12px}
        .amount .fig strong{font-size:26px;direction:ltr;display:inline-block}
        .amount .words{padding:10px 16px;display:flex;align-items:center;font-weight:700;font-size:15px}
        table{width:100%;border-collapse:collapse}
        td,th{border:1px solid var(--line);padding:7px 10px;text-align:right;vertical-align:top}
        th{background:var(--brand-soft);color:var(--brand);font-size:13px}
        .lbl{background:var(--soft);font-weight:700;width:17%;color:#374151}
        .num{direction:ltr;text-align:left;white-space:nowrap;font-weight:700}
        .ltr{direction:ltr;unicode-bidi:embed}
        .section{margin:16px 0 6px;font-weight:700;color:var(--brand);font-size:14px;display:flex;align-items:center;gap:8px}
        .section:after{content:"";flex:1;border-top:1px solid var(--line)}
        .statement{border:1px solid var(--line);background:var(--soft);padding:9px 12px;min-height:44px}
        .cols{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        tfoot td{background:var(--brand-soft);font-weight:700}
        .grand td{background:var(--ok-soft);color:var(--ok);font-weight:700}
        .due td{color:var(--red);font-weight:700}
        .sign{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:30px;text-align:center}
        .sign div{border-top:1px dashed #9ca3af;padding-top:8px}
        .sign small{display:block;color:var(--muted)}
        .sign b{display:block;min-height:22px}
        .foot{margin-top:18px;padding-top:8px;border-top:1px solid #e5e7eb;color:var(--muted);font-size:11px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
        @media(max-width:700px){.voucher{padding:16px}.head{flex-direction:column;align-items:flex-start}.meta,.cols,.sign{grid-template-columns:1fr}.amount{flex-direction:column}.amount .fig{border-left:0;border-bottom:1px solid var(--ok)}.lbl{width:35%}.table-wrap{overflow-x:auto}}
        @media print{@page{size:A4 portrait;margin:10mm}body{background:#fff}.toolbar{display:none}.voucher{margin:0;max-width:none;border:0;padding:0}.voucher:before{display:none}}
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
    <button type="button" class="ghost" onclick="history.length > 1 ? history.back() : window.close()">رجوع</button>
</div>

<article class="voucher">
    <header class="head">
        <div class="company">
            @if($receiptLogo)<img src="{{ $receiptLogo }}" alt="">@endif
            <div>
                <h2>{{ $companyName }}</h2>
                @if($printSettings?->second_title)<p>{{ $printSettings->second_title }}</p>@endif
                @foreach(($printSettings?->printDetails ?? []) as $detail)<p>{{ $detail->title }}: <span class="ltr">{{ $detail->title_value }}</span></p>@endforeach
            </div>
        </div>
        <div class="doc"><h1>سند قبض</h1><span>RECEIPT VOUCHER</span></div>
    </header>

    <section class="meta">
        <div class="no"><small>رقم السند</small><b class="ltr">{{ $voucherNo }}</b></div>
        <div><small>تاريخ السند</small><b class="ltr">{{ $paidAt->format('Y-m-d') ?: '—' }}</b></div>
        <div><small>وقت التحصيل</small><b class="ltr">{{ $paidAt->format('H:i') ?: '—' }}</b></div>
    </section>

    <section class="amount">
        <div class="fig"><small>المبلغ المقبوض (ر.س)</small><strong>{{ $money($amount) }}</strong></div>
        <div class="words">{{ \App\Support\ArabicAmountInWords::sar($amount) }}</div>
    </section>

    <table>
        <tr><td class="lbl">استلمنا من</td><td>{{ $customer }}</td><td class="lbl">رقم الجوال</td><td class="ltr">{{ $order->contact_phone ?: $order->user?->phone ?: '—' }}</td></tr>
        <tr><td class="lbl">رقم الطلب</td><td class="ltr">#{{ $order->order_number }}</td><td class="lbl">رقم الفاتورة</td><td class="ltr">{{ $order->invoice_number ?: '—' }}</td></tr>
        <tr><td class="lbl">طريقة السداد</td><td>{{ __('hourly.'.$payment->method) }}</td><td class="lbl">مرجع العملية</td><td class="ltr">{{ $reference }}</td></tr>
        <tr><td class="lbl">المحصِّل</td><td>{{ $collectorName }}</td><td class="lbl">حالة الدفعة</td><td>{{ __('hourly.'.$payment->settlement_status) }}</td></tr>
    </table>

    <div class="section">وذلك عن</div>
    <div class="statement">{{ $statement }} @if($order->serviceCategory?->name)— {{ $order->serviceCategory->name }}@endif</div>

    <div class="section">التوجيه المحاسبي</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th style="width:50%">الحساب</th><th>البيان</th><th style="width:15%">مدين</th><th style="width:15%">دائن</th></tr></thead>
            <tbody>
                <tr><td>{{ $debitAccount }}</td><td>تحصيل {{ __('hourly.'.$payment->method) }}</td><td class="num">{{ $money($amount) }}</td><td class="num">—</td></tr>
                <tr><td>{{ $creditAccount }}</td><td>سداد من رصيد الطلب #{{ $order->order_number }}</td><td class="num">—</td><td class="num">{{ $money($amount) }}</td></tr>
            </tbody>
            <tfoot><tr><td colspan="2">الإجمالي</td><td class="num">{{ $money($amount) }}</td><td class="num">{{ $money($amount) }}</td></tr></tfoot>
        </table>
    </div>

    <div class="cols">
        <div>
            <div class="section">موقف حساب الطلب</div>
            <table>
                <tr><td class="lbl" style="width:60%">إجمالي الفاتورة شامل الضريبة</td><td class="num">{{ $money($total) }}</td></tr>
                <tr><td class="lbl">المسدد قبل هذا السند</td><td class="num">{{ $money($paidBefore) }}</td></tr>
                <tr class="grand"><td>المقبوض بهذا السند</td><td class="num">{{ $money($amount) }}</td></tr>
                <tr><td class="lbl">إجمالي المسدد بعد السند</td><td class="num">{{ $money($paidThrough) }}</td></tr>
                <tr class="due"><td>المتبقي على العميل</td><td class="num">{{ $money($remainingAfter) }}</td></tr>
            </table>
        </div>
        <div>
            <div class="section">تحليل المبلغ المقبوض</div>
            <table>
                <tr><td class="lbl" style="width:60%">المبلغ قبل الضريبة</td><td class="num">{{ $money($amount - $vatShare) }}</td></tr>
                <tr><td class="lbl">ضريبة القيمة المضافة ({{ (float) $order->tax_rate }}%)</td><td class="num">{{ $money($vatShare) }}</td></tr>
                <tr class="grand"><td>الإجمالي المقبوض</td><td class="num">{{ $money($amount) }}</td></tr>
                @if($refunded > 0)
                    <tr class="due"><td>المسترد من هذه الدفعة</td><td class="num">{{ $money($refunded) }}</td></tr>
                    <tr><td class="lbl">صافي الدفعة بعد الاسترداد</td><td class="num">{{ $money($amount - $refunded) }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <section class="sign">
        <div><small>المستلم (المحصِّل)</small><b>{{ $collectorName }}</b></div>
        <div><small>المحاسب</small><b></b></div>
        <div><small>الختم</small><b></b></div>
    </section>

    <footer class="foot">
        <span>هذا السند يثبت استلام المبلغ الموضح أعلاه ولا يُعد بديلًا عن الفاتورة الضريبية رقم {{ $order->invoice_number }}.</span>
        <span class="ltr">طُبع في {{ now()->format('Y-m-d H:i') }}</span>
    </footer>
</article>
</body>
</html>

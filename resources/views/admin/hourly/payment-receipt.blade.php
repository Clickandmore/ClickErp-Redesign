<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>سند قبض #{{ $payment->id }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#1d2c3d;font-family:Tahoma,Arial,sans-serif}.receipt{max-width:820px;margin:36px auto;background:#fff;border:1px solid #dfe8f2;border-radius:18px;overflow:hidden;box-shadow:0 16px 45px rgba(20,80,127,.1)}.head{padding:26px 30px;background:linear-gradient(110deg,#14507f,#2794eb);color:#fff;display:flex;justify-content:space-between;gap:20px;align-items:center}.head h1{margin:0 0 6px;font-size:26px}.head p{margin:0;opacity:.88}.number{direction:ltr;text-align:left;font-size:18px;font-weight:700}.body{padding:28px 30px}.amount{text-align:center;padding:22px;border-radius:14px;background:#eaf8f5;color:#087f72;margin-bottom:24px}.amount small{display:block;color:#5d7185;margin-bottom:6px}.amount strong{font-size:34px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.item{padding:14px 16px;border:1px solid #e4ebf3;border-radius:12px;min-width:0}.item small{display:block;color:#718096;margin-bottom:5px}.item b{display:block;overflow-wrap:anywhere}.ltr{direction:ltr;text-align:right}.note{margin-top:22px;padding-top:18px;border-top:1px dashed #ccd8e5;color:#65758a;font-size:13px}.actions{text-align:center;padding:0 30px 28px}.actions button{border:0;border-radius:10px;background:#1678c5;color:#fff;padding:11px 24px;font-weight:700;cursor:pointer}@media(max-width:600px){.receipt{margin:0;border-radius:0;min-height:100vh}.head{align-items:flex-start;flex-direction:column}.number{text-align:right}.body{padding:20px}.grid{grid-template-columns:1fr}}@media print{body{background:#fff}.receipt{margin:0;max-width:none;border:0;box-shadow:none}.actions{display:none}}
    </style>
</head>
<body>
<article class="receipt">
    <header class="head">
        <div><h1>سند قبض</h1><p>خدمات الساعات — إثبات استلام دفعة</p></div>
        <div class="number">#{{ str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT) }}</div>
    </header>
    <div class="body">
        <div class="amount"><small>المبلغ المستلم</small><strong>{{ number_format((float) $payment->amount, 2) }} ر.س</strong></div>
        <div class="grid">
            <div class="item"><small>العميل</small><b>{{ $order->user?->name ?: '—' }}</b></div>
            <div class="item"><small>رقم الجوال</small><b class="ltr">{{ $order->contact_phone ?: $order->user?->phone ?: '—' }}</b></div>
            <div class="item"><small>رقم الطلب</small><b class="ltr">#{{ $order->order_number }}</b></div>
            <div class="item"><small>طريقة الدفع</small><b>{{ __('hourly.'.$payment->method) }}</b></div>
            <div class="item"><small>تاريخ ووقت الدفع</small><b class="ltr">{{ optional($payment->paid_at)->format('Y-m-d H:i') ?: '—' }}</b></div>
            <div class="item"><small>مستلم الدفعة</small><b>{{ $collector ?: ($payment->provider === 'moyasar' ? 'بوابة ميسر' : 'النظام') }}</b></div>
            <div class="item"><small>حالة الدفعة</small><b>{{ __('hourly.'.$payment->settlement_status) }}</b></div>
            <div class="item"><small>الخدمة</small><b>{{ $order->serviceCategory?->name ?: 'خدمات الساعات' }}</b></div>
        </div>
        @php $payload = $payment->provider_payload ?? []; @endphp
        @if(!empty($payload['description']))<p class="note">البيان: {{ $payload['description'] }}</p>@endif
        <p class="note">هذا السند يثبت استلام الدفعة الموضحة أعلاه، ولا يُعد بديلًا عن الفاتورة الضريبية للطلب.</p>
    </div>
    <div class="actions"><button type="button" onclick="window.print()">طباعة سند القبض</button></div>
</article>
</body>
</html>

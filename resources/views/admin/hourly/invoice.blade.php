<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
<style>
body{font-family:xbriyaz;color:#17354d;font-size:12pt;direction:rtl}table{width:100%;border-collapse:collapse}td,th{padding:9px;vertical-align:top;text-align:right}h1{font-size:22pt;margin:5px 0}h2{font-size:15pt}p{margin:5px 0}.header{border-bottom:3px solid #176795}.muted{color:#64748b;font-size:10pt}.box{background:#f5f8fb;margin-top:18px}.items{margin-top:20px}.items th{background:#17354d;color:white}.items td{border-bottom:1px solid #dce4ed}.totals{margin-top:20px}.totals td{border-bottom:1px solid #dce4ed}.grand td{background:#176795;color:white;font-size:15pt;font-weight:bold}.footer{border-top:1px solid #dce4ed;margin-top:25px;padding-top:10px;text-align:center;font-size:10pt}
</style></head><body>
@php
 $snapshot=$order->package_snapshot ?? [];
 $title=collect($snapshot['translations'] ?? [])->firstWhere('locale',app()->getLocale())['title'] ?? ($snapshot['title'] ?? $snapshot['code'] ?? '—');
@endphp
<table class="header"><tr><td>
<h2>{{ documentSetting('document_company_name', documentSetting('website_name', config('app.name'))) }}</h2><p>{{ $printSettings?->second_title ?? '' }}</p>
@foreach(($printSettings?->printDetails ?? []) as $detail)<p class="muted">{{ $detail->title }}: {{ $detail->title_value }}</p>@endforeach
</td><td style="text-align:left">@if($invoiceLogo)<img src="{{ $invoiceLogo }}" style="max-width:150px;max-height:85px">@endif</td></tr></table>
<h1>فاتورة التأجير بالساعة</h1><p class="muted">رقم الفاتورة: {{ $order->invoice_number }} — رقم الطلب: {{ $order->order_number }}</p><p>تاريخ الإصدار: {{ $order->created_at->format('Y-m-d H:i') }}</p>
<table class="box"><tr><td style="width:50%"><strong>بيانات العميل</strong><p>{{ $order->user?->name }}</p><p>{{ $order->user?->email }}</p><p>{{ $order->user?->phone }}</p></td><td><strong>عنوان الخدمة</strong><p>{{ $order->address_text }}</p><p>{{ $order->district }} — {{ $order->building }} / {{ $order->unit }}</p><p>{{ $order->contact_phone }}</p></td></tr></table>
<table class="items"><thead><tr><th>الخدمة / الباقة</th><th>الزيارات</th><th>ساعات الزيارة</th><th>مقدمو الخدمة</th><th>القيمة قبل الخصم شاملة الضريبة</th></tr></thead><tbody><tr><td>{{ $snapshot['category'] ?? $order->serviceCategory?->name }}<br>{{ $title }}</td><td>{{ $order->visits_total }}</td><td>{{ $order->hours_per_visit }}</td><td>{{ $order->workers_per_visit }}</td><td>{{ number_format($order->total_amount + $order->discount_amount,2) }} ر.س</td></tr></tbody></table>
<table class="totals"><tr><td>الخصم</td><td>{{ number_format($order->discount_amount,2) }} ر.س</td></tr><tr><td>الإجمالي قبل الضريبة بعد الخصم</td><td>{{ number_format($order->subtotal,2) }} ر.س</td></tr><tr><td>ضريبة القيمة المضافة ({{ $order->tax_rate }}%)</td><td>{{ number_format($order->tax_amount,2) }} ر.س</td></tr><tr class="grand"><td>الإجمالي شامل الضريبة</td><td>{{ number_format($order->total_amount,2) }} ر.س</td></tr><tr><td>المبلغ المحصل</td><td>{{ number_format($order->paid_amount,2) }} ر.س</td></tr><tr><td>المتبقي</td><td>{{ number_format(max(0,$order->total_amount-$order->paid_amount),2) }} ر.س</td></tr></table>
<p>طريقة الدفع: {{ __('hourly.'.$order->payment_method) }} — حالة السداد: {{ __('hourly.'.$order->payment_status) }}</p>
@php($invoiceCompensations = app(\App\Services\Hourly\OrderCompensation::class)->forOrder($order))
@if($invoiceCompensations->isNotEmpty())
<h2>تعويضات مقدَّمة للعميل</h2><p class="muted">للعلم: التعويض مصروف مستقل لا يغيّر قيمة هذه الفاتورة ولا ضريبتها.</p>
<table class="items"><thead><tr><th>التاريخ</th><th>السبب</th><th>طريقة الإيصال</th><th>المبلغ</th></tr></thead><tbody>@foreach($invoiceCompensations as $comp)<tr><td>{{ \Carbon\Carbon::parse($comp->created_at)->format('Y-m-d') }}</td><td>{{ \App\Services\Hourly\OrderCompensation::REASONS[$comp->reason] ?? $comp->reason }}</td><td>{{ \App\Services\Hourly\OrderCompensation::DESTINATIONS[$comp->destination] ?? $comp->destination }}</td><td>{{ number_format((float) $comp->amount,2) }} ر.س</td></tr>@endforeach</tbody></table>
@endif
<h2>مواعيد الزيارات</h2><table class="items"><thead><tr><th>الزيارة</th><th>التاريخ</th><th>من</th><th>إلى</th></tr></thead><tbody>@foreach($order->visits as $visit)<tr><td>{{ $visit->visit_number }} @if($visit->is_free || (float)$visit->visit_price===0.0)<strong>زيارة مجانية</strong>@endif <small>(مرجع #{{ $visit->id }})</small></td><td>{{ $visit->scheduled_start_at?->format('Y-m-d') ?: 'لم يحدد' }}</td><td>{{ $visit->scheduled_start_at?->format('H:i') ?: '—' }}</td><td>{{ $visit->scheduled_end_at?->format('H:i') ?: '—' }}</td></tr>@endforeach</tbody></table>
<div class="footer">{{ $settings?->phone ?? '' }} &nbsp; {{ $settings?->email ?? '' }}<br>شكرًا لاختياركم خدماتنا</div>
</body></html>

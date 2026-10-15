@extends('web.hourly.layout')
@section('content')
@php
    $details = app(\App\Services\Hourly\CustomerHourlyData::class)->order($order,false);
    $canPay = ($details['payment_schedule']['policy']!=='legacy' && in_array($order->status,['active','completed'])) || (!in_array($order->status,['cancelled','expired','completed']) && $order->expires_at->isFuture());
    $canPay = $canPay && $details['payment_schedule']['due_now']>0;
    $paymentLabels = ['moyasar'=>'دفع إلكتروني (ميسر)','bank_transfer'=>'تحويل بنكي','cash'=>'الدفع عند الاستلام'];
@endphp
<main>
    @include('web.hourly._header', ['title' => 'طلب ' . $order->order_number, 'crumb' => 'تفاصيل الطلب'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')
            @if(session('hourly_payment_error'))<p class="hr-error" role="alert">{{ session('hourly_payment_error') }}</p>@endif

            <div class="hr-layout">
                <div>
                    <article class="hr-card">
                        <div class="hr-card-title"><span class="hr-card-icon"><i class="fa-light fa-box-open"></i></span><div><h2>{{ $details['package_title'] }}</h2><p class="hr-muted m-0">طلب {{ $order->order_number }}</p></div></div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="hr-muted">حالة الطلب:</span> @include('web.hourly._status', ['value' => $order->status])
                            <span class="hr-muted ms-2">الدفع:</span> @include('web.hourly._status', ['value' => $order->payment_status])
                        </div>
                        <ul class="hr-meta">
                            <li><span>العنوان</span><strong>{{ $order->address_text }} — {{ $order->district }}</strong></li>
                            <li><span>العاملات لكل زيارة</span><strong>{{ $order->workers_per_visit }}</strong></li>
                            <li><span>عدد الزيارات</span><strong>{{ $order->visits_total }}</strong></li>
                            <li><span>ساعات كل زيارة</span><strong>{{ $order->hours_per_visit }}</strong></li>
                        </ul>
                    </article>

                    <article class="hr-card">
                        <div class="hr-card-title"><span class="hr-card-icon"><i class="fa-light fa-calendar-days"></i></span><h2>الزيارات</h2></div>
                        <div class="hr-table-wrap"><table class="hr-table"><thead><tr><th>الزيارة</th><th>الموعد</th><th>الحالة</th></tr></thead><tbody>
                        @foreach($order->visits->sortBy('visit_number') as $visit)<tr><td>{{ $visit->visit_number }}</td><td>{{ $visit->scheduled_start_at?->format('Y-m-d H:i') ?? 'لم تُجدول بعد' }}</td><td>@include('web.hourly._status', ['value' => $visit->display_status])
@php $financial=$details['payment_schedule']['visits'][$visit->id]??null; @endphp
@if($financial)<div class="hr-muted">الدفع: {{ ['paid'=>'مسددة','partial'=>'مسددة جزئيًا','unpaid'=>'غير مسددة'][$financial['payment_status']] }} · المتبقي {{ number_format($financial['remaining'],2) }} ر.س {{ $financial['is_overdue']?'(متأخر)':'' }}</div>@endif
@if(\App\Services\Hourly\CashCollectionService::ready() && in_array($order->payment_method,['cash','cash_and_card']))
@php $collector=app(\App\Services\Hourly\CashCollectionService::class)->collector($visit); @endphp
<small class="hr-muted">مسؤول التحصيل: {{ $collector['label'] }}</small>
@endif @if($visit->is_missed)<div class="hr-muted">يرجى التواصل مع الإدارة لإعادة الجدولة أو إلغاء الزيارة.</div>@endif</td></tr>@endforeach
                        </tbody></table></div>
                    </article>
                </div>

                <aside class="hr-card hr-side">
                    <div class="hr-card-title"><span class="hr-card-icon"><i class="fa-light fa-receipt"></i></span><h2>الفاتورة والدفع</h2></div>
<div class="hr-notice"><div>سياسة الدفع: {{ $details['payment_schedule']['policy_label'] }}<br>المستحق الآن: <strong>{{ number_format($details['payment_schedule']['due_now'],2) }} ر.س</strong><br>باقي الباقة: {{ number_format($details['payment_schedule']['remaining'],2) }} ر.س</div></div>
@if($details['payment_schedule']['installments'])<details><summary>جدول الأقساط</summary>@foreach($details['payment_schedule']['installments'] as $inst)<p>{{ $inst['due_date'] }} · {{ number_format($inst['amount'],2) }} ر.س · متبقٍ {{ number_format($inst['remaining'],2) }} {{ $inst['is_overdue']?'(متأخر)':'' }}</p>@endforeach</details>@endif
@if(\App\Services\Hourly\CashCollectionService::ready())
                        @foreach(\Illuminate\Support\Facades\DB::table('hourly_cash_collections')->where('order_id',$order->id)->get() as $cashReceipt)
                            <p><a target="_blank" rel="noopener" href="{{ route('web.hourly.cash-receipt',$cashReceipt->id) }}">سند استلام النقد HC-{{ str_pad($cashReceipt->payment_id,8,'0',STR_PAD_LEFT) }}</a> · {{ number_format($cashReceipt->amount,2) }} ر.س</p>
                        @endforeach
                    @endif
                                        @php $refunded = -(float)$order->payments->where('status','paid')->filter(fn($p)=>(float)$p->amount<0)->sum('amount'); @endphp
                    @if($refunded>0)<div class="hr-notice">المبلغ المسترد: {{ number_format($refunded,2) }} ر.س. راجع رصيد المحفظة وسجل الدفعات لمعرفة طريقة الاسترداد.</div>@endif
                    @if((float)$order->paid_amount>(float)$order->total_amount)<div class="hr-notice">يوجد مبلغ مستحق للاسترداد: {{ number_format($order->paid_amount-$order->total_amount,2) }} ر.س. يرجى التواصل مع الإدارة لتسويته.</div>@endif
                    @foreach($details['pricing_presentation']['lines'] as $line)<div class="hr-summary-line"><span>{{ $line['label'] }}</span><span>{{ number_format($line['amount'],2) }} ر.س</span></div>@endforeach
                    @if($details['pricing_presentation']['tax_line'])<div class="hr-summary-line"><span>{{ $details['pricing_presentation']['tax_line']['label'] }}</span><span>{{ number_format($details['pricing_presentation']['tax_line']['amount'],2) }} ر.س</span></div>@endif
                    <div class="hr-summary-line total"><strong>الإجمالي شامل الضريبة</strong><strong>{{ number_format($order->total_amount,2) }} ر.س</strong></div>
                    <div class="hr-summary-line"><span>المدفوع</span><span>{{ number_format($order->paid_amount,2) }} ر.س</span></div>
                    <div class="hr-summary-line"><span>طريقة الدفع</span><span>{{ $paymentLabels[$order->payment_method] ?? __('hourly.'.$order->payment_method) }}</span></div>

                    @if($canPay && $order->payment_method==='moyasar')
                        <div class="hr-notice mt-3"><i class="fa-light fa-credit-card mt-1"></i><p>المطلوب سداده الآن: <strong>{{ number_format(app(\App\Services\Hourly\HourlyPaymentService::class)->dueNow($order),2) }} ر.س</strong></p></div>
                        <form method="post" action="{{ route('web.hourly.pay',$order->id) }}">@csrf<button class="hr-btn block" type="submit"><i class="fa-light fa-lock"></i> المتابعة إلى الدفع الإلكتروني</button></form>
                    @elseif($canPay && $order->payment_method==='bank_transfer')
                        <h2 class="mt-4">التحويل البنكي</h2>
                        @if($banks->isEmpty())<div class="hr-notice"><p>تواصلي معنا للحصول على بيانات الحساب البنكي الخاصة بالتحويل لهذا الطلب.</p></div>@endif
                        @foreach($banks as $bank)<div class="hr-bank"><strong>{{ $bank->name }}</strong><p>IBAN: <bdi>{{ $bank->iban }}</bdi></p><p>رقم الحساب: <bdi>{{ $bank->account_number }}</bdi></p></div>@endforeach
                        @if($order->payments->contains('status','pending_review'))<div class="hr-notice"><i class="fa-light fa-hourglass-half mt-1"></i><p>إيصال التحويل قيد المراجعة. يتحدث وضع الدفع بعد اعتماد التحويل.</p></div>
                        @else
                            <form id="hourly-bank-form" action="{{ route('web.hourly.bank-transfer',$order->id) }}" method="post" enctype="multipart/form-data">
                                @csrf<label for="hr-receipt">إيصال التحويل (صورة أو PDF، حتى 5 ميجابايت)</label><input id="hr-receipt" name="receipt" type="file" accept="image/jpeg,image/png,application/pdf" required>
                                <label for="hr-reference">رقم مرجع التحويل</label><input id="hr-reference" name="reference_number" maxlength="255">
                                <p id="hr-bank-status" class="hr-muted mt-2" role="status"></p><button type="submit" class="hr-btn block"><i class="fa-light fa-paper-plane"></i> إرسال الإيصال للمراجعة</button>
                            </form>
                        @endif
                    @elseif($order->payment_method==='cash')<div class="hr-notice mt-3"><i class="fa-light fa-money-bill-wave mt-1"></i><p>طريقة الدفع المختارة هي الدفع عند الاستلام. تابعي حالة الطلب لمعرفة تأكيد الحجز.</p></div>@endif
                </aside>
            </div>
        </div>
    </section>
    @include('web.hourly._additional-worker')
</main>
@endsection
@push('js')<script src="{{ asset('web/assets/js/hourly-rental.js') }}?v={{ filemtime(public_path('web/assets/js/hourly-rental.js')) }}" defer></script>@endpush

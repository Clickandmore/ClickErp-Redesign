@extends('web.hourly.layout')
@section('style')
<link rel="stylesheet" href="{{ asset('web/assets/css/hourly-customer-orders.css') }}?v={{ filemtime(public_path('web/assets/css/hourly-customer-orders.css')) }}">
@endsection
@section('content')
@php
    $details = app(\App\Services\Hourly\CustomerHourlyData::class)->order($order,false);
    $canPay = ($details['payment_schedule']['policy']!=='legacy' && in_array($order->status,['active','completed','awaiting_payment'])) || (!in_array($order->status,['cancelled','expired','completed']) && $order->expires_at->isFuture());
    $canPay = $canPay && $details['payment_schedule']['due_now']>0;
    $paymentLabels = ['moyasar'=>'دفع إلكتروني (ميسر)','bank_transfer'=>'تحويل بنكي','cash'=>'الدفع عند الاستلام'];
    $schedule = $details['payment_schedule'];
    $visits = $order->visits->sortBy('visit_number');
    $doneVisits = $visits->where('status', 'completed')->count();
    $paidShare = (float) $order->total_amount > 0 ? min(100, round((float) $order->paid_amount / (float) $order->total_amount * 100)) : 100;
    $money = fn ($amount) => number_format((float) $amount, 2);
    $hours = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $refunded = -(float)$order->payments->where('status','paid')->filter(fn($p)=>(float)$p->amount<0)->sum('amount');
    $cashReady = \App\Services\Hourly\CashCollectionService::ready();
@endphp
<main>
    @include('web.hourly._header', ['title' => 'طلب ' . $order->order_number, 'crumb' => 'تفاصيل الطلب'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')
            @if(session('hourly_payment_error'))<p class="hr-error" role="alert">{{ session('hourly_payment_error') }}</p>@endif

            {{-- ملخص الطلب --}}
            <section class="od-summary">
                <div class="od-summary-head">
                    <div>
                        <a class="od-back" href="{{ route('web.hourly.orders') }}"><i class="fa-light fa-arrow-right"></i> حجوزاتي</a>
                        <h2>{{ $details['package_title'] }}</h2>
                        <span class="od-number">طلب <bdi>{{ $order->order_number }}</bdi> · بتاريخ <bdi>{{ $order->created_at?->format('Y-m-d') }}</bdi></span>
                    </div>
                    <div class="od-states">
                        <span class="od-state"><small>حالة الطلب</small>@include('web.hourly._status', ['value' => $order->status, 'label' => \App\Services\Hourly\HourlyOrderService::statusLabel($order)])</span>
                        <span class="od-state"><small>الدفع</small>@include('web.hourly._status', ['value' => $order->payment_status])</span>
                    </div>
                </div>
                <dl class="od-facts">
                    <div><dt>الزيارات</dt><dd>{{ $doneVisits }} <small>من {{ $order->visits_total }} منفذة</small></dd></div>
                    <div><dt>العاملات لكل زيارة</dt><dd>{{ $order->workers_per_visit }}</dd></div>
                    <div><dt>مدة الزيارة</dt><dd>{{ $hours($order->hours_per_visit) }} <small>ساعات</small></dd></div>
                    <div><dt>صلاحية الباقة حتى</dt><dd><bdi>{{ $order->expires_at?->format('Y-m-d') }}</bdi></dd></div>
                    <div class="is-wide"><dt>العنوان</dt><dd>{{ $order->address_text }}{{ $order->district ? ' — '.$order->district : '' }}</dd></div>
                </dl>
            </section>

            <div class="od-layout">
                {{-- الزيارات --}}
                <section class="od-card" aria-labelledby="od-visits-title">
                    <div class="od-card-head">
                        <h2 id="od-visits-title">الزيارات</h2>
                        <span class="od-count">{{ $visits->count() }} زيارة</span>
                    </div>
                    <ol class="od-visits">
                    @foreach($visits as $visit)
                        @php
                            $financial = $schedule['visits'][$visit->id] ?? null;
                            $collector = $cashReady && in_array($order->payment_method, ['cash','cash_and_card']) ? app(\App\Services\Hourly\CashCollectionService::class)->collector($visit) : null;
                            $extraActions = app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit, $order);
                        @endphp
                        <li class="od-visit">
                            <div class="od-visit-head">
                                <span class="od-visit-no">{{ $visit->visit_number }}</span>
                                <div class="od-visit-when">
                                    @if($visit->scheduled_start_at)
                                        <strong>{{ $visit->scheduled_start_at->translatedFormat('l j F Y') }}</strong>
                                        <span><bdi>{{ $visit->scheduled_start_at->format('H:i') }} – {{ $visit->scheduled_end_at?->format('H:i') }}</bdi> · {{ $hours($visit->planned_hours) }} ساعات</span>
                                    @else
                                        <strong>لم تُجدول بعد</strong>
                                        <span>{{ $hours($visit->planned_hours) }} ساعات</span>
                                    @endif
                                </div>
                                @include('web.hourly._status', ['value' => $visit->display_status])
                            </div>

                            @if($financial || $collector)
                            <div class="od-visit-meta">
                                @if($financial)
                                    <span>الدفع: <b>{{ ['paid'=>'مسددة','partial'=>'مسددة جزئيًا','unpaid'=>'غير مسددة'][$financial['payment_status']] }}</b>@if($financial['remaining'] > 0) · المتبقي <b>{{ $money($financial['remaining']) }} ر.س</b>@endif @if($financial['is_overdue'])<em class="od-late">متأخر</em>@endif</span>
                                @endif
                                @if($collector)<span>مسؤول التحصيل: <b>{{ $collector['label'] }}</b></span>@endif
                            </div>
                            @endif

                            <div class="od-visit-body">
                                @if($visit->is_missed)<p class="od-note">يرجى التواصل مع الإدارة لإعادة الجدولة أو إلغاء الزيارة.</p>@endif
                                @include('web.hourly._service-time',['serviceTime'=>$actions['service_time']??[]])
                                @include('web.hourly._early-finish', ['earlyFinishes' => $extraActions['early_finishes'] ?? []])
                                @include('web.hourly._visit-recovery')
                                <div class="od-visit-actions">
                                    @include('web.hourly._additional-worker-button')
                                    @if(!empty($extraActions['can_request_extra_hour']) && (float)$visit->planned_hours < \App\Services\Hourly\CustomerVisitActions::MAX_VISIT_HOURS)
                                        <button type="button" class="hr-btn secondary sm" data-extra-hour="{{ $visit->id }}" data-visit-number="{{ $visit->visit_number }}"><i class="fa-light fa-clock"></i> طلب ساعات إضافية</button>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                    </ol>
                </section>

                {{-- الفاتورة والدفع --}}
                <aside class="od-card od-side" aria-labelledby="od-pay-title">
                    <div class="od-card-head"><h2 id="od-pay-title">الفاتورة والدفع</h2></div>

                    <div class="od-due {{ $schedule['due_now'] > 0 ? 'has-due' : '' }}">
                        <span>المستحق الآن</span>
                        <strong>{{ $money($schedule['due_now']) }} <small>ر.س</small></strong>
                    </div>
                    <div class="od-paid">
                        <div class="od-paid-line"><span>المدفوع <b>{{ $money($order->paid_amount) }}</b> من <b>{{ $money($order->total_amount) }}</b> ر.س</span><span>{{ $paidShare }}%</span></div>
                        <span class="ho-bar"><span style="width: {{ $paidShare }}%"></span></span>
                    </div>

                    <dl class="od-lines">
                        @foreach($details['pricing_presentation']['lines'] as $line)<div><dt>{{ $line['label'] }}</dt><dd>{{ $money($line['amount']) }} ر.س</dd></div>@endforeach
                        @if($details['pricing_presentation']['tax_line'])<div><dt>{{ $details['pricing_presentation']['tax_line']['label'] }}</dt><dd>{{ $money($details['pricing_presentation']['tax_line']['amount']) }} ر.س</dd></div>@endif
                        <div class="is-total"><dt>الإجمالي شامل الضريبة</dt><dd>{{ $money($order->total_amount) }} ر.س</dd></div>
                        <div><dt>باقي قيمة الباقة</dt><dd>{{ $money($schedule['remaining']) }} ر.س</dd></div>
                        <div><dt>طريقة الدفع</dt><dd>{{ $paymentLabels[$order->payment_method] ?? __('hourly.'.$order->payment_method) }}</dd></div>
                        <div><dt>سياسة الدفع</dt><dd>{{ $schedule['policy_label'] }}</dd></div>
                    </dl>

                    @if($schedule['installments'])
                        <details class="od-more"><summary>جدول الأقساط</summary>
                            @foreach($schedule['installments'] as $inst)
                                <div class="od-inst"><bdi>{{ $inst['due_date'] }}</bdi><span>{{ $money($inst['amount']) }} ر.س</span><span class="{{ $inst['is_overdue'] ? 'od-late' : '' }}">{{ $inst['is_overdue'] ? 'متأخر' : 'متبقٍ '.$money($inst['remaining']) }}</span></div>
                            @endforeach
                        </details>
                    @endif

                    @if($cashReady)
                        @foreach(\Illuminate\Support\Facades\DB::table('hourly_cash_collections')->where('order_id',$order->id)->get() as $cashReceipt)
                            <a class="od-receipt" target="_blank" rel="noopener" href="{{ route('web.hourly.cash-receipt',$cashReceipt->id) }}"><i class="fa-light fa-receipt"></i> سند استلام النقد HC-{{ str_pad($cashReceipt->payment_id,8,'0',STR_PAD_LEFT) }}<b>{{ $money($cashReceipt->amount) }} ر.س</b></a>
                        @endforeach
                    @endif
                    @if($refunded>0)<p class="od-note">المبلغ المسترد: {{ $money($refunded) }} ر.س. راجع رصيد المحفظة وسجل الدفعات لمعرفة طريقة الاسترداد.</p>@endif
                    @if((float)$order->paid_amount>(float)$order->total_amount)<p class="od-note">يوجد مبلغ مستحق للاسترداد: {{ $money($order->paid_amount-$order->total_amount) }} ر.س. يرجى التواصل مع الإدارة لتسويته.</p>@endif

                    @if($canPay && $order->payment_method==='moyasar')
                        <p class="od-note">المطلوب سداده الآن: <strong>{{ $money(app(\App\Services\Hourly\HourlyPaymentService::class)->dueNow($order)) }} ر.س</strong></p>
                        <form method="post" action="{{ route('web.hourly.pay',$order->id) }}">@csrf<button class="hr-btn block" type="submit"><i class="fa-light fa-lock"></i> المتابعة إلى الدفع الإلكتروني</button></form>
                    @elseif($canPay && $order->payment_method==='bank_transfer')
                        <h3 class="od-sub-title">التحويل البنكي</h3>
                        @if($banks->isEmpty())<p class="od-note">تواصلي معنا للحصول على بيانات الحساب البنكي الخاصة بالتحويل لهذا الطلب.</p>@endif
                        @foreach($banks as $bank)<div class="hr-bank"><strong>{{ $bank->name }}</strong><p>IBAN: <bdi>{{ $bank->iban }}</bdi></p><p>رقم الحساب: <bdi>{{ $bank->account_number }}</bdi></p></div>@endforeach
                        @if($order->payments->contains('status','pending_review'))<p class="od-note"><i class="fa-light fa-hourglass-half"></i> إيصال التحويل قيد المراجعة. يتحدث وضع الدفع بعد اعتماد التحويل.</p>
                        @else
                            <form id="hourly-bank-form" class="od-form" action="{{ route('web.hourly.bank-transfer',$order->id) }}" method="post" enctype="multipart/form-data">
                                @csrf<label for="hr-receipt">إيصال التحويل (صورة أو PDF، حتى 5 ميجابايت)</label><input id="hr-receipt" name="receipt" type="file" accept="image/jpeg,image/png,application/pdf" required>
                                <label for="hr-reference">رقم مرجع التحويل</label><input id="hr-reference" name="reference_number" maxlength="255">
                                <p id="hr-bank-status" class="hr-muted mt-2" role="status"></p><button type="submit" class="hr-btn block"><i class="fa-light fa-paper-plane"></i> إرسال الإيصال للمراجعة</button>
                            </form>
                        @endif
                    @elseif($order->payment_method==='cash')<p class="od-note"><i class="fa-light fa-money-bill-wave"></i> طريقة الدفع المختارة هي الدفع عند الاستلام. تابعي حالة الطلب لمعرفة تأكيد الحجز.</p>@endif
                </aside>
            </div>
        </div>
    </section>
    @include('web.hourly._additional-worker')
    @include('web.hourly._extra-hours')
</main>
@endsection
@push('js')<script src="{{ asset('web/assets/js/hourly-rental.js') }}?v={{ filemtime(public_path('web/assets/js/hourly-rental.js')) }}" defer></script>@endpush

@push('js')<script src="{{ asset('web/assets/js/hourly-visit-recovery.js') }}?v={{ filemtime(public_path('web/assets/js/hourly-visit-recovery.js')) }}" defer></script>@endpush

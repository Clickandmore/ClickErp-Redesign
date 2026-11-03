@php
    $collectionSchedule=app(\App\Services\Hourly\PaymentScheduleService::class)->forOrder($order);
    $collectibleNow=$collectionSchedule['policy']==='legacy'?$collectionSchedule['remaining']:$collectionSchedule['due_now'];
    $awaitingConfirmation = $order->status === 'pending_confirmation';
    $canConfirm = AdminGuardHasPerm('hourly_orders_confirm') && \App\Services\Hourly\HourlyOrderService::confirmable($order);
    $canCollect = $collectibleNow>0 && in_array($order->status,['pending_confirmation','active','completed','awaiting_payment'],true) && AdminGuardHasPerm('hourly_orders_collect') && $order->payment_status != 'paid' && !in_array($order->payment_method, ['moyasar', 'customer_balance']);
@endphp
@if($canConfirm)
    <div class="om-alert">
        <i class="fa fa-hourglass-half"></i>
        <div>
            <div>الطلب بانتظار تأكيد الصرّاف؛ لا تُفعَّل زيارات الرصيد قبل التأكيد.</div>
            <form method="POST" action="{{ route('admin.hourly-orders.confirm',$order) }}" class="mt-2">@csrf
                <button class="om-btn om-btn-warn" style="width:auto;margin:0"><i class="fa fa-circle-check"></i> تأكيد الصرّاف</button>
            </form>
        </div>
    </div>
@elseif($awaitingConfirmation && $order->payment_method === 'moyasar')
    <div class="om-alert">
        <i class="fa fa-credit-card"></i>
        <div>الطلب بانتظار إتمام العميل للدفع عبر رابط ميسر، وسيتفعّل تلقائيًا بعد تأكيد البوابة.</div>
    </div>
@elseif($awaitingConfirmation && $order->payment_method === 'bank_transfer')
    <div class="om-alert">
        <i class="fa fa-building-columns"></i>
        <div>{{ $order->payment_status === 'pending_review' ? 'التحويل البنكي بانتظار المراجعة من سجل الدفعات.' : 'الطلب بانتظار رفع إثبات التحويل البنكي أو تسجيل التحصيل.' }}</div>
    </div>
@endif
@if($order->payment_method === 'customer_balance' && $collectibleNow > 0 && $order->payment_status !== 'paid' && in_array($order->status,['pending_confirmation','active','completed','awaiting_payment'],true) && AdminGuardHasPerm('hourly_orders_collect'))
    @php $customerBalance = (float) ($order->user?->hourly_wallet_balance ?? 0); $balanceDue = min($collectibleNow, (float) $order->total_amount - (float) $order->paid_amount); @endphp
    {{-- طلب طريقة دفعه «رصيد العميل» ولم يُخصم بعد (غُيّرت طريقته بعد الحجز): الخصم وسند القبض بخطوة واحدة. --}}
    <form method="POST" action="{{ route('admin.hourly-orders.charge-balance',$order) }}" class="om-collect" onsubmit="return confirm('خصم {{ number_format($balanceDue,2) }} ر.س من رصيد العميل وتسجيل السداد؟');">@csrf
        <div>رصيد العميل: <strong>{{ number_format($customerBalance,2) }} ر.س</strong> — المستحق الآن: <strong>{{ number_format($balanceDue,2) }} ر.س</strong>
            @if($customerBalance < $balanceDue)<div class="text-danger small mt-1">الرصيد غير كافٍ؛ اشحن رصيد العميل أو غيّر طريقة الدفع.</div>@endif</div>
        <div><button class="om-btn om-btn-green" style="margin:0" {{ $customerBalance < $balanceDue ? 'disabled' : '' }}><i class="fa fa-wallet"></i> خصم من رصيد العميل</button></div>
    </form>
@endif
@if($canCollect)
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.hourly-orders.collect',$order) }}" class="om-collect">@csrf
        <div><label for="collect-amount">المبلغ المحصل — المستحق الآن {{ number_format($collectibleNow,2) }} ر.س</label>
            <input id="collect-amount" type="number" step="0.01" min="0.01" max="{{ $collectibleNow }}" name="amount" class="form-control" placeholder="0.00" required></div>
        @if($order->payment_method === 'cash_and_card')
            <div><label for="collect-method">طريقة تحصيل هذه الدفعة</label>
                <select id="collect-method" name="method" class="form-select" required><option value="cash">نقدي</option><option value="card_on_delivery">بطاقة</option></select></div>
        @endif
        <div><label for="collect-attachment">إثبات التحويل <span class="text-muted">{{ $order->payment_method === 'bank_transfer' ? '(مطلوب)' : '(اختياري)' }}</span></label>
            <input id="collect-attachment" type="file" name="attachment" class="form-control" accept=".pdf,.png,.jpg,.jpeg" {{ $order->payment_method === 'bank_transfer' ? 'required' : '' }}></div>
        <div><button class="om-btn om-btn-green" style="margin:0"><i class="fa fa-receipt"></i> تسجيل التحصيل</button></div>
    </form>
@endif

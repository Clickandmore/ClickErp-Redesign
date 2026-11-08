<div class="card my-4" id="customer-expiry-responses" dir="rtl">
    <div class="card-header"><h5 class="mb-0">طلبات العميل من رابط انتهاء الإيجار</h5></div>
    <div class="card-body">
        @forelse($customerExpiryResponses as $response)
            <div class="border rounded p-3 mb-3">
                <strong>{{ $response->response === 'return' ? 'طلب تسليم العاملة' : 'طلب تمديد العقد' }}</strong>
                <span class="text-muted"> — {{ $response->responded_at->format('Y-m-d H:i') }}</span>
                @if($response->response === 'return')
                    <div>{{ $contract->closed_at ? 'أغلق العقد؛ راجع إجراءات الاستلام والإغلاق المسجلة.' : 'بانتظار الموظف لترتيب الاستلام والتسوية وإغلاق العقد.' }}</div>
                    @if($response->preferred_return_date)<div>الموعد المقترح: {{ $response->preferred_return_date->format('Y-m-d') }}</div>@endif
                @elseif($response->extension_id)
                    <div class="text-success">تم الدفع وتسجيل التمديد #{{ $response->extension_id }}</div>
                @else
                    <div>{{ $response->payment_status === 'paid' ? 'دفعة مؤكدة تحتاج مراجعة الموظف' : ($response->renewal_quote ? 'بانتظار إتمام الدفع؛ لم يسجل تمديد' : 'طلب تمديد يحتاج تحديد المدة والسعر بواسطة الموظف') }}</div>
                @endif
                @if($response->renewal_quote)<div>المدة: {{ $response->renewal_quote['days'] }} يوم — القيمة: {{ number_format($response->renewal_quote['amount'], 2) }} ريال — تاريخ النهاية: {{ $response->renewal_quote['end_date'] }}</div>@endif
                @if($response->customer_payment_method)<div>طريقة الدفع: {{ \App\Support\NotificationAlertSettings::renewalPaymentMethodOptions()[$response->customer_payment_method] ?? $response->customer_payment_method }}</div>@endif
                @if($response->response_notes)<div style="white-space:pre-wrap">{{ $response->response_notes }}</div>@endif
                @if($response->contract_effect_error)<div class="text-danger">{{ $response->contract_effect_error }}</div>@endif
            </div>
        @empty
            <p class="text-muted mb-0">لا توجد ردود من العميل على تنبيهات انتهاء هذا العقد.</p>
        @endforelse
    </div>
</div>

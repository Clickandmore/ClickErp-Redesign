{{-- تأكيد الطلب من القوائم بنفس قاعدة صفحة الطلب: الدفع الآجل يُفعَّل دون سداد، والمسبق ينتظر التحصيل. يتطلب: $order --}}
@if($order->status === 'pending_confirmation')
    @php $confirmBlocker = \App\Services\Hourly\HourlyOrderService::confirmBlocker($order); @endphp
    @if(!$confirmBlocker && AdminGuardHasPerm('hourly_orders_confirm'))
        <form method="POST" action="{{ route('admin.hourly-orders.confirm', $order) }}" class="m-0" onsubmit="return confirm('تأكيد الطلب {{ $order->order_number }} وتفعيله؟ يُحصَّل المبلغ عند الزيارة.')">
            @csrf
            <button class="btn btn-sm btn-warning w-100" style="white-space:nowrap"><i class="fa fa-circle-check"></i> تأكيد الصرّاف</button>
        </form>
    @elseif($confirmBlocker)
        <span class="badge bg-light text-dark border" style="white-space:normal" title="{{ $confirmBlocker }}"><i class="fa fa-sack-dollar"></i> بانتظار السداد</span>
    @endif
@endif

{{-- طلبات الساعات للعميل. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $money = fn ($value) => number_format((float) $value, 2);
@endphp
@if($orders->isEmpty())
    <div class="hc-empty">{{ $c('orders.empty') }}</div>
@else
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('orders.number') }}</th><th>{{ $c('orders.created') }}</th><th>{{ $c('orders.status') }}</th><th>{{ $c('orders.visits') }}</th><th>{{ $c('orders.total') }}</th><th>{{ $c('orders.paid') }}</th><th>{{ $c('orders.payment') }}</th></tr></thead>
        <tbody>
        @foreach($orders as $order)
            <tr>
                <td><a class="hc-name" href="{{ route('admin.hourly-orders.show', $order) }}">#{{ $order->order_number }}</a></td>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                <td><span class="hc-badge">{{ \Illuminate\Support\Facades\Lang::has('hourly_customers.order_statuses.'.$order->status) ? $c('order_statuses.'.$order->status) : $order->status }}</span></td>
                <td class="hc-money">{{ $order->visits_count }}</td>
                <td class="hc-money">{{ $money($order->total_amount) }}</td>
                <td class="hc-money is-pos">{{ $money($order->paid_amount) }}</td>
                <td>{{ \Illuminate\Support\Facades\Lang::has('hourly_customers.payment_statuses.'.$order->payment_status) ? $c('payment_statuses.'.$order->payment_status) : $order->payment_status }} · {{ \Illuminate\Support\Facades\Lang::has('hourly_customers.methods.'.$order->payment_method) ? $c('methods.'.$order->payment_method) : $order->payment_method }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="mt-2">{{ $orders->links('admin.hourly_customers._pagination') }}</div>
@endif

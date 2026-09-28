{{-- الرصيد: حركات المحفظة (إيداع/استرداد/خصم لطلب) والدفعات المسجلة على طلبات العميل. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $money = fn ($value) => number_format((float) $value, 2);
    // الحركات السابقة لهذه الشاشة بلا نوع مخزَّن: المرتبطة بطلب خصم، والموجبة استرداد.
    $walletType = fn ($t) => $t->type ?: ($t->hourly_rental_order_id ? 'order_payment' : ((float) $t->amount > 0 ? 'refund' : 'adjustment'));
    $who = fn ($type, $id) => $id ? ($people[$type][$id] ?? '#'.$id) : '—';
    $label = fn (string $group, ?string $key) => $key ? (\Illuminate\Support\Facades\Lang::has('hourly_customers.'.$group.'.'.$key) ? $c($group.'.'.$key) : $key) : '—';
@endphp
<h6 class="fw-bold mb-2"><i class="fa fa-wallet"></i> {{ $c('ledger.wallet_title') }}</h6>
@if($wallet->isEmpty())
    <div class="hc-empty">{{ $c('ledger.empty') }}</div>
@else
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('ledger.date') }}</th><th>{{ $c('ledger.type') }}</th><th>{{ $c('ledger.amount') }}</th><th>{{ $c('ledger.balance_after') }}</th><th>{{ $c('ledger.method') }}</th><th>{{ $c('ledger.collected_by') }}</th><th>{{ $c('ledger.order') }}</th><th>{{ $c('ledger.vat') }}</th><th>{{ $c('ledger.description') }}</th></tr></thead>
        <tbody>
        @foreach($wallet as $t)
            <tr>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ \Carbon\Carbon::parse($t->paid_at ?? $t->created_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $c('ledger.types.'.$walletType($t)) }}</td>
                <td class="hc-money {{ $t->amount >= 0 ? 'is-pos' : 'is-neg' }}">{{ $money($t->amount) }}</td>
                <td class="hc-money">{{ $money($t->balance_after) }}</td>
                <td>{{ $label('methods', $t->payment_method) }}</td>
                <td>{{ $who($t->collected_by_type ?? 'admin', $t->collected_by_id ?? $t->admin_id) }}</td>
                <td>@if($t->order_number)#{{ $t->order_number }}@else — @endif</td>
                <td>{{ $t->vat_rate !== null ? rtrim(rtrim(number_format((float) $t->vat_rate, 2), '0'), '.').'%' : '—' }}</td>
                <td>{{ $t->description ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="mt-2">{{ $wallet->links('admin.hourly_customers._pagination') }}</div>
@endif

<h6 class="fw-bold mt-4 mb-2"><i class="fa fa-receipt"></i> {{ $c('ledger.payments_title') }}</h6>
@if($payments->isEmpty())
    <div class="hc-empty">{{ $c('ledger.empty') }}</div>
@else
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('ledger.date') }}</th><th>{{ $c('ledger.order') }}</th><th>{{ $c('ledger.amount') }}</th><th>{{ $c('ledger.method') }}</th><th>{{ $c('ledger.status') }}</th><th>{{ $c('ledger.collected_by') }}</th><th>{{ $c('ledger.description') }}</th><th>سند القبض</th></tr></thead>
        <tbody>
        @foreach($payments as $p)
            @php $payload = json_decode((string) $p->provider_payload, true) ?: []; @endphp
            <tr>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ \Carbon\Carbon::parse($p->paid_at ?? $p->created_at)->format('d/m/Y H:i') }}</td>
                <td><a href="{{ route('admin.hourly-orders.show', $p->hourly_rental_order_id) }}">#{{ $p->order_number }}</a></td>
                <td class="hc-money {{ $p->amount >= 0 ? 'is-pos' : 'is-neg' }}">{{ $money($p->amount) }}</td>
                <td>{{ $label('methods', $p->method) }}</td>
                <td>{{ $label('payment_statuses', $p->status) }}</td>
                <td>{{ $who($p->collected_by_type ?? 'admin', $p->collected_by_id) }}</td>
                <td>{{ $payload['description'] ?? $payload['reason'] ?? '—' }}</td>
                <td>@if($p->status === 'paid' && (float) $p->amount > 0)<a class="btn btn-sm btn-outline-primary text-nowrap" target="_blank" href="{{ route('admin.hourly-orders.receipt', [$p->hourly_rental_order_id, $p->id]) }}"><i class="fa fa-receipt"></i> عرض السند</a>@else — @endif</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="mt-2">{{ $payments->links('admin.hourly_customers._pagination') }}</div>
@endif

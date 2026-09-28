{{-- سجل التعديلات على بيانات العميل المشتركة وملف الساعات (من activity_log). --}}
@php $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace); @endphp
@if($logs->isEmpty())
    <div class="hc-empty">{{ $c('logs.empty') }}</div>
@else
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('logs.when') }}</th><th>{{ $c('logs.by') }}</th><th>{{ $c('logs.event') }}</th><th>{{ $c('logs.changes') }}</th></tr></thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ \Carbon\Carbon::parse($log['at'])->format('d/m/Y H:i') }}</td>
                <td>{{ $log['by'] ?? $c('logs.system') }}</td>
                <td>{{ \Illuminate\Support\Facades\Lang::has('hourly_customers.logs.events.'.$log['event']) ? $c('logs.events.'.$log['event']) : $log['event'] }}</td>
                <td>
                    <ul class="hc-changes">
                        @foreach($log['changes'] as $change)
                            <li><code>{{ $change['field'] }}</code>: <span class="hc-muted">{{ is_scalar($change['old']) ? $change['old'] : ($change['old'] === null ? '—' : json_encode($change['old'], JSON_UNESCAPED_UNICODE)) }}</span> ← {{ is_scalar($change['new']) ? $change['new'] : ($change['new'] === null ? '—' : json_encode($change['new'], JSON_UNESCAPED_UNICODE)) }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif

{{-- «دمج الحسابات» من ملف العميل: نفس MergeCustomersService المستخدم في صفحة الحسابات المكررة. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $formUrl = fn (array $ids) => route('admin.hourly-customers.merge-form').'?'.http_build_query(['ids' => array_values($ids), 'back' => $customer->id]);
@endphp
<div class="hc-note mb-3"><i class="fa fa-circle-info mt-1"></i><div>{{ $c('merge.hint') }}</div></div>

@if($deleted)
    <div class="hc-note is-warn mb-3"><i class="fa fa-lock mt-1"></i><div>{{ $c('merge.primary_deleted') }}</div></div>
@else
    @forelse($groups as $group)
        <div class="hc-group-card mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="hc-chips">@foreach($group['reasons'] as $reason)<span class="hc-badge">{{ $c('merge.reasons.'.$reason) }}</span>@endforeach</div>
                <a class="hc-btn is-primary is-sm" href="{{ $formUrl($group['members']->pluck('id')->all()) }}"><i class="fa fa-code-merge"></i> {{ $c('merge.open') }}</a>
            </div>
            @foreach($group['members'] as $member)
                <div class="hc-member">
                    <b>#{{ $member['id'] }}</b>
                    <span><a href="{{ route('admin.hourly-customers.show', $member['id']) }}">{{ $member['name'] }}</a>
                        <span class="hc-muted" dir="ltr">{{ $member['phone'] }}{{ $member['email'] ? ' · '.$member['email'] : '' }}</span></span>
                    <span class="hc-muted">{{ $c('merge.orders', ['count' => $member['orders']]) }}{{ (int) $member['id'] === (int) $customer->id ? ' · ★' : '' }}</span>
                </div>
            @endforeach
        </div>
    @empty
        <div class="hc-empty mb-3">{{ $c('merge.none_for_customer') }}</div>
    @endforelse

    <form method="GET" action="{{ route('admin.hourly-customers.merge-form') }}" class="d-flex flex-wrap gap-2 align-items-end mb-4">
        <input type="hidden" name="ids[]" value="{{ $customer->id }}"><input type="hidden" name="back" value="{{ $customer->id }}">
        <div class="hc-field"><label for="hc-merge-other">{{ $c('merge.add_by_id') }}</label>
            <input class="form-control" type="number" min="1" id="hc-merge-other" name="ids[]" placeholder="{{ $c('merge.customer_id') }}" required dir="ltr"></div>
        <button class="hc-btn"><i class="fa fa-code-merge"></i> {{ $c('merge.open') }}</button>
    </form>
@endif

<h6 style="font-weight:800"><i class="fa fa-clock-rotate-left"></i> {{ $c('merge.history') }}</h6>
@if($history->isEmpty())
    <div class="hc-empty">{{ $c('logs.empty') }}</div>
@else
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('logs.when') }}</th><th>{{ $c('logs.event') }}</th><th>{{ $c('logs.by') }}</th><th>{{ $c('merge.reason') }}</th></tr></thead>
        <tbody>
        @foreach($history as $row)
            <tr>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                <td>@if((int) $row->primary_user_id === (int) $customer->id)
                        <a href="{{ route('admin.hourly-customers.show', $row->merged_user_id) }}">{{ $c('merge.merged_from', ['id' => $row->merged_user_id]) }}</a>
                    @else
                        <a href="{{ route('admin.hourly-customers.show', $row->primary_user_id) }}">{{ $c('merge.merged_into', ['id' => $row->primary_user_id]) }}</a>
                    @endif</td>
                <td>{{ $people['admin'][$row->admin_id] ?? '—' }}</td>
                <td>{{ $row->reason ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif

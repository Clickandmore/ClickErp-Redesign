{{-- جدول زيارات لقسمي «غير مخصص» (تنقصها عاملات) و«ألغيت» (مع سبب الإلغاء). --}}
@php $sep = app()->getLocale() === 'ar' ? '، ' : ', '; @endphp
@if(!$rows)
    <div class="rt-empty">{{ $empty }}</div>
@else
<div class="table-responsive">
<table class="rt-table">
    <thead><tr>
        <th>{{ __('hourly_routing.table.task') }}</th>
        <th>{{ $mode === 'cancelled' ? __('hourly_routing.table.status') : __('hourly_routing.understaffed_section.title') }}</th>
        @if($mode === 'cancelled')<th>{{ __('hourly_routing.cancelled_section.reason') }}</th>@endif
    </tr></thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>
                <div class="rt-task-main">
                    <span class="rt-time">{{ $row['window'] }}</span>
                    <b><i class="fa fa-person-dress"></i>
                        @forelse($row['worker_list'] as $worker)<a href="{{ $worker['url'] }}" target="_blank" rel="noopener">{{ $worker['name'] }}</a>{{ $loop->last ? '' : $sep }}@empty{{ __('hourly_routing.no_worker') }}@endforelse
                    </b>
                </div>
                <div class="rt-sub"><span class="rt-customer"><i class="fa fa-user"></i> <a href="{{ $row['order_url'] }}" target="_blank" rel="noopener">{{ $row['customer'] ?? '—' }}</a> - <a href="{{ $row['order_url'] }}" target="_blank" rel="noopener">#{{ $row['order_number'] }}</a> · <a href="{{ $row['visit_url'] }}" target="_blank" rel="noopener">{{ __('hourly_routing.visit') }} {{ $row['visit_number'] }}</a></span></div>
            </td>
            @if($mode === 'cancelled')
                <td><span class="rt-status is-failed"><i class="fa fa-ban"></i> {{ $row['status_label'] }}</span></td>
                <td class="{{ $row['reason'] ? 'rt-reason' : 'rt-sub' }}">{{ $row['reason'] ?? __('hourly_routing.cancelled_section.no_reason') }}</td>
            @else
                <td><span class="rt-status is-pending"><i class="fa fa-user-plus"></i> {{ __('hourly_routing.understaffed_section.workers', ['filled' => $row['filled'], 'required' => $row['required']]) }}</span></td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif

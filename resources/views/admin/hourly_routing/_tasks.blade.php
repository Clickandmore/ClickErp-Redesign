{{-- جدول مهام السائق: قائمة واحدة (قابلة للسحب) أو مجمّعة بالساعة. المهام المنفذة لا تُحدَّد. --}}
@php
    $sep = app()->getLocale() === 'ar' ? '، ' : ', ';
    $leading = $selectable || $sortable;
@endphp
<div class="table-responsive">
<table class="rt-table">
    <thead><tr>
        @if($leading)<th class="rt-lead"></th>@endif
        <th>{{ __('hourly_routing.table.task') }}</th><th>{{ __('hourly_routing.table.status') }}</th><th>{{ __('hourly_routing.table.updated') }}</th>
        <th><i class="fa fa-circle-info" title="{{ __('hourly_routing.table.distance_hint') }}"></i> {{ __('hourly_routing.table.distance') }}</th><th>{{ __('hourly_routing.table.actions') }}</th>
    </tr></thead>
    <tbody @if($sortable) data-sortable="{{ $driverId }}" @endif>
    @php $band = null; @endphp
    @foreach($tasks as $task)
        @if($bands && substr($task['clock'], 0, 2) !== $band)
            @php $band = substr($task['clock'], 0, 2); $inBand = collect($tasks)->filter(fn ($t) => substr($t['clock'], 0, 2) === $band)->count(); @endphp
            <tr class="rt-band"><td colspan="{{ $leading ? 6 : 5 }}"><i class="fa fa-clock"></i> {{ $band }}:00 – {{ sprintf('%02d', ((int) $band + 1) % 24) }}:00 · {{ __('hourly_routing.band_tasks', ['count' => $inBand]) }}</td></tr>
        @endif
        <tr data-key="{{ $task['key'] }}">
            @if($leading)
                <td class="rt-lead">
                    @if($sortable)<span class="rt-handle" title="{{ __('hourly_routing.drag') }}" aria-label="{{ __('hourly_routing.drag') }}"><i class="fa fa-grip-vertical"></i></span>@endif
                    @if($selectable)
                        <input type="checkbox" class="rt-check" data-select-task="{{ $task['key'] }}" data-group="{{ $driverId }}" {{ $task['movable'] ? '' : 'disabled' }}
                               title="{{ $task['movable'] ? __('hourly_routing.select.task') : __('hourly_routing.select.done_locked') }}" aria-label="{{ __('hourly_routing.select.task') }}">
                    @endif
                </td>
            @endif
            <td>
                <div class="rt-task-main">
                    <span class="rt-time">{{ $task['clock'] }}</span>
                    <span class="rt-kind is-{{ $task['role'] }}"><i class="fa {{ $task['role'] === 'delivery' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i> {{ $task['role_label'] }}</span>
                    <b><i class="fa fa-person-dress"></i>
                        @forelse($task['worker_list'] as $worker)<a href="{{ $worker['url'] }}" target="_blank" rel="noopener">{{ $worker['name'] }}</a>{{ $loop->last ? '' : $sep }}@empty{{ __('hourly_routing.no_worker') }}@endforelse
                    </b>
                </div>
                <div class="rt-sub">
                    <span class="rt-customer"><i class="fa fa-user"></i> <a href="{{ $task['order_url'] }}" target="_blank" rel="noopener">{{ $task['customer'] ?? '—' }}</a> - <a href="{{ $task['order_url'] }}" target="_blank" rel="noopener">#{{ $task['order_number'] }}</a> · <a href="{{ $task['visit_url'] }}" target="_blank" rel="noopener">{{ __('hourly_routing.visit') }} {{ $task['visit_number'] }}</a></span>
                    @if($task['city'] || $task['district']) · {{ collect([$task['city'], $task['district']])->filter()->implode($sep) }}@endif
                </div>
                <div class="rt-sub">{{ $task['address'] }}</div>
            </td>
            <td><span class="rt-status is-{{ $task['state'] }}"><i class="fa fa-clock"></i> {{ $task['state_label'] }}</span></td>
            <td class="rt-time">{{ $task['updated'] ?? '-' }}</td>
            <td class="rt-km {{ $task['far'] ? 'is-far' : '' }}" @if($task['far']) title="{{ __('hourly_routing.far', ['km' => number_format($task['km'], 2), 'limit' => $warning]) }}" @endif>
                @if($task['far'])<i class="fa fa-triangle-exclamation"></i>@endif{{ $task['km'] !== null ? number_format($task['km'], 2).' '.__('hourly_routing.km') : '—' }}
            </td>
            <td>
                <span class="rt-icons">
                    <button type="button" class="rt-icon is-map" data-location="{{ $task['key'] }}" title="{{ __('hourly_routing.location') }}" aria-label="{{ __('hourly_routing.location') }}" {{ $task['lat'] === null ? 'disabled' : '' }}><i class="fa fa-location-dot"></i></button>
                    @if($selectable)
                        <button type="button" class="rt-icon is-transfer" data-transfer-task="{{ $task['key'] }}" title="{{ $task['movable'] ? __($task['driver_id'] ? 'hourly_routing.transfer_task' : 'hourly_routing.assign_task') : __('hourly_routing.task_finished') }}" {{ $task['movable'] ? '' : 'disabled' }}><i class="fa fa-share"></i></button>
                    @endif
                </span>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

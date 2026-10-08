@extends('agent.layout')

@php $isAr = app()->getLocale() === 'ar'; @endphp

@section('page-title', $isAr ? 'إرسالات المكتب الخارجي' : 'External Office Dispatches')

@section('style')
    <style>
        .dispatch-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .dispatch-stat-btn {
            background: #fff;
            border: 1px solid var(--agent-line);
            border-radius: 10px;
            padding: 15px;
            text-align: start;
            cursor: pointer;
            transition: all 0.2s;
            color: inherit;
            text-decoration: none;
            display: block;
        }
        .dispatch-stat-btn:hover {
            border-color: var(--agent-primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .dispatch-stat-btn.active {
            border-color: var(--agent-primary);
            background: #f0f9f9;
        }
        .dispatch-stat-label {
            display: block;
            font-size: 13px;
            color: var(--agent-muted);
            margin-bottom: 5px;
        }
        .dispatch-stat-value {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: var(--agent-primary-dark);
        }
        @media (max-width: 768px) {
            .dispatch-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <section class="agent-section">
        <div class="dispatch-stats">
            <a href="{{ route('agent.dispatches.index', ['dispatch_filter' => 'all']) }}" 
               class="dispatch-stat-btn {{ $dispatchFilter === 'all' ? 'active' : '' }}">
                <span class="dispatch-stat-label">{{ $isAr ? 'كل العقود' : 'All Contracts' }}</span>
                <span class="dispatch-stat-value">{{ $stats['all'] }}</span>
            </a>
            <a href="{{ route('agent.dispatches.index', ['dispatch_filter' => 'sent']) }}" 
               class="dispatch-stat-btn {{ $dispatchFilter === 'sent' ? 'active' : '' }}">
                <span class="dispatch-stat-label">{{ $isAr ? 'العقود المرسلة' : 'Sent Contracts' }}</span>
                <span class="dispatch-stat-value">{{ $stats['sent'] }}</span>
            </a>
            <a href="{{ route('agent.dispatches.index', ['dispatch_filter' => 'not_sent']) }}" 
               class="dispatch-stat-btn {{ $dispatchFilter === 'not_sent' ? 'active' : '' }}">
                <span class="dispatch-stat-label">{{ $isAr ? 'العقود غير المرسلة' : 'Not Sent Contracts' }}</span>
                <span class="dispatch-stat-value">{{ $stats['not_sent'] }}</span>
            </a>
        </div>

        <form action="{{ route('agent.dispatches.index') }}" method="GET" class="agent-filter">
            <input type="hidden" name="dispatch_filter" value="{{ $dispatchFilter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'بحث برقم العقد، العميل، العاملة...' : 'Search by contract, client, worker...' }}">
            <select name="status">
                <option value="">{{ $isAr ? 'كل حالات العقود' : 'All Contract Statuses' }}</option>
                <option value="pending_maid_selection" {{ request('status') === 'pending_maid_selection' ? 'selected' : '' }}>{{ $isAr ? 'بانتظار اختيار العاملة' : 'Pending Maid Selection' }}</option>
                <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>{{ $isAr ? 'جديد' : 'New' }}</option>
                <option value="msaned" {{ request('status') === 'msaned' ? 'selected' : '' }}>{{ $isAr ? 'مساند' : 'Msaned' }}</option>
                <option value="tafwed" {{ request('status') === 'tafwed' ? 'selected' : '' }}>{{ $isAr ? 'تفويض' : 'Tafwed' }}</option>
                <option value="taffez" {{ request('status') === 'taffez' ? 'selected' : '' }}>{{ $isAr ? 'تفييز' : 'Taffez' }}</option>
                <option value="tazker" {{ request('status') === 'tazker' ? 'selected' : '' }}>{{ $isAr ? 'تذكرة' : 'Tazker' }}</option>
            </select>
            <button type="submit" class="agent-button">
                <i class="fa fa-magnifying-glass"></i>
                <span>{{ $isAr ? 'بحث' : 'Search' }}</span>
            </button>
        </form>

        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ $isAr ? 'العميل' : 'Client' }}</th>
                        <th>{{ $isAr ? 'العاملة' : 'Worker' }}</th>
                        <th>{{ $isAr ? 'حالة العقد' : 'Contract Status' }}</th>
                        <th>{{ $isAr ? 'حالة فاتورة المورد' : 'Dispatch Status' }}</th>
                        <th>{{ $isAr ? 'تاريخ فاتورة المورد' : 'Dispatch Date' }}</th>
                        <th>{{ $isAr ? 'محطة الوصول' : 'Arrival Station' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        @php $dispatchState = $contract->getExternalOfficeDispatchState(); @endphp
                        <tr>
                            <td>{{ $contract->id }}</td>
                            <td>
                                <strong>{{ $contract->user?->name ?: '-' }}</strong>
                                <div style="font-size: 11px; color: var(--agent-muted);">{{ $contract->user?->national_num }}</div>
                            </td>
                            <td>
                                <strong>{{ $contract->maid?->name ?: ($contract->info?->manual_maid_name ?: '-') }}</strong>
                                <div style="font-size: 11px; color: var(--agent-muted);">{{ $isAr ? 'جواز:' : 'Pass:' }} {{ $contract->maid?->passport_num ?: ($contract->info?->manual_maid_passport_num ?: '-') }}</div>
                            </td>
                            <td>
                                <span class="agent-badge warning">{{ getContractStatus($contract->status) }}</span>
                            </td>
                            <td>
                                @php
                                    $badgeClass = match ($dispatchState['code']) {
                                        'completed' => '',
                                        'outdated' => 'warning',
                                        'incomplete' => 'warning',
                                        'missing' => 'warning',
                                        default => 'warning',
                                    };
                                    // Custom colors for dispatch states in agent panel if needed
                                @endphp
                                <span class="agent-badge {{ $badgeClass }}" title="{{ $dispatchState['message'] }}">
                                    {{ $dispatchState['label'] }}
                                </span>
                            </td>
                            <td>{{ optional($contract->externalOfficeDispatch?->dispatch_date)->format('Y-m-d') ?: '-' }}</td>
                            <td>{{ $contract->resolveExternalOfficeDispatchArrivalStation() ?: '-' }}</td>
                            <td>
                                <a href="{{ route('agent.contracts.show', $contract) }}" class="agent-button">
                                    <i class="fa fa-eye"></i>
                                    <span>{{ $isAr ? 'عرض' : 'View' }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">{{ $isAr ? 'لا توجد سجلات مطابقة.' : 'No matching records were found.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="agent-pagination">
            {{ $contracts->links() }}
        </div>
    </section>
@endsection

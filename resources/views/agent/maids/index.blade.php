@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
    $tabs = [
        'all' => [
            'label' => $isAr ? 'كل العمالة' : 'All Workers',
            'icon' => 'fa-users',
        ],
        'available' => [
            'label' => $isAr ? 'العمالة المتاحة' : 'Available Workers',
            'icon' => 'fa-circle-check',
        ],
        'contracted' => [
            'label' => $isAr ? 'العمالة المتعاقد عليها' : 'Contracted Workers',
            'icon' => 'fa-file-signature',
        ],
    ];
    $baseQuery = request()->except(['page', 'worker_filter']);
@endphp

@section('page-title', $isAr ? 'العمالة' : 'Workers')

@section('content')
    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'العمالة' : 'Workers' }}</h3>
            <a href="{{ route('agent.maids.create') }}" class="agent-button" style="background: var(--agent-accent);">
                <i class="fa fa-plus"></i>
                <span>{{ $isAr ? 'إضافة سيرة جديدة' : 'Add New CV' }}</span>
            </a>
            <a href="{{ route('agent.maids.bulk-import.create') }}" class="agent-button" style="background:#198754;">
                <i class="fa fa-file-import"></i>
                <span>{{ $isAr ? 'رفع من ملف' : 'Import from File' }}</span>
            </a>
        </div>

        <div class="agent-worker-tabs">
            @foreach ($tabs as $filterKey => $tab)
                @php
                    $tabQuery = $baseQuery;
                    if ($filterKey !== 'all') {
                        $tabQuery['worker_filter'] = $filterKey;
                    }
                @endphp
                <a href="{{ route('agent.maids.index', $tabQuery) }}"
                    class="agent-worker-tab {{ $workerFilter === $filterKey ? 'active' : '' }}">
                    <span>
                        <i class="fa {{ $tab['icon'] }}"></i>
                        {{ $tab['label'] }}
                    </span>
                    <strong>{{ $filterCounts[$filterKey] ?? 0 }}</strong>
                </a>
            @endforeach
        </div>

        <form action="{{ route('agent.maids.index') }}" method="GET" class="agent-filter agent-maids-filter">
            @if ($workerFilter !== 'all')
                <input type="hidden" name="worker_filter" value="{{ $workerFilter }}">
            @endif
            <input type="search" name="search" value="{{ request('search') }}"
                placeholder="{{ $isAr ? 'البحث بالاسم أو رقم الجواز' : 'Search by name or passport' }}">
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
                        <th>{{ $isAr ? 'الاسم' : 'Name' }}</th>
                        <th>{{ $isAr ? 'الجنسية' : 'Nationality' }}</th>
                        <th>{{ $isAr ? 'جواز السفر' : 'Passport' }}</th>
                        <th>{{ $isAr ? 'العمر' : 'Age' }}</th>
                        <th>{{ $isAr ? 'السعر' : 'Price' }}</th>
                        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                        <th>{{ $isAr ? 'العقود النشطة' : 'Active Contracts' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maids as $maid)
                        @php
                            $activeContractsCount = (int) ($maid->active_contracts_count ?? 0);
                            $isContracted = $activeContractsCount > 0 || $maid->status === 'contracted';
                            $statusLabel = $isContracted
                                ? ($isAr ? 'متعاقد عليها' : 'Contracted')
                                : match ($maid->status) {
                                    'available' => $isAr ? 'متاحة' : 'Available',
                                    'reserved' => $isAr ? 'محجوزة' : 'Reserved',
                                    default => $maid->status ?: '-',
                                };
                        @endphp
                        <tr>
                            <td>{{ $maid->id }}</td>
                            <td>{{ $maid->name ?: '-' }}</td>
                            <td>{{ $maid->nationality?->sub_title ?: ($maid->nationality?->title ?: '-') }}</td>
                            <td>{{ $maid->passport_num ?: '-' }}</td>
                            <td>{{ $maid->age ?: '-' }}</td>
                            <td>{{ $maid->resolved_price ? formatMoney($maid->resolved_price) : '-' }}</td>
                            <td>
                                <span class="agent-badge {{ $isContracted ? 'warning' : '' }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>{{ $activeContractsCount }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                {{ $isAr ? 'لا توجد سجلات مطابقة.' : 'No matching records were found.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="agent-pagination">
            {{ $maids->links() }}
        </div>
    </section>
@endsection

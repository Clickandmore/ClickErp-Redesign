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
                        <th>{{ $isAr ? 'الإجراءات' : 'Actions' }}</th>
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
                            <td>
                                <a href="{{ route('agent.maids.show', $maid->id) }}" class="agent-button" style="padding: 4px 8px; min-height: 28px; font-size: 12px; background: var(--agent-primary); text-decoration: none;">
                                    <i class="fa fa-eye"></i> {{ $isAr ? 'عرض' : 'View' }}
                                </a>
                                <button type="button" class="agent-button edit-notes-btn" data-id="{{ $maid->id }}" data-notes="{{ $maid->additional_notes ?? '' }}" style="padding: 4px 8px; min-height: 28px; font-size: 12px; background: var(--agent-muted);">
                                    <i class="fa fa-edit"></i> {{ $isAr ? 'ملاحظات' : 'Notes' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
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

    <!-- Notes Modal -->
    <div id="notesModal" class="agent-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="agent-modal-content" style="background: var(--agent-panel); padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--agent-line); padding-bottom: 10px;">
                <h4 style="margin: 0; font-size: 18px;">{{ $isAr ? 'ملاحظات العمالة' : 'Worker Notes' }}</h4>
                <button type="button" id="closeNotesModal" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--agent-muted);">&times;</button>
            </div>
            <form id="notesForm" action="" method="POST">
                @csrf
                <textarea name="additional_notes" id="maidNotes" rows="5" style="width: 100%; padding: 10px; border: 1px solid var(--agent-line); border-radius: 8px; resize: vertical; font-family: inherit;" placeholder="{{ $isAr ? 'أدخل ملاحظاتك هنا...' : 'Enter your notes here...' }}"></textarea>
                <div style="margin-top: 15px; text-align: end;">
                    <button type="submit" class="agent-button">{{ $isAr ? 'حفظ' : 'Save' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('notesModal');
        const closeBtn = document.getElementById('closeNotesModal');
        const form = document.getElementById('notesForm');
        const textarea = document.getElementById('maidNotes');
        const editBtns = document.querySelectorAll('.edit-notes-btn');

        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const maidId = this.getAttribute('data-id');
                const notes = this.getAttribute('data-notes');
                
                // Set form action dynamically
                form.action = `{{ url('agent/maids') }}/${maidId}/notes`;
                
                // Set textarea value
                textarea.value = notes;
                
                // Show modal
                modal.style.display = 'flex';
            });
        });

        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>
@endpush

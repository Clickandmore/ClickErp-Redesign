@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('page-title', $isAr ? 'الشكاوى' : 'Complaints')

@push('style')
    <style>
        .agent-badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .agent-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .agent-badge.muted {
            background: #e2e8f0;
            color: #334155;
        }

        .agent-flash {
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-size: 13px;
        }

        .agent-complaint-subject {
            display: block;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .agent-complaint-subject small {
            display: block;
            font-weight: 400;
            color: var(--agent-muted);
        }
    </style>
@endpush

@section('page-action')
    <a href="{{ route('agent.complaints.create') }}" class="agent-button">
        <i class="fa fa-plus"></i>
        <span>{{ $isAr ? 'شكوى جديدة' : 'New Complaint' }}</span>
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="agent-flash">{{ session('success') }}</div>
    @endif

    <section class="agent-grid">
        <div class="agent-card">
            <span>{{ $isAr ? 'إجمالي الشكاوى' : 'Total Complaints' }}</span>
            <strong>{{ $stats['total'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ $isAr ? 'قيد الانتظار' : 'Pending' }}</span>
            <strong>{{ $stats['pending'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ $isAr ? 'قيد المعالجة' : 'In Progress' }}</span>
            <strong>{{ $stats['in_progress'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ $isAr ? 'تم الحل' : 'Resolved' }}</span>
            <strong>{{ $stats['resolved'] }}</strong>
        </div>
    </section>

    <section class="agent-section">
        <form action="{{ route('agent.complaints.index') }}" method="GET" class="agent-filter">
            <input type="search" name="search" value="{{ request('search') }}"
                placeholder="{{ $isAr ? 'بحث بالموضوع، الطرف الآخر، أو العاملة' : 'Search by subject, other party, or worker' }}">
            <select name="status">
                <option value="">{{ $isAr ? 'كل الحالات' : 'All Statuses' }}</option>
                @foreach (\App\Support\AgentComplaintLabels::statuses() as $statusKey => $statusLabel)
                    <option value="{{ $statusKey }}" @selected(request('status') === $statusKey)>
                        {{ $isAr ? $statusLabel['ar'] : $statusLabel['en'] }}
                    </option>
                @endforeach
            </select>
            <select name="complaint_type_id">
                <option value="">{{ $isAr ? 'كل الأنواع' : 'All Types' }}</option>
                @foreach ($complaintTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) request('complaint_type_id') === (string) $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
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
                        <th>{{ $isAr ? 'الموضوع' : 'Subject' }}</th>
                        <th>{{ $isAr ? 'نوع الشكوى' : 'Type' }}</th>
                        <th>{{ $isAr ? 'مرتبطة بـ' : 'Related To' }}</th>
                        <th>{{ $isAr ? 'الطرف الآخر' : 'Other Party' }}</th>
                        <th>{{ $isAr ? 'الأولوية' : 'Priority' }}</th>
                        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                        <th>{{ $isAr ? 'تاريخ الإرسال' : 'Submitted At' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($complaints as $complaint)
                        @php
                            $status = \App\Support\AgentComplaintLabels::status($complaint->status);
                            $priority = \App\Support\AgentComplaintLabels::priority($complaint->priority);
                        @endphp
                        <tr>
                            <td><strong>#{{ $complaint->id }}</strong></td>
                            <td>
                                <span class="agent-complaint-subject">
                                    {{ $complaint->subject ?: '-' }}
                                    {{-- الشكاوى القديمة تحمل النص في message وحده --}}
                                    <small>{{ \Illuminate\Support\Str::limit((string) ($complaint->details ?: $complaint->message), 60) }}</small>
                                </span>
                            </td>
                            <td>{{ $complaint->complaintType?->name ?: '-' }}</td>
                            <td>
                                @if ($complaint->contract_id)
                                    {{ $isAr ? 'عقد' : 'Contract' }} #{{ $complaint->contract_id }}
                                    @if ($complaint->contract?->maid)
                                        <small style="display: block; color: var(--agent-muted);">
                                            {{ $complaint->contract->maid->name }}
                                        </small>
                                    @endif
                                @else
                                    {{ $complaint->relation_reference ?: '-' }}
                                @endif
                            </td>
                            <td>{{ $complaint->against_name ?: '-' }}</td>
                            <td><span class="agent-badge {{ $priority['class'] }}">{{ $isAr ? $priority['ar'] : $priority['en'] }}</span></td>
                            <td><span class="agent-badge {{ $status['class'] }}">{{ $isAr ? $status['ar'] : $status['en'] }}</span></td>
                            <td>{{ $complaint->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
                            <td>
                                <a href="{{ route('agent.complaints.show', $complaint->id) }}" class="agent-button">
                                    <i class="fa fa-eye"></i>
                                    <span>{{ $isAr ? 'عرض' : 'View' }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">{{ $isAr ? 'لا توجد شكاوى مطابقة.' : 'No matching complaints were found.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="agent-pagination">
            {{ $complaints->links() }}
        </div>
    </section>
@endsection

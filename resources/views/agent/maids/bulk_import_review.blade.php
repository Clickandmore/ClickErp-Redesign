@extends('agent.layout')

@php $isAr = app()->getLocale() === 'ar'; @endphp

@section('page-title', $isAr ? 'مراجعة الملف المرفوع' : 'Review Uploaded File')

@section('content')
<section class="agent-section">
    <div class="agent-section-header">
        <h3>{{ $isAr ? 'مراجعة الملف المرفوع' : 'Review Uploaded File' }}</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('agent.maids.bulk-import.download', $import) }}" class="agent-button" style="background:#198754;">
                <i class="fa fa-download"></i>
                <span>{{ $isAr ? 'تحميل الملف الأصلي' : 'Download Original File' }}</span>
            </a>
            <a href="{{ route('agent.maids.bulk-import.create') }}" class="agent-button" style="background:var(--agent-muted,#6c757d);">
                <i class="fa fa-upload"></i>
                <span>{{ $isAr ? 'رفع ملف جديد' : 'Upload New File' }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="agent-alert success">{{ session('success') }}</div>
    @endif

    {{-- Import Summary --}}
    <div class="agent-card" style="margin-bottom:20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;">
            <div style="text-align:center;padding:12px;background:#f8f9fa;border-radius:8px;">
                <div style="font-size:24px;font-weight:700;">{{ $import->total_rows }}</div>
                <div style="font-size:12px;color:#666;">{{ $isAr ? 'إجمالي السجلات' : 'Total' }}</div>
            </div>
            <div style="text-align:center;padding:12px;background:#d1fae5;border-radius:8px;">
                <div style="font-size:24px;font-weight:700;color:#065f46;">{{ $import->valid_rows }}</div>
                <div style="font-size:12px;color:#065f46;">{{ $isAr ? 'صحيحة' : 'Valid' }}</div>
            </div>
            <div style="text-align:center;padding:12px;background:#fee2e2;border-radius:8px;">
                <div style="font-size:24px;font-weight:700;color:#991b1b;">{{ $import->invalid_rows }}</div>
                <div style="font-size:12px;color:#991b1b;">{{ $isAr ? 'بها أخطاء' : 'Invalid' }}</div>
            </div>
            <div style="text-align:center;padding:12px;background:#dbeafe;border-radius:8px;">
                <div style="font-size:24px;font-weight:700;color:#1e40af;">{{ $import->imported_rows }}</div>
                <div style="font-size:12px;color:#1e40af;">{{ $isAr ? 'تم استيرادها' : 'Imported' }}</div>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
            <div>
                <strong>{{ $isAr ? 'الحالة:' : 'Status:' }}</strong>
                @php
                    $badgeClass = match($import->status) {
                        'completed' => 'success',
                        'rejected', 'failed' => 'danger',
                        'pending_review' => 'warning',
                        default => ''
                    };
                @endphp
                <span class="agent-badge {{ $badgeClass }}">{{ $import->status_label }}</span>
            </div>
            <div>
                <strong>{{ $isAr ? 'المكتب:' : 'Office:' }}</strong>
                {{ $import->office?->title ?? '-' }}
            </div>
            <div>
                <strong>{{ $isAr ? 'الملف:' : 'File:' }}</strong>
                <i class="fa {{ $import->file_type_icon }}"></i>
                {{ $import->original_filename }}
            </div>
            <div>
                <strong>{{ $isAr ? 'تاريخ الرفع:' : 'Uploaded:' }}</strong>
                {{ $import->created_at->format('Y-m-d H:i') }}
            </div>
        </div>

        @if($import->status === 'rejected' && $import->rejection_reason)
            <div style="margin-top:12px;padding:10px 14px;background:#fee2e2;border-radius:6px;border-right:4px solid #dc3545;">
                <strong>{{ $isAr ? 'سبب الرفض:' : 'Rejection Reason:' }}</strong>
                {{ $import->rejection_reason }}
            </div>
        @endif

        @if($import->status === 'pending_review')
            <div style="margin-top:12px;padding:10px 14px;background:#fff3cd;border-radius:6px;border-right:4px solid #f59e0b;">
                <i class="fa fa-clock"></i>
                {{ $isAr ? 'الملف قيد مراجعة الإدارة. سيتم إشعارك عند الاعتماد.' : 'File is under admin review. You will be notified upon approval.' }}
            </div>
        @endif
    </div>

    {{-- Rows Table --}}
    <div class="agent-card">
        <h4 style="margin-bottom:16px;">{{ $isAr ? 'السجلات المستخرجة' : 'Extracted Records' }}</h4>
        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>{{ $isAr ? 'رقم الصف' : 'Row' }}</th>
                        <th>{{ $isAr ? 'الاسم' : 'Name' }}</th>
                        <th>{{ $isAr ? 'رقم الجواز' : 'Passport' }}</th>
                        <th>{{ $isAr ? 'الجنسية' : 'Nationality' }}</th>
                        <th>{{ $isAr ? 'العمر' : 'Age' }}</th>
                        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                        <th>{{ $isAr ? 'الأخطاء' : 'Errors' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($import->rows as $row)
                    @php
                        $data = $row->normalized_data ?? [];
                        $rowBadge = match($row->status) {
                            'pending'  => ['label' => $isAr ? 'بانتظار الاعتماد' : 'Pending', 'class' => 'warning'],
                            'imported' => ['label' => $isAr ? 'تم الاستيراد' : 'Imported', 'class' => 'success'],
                            'invalid'  => ['label' => $isAr ? 'بيانات خاطئة' : 'Invalid', 'class' => 'danger'],
                            'failed'   => ['label' => $isAr ? 'فشل الاستيراد' : 'Failed', 'class' => 'danger'],
                            default    => ['label' => $row->status, 'class' => ''],
                        };
                    @endphp
                    <tr>
                        <td>{{ $row->row_number }}</td>
                        <td>{{ $data['name'] ?? '-' }}</td>
                        <td>{{ $data['passport_num'] ?? '-' }}</td>
                        <td>{{ $data['nationality_id'] ?? '-' }}</td>
                        <td>{{ $data['age'] ?? '-' }}</td>
                        <td><span class="agent-badge {{ $rowBadge['class'] }}">{{ $rowBadge['label'] }}</span></td>
                        <td>
                            @if(!empty($row->error_messages))
                                <ul style="margin:0;padding-right:16px;color:#dc3545;font-size:12px;">
                                    @foreach($row->error_messages as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span style="color:#198754;">✓</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">{{ $isAr ? 'لا توجد سجلات.' : 'No records found.' }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

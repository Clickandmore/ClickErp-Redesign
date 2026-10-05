<div class="row" id="contractComplaintsSection">
    <div class="col-12 mb-4">
        <div class="info-card">
            <div class="card-title mb-3">
                <i class="fas fa-comments text-warning"></i>
                الشكاوى المرتبطة بالعقد
                @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_VIEW))
                    <span class="badge bg-secondary ms-2">{{ $contractComplaints->count() }}</span>
                @endif
            </div>
            @if (!@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_VIEW))
                <p class="text-muted mb-0">يلزم امتلاك صلاحية عرض الشكاوى للاطلاع على شكاوى هذا العقد.</p>
            @elseif ($contractComplaints->isEmpty())
                <p class="text-muted mb-0">لا توجد شكاوى مرتبطة بهذا العقد أو بطلبه.</p>
            @else
                <div class="table-responsive">
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th>رقم الشكوى</th>
                                <th>الموضوع والتفاصيل</th>
                                <th>نوع الشكوى</th>
                                <th>الحالة</th>
                                <th>الأولوية</th>
                                <th>المسؤول</th>
                                <th>التاريخ</th>
                                <th>التفاصيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contractComplaints as $complaint)
                                @php
                                    $statusLabel = match ($complaint->status) {
                                        'pending' => 'قيد الانتظار',
                                        'in_progress' => 'قيد المعالجة',
                                        'resolved' => 'تم الحل',
                                        'closed' => 'مغلقة',
                                        'rejected' => 'مرفوضة',
                                        default => $complaint->status ?: 'غير محددة',
                                    };
                                    $statusColor = match ($complaint->status) {
                                        'resolved' => 'success', 'rejected' => 'danger',
                                        'pending' => 'warning', 'in_progress' => 'info',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>#{{ $complaint->id }}</td>
                                    <td style="min-width: 240px; max-width: 420px; white-space: normal;">
                                        <strong>{{ $complaint->subject ?: 'شكوى بدون عنوان' }}</strong>
                                        @if ($complaint->message || $complaint->details)
                                            <details class="mt-1">
                                                <summary>عرض وصف الشكوى</summary>
                                                <div style="white-space: pre-line;">{{ $complaint->message }}</div>
                                                @if ($complaint->details && $complaint->details !== $complaint->message)
                                                    <div class="mt-1" style="white-space: pre-line;">{{ $complaint->details }}</div>
                                                @endif
                                            </details>
                                        @endif
                                        @if (!$complaint->contract_id)
                                            <small class="text-muted d-block mt-1">مرتبطة بطلب العقد #{{ $contract->order_id }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $complaint->complaintType?->name ?: 'غير محدد' }}</td>
                                    <td><span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span></td>
                                    <td><span class="{{ $complaint->priority_badge }}">{{ $complaint->priority_text }}</span></td>
                                    <td>{{ $complaint->assignedTo?->name ?: 'غير مسندة' }}</td>
                                    <td>{{ ($complaint->complaint_date ?: $complaint->created_at)?->format('Y-m-d') ?: 'غير محدد' }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('complaints.show', $complaint->id) }}">فتح الشكوى</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

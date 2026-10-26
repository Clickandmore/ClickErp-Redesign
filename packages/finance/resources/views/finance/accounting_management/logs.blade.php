<div class="table-responsive">
    @if ($logs->isEmpty())
        <div class="text-muted text-center py-3">لا توجد عمليات حذف أو نقل أو دمج مسجلة.</div>
    @else
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>العملية</th>
                    <th>الحساب</th>
                    <th>التفاصيل</th>
                    <th>السبب</th>
                    <th>بواسطة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    @php($details = (array) $log->details)
                    <tr>
                        <td class="text-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->action_label }}</td>
                        <td>{{ $log->account_code }} - {{ $log->account_name }}</td>
                        <td class="small">
                            @if ($log->action === \Nami\Finance\Models\AccountRestructureLog::ACTION_MERGE)
                                في {{ $details['target']['code'] ?? '' }} - {{ $details['target']['name'] ?? '#' . $log->target_account_id }}
                                @if (!empty($details['moved']))
                                    <div class="text-muted">
                                        {{ collect($details['moved'])->map(fn ($count, $label) => $label . ' ' . $count)->implode('، ') }}
                                    </div>
                                @endif
                                @if ($log->reversed_at)
                                    <span class="badge bg-secondary">تم التراجع {{ $log->reversed_at->format('Y-m-d') }}</span>
                                @endif
                            @elseif ($log->action === \Nami\Finance\Models\AccountRestructureLog::ACTION_MOVE)
                                من #{{ $log->old_parent_id ?? '-' }} إلى #{{ $log->new_parent_id ?? 'الجذر' }}
                            @elseif ($log->action === \Nami\Finance\Models\AccountRestructureLog::ACTION_MERGE_UNDO)
                                @if (!empty($details['restored']))
                                    أُعيد: {{ collect($details['restored'])->map(fn ($count, $label) => $label . ' ' . $count)->implode('، ') }}
                                @endif
                                @if (!empty($details['skipped']))
                                    <div class="text-warning">لم يُعد (تغيّر بعد الدمج): {{ collect($details['skipped'])->map(fn ($count, $label) => $label . ' ' . $count)->implode('، ') }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="small">{{ $log->reason }}</td>
                        <td class="small">{{ $log->creator?->name }}</td>
                        <td>
                            @if ($canUndoMerge && $log->action === \Nami\Finance\Models\AccountRestructureLog::ACTION_MERGE && !$log->reversed_at)
                                <button type="button" class="btn btn-sm btn-outline-danger editButton text-nowrap" data-bs-toggle="modal"
                                    data-bs-target="#createModal"
                                    model-route="{{ route('finance.accounting-guide-management.merge-undo-form', $log->id) }}"
                                    model-title="التراجع عن دمج: {{ $log->account_code }} - {{ $log->account_name }}">
                                    <i class="fa fa-undo"></i> تراجع
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

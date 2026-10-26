@php
    $riyadhTime = fn ($date) => $date?->timezone('Asia/Riyadh');
    $logStates = [
        'pending' => ['بانتظار الاعتماد', 'is-warning', 'fa-hourglass-half'],
        'partial' => ['معتمد جزئيًا', 'is-info', 'fa-adjust'],
        'approved' => ['معتمد', 'is-success', 'fa-check-circle'],
    ];
    $maidStates = [
        'pending_review' => ['is-warning', 'fa-hourglass-half'],
        'processing' => ['is-info', 'fa-spinner'],
        'completed' => ['is-success', 'fa-check-circle'],
        'completed_with_errors' => ['is-warning', 'fa-exclamation-circle'],
        'failed' => ['is-danger', 'fa-times-circle'],
    ];
    $resultLabels = ['created' => 'سجلات أُنشئت', 'updated' => 'سجلات حُدثت', 'skipped' => 'سجلات متجاوزة', 'error_count' => 'أخطاء آخر محاولة'];
    // التنقّل بين صفحات سجل السير الذاتية يعيد فتح تبويبه بدل الرجوع للتبويب الأول
    $activeLogsTab = request()->has('maid_logs_page') ? 'maids' : 'data';
    $percent = fn ($part, $total) => $total > 0 ? round(min(100, max(0, $part) / $total * 100), 2) : 0;
@endphp

<style>
    .iol { border: 1px solid #e8edf5; border-radius: 12px; background: #fff; box-shadow: 0 10px 28px rgba(15, 23, 42, .05); overflow: hidden; }
    .iol-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 18px 22px; border-bottom: 1px solid #e8edf5; background: linear-gradient(180deg, #fbfcfe 0%, #f6f8fc 100%); }
    .iol-title { display: flex; gap: 12px; align-items: flex-start; }
    .iol-title-icon { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; border-radius: 10px; background: #eef4ff; color: #0d6efd; font-size: 1.05rem; }
    .iol-title h4 { margin: 0 0 3px; font-size: 1.08rem; font-weight: 700; }
    .iol-title p { margin: 0; color: #64748b; font-size: .84rem; line-height: 1.7; max-width: 760px; }

    .iol-tabs { gap: 6px; padding: 12px 22px 0; border-bottom: 1px solid #e8edf5; flex-wrap: nowrap; overflow-x: auto; }
    .iol-tabs .nav-link { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; border: 0; border-bottom: 3px solid transparent; border-radius: 0; padding: 10px 14px; color: #64748b; font-weight: 600; background: transparent; }
    .iol-tabs .nav-link.active { color: #0d6efd; border-bottom-color: #0d6efd; }
    .iol-tab-count { min-width: 24px; padding: 1px 8px; border-radius: 999px; background: #eef2f7; color: #475569; font-size: .74rem; font-weight: 700; }
    .iol-tabs .nav-link.active .iol-tab-count { background: #e7f0ff; color: #0d6efd; }

    .iol-table { margin: 0; }
    .iol-table thead th { background: #f8fafc; color: #64748b; font-size: .76rem; font-weight: 700; white-space: nowrap; border-bottom: 1px solid #e2e8f0; padding: 11px 14px; }
    .iol-table td { padding: 14px; vertical-align: middle; border-color: #eef2f7; font-size: .86rem; }
    .iol-table tbody tr.iol-row:hover { background: #fafcff; }

    .iol-id { display: inline-flex; align-items: center; padding: 1px 8px; border-radius: 6px; background: #f1f5f9; color: #475569; font-size: .74rem; font-weight: 700; font-family: ui-monospace, Consolas, monospace; }
    .iol-file { display: flex; align-items: center; gap: 8px; margin-top: 6px; max-width: 320px; color: #0f172a; font-weight: 600; }
    .iol-file i { color: #16a34a; }
    .iol-file span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; direction: ltr; text-align: right; unicode-bidi: plaintext; }
    .iol-meta { margin-top: 4px; color: #64748b; font-size: .77rem; }
    .iol-meta i { width: 14px; opacity: .75; }

    .iol-person { display: flex; align-items: center; gap: 9px; }
    .iol-avatar { width: 32px; height: 32px; flex: 0 0 32px; display: grid; place-items: center; border-radius: 50%; background: #eef4ff; color: #0d6efd; font-weight: 700; font-size: .82rem; }
    .iol-avatar.is-approver { background: #ecfdf3; color: #067647; }
    .iol-person-name { font-weight: 600; color: #0f172a; line-height: 1.4; }
    .iol-time { color: #64748b; font-size: .76rem; font-variant-numeric: tabular-nums; }
    .iol-none { color: #94a3b8; font-size: .8rem; }

    .iol-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .76rem; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
    .iol-badge.is-success { color: #067647; background: #ecfdf3; border-color: #abefc6; }
    .iol-badge.is-warning { color: #b54708; background: #fffaeb; border-color: #fedf89; }
    .iol-badge.is-info { color: #175cd3; background: #eff8ff; border-color: #b2ddff; }
    .iol-badge.is-danger { color: #b42318; background: #fef3f2; border-color: #fbd5d1; }
    .iol-badge.is-muted { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }

    .iol-result { min-width: 230px; }
    .iol-bar { display: flex; height: 7px; margin-bottom: 8px; overflow: hidden; border-radius: 999px; background: #eef2f7; direction: rtl; }
    .iol-bar > span { display: block; height: 100%; }
    .iol-bar .is-success { background: #22c55e; }
    .iol-bar .is-info { background: #3b82f6; }
    .iol-bar .is-danger { background: #ef4444; }
    .iol-bar .is-warning { background: #f59e0b; }
    .iol-counts { display: flex; flex-wrap: wrap; gap: 4px 12px; font-size: .76rem; color: #475569; }
    .iol-counts b { font-variant-numeric: tabular-nums; color: #0f172a; }
    .iol-counts i { display: inline-block; width: 8px; height: 8px; margin-inline-end: 4px; border-radius: 50%; vertical-align: middle; }

    .iol-actions { display: flex; gap: 6px; justify-content: flex-end; white-space: nowrap; }
    .iol-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
    .iol-toggle i { transition: transform .15s ease; }

    .iol-detail td { background: #f8fafc; padding: 0 14px 14px !important; }
    .iol-detail-box { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    .iol-stat { padding: 10px 12px; border-radius: 8px; background: #f8fafc; }
    .iol-stat small { display: block; color: #64748b; font-size: .74rem; }
    .iol-stat strong { font-size: .95rem; font-variant-numeric: tabular-nums; }
    .iol-detail-full { grid-column: 1 / -1; font-size: .8rem; }
    .iol-errors { margin: 0; padding: 10px 14px; border-radius: 8px; background: #fef3f2; color: #b42318; list-style: none; }
    .iol-errors li + li { margin-top: 4px; }

    .iol-empty { padding: 44px 16px; text-align: center; color: #94a3b8; }
    .iol-empty i { display: block; margin-bottom: 10px; font-size: 2rem; color: #cbd5e1; }
    .iol-pagination { padding: 12px 22px; border-top: 1px solid #eef2f7; }
    .iol-pagination nav, .iol-pagination .pagination { margin: 0; }
</style>

<div class="container-fluid mt-4 mb-4" id="import-operation-logs" dir="rtl">
    <section class="iol">
        <header class="iol-head">
            <div class="iol-title">
                <span class="iol-title-icon"><i class="fas fa-clipboard-list"></i></span>
                <div>
                    <h4>سجل عمليات الاستيراد</h4>
                    <p>جميع الدفعات المسجلة المتاحة حسب صلاحياتك، الأحدث أولًا. الأوقات بتوقيت الرياض. رفع الملف للفحص لا يعني تنفيذ الاستيراد.</p>
                </div>
            </div>
        </header>

        <ul class="nav iol-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeLogsTab === 'data' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#iol-data" type="button" role="tab" aria-selected="{{ $activeLogsTab === 'data' ? 'true' : 'false' }}">
                    <i class="fas fa-file-invoice"></i> البيانات والفواتير والعقود
                    <span class="iol-tab-count">{{ number_format($importLogs->total()) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeLogsTab === 'maids' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#iol-maids" type="button" role="tab" aria-selected="{{ $activeLogsTab === 'maids' ? 'true' : 'false' }}">
                    <i class="fas fa-id-card"></i> السير الذاتية
                    <span class="iol-tab-count">{{ number_format($maidImportLogs->total()) }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- ================= البيانات والفواتير والعقود ================= --}}
            <div class="tab-pane fade {{ $activeLogsTab === 'data' ? 'show active' : '' }}" id="iol-data" role="tabpanel">
                <div class="table-responsive">
                    <table class="table iol-table">
                        <thead>
                            <tr>
                                <th>العملية / الملف</th>
                                <th>الرفع</th>
                                <th>الاعتماد</th>
                                <th>الحالة</th>
                                <th>النتيجة</th>
                                <th class="text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($importLogs as $log)
                            @php
                                [$stateLabel, $stateTone, $stateIcon] = $logStates[$log->status] ?? [$log->status, 'is-muted', 'fa-circle'];
                                $total = (int) $log->rows_count;
                                $uploaderName = $log->uploadedBy->name ?? 'مستخدم غير متاح';
                            @endphp
                            <tr class="iol-row">
                                <td>
                                    <span class="iol-id">#{{ $log->id }}</span>
                                    <div class="iol-file" title="{{ $log->original_name }}"><i class="fas fa-file-excel"></i><span>{{ $log->original_name }}</span></div>
                                    <div class="iol-meta"><i class="fas fa-tag"></i> {{ $templates[$log->type]['label'] ?? $log->type }}</div>
                                    <div class="iol-meta"><i class="fas fa-code-branch"></i> {{ $log->branchRelation->name ?? $log->branch_id ?? 'فرع غير محدد' }}</div>
                                </td>
                                <td>
                                    <div class="iol-person">
                                        <span class="iol-avatar">{{ mb_substr($uploaderName, 0, 1) }}</span>
                                        <div>
                                            <div class="iol-person-name">{{ $uploaderName }}</div>
                                            <div class="iol-time"><bdi dir="ltr">{{ $riyadhTime($log->created_at)?->format('Y-m-d H:i:s') }}</bdi></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($log->approved_at)
                                        @php $approverName = $log->approvedBy->name ?? 'مستخدم غير متاح'; @endphp
                                        <div class="iol-person">
                                            <span class="iol-avatar is-approver">{{ mb_substr($approverName, 0, 1) }}</span>
                                            <div>
                                                <div class="iol-person-name">{{ $approverName }}</div>
                                                <div class="iol-time"><bdi dir="ltr">{{ $riyadhTime($log->approved_at)->format('Y-m-d H:i:s') }}</bdi></div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="iol-none"><i class="far fa-clock"></i> لم يُعتمد بعد</span>
                                    @endif
                                </td>
                                <td><span class="iol-badge {{ $stateTone }}"><i class="fas {{ $stateIcon }}"></i> {{ $stateLabel }}</span></td>
                                <td class="iol-result">
                                    <div class="iol-bar" title="معتمدة / بانتظار التنفيذ / مرفوضة">
                                        <span class="is-success" style="width: {{ $percent($log->approved_rows_count, $total) }}%"></span>
                                        <span class="is-info" style="width: {{ $percent($log->valid_rows_count, $total) }}%"></span>
                                        <span class="is-danger" style="width: {{ $percent($log->invalid_rows_count, $total) }}%"></span>
                                    </div>
                                    <div class="iol-counts">
                                        <span>الإجمالي <b>{{ number_format($total) }}</b></span>
                                        <span><i style="background:#22c55e"></i>معتمدة <b>{{ number_format($log->approved_rows_count) }}</b></span>
                                        <span><i style="background:#3b82f6"></i>بانتظار <b>{{ number_format($log->valid_rows_count) }}</b></span>
                                        <span><i style="background:#ef4444"></i>مرفوضة <b>{{ number_format($log->invalid_rows_count) }}</b></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="iol-actions">
                                        <a class="btn btn-sm btn-primary" href="{{ route('reports.import.show', $log) }}"><i class="fas fa-list-ul me-1"></i> تفاصيل الصفوف والروابط والأخطاء</a>
                                        <button class="btn btn-sm btn-outline-secondary iol-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#iol-detail-{{ $log->id }}" aria-expanded="false" aria-controls="iol-detail-{{ $log->id }}" title="تفاصيل العملية">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse iol-detail" id="iol-detail-{{ $log->id }}">
                                <td colspan="6">
                                    <div class="iol-detail-box">
                                        <div class="iol-stat"><small>آخر تحديث</small><strong><bdi dir="ltr">{{ $riyadhTime($log->updated_at)?->format('Y-m-d H:i:s') }}</bdi></strong></div>
                                        <div class="iol-stat"><small>رقم الرافع</small><strong>#{{ $log->created_by ?? '—' }}</strong></div>
                                        @if($log->approved_at)
                                            <div class="iol-stat"><small>رقم المعتمد</small><strong>#{{ $log->approved_by }}</strong></div>
                                        @endif
                                        @foreach($resultLabels as $key => $label)
                                            @if(isset($log->result[$key]))
                                                <div class="iol-stat"><small>{{ $label }}</small><strong>{{ number_format((int) $log->result[$key]) }}</strong></div>
                                            @endif
                                        @endforeach
                                        @if(!empty($log->options['finance_constraints_missing_only']))
                                            <div class="iol-detail-full"><span class="iol-badge is-info"><i class="fas fa-filter"></i> استيراد القيود المفقودة فقط</span></div>
                                        @endif
                                        @if(!empty($log->result['errors']))
                                            <ul class="iol-errors iol-detail-full">
                                                @foreach($log->result['errors'] as $issue)
                                                    <li><i class="fas fa-exclamation-triangle me-1"></i> {{ $issue }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        <div class="iol-detail-full text-muted small">إحصاءات التنفيذ حسب النتيجة المسجلة؛ عدد الصفوف المعتمدة هو إجمالي الدفعة.</div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="iol-empty"><i class="fas fa-inbox"></i>لا توجد عمليات استيراد مسجلة.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($importLogs->hasPages())
                    <div class="iol-pagination">{{ $importLogs->links() }}</div>
                @endif
            </div>

            {{-- ================= السير الذاتية ================= --}}
            <div class="tab-pane fade {{ $activeLogsTab === 'maids' ? 'show active' : '' }}" id="iol-maids" role="tabpanel">
                <div class="table-responsive">
                    <table class="table iol-table">
                        <thead>
                            <tr>
                                <th>العملية / الملف</th>
                                <th>الرفع</th>
                                <th>الاعتماد</th>
                                <th>الحالة</th>
                                <th>النتيجة</th>
                                <th class="text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($maidImportLogs as $log)
                            @php
                                [$stateTone, $stateIcon] = $maidStates[$log->status] ?? ['is-muted', 'fa-circle'];
                                $total = (int) $log->total_rows;
                                $uploaderName = $log->uploadedBy->name ?? 'مستخدم غير متاح';
                            @endphp
                            <tr class="iol-row">
                                <td>
                                    <span class="iol-id">#{{ $log->id }}</span>
                                    <div class="iol-file" title="{{ $log->original_filename }}"><i class="fas fa-file-excel"></i><span>{{ $log->original_filename }}</span></div>
                                    <div class="iol-meta"><i class="fas fa-tag"></i> استيراد السير الذاتية</div>
                                    @if($log->processed_at)
                                        <div class="iol-meta"><i class="fas fa-flag-checkered"></i> اكتملت المعالجة <bdi dir="ltr">{{ $riyadhTime($log->processed_at)->format('Y-m-d H:i:s') }}</bdi></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="iol-person">
                                        <span class="iol-avatar">{{ mb_substr($uploaderName, 0, 1) }}</span>
                                        <div>
                                            <div class="iol-person-name">{{ $uploaderName }} <span class="iol-time">#{{ $log->uploaded_by ?? '—' }}</span></div>
                                            <div class="iol-time"><bdi dir="ltr">{{ $riyadhTime($log->created_at)?->format('Y-m-d H:i:s') }}</bdi></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($log->approved_at)
                                        @php $approverName = $log->approvedBy->name ?? 'مستخدم غير متاح'; @endphp
                                        <div class="iol-person">
                                            <span class="iol-avatar is-approver">{{ mb_substr($approverName, 0, 1) }}</span>
                                            <div>
                                                <div class="iol-person-name">{{ $approverName }} <span class="iol-time">#{{ $log->approved_by }}</span></div>
                                                <div class="iol-time"><bdi dir="ltr">{{ $riyadhTime($log->approved_at)->format('Y-m-d H:i:s') }}</bdi></div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="iol-none"><i class="far fa-clock"></i> لم يُعتمد بعد</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="iol-badge {{ $stateTone }}"><i class="fas {{ $stateIcon }}"></i> {{ $log->status_label }}</span>
                                    <div class="iol-time mt-1">آخر تحديث <bdi dir="ltr">{{ $riyadhTime($log->updated_at)?->format('Y-m-d H:i') }}</bdi></div>
                                </td>
                                <td class="iol-result">
                                    <div class="iol-bar" title="مستوردة / تعذر تنفيذها / غير صالحة">
                                        <span class="is-success" style="width: {{ $percent($log->imported_rows, $total) }}%"></span>
                                        <span class="is-warning" style="width: {{ $percent($log->failed_rows, $total) }}%"></span>
                                        <span class="is-danger" style="width: {{ $percent($log->invalid_rows, $total) }}%"></span>
                                    </div>
                                    <div class="iol-counts">
                                        <span>الإجمالي <b>{{ number_format($total) }}</b></span>
                                        <span>سليمة <b>{{ number_format($log->valid_rows) }}</b></span>
                                        <span><i style="background:#22c55e"></i>مستوردة <b>{{ number_format($log->imported_rows) }}</b></span>
                                        <span><i style="background:#f59e0b"></i>تعذر تنفيذها <b>{{ number_format($log->failed_rows) }}</b></span>
                                        <span><i style="background:#ef4444"></i>غير صالحة <b>{{ number_format($log->invalid_rows) }}</b></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="iol-actions">
                                        <a class="btn btn-sm btn-primary" href="{{ route('maids.bulk-import.show', $log) }}"><i class="fas fa-list-ul me-1"></i> تفاصيل الصفوف والأخطاء</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="iol-empty"><i class="fas fa-inbox"></i>لا توجد عمليات استيراد سير ذاتية مسجلة.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($maidImportLogs->hasPages())
                    <div class="iol-pagination">{{ $maidImportLogs->links() }}</div>
                @endif
            </div>
        </div>
    </section>
</div>

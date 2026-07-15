@php
    $accountsCount = $accounts->count();
    $actualCreditorCount = $accounts->where('actual_status', 'creditor')->count();
    $actualDebtorCount = $accounts->where('actual_status', 'debtor')->count();
    $totalBalance = (float) $accounts->sum('balance');
    $totalDebtor = (float) $accounts->sum('debtor_total');
    $totalCreditor = (float) $accounts->sum('creditor_total');
@endphp

<div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6">
        <div class="card creditor-result-card h-100">
            <div class="card-body">
                <div class="text-muted small">الحسابات المخالفة لطبيعتها</div>
                <div class="h4 mb-0 mt-1">{{ number_format($accountsCount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card creditor-result-card h-100">
            <div class="card-body">
                <div class="text-muted small">تحولت إلى رصيد دائن</div>
                <div class="h4 mb-0 mt-1" style="color:#14532d">{{ number_format($actualCreditorCount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card creditor-result-card h-100">
            <div class="card-body">
                <div class="text-muted small">تحولت إلى رصيد مدين</div>
                <div class="h4 mb-0 mt-1" style="color:#9a3412">{{ number_format($actualDebtorCount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card creditor-result-card h-100">
            <div class="card-body">
                <div class="text-muted small">إجمالي القيم المخالفة</div>
                <div class="h4 mb-0 mt-1" style="color:#315a63">{{ number_format($totalBalance, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card creditor-result-card">
    <div class="card-body p-0">
        @if ($accountsCount > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="toExcelTable" style="width:100%">
                    <thead style="background:#f3f4f6;color:#374151">
                        <tr>
                            <th>#</th>
                            <th>{{ __('finance::finance.account_code') }}</th>
                            <th>{{ __('finance::finance.account_name') }}</th>
                            <th>الحساب الأب</th>
                            <th class="text-center">الطبيعة الأصلية للحساب</th>
                            <th class="text-center">اتجاه الرصيد الحالي</th>
                            <th class="text-left">إجمالي المدين</th>
                            <th class="text-left">إجمالي الدائن</th>
                            <th class="text-left">قيمة الرصيد المخالف</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $row)
                            @php
                                $statementUrl = route('finance.account-statement.index', [
                                    'account_id' => $row->id,
                                    'from_date' => $filters['from_date'] ?? '',
                                    'to_date' => $filters['to_date'] ?? '',
                                    'type' => 'both',
                                    'other_account' => 'no',
                                ]);
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ $statementUrl }}" target="_blank" rel="noopener"
                                       class="font-weight-bold" style="color:#315a63" title="فتح كشف الحساب">
                                        {{ $row->code }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ $statementUrl }}" target="_blank" rel="noopener" style="color:inherit">
                                        {{ $row->name }}
                                    </a>
                                </td>
                                <td>
                                    @if ($row->parent_name)
                                        <span class="text-muted">{{ $row->parent_code }}</span>
                                        <span>{{ $row->parent_name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($row->status === 'creditor')
                                        <span class="creditor-status-badge creditor-status-badge--nature-creditor">طبيعته دائن</span>
                                    @else
                                        <span class="creditor-status-badge creditor-status-badge--nature-debtor">طبيعته مدين</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($row->actual_status === 'creditor')
                                        <span class="creditor-status-badge creditor-status-badge--actual-creditor">رصيده الفعلي دائن</span>
                                    @else
                                        <span class="creditor-status-badge creditor-status-badge--actual-debtor">رصيده الفعلي مدين</span>
                                    @endif
                                </td>
                                <td class="text-left">{{ number_format((float) $row->debtor_total, 2) }}</td>
                                <td class="text-left">{{ number_format((float) $row->creditor_total, 2) }}</td>
                                <td class="text-left font-weight-bold" style="color:#315a63">
                                    {{ number_format((float) $row->balance, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:#f8fafb;font-weight:700">
                        <tr>
                            <td colspan="6" class="text-center">{{ __('finance::finance.total') }}</td>
                            <td class="text-left">{{ number_format($totalDebtor, 2) }}</td>
                            <td class="text-left">{{ number_format($totalCreditor, 2) }}</td>
                            <td class="text-left">{{ number_format($totalBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-5 px-3">
                <div class="mb-2 text-muted"><i class="fa fa-check-circle fa-3x"></i></div>
                <h5>{{ __('finance::finance.no_data_found') }}</h5>
                <p class="text-muted mb-0">لا توجد حسابات خالف رصيدها طبيعتها الأصلية ضمن معايير البحث.</p>
            </div>
        @endif
    </div>
</div>

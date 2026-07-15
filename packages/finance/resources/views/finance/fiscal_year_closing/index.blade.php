@extends('click.layout.indexs.index')

@section('style')
    <style>
        .closing-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-bottom:18px; }
        .closing-card { padding:18px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; }
        .closing-card__label { color:#667085; font-size:14px; }
        .closing-card__value { margin-top:7px; font-size:25px; font-weight:800; }
        .closing-card__side { margin-inline-start:7px; font-size:14px; font-weight:700; }
        .closing-panel { padding:20px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; }
        .closing-help { color:#667085; line-height:1.8; }
        .closing-table th, .closing-table td { vertical-align:middle; }
        .closing-table .select2-container { width:100% !important; }
        .closing-total { font-size:16px; font-weight:800; }
        .closing-pagination .page-link { min-width:40px; text-align:center; }
        .closing-pagination .page-item.disabled .page-link { color:#98a2b3; }
        @media (max-width: 767px) { .closing-summary { grid-template-columns:1fr; } }
    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!$year)
        <div class="alert alert-warning">يجب إنشاء سنة مالية أولاً قبل تشغيل الإقفال المالي.</div>
    @else
        <div class="closing-panel mb-3">
            <form method="GET" action="{{ route('finance.fiscal-year-closing.index') }}" class="row align-items-end">
                <div class="col-md-5 mb-2">
                    <label for="year_id_filter" class="form-label">السنة المالية</label>
                    <select id="year_id_filter" name="year_id" class="form-control select2" required>
                        @foreach ($years as $item)
                            <option value="{{ $item->id }}" {{ $item->id == $year->id ? 'selected' : '' }}>
                                {{ $item->title }} ({{ $item->from_date }} - {{ $item->to_date }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label for="closing_date_filter" class="form-label">تاريخ إنشاء قيد الإغلاق المالي</label>
                    <input id="closing_date_filter" name="closing_date" type="date" class="form-control"
                        value="{{ $closingDate }}" min="{{ $year->from_date }}" max="{{ $year->to_date }}" required>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary w-100">احتساب الأرصدة</button>
                </div>
            </form>
            <p class="closing-help mb-0 mt-2">التاريخ المحدد يغلق حركات قائمة الدخل من بداية السنة المالية وحتى هذا التاريخ.</p>
        </div>

        <div class="closing-summary">
            <div class="closing-card">
                <div class="closing-card__label">رصيد الميزانية</div>
                <div class="closing-card__value">
                    {{ number_format(abs($summary['budget_net']), 2) }}
                    <span class="closing-card__side">
                        {{ $summary['budget_side'] === 'debtor' ? 'مدين' : ($summary['budget_side'] === 'creditor' ? 'دائن' : 'متزن') }}
                    </span>
                </div>
            </div>
            <div class="closing-card">
                <div class="closing-card__label">رصيد قائمة الدخل</div>
                <div class="closing-card__value">
                    {{ number_format(abs($summary['income_net']), 2) }}
                    <span class="closing-card__side">
                        {{ $summary['income_side'] === 'debtor' ? 'مدين' : ($summary['income_side'] === 'creditor' ? 'دائن' : 'متزن') }}
                    </span>
                </div>
            </div>
        </div>

        @if ($summary['pending_constraints_count'] > 0)
            <div class="alert alert-warning">
                يوجد <strong>{{ $summary['pending_constraints_count'] }}</strong> قيد بانتظار الاعتماد ضمن الفترة المحددة.
                يجب مراجعتها واعتمادها قبل إنشاء قيد الإقفال المالي.
            </div>
        @endif

        @if ($closingOperation)
            <div class="alert alert-success">
                تم إقفال هذه السنة مسبقاً في العملية رقم {{ $closingOperation->operation_number ?? $closingOperation->id }}
                بتاريخ {{ $closingOperation->closing_date->format('Y-m-d') }}
                @if ($closingConstraint)
                    بالقيد رقم {{ $closingOperation->display_constraint_code }}.
                @endif
            </div>
        @elseif ($summary['pending_constraints_count'] > 0)
        @elseif (abs($summary['income_net']) < 0.01)
            <div class="alert alert-info">رصيد قائمة الدخل متزن، ولا توجد حركة تحتاج إلى إقفال حتى التاريخ المحدد.</div>
        @else
            <form method="POST" action="{{ route('finance.fiscal-year-closing.store') }}" id="fiscalClosingForm" class="closing-panel">
                @csrf
                <input type="hidden" name="year_id" value="{{ $year->id }}">
                <input type="hidden" name="closing_date" value="{{ $closingDate }}">

                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <h5 class="mb-1">قم بتوزيع الحركات المالية لرصيد قائمة الدخل</h5>
                        <p class="closing-help mb-0">
                            الحركة المطلوبة للتوزيع: <strong>{{ number_format(abs($summary['income_net']), 2) }}</strong>
                            <strong>{{ $summary['expected_distribution_side'] === 'debtor' ? 'مدين' : 'دائن' }}</strong>.
                            سيضيف النظام تلقائياً الحركات العكسية لإقفال أرصدة حسابات الإيرادات والمصروفات.
                        </p>
                    </div>
                    <button type="button" id="addDistributionRow" class="btn btn-outline-primary mt-2">
                        <i class="fa fa-plus"></i> إضافة حساب
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered closing-table">
                        <thead>
                            <tr>
                                <th style="width:55px">م</th>
                                <th style="min-width:120px">رقم الحساب</th>
                                <th style="min-width:280px">اسم الحساب</th>
                                <th style="min-width:140px">قيمة</th>
                                <th style="min-width:140px">حركة الرصيد</th>
                                <th style="min-width:220px">بيان</th>
                                <th style="width:55px"></th>
                            </tr>
                        </thead>
                        <tbody id="distributionRows"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="closing-total">إجمالي التوزيع</td>
                                <td class="closing-total" id="distributionTotal">0.00</td>
                                <td colspan="3" id="distributionRemaining"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="form-group mt-3">
                    <label for="description" class="form-label">وصف</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                        placeholder="وصف قيد الإقفال">{{ old('description', 'قيد إقفال السنة المالية ' . $year->title) }}</textarea>
                </div>

                <div class="alert alert-warning mt-3">
                    بعد الإنشاء لا يمكن تشغيل إقفال آخر لنفس السنة المالية. راجع الحسابات والقيم قبل المتابعة.
                </div>
                <button type="submit" id="submitClosing" class="btn btn-success">إنشاء قيد الإقفال المالي</button>
            </form>

            <template id="distributionRowTemplate">
                <tr>
                    <td class="row-number"></td>
                    <td class="account-code">-</td>
                    <td>
                        <select class="form-control distribution-account" data-name="account_id" required>
                            <option value="">اختر الحساب</option>
                            @foreach ($distributionAccounts as $account)
                                <option value="{{ $account->id }}" data-code="{{ $account->code }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" min="0.01" step="0.01" class="form-control distribution-value" data-name="value" required></td>
                    <td>
                        <select class="form-control distribution-movement" data-name="movement" required>
                            <option value="debtor" {{ $summary['expected_distribution_side'] === 'debtor' ? 'selected' : '' }}>مدين</option>
                            <option value="creditor" {{ $summary['expected_distribution_side'] === 'creditor' ? 'selected' : '' }}>دائن</option>
                        </select>
                    </td>
                    <td><input type="text" maxlength="500" class="form-control" data-name="statement" value="توزيع صافي قائمة الدخل"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa fa-trash"></i></button></td>
                </tr>
            </template>
        @endif
    @endif

    <div class="closing-panel mt-4" id="closingOperations">
        <h5 class="mb-3">عمليات الإقفال المالي</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped closing-table">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>رقم العملية</th>
                        <th>رصيد الدخل</th>
                        <th>تاريخ العملية</th>
                        <th>قيد محاسبي</th>
                        <th>تاريخ القيد المحاسبي</th>
                        <th>بواسطة</th>
                        <th>تاريخ التراجع</th>
                        <th>تراجع بواسطة</th>
                        <th>تراجع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($operations as $operation)
                        <tr>
                            <td>{{ $operation->title }}</td>
                            <td>{{ $operation->operation_number ?? $operation->id }}</td>
                            <td>
                                {{ number_format((float) $operation->income_balance, 2) }}
                                {{ $operation->income_side === 'debtor' ? 'مدين' : 'دائن' }}
                            </td>
                            <td>{{ optional($operation->created_at)->format('Y-m-d') ?: '-' }}</td>
                            <td>
                                {{ $operation->display_constraint_code }}
                                @if ($operation->constraint && !$operation->constraint->trashed())
                                    <button type="button" class="btn btn-sm btn-outline-primary editButton"
                                        data-bs-toggle="modal" data-bs-target="#createModal"
                                        model-route="{{ route('finance.constraints.show', $operation->constraint_id) }}"
                                        model-title="تفاصيل القيد رقم {{ $operation->display_constraint_code }}"
                                        title="عرض القيد">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                @endif
                            </td>
                            <td>{{ $operation->closing_date->format('Y-m-d') }}</td>
                            <td>{{ $operation->created_by_name ?: optional($operation->createdBy)->name ?: '-' }}</td>
                            <td>{{ optional($operation->reversed_at)->format('Y-m-d') ?: '-' }}</td>
                            <td>{{ $operation->reversed_by_name ?: optional($operation->reversedBy)->name ?: '-' }}</td>
                            <td>
                                @if (!$operation->is_reversed && $operation->constraint && !$operation->constraint->trashed())
                                    <form method="POST"
                                        action="{{ route('finance.fiscal-year-closing.reverse', $operation->id) }}"
                                        onsubmit="return confirm('هل أنت متأكد من التراجع عن عملية الإقفال؟ سيتم إلغاء القيد المحاسبي المرتبط.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">تراجع</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">لا توجد عمليات إقفال مالي.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($operations->lastPage() > 1)
            <nav class="closing-pagination d-flex justify-content-center" aria-label="صفحات عمليات الإقفال">
                <ul class="pagination mb-0" dir="ltr">
                    <li class="page-item {{ $operations->onFirstPage() ? 'disabled' : '' }}">
                        @if ($operations->onFirstPage())
                            <span class="page-link">السابق</span>
                        @else
                            <a class="page-link" href="{{ $operations->previousPageUrl() }}">السابق</a>
                        @endif
                    </li>

                    @for ($page = 1; $page <= $operations->lastPage(); $page++)
                        <li class="page-item {{ $operations->currentPage() === $page ? 'active' : '' }}">
                            @if ($operations->currentPage() === $page)
                                <span class="page-link">{{ $page }}</span>
                            @else
                                <a class="page-link" href="{{ $operations->url($page) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endfor

                    <li class="page-item {{ $operations->hasMorePages() ? '' : 'disabled' }}">
                        @if ($operations->hasMorePages())
                            <a class="page-link" href="{{ $operations->nextPageUrl() }}">التالي</a>
                        @else
                            <span class="page-link">التالي</span>
                        @endif
                    </li>
                </ul>
            </nav>
        @endif
    </div>
@endsection

@section('js')
    @if ($year && !$closingOperation && $summary['pending_constraints_count'] === 0 && abs($summary['income_net']) >= 0.01)
        <script>
            $(function () {
                const targetNet = {{ json_encode(round($summary['income_net'], 2)) }};
                let rowIndex = 0;

                function addRow(value) {
                    const row = $($('#distributionRowTemplate').html());
                    const index = rowIndex++;
                    row.find('[data-name]').each(function () {
                        $(this).attr('name', 'allocations[' + index + '][' + $(this).data('name') + ']');
                    });
                    if (value) row.find('.distribution-value').val(value.toFixed(2));
                    $('#distributionRows').append(row);
                    row.find('.distribution-account').select2({ width: '100%' });
                    renumberRows();
                    updateTotals();
                }

                function renumberRows() {
                    $('#distributionRows tr').each(function (index) {
                        $(this).find('.row-number').text(index + 1);
                    });
                }

                function updateTotals() {
                    let debit = 0;
                    let credit = 0;
                    $('.distribution-value').each(function () {
                        const value = parseFloat($(this).val()) || 0;
                        const movement = $(this).closest('tr').find('.distribution-movement').val();
                        movement === 'debtor' ? debit += value : credit += value;
                    });
                    const net = debit - credit;
                    const remaining = targetNet - net;
                    $('#distributionTotal').text((debit + credit).toFixed(2));
                    $('#distributionRemaining')
                        .text(Math.abs(remaining) < 0.01 ? 'التوزيع متزن' : 'المتبقي: ' + Math.abs(remaining).toFixed(2))
                        .toggleClass('text-success', Math.abs(remaining) < 0.01)
                        .toggleClass('text-danger', Math.abs(remaining) >= 0.01);
                    $('#submitClosing').prop('disabled', Math.abs(remaining) >= 0.01);
                }

                $('#addDistributionRow').on('click', function () { addRow(); });
                $(document).on('click', '.remove-row', function () {
                    $(this).closest('tr').remove();
                    renumberRows();
                    updateTotals();
                });
                $(document).on('input change', '.distribution-value, .distribution-movement', updateTotals);
                $(document).on('change', '.distribution-account', function () {
                    const code = $(this).find(':selected').data('code') || '-';
                    $(this).closest('tr').find('.account-code').text(code);
                });
                $('#fiscalClosingForm').on('submit', function () {
                    $('#submitClosing').prop('disabled', true).text('جاري إنشاء القيد...');
                });

                addRow(Math.abs(targetNet));
            });
        </script>
    @endif
@endsection

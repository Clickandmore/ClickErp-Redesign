@extends('admin.layout.indexs.index')

@section('page-title', 'فواتير المبيعات بالصافي بعد التكاليف البنكية')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">فواتير المبيعات بالصافي بعد التكاليف البنكية</h4>
                <div class="text-muted">
                    حدّد الفواتير المحصلة من منصة مساند ثم أنشئ قيد التحصيل من شاشة التحويلات.
                </div>
            </div>
        </div>

        <form method="GET" class="mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="status">الحالة</label>
                    <select class="form-control" id="status" name="status">
                        <option value="pending" @selected($status === 'pending')>غير مسوّاة</option>
                        <option value="settled" @selected($status === 'settled')>مسوّاة</option>
                        <option value="all" @selected($status === 'all')>الكل</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="search">بحث برقم الفاتورة أو العقد أو العميل أو رقم مساند</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="from">من تاريخ</label>
                    <input type="date" class="form-control" id="from" name="from" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="to">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to" name="to" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-dark w-100" type="submit"><i class="fas fa-filter me-1"></i>تصفية</button>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-2">
            <div class="col-md-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">إجمالي الفواتير المعروضة</div>
                    <h5 class="mb-0">{{ number_format($totals['total_due'], 2) }}</h5>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">منها غير مسوّاة</div>
                    <h5 class="mb-0">{{ number_format($totals['pending_total'], 2) }}</h5>
                    <small class="text-muted">بانتظار قيد التحصيل</small>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">التكاليف البنكية على المسوّاة</div>
                    <h5 class="mb-0">{{ number_format($totals['commission'], 2) }}</h5>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">الصافي المحوَّل للمسوّاة</div>
                    <h5 class="mb-0">{{ number_format($totals['net'], 2) }}</h5>
                </div></div>
            </div>
        </div>

        <p class="text-muted mb-3">
            إجمالي الفواتير المسوّاة {{ number_format($totals['settled_total'], 2) }}
            = عمولة {{ number_format($totals['commission'], 2) }}
            + صافي محوَّل {{ number_format($totals['net'], 2) }}.
        </p>

        <form method="GET" action="{{ route('musaned-transfers.create') }}">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width:36px"></th>
                                <th>الفاتورة</th>
                                <th>المصدر</th>
                                <th>العميل</th>
                                <th>رقم مساند</th>
                                <th>الدولة</th>
                                <th>التاريخ</th>
                                <th>إجمالي الفاتورة</th>
                                <th>العمولة</th>
                                <th>ضريبة العمولة</th>
                                <th>الصافي</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        @if (!$row['settled'] && $row['contract_id'])
                                            <input type="checkbox" name="contract_ids[]" value="{{ $row['contract_id'] }}">
                                        @elseif (!$row['settled'] && $row['ingaz_delegation_id'])
                                            <input type="checkbox" name="ingaz_delegation_ids[]" value="{{ $row['ingaz_delegation_id'] }}">
                                        @endif
                                    </td>
                                    <td>#{{ $row['invoice_id'] }}</td>
                                    <td>{{ $row['source_label'] }}</td>
                                    <td>{{ $row['client'] }}</td>
                                    <td>{{ $row['msaned_number'] }}</td>
                                    <td>{{ $row['nationality'] }}</td>
                                    <td>{{ $row['date'] }}</td>
                                    <td>{{ number_format($row['total_due'], 2) }}</td>
                                    <td>{{ $row['commission'] === null ? '-' : number_format($row['commission'], 2) }}</td>
                                    <td>{{ $row['expense_tax'] === null ? '-' : number_format($row['expense_tax'], 2) }}</td>
                                    <td>{{ $row['net'] === null ? '-' : number_format($row['net'], 2) }}</td>
                                    <td>
                                        @if ($row['settled'])
                                            <a class="badge bg-success text-white"
                                               href="{{ route('musaned-transfers.show', $row['transfer_id']) }}">
                                                مسوّاة — تحويل #{{ $row['transfer_id'] }}
                                            </a>
                                        @else
                                            <span class="badge bg-warning">غير مسوّاة</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">لا توجد فواتير مطابقة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex align-items-center gap-3">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-hand-holding-usd me-1"></i>إنشاء قيد تحصيل للفواتير المحددة
                    </button>
                    <span class="text-muted">
                        العمولة والصافي يظهران بعد التسوية، لأن مساند تحسم العمولة وقت التحويل.
                        نسبة ضريبة المصروفات المعتمدة: {{ number_format($expenseTaxRate, 2) }}%.
                    </span>
                </div>
            </div>
        </form>

        <div class="mt-3">{{ $invoices->links() }}</div>
    </div>
@endsection

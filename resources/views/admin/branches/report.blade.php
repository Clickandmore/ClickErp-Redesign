@extends('admin.layout.indexs.index')

@section('title', 'تقرير الفروع')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h3 class="mb-1">تقرير الفروع</h3>
                <div class="text-muted">مقارنة تشغيلية سريعة حسب الفرع.</div>
            </div>
            <a href="{{ route('branches.index') }}" class="btn btn-light">
                <i class="fa fa-arrow-right me-1"></i>الفروع
            </a>
        </div>

        <form method="GET" class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-4'])
                    <div class="col-md-2">
                        <button class="btn btn-dark w-100" type="submit">
                            <i class="fa fa-filter me-1"></i>تصفية
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('branches.report') }}" class="btn btn-outline-secondary w-100">الكل</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted">العملاء</div>
                        <h3 class="mb-0">{{ number_format($totals['clients'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted">العقود</div>
                        <h3 class="mb-0">{{ number_format(($totals['contracts'] ?? 0) + ($totals['rental_contracts'] ?? 0)) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted">الطلبات</div>
                        <h3 class="mb-0">{{ number_format($totals['orders'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted">إجمالي الفواتير</div>
                        <h3 class="mb-0">{{ number_format(($totals['invoice_total'] ?? 0) + ($totals['rental_invoice_total'] ?? 0), 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>الفرع</th>
                        <th>العملاء</th>
                        <th>الموظفون</th>
                        <th>الطلبات</th>
                        <th>العقود</th>
                        <th>التأجير</th>
                        <th>الفواتير</th>
                        <th>الشكاوى</th>
                        <th>طلبات مالية</th>
                        <th>نقل كفالة</th>
                        <th>تفويضات</th>
                        <th>مساند</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['branch']->name_ar ?: $row['branch']->name_en }}</strong>
                                <div class="text-muted small">{{ $row['branch']->code ?: '-' }}</div>
                            </td>
                            <td>{{ number_format($row['clients']) }}</td>
                            <td>{{ number_format($row['admins']) }}</td>
                            <td>{{ number_format($row['orders']) }}</td>
                            <td>{{ number_format($row['contracts']) }}</td>
                            <td>{{ number_format($row['rental_contracts']) }}</td>
                            <td>
                                {{ number_format($row['invoices'] + $row['rental_invoices']) }}
                                <div class="text-muted small">{{ number_format($row['invoice_total'] + $row['rental_invoice_total'], 2) }}</div>
                            </td>
                            <td>{{ number_format($row['complaints']) }}</td>
                            <td>{{ number_format($row['financial_requests']) }}</td>
                            <td>{{ number_format($row['sponsorship_transfers'] + $row['transfer_contracts']) }}</td>
                            <td>{{ number_format($row['delegations']) }}</td>
                            <td>{{ number_format($row['musaned_transfers']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">لا توجد بيانات فروع.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

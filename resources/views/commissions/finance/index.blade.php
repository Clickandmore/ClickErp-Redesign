@extends('admin.layout.indexs.index')

@section('page-title', 'الإدارة المالية للعمولات')

@section('page-links')
    <li class="breadcrumb-item">الإدارة المالية</li>
    <li class="breadcrumb-item active">صرف العمولات</li>
@endsection

@section('content')
    <style>
        .finance-commission-hero {
            background: linear-gradient(135deg, #064e3b, #047857);
            color: #fff;
            border-radius: 16px;
        }

        .finance-stat {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .07);
        }

        .finance-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            display: grid;
            place-items: center;
        }

        .employee-dot {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #ecfdf5;
            color: #047857;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .flow-line {
            position: relative;
        }

        .flow-step {
            border: 1px solid #dbeafe;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            min-height: 82px;
        }

        .flow-number {
            width: 28px;
            height: 28px;
            display: inline-grid;
            place-items: center;
            border-radius: 50%;
            background: #047857;
            color: #fff;
            font-weight: 800;
        }
    </style>

    <div class="finance-commission-hero p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="small opacity-75 mb-1">الإدارة المالية ← سندات الصرف</div>
            <h4 class="fw-bold mb-2">مركز صرف عمولات المسوقين</h4>
            <div class="opacity-75">يعرض الأرصدة المعتمدة من التشغيل، ويصدر سندات الصرف ويتابع ما تم دفعه.</div>
        </div>
        <a href="{{ route('commissions.marketers.index') }}" class="btn btn-light">
            <i class="fa fa-clipboard-check me-1"></i> الذهاب للمراجعة التشغيلية
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-sm-6">
            <div class="card finance-stat h-100">
                <div class="card-body d-flex gap-3 align-items-center">
                    <div class="finance-stat-icon bg-primary bg-opacity-10 text-primary"><i class="fa fa-check-double"></i>
                    </div>
                    <div>
                        <div class="text-muted small">عمولات معتمدة</div>
                        <div class="fs-4 fw-bold">{{ number_format($approvedTotal, 2) }} <small class="fs-6">ر.س</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card finance-stat h-100">
                <div class="card-body d-flex gap-3 align-items-center">
                    <div class="finance-stat-icon bg-success bg-opacity-10 text-success"><i
                            class="fa fa-money-check-alt"></i></div>
                    <div>
                        <div class="text-muted small">إجمالي المصروف</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($paidTotal, 2) }} <small
                                class="fs-6">ر.س</small></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card finance-stat h-100">
                <div class="card-body d-flex gap-3 align-items-center">
                    <div class="finance-stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa fa-wallet"></i></div>
                    <div>
                        <div class="text-muted small">الرصيد المستحق</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($outstandingTotal, 2) }} <small
                                class="fs-6">ر.س</small></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card finance-stat h-100">
                <div class="card-body d-flex gap-3 align-items-center">
                    <div class="finance-stat-icon bg-info bg-opacity-10 text-info"><i class="fa fa-receipt"></i></div>
                    <div>
                        <div class="text-muted small">سندات الصرف</div>
                        <div class="fs-4 fw-bold">{{ number_format($vouchersCount) }}</div>
                        <div class="small text-muted">{{ $employeesWithBalance }} موظف لديه رصيد</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-1 fw-bold">كيف تصل العمولة إلى الصرف؟</h5>
            <div class="small text-muted">فصل واضح بين مسؤولية التشغيل ومسؤولية الإدارة المالية.</div>
        </div>
        <div class="card-body">
            <div class="row g-3 flow-line">
                <div class="col-lg-3 col-sm-6">
                    <div class="flow-step"><span class="flow-number">1</span><strong class="ms-2">مراجعة التشغيل</strong>
                        <div class="small text-muted mt-2">اختيار الطلبات والعقود الصحيحة.</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="flow-step"><span class="flow-number">2</span><strong class="ms-2">اعتماد العمولة</strong>
                        <div class="small text-muted mt-2">تُضاف القيمة إلى رصيد الموظف.</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="flow-step"><span class="flow-number">3</span><strong class="ms-2">إصدار السند</strong>
                        <div class="small text-muted mt-2">تحدد المالية المبلغ وتاريخ الصرف.</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="flow-step"><span class="flow-number">4</span><strong class="ms-2">الطباعة
                            والأرشفة</strong>
                        <div class="small text-muted mt-2">يخصم المبلغ ويحفظ سند قابل للطباعة.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">أرصدة موظفي خدمة العملاء</h5>
                <div class="small text-muted">اختر الموظف الذي تريد إصدار أو مراجعة سنداته.</div>
            </div>
            <span class="badge bg-light text-dark border">{{ $employees->count() }} موظف</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="commissionEmployeesTable">
                <thead class="table-light">
                    <tr>
                        <th>الموظف</th>
                        <th class="text-end">إجمالي المصروف</th>
                        <th class="text-center">السندات</th>
                        <th class="text-end">الرصيد المتاح</th>
                        <th class="text-center">الإجراء المالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="employee-dot">{{ mb_substr($employee->name, 0, 1) }}</div>
                                    <div><strong>{{ $employee->name }}</strong>
                                        <div class="small text-muted">#{{ $employee->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">{{ number_format($employee->commission_paid_total, 2) }} ر.س</td>
                            <td class="text-center"><span
                                    class="badge bg-light text-dark border">{{ $employee->commission_vouchers_count }}</span>
                            </td>
                            <td
                                class="text-end fw-bold {{ $employee->commission_balance > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ number_format($employee->commission_balance, 2) }} ر.س</td>
                            <td class="text-center"><a
                                    href="{{ route('commissions.marketers.payments.index', $employee->id) }}"
                                    class="btn btn-sm {{ $employee->commission_balance > 0 ? 'btn-success' : 'btn-outline-secondary' }}"><i
                                        class="fa fa-receipt me-1"></i>{{ $employee->commission_balance > 0 ? 'إصدار سند' : 'عرض السندات' }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">آخر سندات صرف العمولات</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>رقم السند</th>
                        <th>الموظف</th>
                        <th>التاريخ</th>
                        <th class="text-end">المبلغ</th>
                        <th>المعتمد</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                        <tr>
                            <td class="fw-bold">#{{ $payment->id }}</td>
                            <td>{{ $payment->marketer?->name ?? '-' }}</td>
                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($payment->amount, 2) }} ر.س</td>
                            <td>{{ $payment->approvedBy?->name ?? '-' }}</td>
                            <td><a target="_blank" class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('commissions.marketers.payments.print', [$payment->marketer_id, $payment->id]) }}"><i
                                        class="fa fa-print"></i></a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لم تصدر سندات عمولات حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

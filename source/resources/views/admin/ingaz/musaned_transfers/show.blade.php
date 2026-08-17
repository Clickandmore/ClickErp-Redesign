@extends('admin.layout.indexs.index')

@section('page-title', 'عرض تحويل مساند')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">تحويل مساند #{{ $transfer->id }}</h4>
                <div class="text-muted">
                    مرجع الحوالة: {{ $transfer->reference_number ?: 'بدون مرجع' }} |
                    التاريخ: {{ optional($transfer->transfer_date)->format('Y-m-d') }}
                </div>
            </div>
            <div class="d-flex gap-2">
                @if (AdminGuardHasAnyPerm([\App\PermKeys::MUSANED_TRANSFERS_CREATE, \App\PermKeys::MUSANED_TRANSFERS_CONTROL, \App\PermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                    <a href="{{ route('musaned-transfers.create') }}" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-1"></i>تحويل جديد
                    </a>
                @endif
                <a href="{{ route('musaned-transfers.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>الرجوع
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted mb-1">إجمالي قيمة العميل</div>
                        <div class="fs-4 fw-bold">{{ number_format((float) $transfer->customer_paid_total, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted mb-1">إجمالي المحول من مساند</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $transfer->transferred_total, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted mb-1">إجمالي العمولة</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format((float) $transfer->commission_total, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted mb-1">إجمالي ضريبة المصروفات</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format((float) $transfer->expense_tax_total, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <strong>الحساب البنكي:</strong>
                        <div>{{ $transfer->bankAccount->code ?? '-' }} / {{ $transfer->bankAccount->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <strong>الحساب الوسيط:</strong>
                        <div>{{ $transfer->intermediateAccount->code ?? '-' }} / {{ $transfer->intermediateAccount->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <strong>حساب العمولة:</strong>
                        <div>{{ $transfer->commissionAccount->code ?? '-' }} / {{ $transfer->commissionAccount->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <strong>حساب ضريبة المصروفات:</strong>
                        <div>{{ $transfer->expenseTaxAccount->code ?? '-' }} / {{ $transfer->expenseTaxAccount->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <strong>نسبة ضريبة المصروفات:</strong>
                        <div>{{ number_format((float) $transfer->expense_tax_rate, 2) }}%</div>
                    </div>
                    <div class="col-md-4">
                        <strong>المستخدم:</strong>
                        <div>{{ $transfer->createdBy->name ?? 'النظام' }}</div>
                    </div>
                    @if ($transfer->notes)
                        <div class="col-12">
                            <strong>ملاحظات:</strong>
                            <div class="mt-1">{{ $transfer->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>العقود والتفاويض المشمولة</strong>
                <div class="d-flex gap-2">
                    @if ($transfer->settlementConstraint)
                        <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $transfer->settlementConstraint->id]) }}"
                            class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye me-1"></i>عرض قيد التسوية
                        </a>
                    @endif
                    @if ($transfer->bankTransferConstraint)
                        <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $transfer->bankTransferConstraint->id]) }}"
                            class="btn btn-sm btn-outline-success" target="_blank">
                            <i class="fas fa-eye me-1"></i>عرض قيد التحويل البنكي
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>البند</th>
                                <th>العميل</th>
                                <th>رقم مساند</th>
                                <th>قيمة العميل</th>
                                <th>المحول من مساند</th>
                                <th>العمولة</th>
                                <th>ضريبة المصروفات</th>
                                <th>صافي العمولة</th>
                                <th>حساب المصدر</th>
                                <th>مركز التكلفة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfer->items as $item)
                                <tr>
                                    <td>
                                        @if ($item->contract_id)
                                            <a href="{{ route('contracts.details', $item->contract_id) }}" target="_blank">
                                                عقد #{{ $item->contract_id }}
                                            </a>
                                        @else
                                            <a href="{{ route('ingaz-delegations.show', $item->ingaz_delegation_id) }}" target="_blank">
                                                تفويض #{{ $item->ingaz_delegation_id }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $item->contract?->user?->name ?? $item->ingazDelegation?->user?->name ?? 'غير محدد' }}</td>
                                    <td>{{ $item->invoice?->contract?->info?->msaned_number ?? $item->invoice?->ingazDelegation?->msaned_contract_number ?? '-' }}</td>
                                    <td>{{ number_format((float) $item->customer_paid_amount, 2) }}</td>
                                    <td>{{ number_format((float) $item->transferred_amount, 2) }}</td>
                                    <td>{{ number_format((float) $item->commission_amount, 2) }}</td>
                                    <td>{{ number_format((float) $item->expense_tax_amount, 2) }}</td>
                                    <td>{{ number_format((float) $item->commission_net_amount, 2) }}</td>
                                    <td>{{ $item->sourceAccount->code ?? '-' }} / {{ $item->sourceAccount->name ?? '-' }}</td>
                                    <td>{{ $item->costCenter->title ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

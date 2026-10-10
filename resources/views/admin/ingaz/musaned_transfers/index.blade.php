@extends('admin.layout.indexs.index')

@section('page-title', 'تحويلات مساند')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">تحويلات مساند</h4>
                <div class="text-muted">متابعة الحوالات المفردة والمجمعة وربطها بقيودها المحاسبية.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('musaned-transfers.invoices-report') }}" class="btn btn-outline-dark">
                    <i class="fas fa-file-invoice-dollar me-1"></i>فواتير المبيعات بالصافي
                </a>
                @if (AdminGuardHasAnyPerm([\App\PermKeys::MUSANED_TRANSFERS_CREATE, \App\PermKeys::MUSANED_TRANSFERS_CONTROL, \App\PermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                    <a href="{{ route('musaned-transfers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>إضافة تحويل مساند
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" class="mb-3">
            <div class="row g-3 align-items-end">
                @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-3'])
                <div class="col-md-2">
                    <button class="btn btn-dark w-100" type="submit">
                        <i class="fas fa-filter me-1"></i>تصفية
                    </button>
                </div>
            </div>
        </form>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>مرجع الحوالة</th>
                                <th>عدد البنود</th>
                                <th>إجمالي العميل</th>
                                <th>المحول من مساند</th>
                                <th>العمولة</th>
                                <th>البنك</th>
                                <th class="text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transfers as $transfer)
                                <tr>
                                    <td>{{ $transfer->id }}</td>
                                    <td>{{ optional($transfer->transfer_date)->format('Y-m-d') }}</td>
                                    <td>{{ $transfer->reference_number ?: 'بدون مرجع' }}</td>
                                    <td>{{ $transfer->contracts_count }}</td>
                                    <td>{{ number_format((float) $transfer->customer_paid_total, 2) }}</td>
                                    <td>{{ number_format((float) $transfer->transferred_total, 2) }}</td>
                                    <td>{{ number_format((float) $transfer->commission_total, 2) }}</td>
                                    <td>{{ $transfer->bankAccount->name ?? '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('musaned-transfers.show', $transfer->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>عرض
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        لا توجد تحويلات مساند مسجلة حالياً.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            {{ $transfers->links() }}
        </div>
    </div>
@endsection

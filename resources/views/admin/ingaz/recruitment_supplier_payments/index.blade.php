@extends('admin.layout.indexs.index')

@section('page-title', 'دفعات الوكلاء الخارجيين')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">دفعات الوكلاء الخارجيين</h4>
                <div class="text-muted">فاتورة الوكيل مُثبتة بالكامل على العقد، وهذه الشاشة تسدّدها على دفعات.</div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @unless ($paymentsEnabled)
            <div class="alert alert-warning">
                تسجيل الدفعات من هذه الشاشة غير مفعّل. الوضع الحالي هو سند الصرف العام من شاشة المالية.
                للتفعيل: إعدادات الحسابات ← قسم «قيود المشتريات والوكلاء الخارجيون» ← سداد دفعات الوكيل الخارجي.
                العرض أدناه كشف أرصدة فقط.
            </div>
        @endunless

        @unless ($automaticEntriesEnabled)
            <div class="alert alert-info">القيود التلقائية غير مفعّلة، فلن يُنشأ قيد للدفعة.</div>
        @endunless

        <form method="GET" class="mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="search">بحث برقم العقد أو اسم العميل أو العاملة</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-dark w-100" type="submit"><i class="fas fa-filter me-1"></i>تصفية</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>العقد</th>
                            <th>العميل</th>
                            <th>العاملة</th>
                            <th>الدولة</th>
                            <th>المكتب الخارجي</th>
                            <th>فاتورة الوكيل</th>
                            <th>المسدَّد</th>
                            <th>المتبقي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>#{{ $row['contract_id'] }}</td>
                                <td>{{ $row['client'] }}</td>
                                <td>{{ $row['worker'] }}</td>
                                <td>{{ $row['nationality'] !== '' ? $row['nationality'] : '-' }}</td>
                                <td>{{ $row['office'] }}</td>
                                <td>{{ number_format($row['invoiced'], 2) }}</td>
                                <td>{{ number_format($row['paid'], 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $row['remaining'] > 0 ? 'warning' : 'success' }}">
                                        {{ number_format($row['remaining'], 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if ($paymentsEnabled && $row['remaining'] > 0)
                                        <a class="btn btn-sm btn-primary"
                                           href="{{ route('recruitment-supplier-payments.create', $row['contract_id']) }}">
                                            <i class="fas fa-money-bill-wave me-1"></i>تسجيل دفعة
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    لا توجد عقود مُثبتة عليها فاتورة وكيل خارجي بعد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $contracts->links() }}</div>
    </div>
@endsection

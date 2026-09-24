@extends('admin.layout.indexs.index')

@section('page-title', 'الاسترداد بعد الوصول')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="mb-1">الاسترداد بعد الوصول</h4><div class="text-muted">مستند مالي مستقل؛ أحداث الإرجاع والإيواء لا تنشئ قيوداً.</div></div>
        @if(AdminGuardHasPerm(\App\PermKeys::POST_ARRIVAL_REFUND_CREATE))
            <a href="{{ route('post-arrival-refunds.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>إنشاء مستند</a>
        @endif
    </div>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <form method="get" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">بحث</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="رقم المستند أو العقد أو العميل أو العاملة"></div>
            <div class="col-md-3"><label class="form-label">الحالة</label><select name="status" class="form-select"><option value="">الكل</option>@foreach(['draft'=>'مسودة','posted'=>'معتمد','partially_paid'=>'مسدد جزئياً','closed'=>'مغلق'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select></div>
            @include('admin.partials.branch-filter', ['id' => 'refundBranchFilter', 'containerClass' => 'col-md-3'])
            <div class="col-md-2"><button class="btn btn-dark w-100">تصفية</button></div>
        </div>
    </form>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>#</th><th>التاريخ</th><th>العقد</th><th>العميل / العاملة</th><th>السبب</th><th>البنود</th><th>مستحق العميل</th><th>الحالة</th><th></th></tr></thead>
        <tbody>@forelse($settlements as $item)<tr>
            <td>{{ $item->id }}</td><td>{{ optional($item->settlement_date)->format('Y-m-d') }}</td><td>#{{ $item->contract_id }}</td>
            <td>{{ $item->contract?->user?->name ?: '-' }}<br><small class="text-muted">{{ $item->contract?->maid?->name ?: '-' }}</small></td>
            <td>{{ $item->reasonCode?->name ?: $item->reason }}</td><td>{{ $item->lines_count }}</td><td>{{ number_format((float)$item->client_due_amount,2) }}</td>
            <td><span class="badge bg-{{ $item->status==='draft'?'warning':'success' }}">{{ ['draft'=>'مسودة','posted'=>'معتمد','partially_paid'=>'مسدد جزئياً','closed'=>'مغلق'][$item->status] ?? $item->status }}</span></td>
            <td><a href="{{ route('post-arrival-refunds.show',$item) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
        </tr>@empty<tr><td colspan="9" class="text-center py-5 text-muted">لا توجد مستندات مسجلة.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $settlements->links() }}</div>
</div>
@endsection

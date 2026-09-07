@extends('click.layout.indexs.index')
@php($procurementTitle = request('kind') === 'request' ? 'طلبات الشراء' : (request('kind') === 'invoice' ? 'فواتير الشراء' : 'المشتريات'))
@section('page-title', $procurementTitle)
@section('content')
<div class="container-fluid" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div><h3>{{ $procurementTitle }}</h3><p class="text-muted">طلبات المشتريات وفواتير الموردين العامين والمدفوعات — بالريال السعودي</p></div>
        <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('finance.procurement.create',['kind'=>'request']) }}">طلب مشتريات جديد</a><a class="btn btn-primary" href="{{ route('finance.procurement.create',['kind'=>'invoice']) }}">فاتورة مشتريات جديدة</a></div>
    </div>
    <div class="alert alert-info">الدورة: مسودة طلب ← اعتماد ← تحويل لفاتورة ← ترحيل ← سداد. يمكن إنشاء فاتورة مباشرة. يمكن الشراء من أي مورد عام للخدمات أو المستلزمات، دون اشتراط مكتب أو وكيل خارجي أو عقد استقدام.</div>
    <form method="GET" class="card card-body mb-3"><div class="row g-2">
        <div class="col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="رقم المستند أو المورد أو مرجع الفاتورة"></div>
        <div class="col-md-2"><select class="form-select" name="kind"><option value="">كل المستندات</option value="request" {{ (request('kind')==='request') ? 'selected' : '' }}>طلبات المشتريات</option><option value="invoice" {{ (request('kind')==='invoice') ? 'selected' : '' }}>فواتير المشتريات</option></select></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">كل الحالات</option>@foreach(\Nami\Finance\Models\ProcurementDocument::STATUSES as $key=>$label)<option value="{{ $key }}" {{ (request('status')===$key) ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        @if($branchFilter['enabled'])<div class="col-md-3"><select name="branch_id" class="form-select"><option value="">كل الفروع المتاحة</option>@foreach($branchFilter['branches'] as $branch)<option value="{{ $branch->id }}" {{ ($branchFilter['selected_branch_id']==$branch->id) ? 'selected' : '' }}>{{ $branch->name_ar }}</option>@endforeach</select></div>@endif
        <div class="col-md-2"><button class="btn btn-dark">بحث</button> <a href="{{ route('finance.procurement.index') }}" class="btn btn-light">مسح</a></div>
    </div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>المستند</th><th>المورد</th><th>الفرع</th><th>التاريخ</th><th>الحالة</th><th>الإجمالي</th><th>المتبقي</th></tr></thead><tbody>
    @forelse($documents as $doc)<tr><td><a href="{{ route('finance.procurement.show',$doc->id) }}">{{ $doc->number }}</a></td><td>{{ $doc->supplier?->name }}</td><td>{{ $doc->branch?->name_ar }}</td><td>{{ $doc->date }}</td><td><span class="badge bg-{{ $doc->status==='posted'?'success':'secondary' }}">{{ \Nami\Finance\Models\ProcurementDocument::STATUSES[$doc->status] }}</span></td><td>{{ number_format($doc->total,2) }}</td><td>{{ $doc->status==='posted'?number_format($doc->balance,2):'—' }}</td></tr>
    @empty<tr><td colspan="7" class="text-center p-5">لا توجد مستندات مشتريات مطابقة. ابدأ بإضافة طلب أو فاتورة.</td></tr>@endforelse
    </tbody></table></div><div class="p-3">{{ $documents->links() }}</div></div>
</div>
@endsection

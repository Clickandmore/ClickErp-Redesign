@extends('click.layout.indexs.index')
@section('page-title', $doc->number)
@section('content')
<style>@media print {body *{visibility:hidden} #purchase-document,#purchase-document *{visibility:visible} #purchase-document{position:absolute;left:0;top:0;width:100%}.no-print{display:none!important}}</style>
<div class="container-fluid" dir="rtl">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="d-flex gap-2 mb-3 flex-wrap no-print">
<a class="btn btn-light" href="{{ route('finance.procurement.index') }}">المشتريات</a><button class="btn btn-outline-dark" onclick="window.print()">طباعة / PDF</button>
@if($doc->status==='draft')
<a class="btn btn-outline-primary" href="{{ route('finance.procurement.edit',$doc->id) }}">تعديل</a>
<form method="POST" action="{{ route('finance.procurement.approve',$doc->id) }}">@csrf<button class="btn btn-success">{{ $doc->kind==='request'?'اعتماد الطلب':'ترحيل الفاتورة وإنشاء القيد' }}</button></form>
<form method="POST" action="{{ route('finance.procurement.cancel',$doc->id) }}">@csrf<button class="btn btn-outline-danger">إلغاء المسودة</button></form>
@endif
@if($doc->kind==='request' && $doc->status==='approved')<form method="POST" action="{{ route('finance.procurement.convert',$doc->id) }}">@csrf<button class="btn btn-primary">تحويل إلى فاتورة مشتريات</button></form>@endif
@if($doc->invoice)<a class="btn btn-primary" href="{{ route('finance.procurement.show',$doc->invoice->id) }}">عرض الفاتورة {{ $doc->invoice->number }}</a>@endif
@if($doc->source_id)<a class="btn btn-light" href="{{ route('finance.procurement.show',$doc->source_id) }}">طلب المشتريات المرتبط</a>@endif
@if($doc->constraint_id)<a class="btn btn-outline-primary" href="{{ route('finance.constraints.show',$doc->constraint_id) }}">القيد المحاسبي #{{ $doc->constraint?->code }}</a>@endif
</div>
<div class="card card-body p-4" id="purchase-document">
<div class="d-flex justify-content-between mb-4"><div><h3>{{ $doc->kind==='request'?'طلب مشتريات':'فاتورة مشتريات' }} <span dir="ltr">{{ $doc->number }}</span></h3><span class="badge bg-secondary">{{ \Nami\Finance\Models\ProcurementDocument::STATUSES[$doc->status] }}</span></div><div>{{ $doc->branch?->name_ar }}<br>التاريخ: {{ $doc->date }}<br>الاستحقاق: {{ $doc->due_date ?: '—' }}</div></div>
<div class="row mb-4"><div class="col-6"><strong>المورد: {{ $doc->supplier?->name }}</strong><p>{{ $doc->supplier?->address }}<br>الرقم الضريبي: {{ $doc->supplier?->tax_number ?: '—' }}</p></div><div class="col-6">مرجع المورد: {{ $doc->supplier_reference ?: '—' }}<br>عقد الاستقدام: {{ $doc->contract_id ?: '—' }}<br>العملة: الريال السعودي</div></div>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>البند</th><th>الكمية</th><th>سعر الوحدة</th><th>الخصم</th><th>الضريبة %</th><th>قيمة الضريبة</th><th>الإجمالي</th></tr></thead><tbody>
@foreach($doc->lines as $line)<tr><td>{{ $line->description }}</td><td>{{ $line->quantity }}</td><td>{{ number_format($line->unit_price,2) }}</td><td>{{ number_format($line->discount,2) }}</td><td>{{ $line->tax_rate }}</td><td>{{ number_format($line->tax,2) }}</td><td>{{ number_format($line->total,2) }}</td></tr>@endforeach
</tbody></table></div>
<div class="row"><div class="col-md-8"><p style="white-space:pre-wrap">{{ $doc->notes }}</p></div><div class="col-md-4"><table class="table">
@foreach(['subtotal'=>'المجموع','discount'=>'الخصم','tax'=>'الضريبة','total'=>'الإجمالي'] as $key=>$label)<tr><th>{{ $label }}</th><td>{{ number_format($doc->$key,2) }} ر.س</td></tr>@endforeach
@if($doc->status==='posted')<tr><th>المدفوع</th><td>{{ number_format($doc->payments->sum('amount'),2) }}</td></tr><tr><th>المتبقي</th><td>{{ number_format($doc->balance,2) }} — {{ $doc->balance<=0?'مدفوعة':($doc->payments->count()?'مدفوعة جزئياً':'غير مدفوعة') }}</td></tr>@endif
</table></div></div>
@if($doc->approved_at)<p class="text-muted">الاعتماد: {{ $doc->approved_at }}</p>@endif
@if($doc->constraint && $doc->constraint->approval_status==='pending')<div class="alert alert-warning">القيد بانتظار الاعتماد وفق إعدادات الإدارة المالية.</div>@endif
@if($doc->payments->count())<h5>المدفوعات</h5><table class="table"><thead><tr><th>التاريخ</th><th>المبلغ</th><th>المرجع</th><th>القيد</th></tr></thead><tbody>@foreach($doc->payments as $payment)<tr><td>{{ $payment->date }}</td><td>{{ number_format($payment->amount,2) }}</td><td>{{ $payment->reference }}</td><td><a href="{{ route('finance.constraints.show',$payment->constraint_id) }}">عرض القيد</a></td></tr>@endforeach</tbody></table>@endif
</div>
@if($doc->status==='posted' && $doc->balance>0)
<form class="card card-body mt-3 no-print" method="POST" action="{{ route('finance.procurement.pay',$doc->id) }}">@csrf<input type="hidden" name="token" value="{{ old('token',(string)\Illuminate\Support\Str::uuid()) }}"><h5>إضافة دفعة</h5><p class="text-muted">يُسجل قيد سداد مستقل على حساب المورد مقابل البنك أو الخزينة المختارة.</p><div class="row g-3">
<div class="col-md-2"><label>المبلغ</label><input type="number" class="form-control" name="amount" step="0.01" min="0.01" max="{{ $doc->balance }}" value="{{ old('amount',$doc->balance) }}" required></div>
<div class="col-md-2"><label>التاريخ</label><input type="date" class="form-control" name="date" value="{{ old('date',date('Y-m-d')) }}" required></div>
<div class="col-md-4"><label>حساب البنك / الخزينة</label><select class="form-select" name="account_id" required><option value="">اختر الحساب</option>@foreach($cashAccounts as $account)<option value="{{ $account->id }}" {{ (old('account_id')==$account->id) ? 'selected' : '' }}>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label>مرجع السداد</label><input class="form-control" name="reference" maxlength="255" value="{{ old('reference') }}"></div><div class="col-md-1 align-self-end"><button class="btn btn-success">سداد</button></div>
</div></form>@endif
</div>
@endsection

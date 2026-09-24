@extends('admin.layout.indexs.index')

@section('page-title', 'مستند استرداد بعد الوصول')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3"><div><h4>مستند استرداد بعد الوصول #{{ $settlement->id }}</h4><div class="text-muted">العقد #{{ $settlement->contract_id }} — {{ $settlement->contract?->user?->name }} — {{ $settlement->contract?->maid?->name }}</div></div><a href="{{ route('post-arrival-refunds.index') }}" class="btn btn-outline-secondary">القائمة</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card card-body"><small class="text-muted">الحالة</small><strong>{{ ['draft'=>'مسودة','posted'=>'معتمد','partially_paid'=>'مسدد جزئياً','closed'=>'مغلق'][$settlement->status] ?? $settlement->status }}</strong></div></div>
        <div class="col-md-3"><div class="card card-body"><small class="text-muted">مستحق العميل</small><strong>{{ number_format((float)$settlement->client_due_amount,2) }}</strong></div></div>
        <div class="col-md-3"><div class="card card-body"><small class="text-muted">المدفوع/المطلوب</small><strong>{{ number_format((float)$settlement->client_paid_amount,2) }}</strong></div></div>
        <div class="col-md-3"><div class="card card-body"><small class="text-muted">التاريخ</small><strong>{{ optional($settlement->settlement_date)->format('Y-m-d') }}</strong></div></div>
    </div>
    <div class="card mb-3"><div class="card-body"><strong>سبب الإرجاع:</strong> {{ $settlement->reasonCode?->name }} — {{ $settlement->reason }} @if($settlement->notes)<hr>{{ $settlement->notes }}@endif</div></div>
    @php($remainingPayout=max(0,(float)$settlement->client_due_amount-(float)$settlement->lines->where('type','client_payout')->whereIn('status',['posted','paid'])->sum('approved_amount')))
    @if($remainingPayout>0 && in_array($settlement->status,['posted','partially_paid']) && AdminGuardHasPerm(\App\PermKeys::POST_ARRIVAL_REFUND_PAYOUT))
        <form method="post" action="{{ route('post-arrival-refunds.payout',$settlement) }}" class="card card-body mb-3">@csrf
            <div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">مبلغ الصرف/الرصيد</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $remainingPayout }}" value="{{ number_format($remainingPayout,2,'.','') }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">المعالجة</label><select name="method" class="form-select"><option value="financial_request">إنشاء طلب مالي مربوط</option><option value="balance_only">إبقاء رصيد للعميل فقط</option></select></div>
            <div class="col-md-3"><button class="btn btn-primary">تنفيذ البند</button></div><div class="col-md-2 text-muted">المتبقي {{ number_format($remainingPayout,2) }}</div></div>
        </form>
    @endif
    <div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>البند</th><th>المقترح</th><th>المعتمد</th><th>الحسابات</th><th>المستند</th><th>الحالة</th><th></th></tr></thead><tbody>
        @foreach($settlement->lines as $line)<tr>
            <td>{{ $line->typeLabel() }} @if($line->reason)<br><small class="text-muted">{{ $line->reason }}</small>@endif</td><td>{{ number_format((float)$line->proposed_amount,2) }}</td><td>{{ number_format((float)$line->approved_amount,2) }}</td>
            <td>{{ $line->debitAccount?->name ?: '-' }} @if($line->creditAccount) / {{ $line->creditAccount->name }} @endif</td>
            <td>
                @if($line->constraint_id)<a href="{{ route('finance.add-constraint.show',['add_constraint'=>$line->constraint_id]) }}">قيد #{{ $line->constraint?->code ?: $line->constraint_id }}</a>@endif
                @if($line->credit_note_id)<a href="{{ route('recruitment-credit-notes.download',['id'=>$line->credit_note_id]) }}">إشعار #{{ $line->credit_note_id }}</a>@endif
                @if($line->financial_request_id)<a href="{{ route('financial-requests.show',$line->financial_request_id) }}">طلب مالي #{{ $line->financial_request_id }}</a>@endif
            </td>
            <td><span class="badge bg-{{ $line->status==='reversed'?'secondary':(in_array($line->status,['draft','pending'])?'warning':'success') }}">{{ ['draft'=>'مسودة','pending'=>'بانتظار الإرسالية','posted'=>'منشور','paid'=>'مدفوع','reversed'=>'معكوس'][$line->status] ?? $line->status }}</span></td>
            <td>@if($line->status==='posted' && AdminGuardHasPerm(\App\PermKeys::POST_ARRIVAL_REFUND_REVERSE))<form method="post" action="{{ route('post-arrival-refunds.lines.reverse',[$settlement,$line]) }}" onsubmit="return confirm('عكس هذا البند فقط؟')">@csrf<div class="input-group input-group-sm"><input name="reason" class="form-control" required minlength="5" placeholder="سبب العكس"><button class="btn btn-outline-danger">عكس</button></div></form>@endif</td>
        </tr>@endforeach
    </tbody></table></div>
    @if($settlement->status==='draft' && AdminGuardHasPerm(\App\PermKeys::POST_ARRIVAL_REFUND_POST))<div class="card-footer text-end"><form method="post" action="{{ route('post-arrival-refunds.post',$settlement) }}" onsubmit="return confirm('سيتم إنشاء المستندات المالية للبنود المختارة. هل تعتمد؟')">@csrf<button class="btn btn-success"><i class="fas fa-check me-1"></i>اعتماد ونشر البنود</button></form></div>@endif
    </div>
</div>
@endsection

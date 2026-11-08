@extends('admin.layout.indexs.index')
@section('page-title', 'إعادة نقل الكفالة للشركة')
@section('content')
<div class="container-fluid py-4" dir="rtl">
    <a href="{{ route('SponsorshipTransfer.show', $source->id) }}" class="btn btn-outline-secondary mb-3">العودة لطلب النقل السابق #{{ $source->id }}</a>
    <h3>إعادة نقل الكفالة للشركة</h3>
    <p>العاملة: <strong>{{ $source->contract?->maid?->name }}</strong> — الجواز: <strong>{{ $source->contract?->maid?->passport_num }}</strong></p>
    <p>الكفيل المستلم في النقل السابق: {{ $source->newSponsor?->name }} — الشركة المستقبلة: {{ $company?->name ?: 'لم تُسجل بيانات الشركة' }}</p>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    @php($latest = $returns->first())
    @if(!$latest || $latest->status === 'cancelled')
    <form method="post" action="{{ route('SponsorshipTransfer.returnCompany.store', $source->id) }}" class="card card-body mb-4">
        @csrf
        <h5>طلب رجوع جديد</h5>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">تاريخ الطلب</label><input type="date" name="request_date" class="form-control" value="{{ old('request_date', now()->toDateString()) }}" required></div>
            <div class="col-md-4"><label class="form-label">الإيواء المستقبِل</label><select name="shelter_id" class="form-select" required><option value="">اختر الإيواء</option>@foreach($shelters as $shelter)<option value="{{ $shelter->id }}" @selected((string)old('shelter_id') === (string)$shelter->id)>{{ $shelter->title }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control" maxlength="2000">{{ old('notes') }}</textarea></div>
        </div>
        <button class="btn btn-primary mt-3 align-self-start" @disabled(!$company || $source->status !== 'transferred')>إنشاء طلب إعادة الكفالة للشركة</button>
    </form>
    @endif
    @foreach($returns as $operation)
    <div class="card card-body mb-4">
        <h5>طلب الرجوع #{{ $operation->id }} — {{ \App\Models\RentalSponsorshipReturn::LABELS[$operation->status] }}</h5>
        <p>تاريخ الطلب: {{ $operation->request_date->format('Y-m-d') }} — الإيواء: {{ $operation->shelter?->title }} — الشركة: {{ $operation->company?->name }}</p>
        <p>{{ $operation->notes }}</p>
        @if($operation->status === 'received')<div class="alert alert-info">العاملة داخل الإيواء، والكفالة ما زالت على الكفيل الحالي حتى تأكيد اكتمال النقل للشركة.</div>@endif
        @if($operation->status === 'transferred')<div class="alert alert-success">اكتمل النقل إلى الشركة. إتاحة العاملة للإيجار حسب الجاهزية المحددة عند التأكيد، وظهورها على الموقع يُدار من إعدادات الإظهار المعتادة.</div>@endif
        @if($next = \App\Models\RentalSponsorshipReturn::NEXT[$operation->status] ?? null)
        <form method="post" enctype="multipart/form-data" action="{{ route('SponsorshipTransfer.returnCompany.advance', $operation->id) }}" class="mb-3">
            @csrf<input type="hidden" name="status" value="{{ $next }}">
            @if($next === 'transferred')<label class="form-label">جاهزية العاملة للإيجار بعد النقل</label><select class="form-select mb-2" name="ready_for_rental" required><option value="">اختر الجاهزية</option><option value="1">جاهزة وترغب في العمل</option><option value="0">غير جاهزة حاليًا</option></select>@endif
            <label class="form-label">مرفق المرحلة (PDF أو صورة، اختياري)</label><input type="file" name="attachment" class="form-control mb-2" accept=".pdf,.jpg,.jpeg,.png">
            <button class="btn btn-primary">{{ ['approved'=>'اعتماد الطلب','contract_sent'=>'تأكيد إرسال العقد','contract_signed'=>'تأكيد توقيع العقد','received'=>'تأكيد استلام العاملة داخل الإيواء','transferred'=>'تأكيد اكتمال نقل الكفالة للشركة'][$next] }}</button>
        </form>
        <form method="post" action="{{ route('SponsorshipTransfer.returnCompany.advance', $operation->id) }}" class="d-flex gap-2">
            @csrf<input type="hidden" name="status" value="cancelled"><input name="reason" class="form-control" placeholder="سبب إلغاء الطلب" maxlength="2000" required><button class="btn btn-outline-danger">إلغاء الطلب</button>
        </form>
        @endif
        <div class="table-responsive mt-3"><table class="table"><thead><tr><th>المرحلة</th><th>التاريخ</th><th>الموظف</th><th>الملاحظات والمرفقات</th></tr></thead><tbody>
        @foreach($operation->history as $index => $event)<tr><td>{{ \App\Models\RentalSponsorshipReturn::LABELS[$event['status']] }}</td><td>{{ $event['at'] }}</td><td>#{{ $event['admin_id'] }}</td><td>{{ $event['reason'] ?? '' }} @if(!empty($event['attachment']))<a href="{{ route('SponsorshipTransfer.returnCompany.attachment', [$operation->id, $index]) }}">تنزيل المرفق</a>@endif</td></tr>@endforeach
        </tbody></table></div>
    </div>
    @endforeach
</div>
@endsection

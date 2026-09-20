@extends('admin.layout.indexs.index')
@section('content')
<div class="card" dir="rtl">
    <div class="card-header d-flex justify-content-between">
        <h4>بلاغ هروب للعقد رقم {{ $contract->id }}</h4>
        <a href="{{ route('RecruitmentContract.index') }}" class="btn btn-outline-secondary">العودة للعقود</a>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @if($report)
            <div class="alert alert-danger">يوجد بلاغ هروب مسجل لهذا العقد. يرجى حذف البلاغ الحالي قبل إضافة بلاغ جديد.</div>
            <p>تاريخ الهروب: {{ $report->escape_date->format('Y-m-d') }} — مسجل البلاغ: {{ $report->reporter?->name }}</p>
            <p>حالة العقد وقت الهروب: {{ $report->contract_status_at_escape }}</p>
            <div class="border rounded p-3 mb-3" style="white-space: pre-wrap">{{ $report->notes }}</div>
            @foreach($report->attachments as $index => $file)
                <a class="btn btn-outline-primary mb-2" href="{{ route('RecruitmentContract.escapeReports.attachment', [$contract->id, $report->id, $index]) }}">{{ $file['name'] }}</a>
            @endforeach
            @if(AdminGuardHasPerm(\App\PermKeys::REC_CONTRACTS_ESCAPE_REPORT_DELETE))
                <form method="POST" action="{{ route('RecruitmentContract.escapeReports.destroy', [$contract->id, $report->id]) }}" onsubmit="return confirm('هل تريد حذف بلاغ الهروب ومرفقاته؟');">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger mt-3">حذف البلاغ</button>
                </form>
            @endif
        @elseif($contract->status === 'completed' && AdminGuardHasPerm(\App\PermKeys::REC_CONTRACTS_ESCAPE_REPORT_CREATE))
            <form method="POST" action="{{ route('RecruitmentContract.escapeReports.store', $contract->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3"><label for="escape_date" class="form-label">تاريخ هروب العاملة</label>
                    <input id="escape_date" type="date" name="escape_date" class="form-control" value="{{ old('escape_date', now()->toDateString()) }}" max="{{ now()->addDays(5)->toDateString() }}" required>
                </div>
                <div class="alert alert-info">
                    <div>{{ $formula['formula_text'] }}</div>
                    <div>إجمالي تكلفة الاستقدام: {{ number_format($formula['total_recruitment_cost'], 2) }} ريال</div>
                    <div>مدة العقد: {{ $formula['contract_duration_days'] }} يوم</div>
                    <div>المدة المتبقية: <span id="escape_remaining"></span> يوم — الناتج: <span id="escape_amount"></span> ريال</div>
                    <small>تُحفظ الحسبة مع البلاغ حسب تاريخ الهروب المحدد.</small>
                </div>
                <div class="mb-3"><label for="escape_notes" class="form-label">ملاحظات هروب العاملة</label><textarea id="escape_notes" name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea></div>
                <div class="mb-3"><label for="escape_attachments" class="form-label">المرفقات (مطلوبة)</label>
                    <input id="escape_attachments" type="file" name="attachments[]" class="form-control" multiple required accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                    <small>صورة أو مستند واحد على الأقل، بحد أقصى 10 ميجابايت لكل ملف.</small>
                </div>
                <button class="btn btn-primary" type="submit">حفظ بلاغ الهروب</button>
            </form>
            <script>
                (() => {
                    const formula = @json($formula);
                    const input = document.getElementById('escape_date');
                    function update() {
                        const end = Date.parse(formula.contract_end_date);
                        const date = Date.parse(input.value);
                        const days = Number.isFinite(end) && Number.isFinite(date) ? Math.max(0, Math.round((end - date) / 86400000)) : 0;
                        document.getElementById('escape_remaining').textContent = days;
                        document.getElementById('escape_amount').textContent = (formula.contract_duration_days > 0 ? formula.total_recruitment_cost / formula.contract_duration_days * days : 0).toFixed(2);
                    }
                    input.addEventListener('change', update); update();
                })();
            </script>
        @else
            <div class="alert alert-info">يمكن إضافة بلاغ هروب للعقود المكتملة فقط وبصلاحية إضافة بلاغ.</div>
        @endif
    </div>
</div>
@endsection

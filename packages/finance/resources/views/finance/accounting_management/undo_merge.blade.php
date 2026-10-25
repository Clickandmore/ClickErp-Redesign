@php($details = (array) $log->details)
<form class="row g-3 from-submit-update-global" method="post" action="{{ $undoRoute }}">
    <div class="col-12">
        <div class="alert alert-light border mb-0">
            <div>الحساب الذي سيُعاد: <strong>{{ $log->account_code }} - {{ $log->account_name }}</strong></div>
            <div class="text-muted">دُمج في {{ $details['target']['code'] ?? '' }} - {{ $details['target']['name'] ?? '#' . $log->target_account_id }}
                بتاريخ {{ $log->created_at?->format('Y-m-d H:i') }}</div>
            @if ($log->reason)
                <div class="text-muted">سبب الدمج: {{ $log->reason }}</div>
            @endif
        </div>
    </div>

    @if (!empty($details['moved']))
        <div class="col-12">
            <label class="form-label mb-1">ما سيعود إلى الحساب</label>
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                    @foreach ($details['moved'] as $label => $count)
                        <tr><td>{{ $label }}</td><td class="text-center" style="width:90px">{{ $count }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <small class="text-muted d-block mt-1">تعود الصفوف التي نُقلت بالدمج فقط. أي قيد أو ربط جديد سُجّل على الحساب الهدف بعد الدمج يبقى عليه.</small>
        </div>
    @endif

    @if ($blockers)
        <div class="col-12">
            <div class="alert alert-danger mb-0">
                @foreach ($blockers as $blocker)
                    <div>{{ $blocker }}</div>
                @endforeach
            </div>
        </div>
    @else
        <div class="col-12">
            <label class="form-label">سبب التراجع</label>
            <textarea name="reason" class="form-control" rows="2" maxlength="1000" data-validation="required"></textarea>
        </div>

        <div class="col-12 modal-footer">
            {!! submitButton($undoRoute) !!}
            {!! closeButton() !!}
        </div>
    @endif
</form>

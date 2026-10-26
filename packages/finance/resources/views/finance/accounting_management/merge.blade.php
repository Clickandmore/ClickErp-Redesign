<form class="row g-3 from-submit-update-global" method="post" action="{{ $mergeRoute }}" id="accountMergeForm">
    <div class="col-12">
        <div class="alert alert-light border mb-0">
            <div class="mb-1">الحساب المصدر (سيُحذف بعد الدمج):</div>
            <div><strong>{{ $obj->code }} - {{ $obj->name }}</strong>
                <span class="text-muted">({{ $obj->status?->lang() }} — {{ $obj->type?->lang() }})</span></div>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label mb-1">ما سينتقل إلى الحساب الهدف</label>
        @if ($usage['references'] || $usage['settings'])
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                    @foreach ($usage['references'] as $reference)
                        <tr><td>{{ $reference['label'] }}</td><td class="text-center" style="width:90px">{{ $reference['count'] }}</td></tr>
                    @endforeach
                    @if ($usage['settings'])
                        <tr><td>إعدادات الحسابات: {{ implode('، ', $usage['settings']) }}</td><td class="text-center">{{ count($usage['settings']) }}</td></tr>
                    @endif
                </tbody>
            </table>
        @else
            <div class="text-muted">لا توجد قيود أو ارتباطات على هذا الحساب.</div>
        @endif
    </div>

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
            <label class="form-label">دمج في الحساب</label>
            <select name="target_account_id" class="form-control select2 merge-target-select" data-validation="required">
                <option value="">اختر...</option>
                @foreach ($targets as $target)
                    <option value="{{ $target->id }}"
                        data-status="{{ $target->status?->value }}" data-type="{{ $target->type?->value }}">
                        {{ $target->code }} - {{ $target->name }}
                    </option>
                @endforeach
            </select>
            <div class="alert alert-warning mt-2 mb-0 d-none merge-diff-warning"
                data-source-status="{{ $obj->status?->value }}" data-source-type="{{ $obj->type?->value }}">
                تنبيه: طبيعة أو نوع الحساب الهدف يختلف عن الحساب المصدر. تأكد أن هذا مقصود قبل الدمج.
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">سبب الدمج</label>
            <textarea name="reason" class="form-control" rows="2" maxlength="1000" data-validation="required"></textarea>
        </div>

        <div class="col-12">
            <div class="alert alert-danger mb-2">
                الدمج ينقل كل القيود السابقة والارتباطات أعلاه إلى الحساب الهدف ثم يحذف الحساب المصدر.
                كشف حساب الهدف سيشمل حركات المصدر. العملية مسجلة في سجل التدقيق ولا يوجد زر تراجع.
            </div>
            <label class="form-label">للتأكيد اكتب كود الحساب المصدر: <strong>{{ $obj->code }}</strong></label>
            <input type="text" name="confirm_code" class="form-control" autocomplete="off" data-validation="required">
        </div>

        <div class="col-12 modal-footer">
            {!! submitButton($mergeRoute) !!}
            {!! closeButton() !!}
        </div>
    @endif
</form>
<script>
    (function () {
        const form = $('#accountMergeForm');
        const warning = form.find('.merge-diff-warning');
        form.find('.merge-target-select').on('change', function () {
            const option = $(this).find('option:selected');
            const differs = option.val() && (
                String(option.data('status')) !== String(warning.data('source-status')) ||
                String(option.data('type')) !== String(warning.data('source-type'))
            );
            warning.toggleClass('d-none', !differs);
        });
    })();
</script>

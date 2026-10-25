<form class="row g-3 from-submit-update-global" method="post" action="{{ $moveRoute }}">
    <div class="col-12">
        <div class="alert alert-light border mb-0">
            <div><strong>{{ $obj->code }} - {{ $obj->name }}</strong></div>
            <div class="text-muted">الحساب الرئيسي الحالي: {{ $obj->parent ? $obj->parent->code . ' - ' . $obj->parent->name : 'بدون (حساب جذري)' }}</div>
            @if ($childrenCount > 0)
                <div class="text-muted">سيُنقل معه {{ $childrenCount }} حساب فرعي مباشر وكل ما تحتها.</div>
            @endif
        </div>
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
            <label class="form-label">الحساب الرئيسي الجديد</label>
            <select name="parent_id" class="form-control select2" data-validation="required">
                <option value="">اختر...</option>
                @if ($obj->parent_id !== null)
                    <option value="without">بدون (حساب جذري)</option>
                @endif
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">لا تظهر هنا الحسابات التي عليها قيود ولا فروع هذا الحساب.</small>
        </div>

        <div class="col-12">
            <label class="form-label">سبب النقل (اختياري)</label>
            <textarea name="reason" class="form-control" rows="2" maxlength="1000"></textarea>
        </div>

        <div class="col-12">
            <div class="alert alert-warning mb-0">
                سيتغير كود الحساب وأكواد كل فروعه حسب الحساب الرئيسي الجديد. القيود والأرصدة تبقى على نفس الحساب ولا تتغير.
            </div>
        </div>

        <div class="col-12 modal-footer">
            {!! submitButton($moveRoute) !!}
            {!! closeButton() !!}
        </div>
    @endif
</form>

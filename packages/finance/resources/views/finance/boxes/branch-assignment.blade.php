@if($accountBranchAssignmentsEnabled ?? false)
    @php($selectedBranchIds = old('branch_ids', isset($obj) ? ($obj->account?->branches->pluck('id')->all() ?? []) : []))
    <div class="col-12">
        <label class="form-label">الفروع المتاح لها الصندوق</label>
        <input type="hidden" name="account_branch_scope_present" value="1">
        <select name="branch_ids[]" class="form-control select2" multiple>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(in_array($branch->id, (array) $selectedBranchIds))>{{ $branch->name_ar ?: $branch->name_en }}</option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">اختر الفرع الذي يستخدم الصندوق. يمكن تحديد أكثر من فرع، وترك الاختيار فارغًا يجعله متاحًا لكل الفروع.</small>
    </div>
@endif

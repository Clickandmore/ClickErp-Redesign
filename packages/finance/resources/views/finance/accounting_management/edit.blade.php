<form class="row g-3 from-submit-update-global" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
    @method('PUT')

    <div class="col-12">
        <label class="form-label">{{ __('finance::finance.name') }}</label>
        <input type="text" name="name" class="form-control" data-validation="required" value="{{ $obj->name }}">
    </div>

    <input type="hidden" name="code" value="{{ $obj->code }}">
    <input type="hidden" name="status" value="{{ $obj->status->value }}">
    <input type="hidden" name="parent_id" value="{{ $obj->parent_id ?? 'without' }}">
    <input type="hidden" name="type" value="{{ $obj->type->value }}">
    <input type="hidden" name="operation_type" value="{{ $obj->operation_type }}">

    @if($accountBranchAssignmentsEnabled ?? false)
        @php($selectedBranchIds = $obj->branches->pluck('id')->map(fn($id) => (int) $id)->all())
        <div class="col-12">
            <label class="form-label">الفروع المتاح لها الحساب</label>
            <input type="hidden" name="account_branch_scope_present" value="1">
            <select class="form-control select2" name="branch_ids[]" multiple>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}" {{ in_array((int) $branch->id, $selectedBranchIds, true) ? 'selected' : '' }}>
                        {{ $branch->name_ar ?: $branch->name_en }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">لا توجد فروع محددة = الحساب متاح لكل الفروع. اختيار فروع يحصر استخدام الحساب عليها فقط.</small>
        </div>
    @endif

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>
</form>

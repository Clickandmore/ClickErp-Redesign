<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.name') }} </label>
        <input type="text" name="name" class="form-control"  data-validation="required" value="{{$obj->name}}">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.Main Account') }}</label>
        <select class="form-control  show-tick ms select2" name="parent_id" id="parent_id" data-validation="required">
            <option value="without">{{ trans('finance::finance.without') }}</option>
            @if($obj->parent_id != null && !in_array($obj->parent_id,$accounts->pluck('id')->toArray()))
                <option value="{{$obj->parent_id}}" selected>{{ $obj->parent->name}}</option>;
            @endif
            @foreach($accounts as $one)
                <option
                    value="{{$one->id}}" {{ $one->id == $obj->parent_id ? 'selected' : '' }} data-status="{{$one->status->value}}"> {{ $one->name}} </option>;
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label class="form-label"> {{ __('finance::finance.Nature of account') }} </label>
        <select class="form-control" name="status"  data-validation="required" id="statusCildSelect">
            {!! showSelectElement($accountTypes,"name",$obj->status->value) !!}
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.code') }} </label>
        <input type="text" name="code" class="form-control" id="code"  value="{{$obj->code}}"
               data-validation="required" readonly="">
    </div>
    @if(auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::GlobalManger->value || auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
        <div class="col-6">
            <label class="form-label">{{ __('finance::finance.type') }} </label>
            <select class="form-control select2" name="type" id="type">
                {!! getEnumData($types,$obj->type) !!}
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">{{ __('finance::finance.operation_type') }} </label>
            <select class="form-control" name="operation_type" id="operation_type">
                {!! getEnumData($operationTypes,$obj->operation_type) !!}
            </select>
        </div>
    @endif

    @if($accountBranchAssignmentsEnabled ?? false)
        @php($selectedBranchIds = $obj->relationLoaded('branches') ? $obj->branches->pluck('id')->map(fn($id) => (int) $id)->all() : [])
        <div class="col-12">
            <label class="form-label">الفروع المتاح لها الحساب</label>
            <input type="hidden" name="account_branch_scope_present" value="1">
            <select class="form-control select2" name="branch_ids[]" multiple>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}" {{ in_array((int) $branch->id, $selectedBranchIds, true) ? 'selected' : '' }}>
                        {{ $branch->name_ar ?: $branch->name_en }}
                        @if(!empty($branch->code))
                            - {{ $branch->code }}
                        @endif
                    </option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">
                لا توجد فروع محددة = الحساب متاح لكل الفروع. اختيار فروع يحصر استخدام الحساب عليها فقط.
            </small>
        </div>
    @endif

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>

<script>
    $('#parent_id').on('change', function () {
        let url = '{{route("finance.child-code.show",":id")}}';
        url = url.replace(':id', $(this).val());
        $.ajax({
            type: "GET",
            url: url,
            data: {
                parent_id: $('#parent_id').val()
            },
            success: function (data) {
                $('#code').val(data);
                let parentStatus = $('#parent_id option:selected').data('status');
                $('#statusCildSelect option').each(function() {
                    // If the option's value matches the parent status
                    if ($(this).val() == parentStatus) {
                        // Select this option
                        $(this).prop('selected', true);
                    }
                });
            }
        });
    });
</script>

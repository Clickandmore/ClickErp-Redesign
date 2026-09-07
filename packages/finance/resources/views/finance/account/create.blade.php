<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}"
      enctype="multipart/form-data">



    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.name') }} </label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.Main Account') }}</label>
        <select class="form-control  show-tick ms select2" name="parent_id" id="parent_id" data-validation="required">
            <option value="">{{ trans('finance::finance.Select') }} </option>
            <option value="without">{{ trans('finance::finance.without') }}</option>
            @foreach($accounts as $one)
                <option value="{{$one->id}}" data-status="{{@$one->status->value}}" {{ request()->has('parent_id') && request()->parent_id == $one->id ? 'selected' : '' }}> {{ $one->name}} </option>;
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label class="form-label">{{ __('finance::finance.Nature of account') }} </label>
        <select class="form-control" name="status" data-validation="required" id="statusCildSelect">

            {!! showSelectElement($accountTypes,"name") !!}
        </select>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.code') }} </label>
        <input type="text" name="code"  id="code" value="" class="form-control" data-validation="required"
               readonly="">
    </div>
    @if(
        (auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value))

        <div class="col-6">
            <label class="form-label">{{ __('finance::finance.type') }} </label>
            <select class="form-control select2" name="type" id="type">
                {!! getEnumData($types) !!}
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">{{ __('finance::finance.operation_type') }} </label>
            <select class="form-control" name="operation_type" id="operation_type">
                {!! getEnumData($operationTypes) !!}
            </select>
        </div>
    @else
        <input type="hidden" name="type" value="1" >
        <input type="hidden" name="operation_type" value="3" >
    @endif

    @if($accountBranchAssignmentsEnabled ?? false)
        <div class="col-12">
            <label class="form-label">الفروع المتاح لها الحساب</label>
            <input type="hidden" name="account_branch_scope_present" value="1">
            <select class="form-control select2" name="branch_ids[]" multiple>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}">
                        {{ $branch->name_ar ?: $branch->name_en }}
                        @if(!empty($branch->code))
                            - {{ $branch->code }}
                        @endif
                    </option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">
                اترك التحديد فارغاً ليظل الحساب متاحاً لكل الفروع. اختيار فرع أو أكثر يجعل الحساب متاحاً لهذه الفروع فقط.
            </small>
        </div>
    @endif

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
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
    @if(request()->has('parent_id'))
    $(document).ready(function () {
        let url = '{{route("finance.child-code.show",":id")}}';
        url = url.replace(':id', '{{ request()->parent_id }}');
        $.ajax({
            type: "GET",
            url: url,
            data: {
                parent_id: '{{ request()->parent_id }}'
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
    @endif
</script>

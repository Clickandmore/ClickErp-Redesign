<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">
    <div class="col-6">
        <label for="title" class="form-label">{{ __('finance::finance.cost_center_name') }} </label>
        <input type="text" name="title" id="title" class="form-control" required data-validation="required">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.main-fn-cost-centers') }}</label>
        <select class="form-control"  name="parent_id" id="parent_id">
            <option value="">{{ trans('finance::finance.Select') }} </option>
            <option value="">{{ trans('finance::finance.without') }}</option>
            @foreach($cost_centers as $one)
                <option value="{{$one->id}}" {{($one->id == @$parent_id) ? 'selected' : ''}} > {{ $one->title}} / {{ $one->code}} </option>
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label for="code" class="form-label">{{ __('finance::finance.code') }} </label>
        <input type="text" name="code" id="code" class="form-control" required data-validation="required">
    </div>

    @if(isset($request['table_name']) && $request['table_name'] == 'stocks')
        <input type="hidden" name="table_name" value="stocks">
        <input type="hidden" name="table_related_id" value="{{$request['table_related_id']}}">
    @elseif (isset($request['table_name']) && $request['table_name'] == 'employees')
        <input type="hidden" name="table_name" value="employees">
        <input type="hidden" name="table_related_id" value="{{$request['table_related_id']}}">
    @else
        <div class="col-6">
            <label for="type" class="form-label">{{ __('finance::finance.cost_center_associated_with') }} </label>
            <span class="text-danger">*</span>
            <select class="form-control" name="table_name" id="table_name" data-validation="required">
                {!! getEnumData($types) !!}
            </select>
        </div>

        <div class="col-12" id="stocks_menu"  style="display: none;">
            <label for="TextInput" class="form-label">{{ __('finance::finance.stock') }}</label>
            <select id="stock_id" name="table_related_id" class="form-control" data-validation="required">
                {!! showSelectElement($stocks,'name') !!}
            </select>
        </div>

{{--        <div class="col-12" id="employee_menu"  style="display: none;">--}}
{{--            <label for="TextInput" class="form-label">{{ __("hr.employee") }}</label>--}}
{{--            <select class="form-control show-tick ms" name="table_related_id" id="employee_id" data-validation="required">--}}
{{--                {!! showSelectElement($employees,'name') !!}--}}
{{--            </select>--}}
{{--        </div>--}}
    @endif


    <div class="col-12 modal-footer">
         {!! submitButton(   $storeRoute  ) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    $(document).ready(function () {

        $('#table_name').on('change', function () {
            var selectedValue = $(this).val();
            if (selectedValue === 'stocks') {
                $('#stocks_menu').show();
                $('#employee_menu').hide();
                $('#stock_id').prop('disabled', false);
                $('#employee_id').prop('disabled', true);
            } else if (selectedValue === 'employees') {
                $('#employee_menu').show();
                $('#stocks_menu').hide();
                $('#employee_id').prop('disabled', false);
                $('#stock_id').prop('disabled', true);
            } else {
                $('#stocks_menu').hide();
                $('#employee_menu').hide();
                $('#stock_id').prop('disabled', true);
                $('#employee_id').prop('disabled', true);
            }
        });
    });
</script>

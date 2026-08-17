<form class="row g-3 from-submit-global" method="post" action="{{route("sanctions.store")}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.sanction_name") }}</label>
        <input type="text" name="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <input type="checkbox" name="is_delays" value="1" checked>
        <label for="TextInput" class="form-label">{{ __("hr::hr.Is the penalty for delay?") }} </label>
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.sanction_type") }}</label>
        <select class="form-control show-tick ms" name="type"
                data-validation="required" id="allowance-type" onchange="getTypeHtml();">
                {!! getEnumData($allowance_typeis) !!}
        </select>
    </div>


    <div id="field" class="col-12" style="display: none;">
        <label for="day-input" id="day-field" style="display: none;">
            {{ __("hr::hr.days_nmuber") }}
            <span style="color: red; font-size: 14px">
                {{ __('hr::hr.dayDesc') }}
            </span>
        </label>
        <label for="value-input" id="value-field" style="display: none;">{{ __("hr::hr.value") }}</label>
        <label for="percentage-input" id="percentage-field" style="display: none;">{{ __("hr::hr.percentage") }}</label>
        <input type="number" class="form-control" min="0" name="value" id="day-input" data-validation="required,number" data-validation-allowing="">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("sanctions.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>



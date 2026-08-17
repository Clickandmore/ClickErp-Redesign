<form class="row g-3 from-submit-global" method="post" action="{{ route('sanctions.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.sanction_name") }}</label>
        <input type="text" name="title" value="{{ $obj->title }}" class="form-control" data-validation="required">
        <input type="hidden" name="sanctionsss_id" value="{{ $obj->id }}" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <input type="checkbox" value="1" {{ $obj->is_delays == 1 ? 'checked' : '' }} name="is_delays">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Is the penalty for delay?") }} </label>
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.sanction_type") }}</label>
        <select class="form-control show-tick ms" name="type"
                data-validation="required" id="allowance-type" onchange="getTypeHtml();">
                {!! getEnumData($allowance_typeis , $obj->type) !!}
        </select>
    </div>

    <div id="field" class="col-12" @if ($obj->value)style="display: block;"@endif>
        <label for="day-input" id="day-field" @if($obj->type == 1)style="display: block;"@endif style="display: none;">
            {{ __("hr::hr.days_nmuber") }}
            <span style="color: red; font-size: 14px">
                {{ __('hr::hr.dayDesc') }}
            </span>
        </label>
        <label for="value-input" id="value-field" @if($obj->type == 2)style="display: block;"@endif style="display: none;">{{ __("hr::hr.value") }}</label>
        <label for="percentage-input" id="percentage-field" @if($obj->type == 3)style="display: block;"@endif style="display: none;">{{ __("hr::hr.percentage") }}</label>
        <input type="number" class="form-control"  min="0" value="{{ $obj->value }}" name="value" id="day-input" data-validation="required,number" data-validation-allowing="">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('sanctions.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>

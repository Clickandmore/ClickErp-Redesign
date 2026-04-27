<form class="row g-3 from-submit-global" method="post" action="{{route("rewards.store")}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.reward_name") }}  </label>
        <input type="text" name="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.calculation method") }}</label>
        <select class="form-control show-tick ms" name="type"
                data-validation="required" id="allowance-type" onchange="getTypeHtml();">
                {!! getEnumData($allowance_typeis) !!}
        </select>
    </div>


    <div id="field" class="col-12" style="display: none;">
        <label for="day-input" id="day-field" style="display: none;">{{ __("hr::hr.day") }}</label>
        <label for="value-input" id="value-field" style="display: none;">{{ __("hr::hr.value") }}</label>
        <label for="percentage-input" id="percentage-field" style="display: none;">{{ __("hr::hr.percentage") }}</label>
        <input type="text" class="form-control" name="value" id="day-input" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("rewards.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>



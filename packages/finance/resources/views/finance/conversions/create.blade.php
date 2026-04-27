<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

    <div class="col-md-3">
        <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
        <input type="text" name="code" id="code" value="{{ $code }}" class="form-control" readonly data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="date" class="form-label">{{ __("finance::finance.date") }}</label>
        <input type="date" name="date" id="date" class="form-control" data-validation="required" value="{{ date('Y-m-d') }}">
        <input type="hidden" name="created_by_id" value="{{ auth()->guard('admin')->user()->id }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="from_stock" class="form-label">{{ __("finance::finance.from_stock") }}</label>
        {!! addUpButton(route('finance.stocks.create'),'from_stock','' ,'name',false) !!}
        <select name="from_stock_id" id="from_stock" class="form-control" data-validation="required">
            {!! showSelectElement($stocks, 'name') !!}
        </select>
    </div>

    <div class="col-md-3">
        <label for="to_stock" class="form-label">{{ __("finance::finance.to_stock") }}</label>
        {!! addUpButton(route('finance.stocks.create'),'to_stock','' ,'name',false) !!}
        <select name="to_stock_id" id="to_stock" class="form-control" data-validation="required">
            {!! showSelectElement($stocks, 'name') !!}
        </select>
    </div>

    <div id="table_items">
        @include("finance::finance.conversions.tbody_render")
    </div>

    <div class="col-md-6">
        <label for="TextInput" class="form-label">{{ __("finance::finance.file") }}</label>
        <input type="file" name="file" class="dropify">
    </div>

    <div class="col-md-6">
        <label for="responsible" class="form-label">{{ __("finance::finance.notes") }}</label>
        <textarea name="notes" id="notes" cols="15" rows="8" class="form-control"></textarea>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>



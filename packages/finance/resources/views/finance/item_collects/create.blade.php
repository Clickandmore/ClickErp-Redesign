<form class="row g-3 from-submit-items" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

        <div class="col-md-4">
            <label for="title" class="form-label">{{ __("finance::finance.code") }}</label>
            <input type="text" value="{{$code}}" name="code" id="code" class="form-control" data-validation="required" readonly>
        </div>
    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("finance::finance.date") }}</label>
        <input type="date" value="{{date('Y-m-d')}}" name="date" id="date" class="form-control" data-validation="required">
    </div>



    <div class="col-md-4">
        <label for="to_stock_id" class="form-label">{{ __("finance::finance.add_to_stock") }}</label>
        <select name="to_stock_id" id="to_stock_id" class="form-control" data-validation="required">
            {!! showSelectElement($stocks, 'name') !!}
        </select>
    </div>
    <div class="col-md-4">
        <label for="" class="form-label">{{ __("finance::finance.from_to_stock") }}</label>
        <select name="from_stock_id[]" id="from_stock_id" multiple="multiple" class="form-control select2">
{{--            <option value="all"> {{__('crm.all')}} </option> <!-- إضافة قيمة "all" لخيار الكل -->--}}
            @foreach($stocks as $stock)
                <option value="{{ $stock->id }}">{{ $stock->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="item_id" class="form-label">{{ __("finance::finance.item") }}</label>
        <select name="item_id" id="item_id" class="form-control" data-validation="required">
            {!! showSelectElement($items, 'name') !!}
        </select>
    </div>
    <div class="col-md-4">
        <label for="amount" class="form-label">{{ __("finance::finance.amount") }}</label>
        <input type="number" name="amount" id="amount" class="form-control" value="1" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="notes" class="form-label">{{ __("finance::finance.notes") }}</label>
        <textarea name="notes" id="notes" class="form-control" rows="4"></textarea>
    </div>
    <div class="col-md-4">
        <label for="file" class="form-label">{{ __("finance::finance.file") }}</label>
        <input type="file" name="attachment" class="dropify">
    </div>

    <div id="items_list">

    </div>
    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>

<script>
    // حدث عند تغيير الاختيار في القائمة
    $('#from_stock_id').on('change', function() {
        // تحقق إذا تم اختيار خيار "الكل"
        if ($(this).val().includes("all")) {
            // إذا تم اختيار "الكل"، حدد جميع العناصر الأخرى
            $('#from_stock_id > option').prop("selected", true);
            $('#from_stock_id').trigger("change"); // إعادة تهيئة select2 للتحديث
        }
    });
</script>

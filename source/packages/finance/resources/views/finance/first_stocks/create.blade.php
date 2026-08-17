<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.date") }}</label>
        <input type="date" name="date" id="date" class="form-control">
    </div>
    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.stock") }}</label>
        <select name="stock_id" id="stock_id" class="form-select" data-validation="required">
            {!! showSelectElement($stocks,'name') !!}
        </select>
    </div>




<div id="items_list" >


</div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>

<script>
    $(document).on('change', '#stock_id', function(e) {
        const stock_id = this.value;
        var url = "{{ route('finance.stock_items.index') }}";
        $.ajax({
            type: 'get',
            data: {
                stock_id : stock_id,
            },
            url: url,
            success: function(data) {
                console.log(data)
                $("#items_list").html('');
                $("#items_list").html(data.data.html);
            },
            error: function() {
                alert('حدث خطأ أثناء التحديث.');
            }
        });
    });

</script>

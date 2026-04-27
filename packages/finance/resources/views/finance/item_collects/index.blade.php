@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{__("finance::finance.code")}}</th>
            <th>{{__("finance::finance.item")}}</th>
            <th>{{__("finance::finance.stock")}}</th>
            <th>{{__("finance::finance.amount")}}</th>
            <th>{{__("finance::finance.date")}}</th>
            <th>{{__("finance::finance.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "code", orderable: false, searchable: false},
                {"data": "item", orderable: false, searchable: false},
                {"data": "stock", orderable: false, searchable: false},
                {"data": "amount", orderable: false, searchable: false},
                {"data": "date", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);




        });
        $(document).ajaxComplete(function () {
            $('#json_array .y-group').each(function (index, element) {
                var $row = $(element);  // الصف الحالي
                var availableAmount = $row.find('.availableAmount').text(); // الحصول على الكمية المتاحة من هذا الصف
                console.log(availableAmount, index);
                // إضافة المدقق max_value لكل صف بناءً على الكمية المتاحة في هذا الصف
                $.formUtils.addValidator({
                    name: 'max_value_' + index, // إضافة معرف فريد لكل مدقق
                    validatorFunction: function (value, $el, config, language, $form) {
                        return parseFloat(value) <= parseFloat(availableAmount); // التأكد أن الكمية المدخلة <= الكمية المتاحة
                    },
                    errorMessage: '{{ __("finance::finance.available_amount_value") }}', // رسالة الخطأ
                    errorMessageKey: 'max_value_' + index  // معرف فريد لكل مدقق
                });

                // تعيين المدقق لحقل order_amount في هذا الصف
            });
            // إضافة حدث عند تغيير أي حقل `addedAmount` لإعادة التحقق
            $('#json_array').on('input', '.addedAmount', function () {
                // تحديد `data-row` الخاص بالمنتج الحالي
                var dataRow = $(this).data('row');

                // الحصول على الكمية المطلوبة للمنتج الحالي
                var requiredAmount = parseFloat($(`.orderAmountInput_${dataRow}`).val());

                // جمع جميع الكميات المضافة (addedAmount) لجميع المخازن لنفس المنتج
                var sumAddedAmount = 0;
                $(`#json_array .addedAmount[data-row="${dataRow}"]`).each(function () {
                    sumAddedAmount += parseFloat($(this).val()) || 0;
                });

                // تحقق إذا كان المجموع يتجاوز الكمية المطلوبة
                if (sumAddedAmount > requiredAmount) {
                    validToster('{{__("auth.an error occurred")}}', '{{__('finance::finance.available_amount_value')}}');
                    $(this).val(0); // إعادة الحقل الذي تم إدخاله للتو إلى الصفر
                }
            });


        });
            $(document).on('change', '#item_id, #from_stock_id', function(e) {
                const item_id = $('#item_id').val();
                const from_stock_id = $('#from_stock_id').val();
                var url = "{{ route('finance.item-collects.details') }}";
                $.ajax({
                    type: 'get',
                    data: {
                        item_id : item_id,
                        from_stock_id : from_stock_id
                    },
                    url: url,
                    success: function(data) {
                        console.log(data)
                        $("#items_list").html('');
                        $("#items_list").html(data.data.html);
                        var quantity = $('#amount').val(); // الحصول على الكمية المدخلة في الحقل الأعلى

                        // التأكد أن الكمية مدخلة وصحيحة
                        if (!quantity || isNaN(quantity) || quantity <= 0) {
                            alert('يرجى إدخال كمية صالحة');

                        }

                        // التأكد من أن الحقول المناسبة في الجدول تحتوي على الكمية المتاحة
                        $('.y-group').each(function(index, element) {
                            const $row = $(element);  // الصف الحالي

                            const itemAmount = $(this).find('.itemAmount').val(); // الحصول على الكمية المتاحة لكل صنف
                            if (itemAmount && !isNaN(itemAmount)) {
                                const result = parseFloat(quantity) * parseFloat(itemAmount) ; // عملية الضرب
                                $(this).find('.orderAmountInput').val(result); // وضع النتيجة في الحقل المناسب
                                $(this).find('.orderAmountInput').text(result); // وضع النتيجة في الحقل المناسب
                                 // $(this).find('.addedAmount').val(result);
                                // if (itemAmount && !isNaN(itemAmount)) {
                                //     // إذا كان هذا هو المخزن الأول
                                //     if (index === 0) {
                                //         // وضع الكمية في أول مخزن
                                //         $row.find('.addedAmount').val(result);
                                //     } else {
                                //         // وضع 0 في باقي المخازن
                                //         $row.find('.addedAmount').val(0);
                                //     }
                                // }
                            }

                        });
                    },
                    error: function() {
                        alert('حدث خطأ أثناء التحديث.');
                    }
                });
            });

            $(document).on('input', '#amount', function(e) {
                var quantity = $('#amount').val(); // الحصول على الكمية المدخلة في الحقل الأعلى

                // التأكد أن الكمية مدخلة وصحيحة
                if (!quantity || isNaN(quantity) || quantity <= 0) {
                    alert('يرجى إدخال كمية صالحة');

                }

                // التأكد من أن الحقول المناسبة في الجدول تحتوي على الكمية المتاحة
                $('.y-group').each(function() {


                    const itemAmount = $(this).find('.itemAmount').val(); // الحصول على الكمية المتاحة لكل صنف

                    if (itemAmount && !isNaN(itemAmount)) {
                        const result = parseFloat(quantity) * parseFloat(itemAmount); // عملية الضرب
                        $(this).find('.orderAmountInput').val(result); // وضع النتيجة في الحقل المناسب
                        $(this).find('.orderAmountInput').text(result); // وضع النتيجة في الحقل المناسب
                        // $(this).find('.addedAmount').val(result);
                    }

                    // if (itemAmount && !isNaN(itemAmount)) {
                    //     // إذا كان هذا هو المخزن الأول
                    //     if (index === 0) {
                    //         // وضع الكمية في أول مخزن
                    //         $row.find('.addedAmount').val(quantity);
                    //     } else {
                    //         // وضع 0 في باقي المخازن
                    //         $row.find('.addedAmount').val(0);
                    //     }
                    // }
                });
            });

        $(document).ready(function() {

                // نستخدم each للتنقل عبر كل صف في الجدول
                $('#json_array .y-group').each(function (index, element) {
                    var $row = $(element);  // الصف الحالي
                    var availableAmount = $row.find('.availableAmount').text(); // الحصول على الكمية المتاحة من هذا الصف
                    console.log(availableAmount);
                    // إضافة المدقق max_value لكل صف بناءً على الكمية المتاحة في هذا الصف
                    $.formUtils.addValidator({
                        name: 'max_value_' + index, // إضافة معرف فريد لكل مدقق
                        validatorFunction: function (value, $el, config, language, $form) {
                            return parseFloat(value) <= parseFloat(availableAmount); // التأكد أن الكمية المدخلة <= الكمية المتاحة
                        },
                        errorMessage: '{{ __("finance::finance.value_min_error") }}', // رسالة الخطأ
                        errorMessageKey: 'max_value_' + index  // معرف فريد لكل مدقق
                    });

                    // تعيين المدقق لحقل order_amount في هذا الصف
                });
        });

        $(document).ready(function() {
            $(document).on('submit', '.from-submit-items', function (e) {
                e.preventDefault();

                let isAmountValid = true; // متغير للتحقق من صحة مجموع الكميات المضافة
                $('.addedAmount').each(function() {
                    let dataRow = $(this).data('row'); // احصل على data-row للمنتج الحالي
                    let requiredAmount = parseFloat($(`.orderAmountInput_${dataRow}`).val()); // احصل على الكمية المطلوبة لهذا المنتج
                    let sumAddedAmount = 0;

                    // احسب مجموع الكميات المضافة لكل عنصر له نفس data-row
                    $(`.addedAmount[data-row="${dataRow}"]`).each(function() {
                        sumAddedAmount += parseFloat($(this).val()) || 0;
                    });


                    // تحقق إذا كان المجموع لا يساوي الكمية المطلوبة
                    if (sumAddedAmount !== requiredAmount) {

                        validToster('{{__("auth.an error occurred")}}', '{{__('finance::finance.available_added_amount_value')}}');
                        isAmountValid = false;
                        return false; // أوقف التكرار إذا لم يكن الشرط صحيحًا
                    }
                });

                // إذا لم تتحقق صحة الكميات، لا ترسل الطلب
                if (!isAmountValid) {
                    return;
                }
                let formData = new FormData(this);
                formData.append('up_id', $('#item_add_id').val());
                formData.append('select_class', $('#select_class').val());
                formData.append('up_name_id', $('#item_add_name_id').val());
                let url = $(this).attr('action');
                let submitButton = $(e.originalEvent.submitter);
                let buttonText = submitButton.text();
                let modalResult = $("#createModal");
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function () {
                        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText)
                    },
                    complete: function () {
                        submitButton.html('{{__('buttons.save')}}')
                    },
                    success: function (result) {
                        //console.log(200)
                        handleSuccessResponce(result);
                        reloadDataTable();
                        submitButton.html(buttonText)
                        if (modalResult.is(":visible")) {
                            modalResult.modal('toggle');
                        }

                        // modalResult.modal('toggle');
                        // console.log(modalResult.is(':visible'));
                    },
                    error: function (errorObj, errorText, errorThrown) {
                        console.log(500)
                        console.log(errorObj)
                        handleErrorResponce(errorObj);
                    }
                });

            });

        });
        // })
    </script>
@endsection

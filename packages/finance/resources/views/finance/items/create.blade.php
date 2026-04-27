@extends('click.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')


    <form class="row g-3 from-submit-item" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
        <div class="col-md-3">
            <label for="name" class="form-label">{{ __("finance::finance.name") }}</label>
            <input type="text" name="name" id="name" class="form-control" data-validation="required">
        </div>

        <div class="col-md-2">
            <label for="title" class="form-label">{{ __("finance::finance.type") }}</label>
            <select name="type" id="type" class="form-control" data-validation="required" onchange="handleTypeChange()">
                {!! getEnumData($category_types) !!}
            </select>
        </div>

        <div class="col-md-2">
            <label for="main_category_id" class="form-label">{{ __("finance::finance.main_categories") }}</label>
            {!! addUpButton(route('finance.categories.create'),'main_category_id','' ,'title',true) !!}
            <select name="main_category_id" id="main_category_id" class="form-control" data-validation="required">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($main_categories as $item)
                    <option value="{{ $item->id }}" data-type="{{ $item->type }}"  data-code="{{ $item->type }}">{{ $item->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label for="sub_category_id" class="form-label">{{ __("finance::finance.sub_categories") }}</label>
            {!! addUpButton(route('finance.categories.create'),'sub_category_id','' ,'title',true) !!}
            <select name="sub_category_id" id="sub_category_id" class="form-control">
                <option value="">{{ __("finance::finance.choose") }}</option>
                <!-- سيتم تعبئة هذه الخيارات عبر JavaScript -->
            </select>
        </div>

        <div class="col-md-3">
            <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
            <input type="text" name="code" value="{{ $code }}" data-validation="required" readonly id="itemCode" class="form-control" data-validation="required">
        </div>

        <div class="col-md-3">
            <label for="sale_account_id" class="form-label">{{ __("finance::finance.sale_account") }}:</label>
            {!! addUpButton(route('finance.accounts.create'),'sale_account_id','' ,'name',true) !!}
            <select name="sale_account_id" id="sale_account_id" class="form-control" data-validation="required">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($sale_accounts as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="purchase_price" class="form-label">{{ __("finance::finance.purchase price or cost") }}:</label>
            <input type="number" name="purchase_price" id="purchase_price" class="form-control" data-validation="required" step="0.01" min="0">
        </div>

        <div class="col-md-3">
            <label for="sale_price" class="form-label">{{ __("finance::finance.sale price") }}:</label>
            <input type="number" name="sale_price" id="sale_price" class="form-control" data-validation="required" step="0.01" min="0">
        </div>

        <div class="col-md-3">
            <label for="is_tax" class="form-label">{{ __("finance::finance.Is there a tax?") }}:</label>
            <div>
                <input type="radio" id="is_tax_yes" value="1" name="is_tax" onchange="toggleTaxFields()"> {{ __("finance::finance.Yes") }}
                <input type="radio" id="is_tax_no" value="0" name="is_tax" onchange="toggleTaxFields()" checked> {{ __("finance::finance.No") }}
            </div>
        </div>

        <div id="tax-fields" style="display: none;" class="col-12">
            <div class="row">
                <div class="col-md-4">
                    <label for="tax_setting_id" class="form-label">{{ __("finance::finance.Tax Account") }}:</label>
                    <select name="tax_setting_id" id="tax_setting_id" class="form-control" data-validation="required">
                        <option value="">{{ __("finance::finance.choose") }}</option>
                        @foreach ($tax_settings as $item)
                            <option value="{{ $item->id }}" data-value="{{ $item->value }}" data-account-id="{{ $item->account_id }}">
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" id="tax_account_id" name="tax_account_id" value="" data-validation="required">
                </div>
                <div class="col-md-4">
                    <label for="tax_rate" class="form-label">{{ __("finance::finance.Tax rate") }}:</label>
                    <input type="number" name="tax_rate" value="" readonly id="tax_rate" class="form-control">
                </div>

                <div class="col-md-4">
                    <label for="tax_amount" class="form-label">{{ __("finance::finance.Tax amount") }}:</label>
                    <input type="number" name="tax_amount" id="tax_amount" class="form-control" readonly>
                </div>
            </div>
        </div>

        <div id="blade-container"></div>

        <div class="col-md-6">
            <label for="details" class="form-label">{{ __("finance::finance.description") }}</label>
            <textarea name="details" id="details" class="form-control"></textarea>
        </div>
        <div class="col-md-6">
            <label for="TextInput" class="form-label">{{ __("finance::finance.image") }}</label>
            <input type="file" name="image" class="dropify">
        </div>
        <div class="col-12 modal-footer">
            {!! submitButton($storeRoute) !!}
            {!! submitNewButton($storeRoute) !!}
        </div>

    </form>


@endsection
@section('js')
<script>
    $(document).on('submit', '.from-submit-item', function (e) {
        e.preventDefault();

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
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
            },
            complete: function () {
                submitButton.html(buttonText);
            },
            success: function (result) {
                handleSuccessResponce(result);
                reloadDataTable();

                // Check which button was clicked
                if (submitButton.attr('id') === 'save_new_add') {
                    window.location.reload();
                } else {
                    window.location.href = "{{ route('finance.items.index') }}";
                }
            },
            error: function (errorObj, errorText, errorThrown) {
                console.log(500);
                console.log(errorObj);
                handleErrorResponce(errorObj);
            }
        });
    });
</script>

<script>
    function handleTypeChange() {
        var type = $('#type').val();

        // If the type is 'finished_product' or 'boxs', make 'purchase_price' readonly and calculate the total
        if (type === 'finished_product' || type === 'boxs') {
            $('#purchase_price').attr('readonly', true);

            // Calculate the total purchase price based on the components
            calculateTotalPurchasePrice();
        } else {
            // Make 'purchase_price' editable if type is different
            $('#purchase_price').attr('readonly', false);
        }
    }

    function calculateTotalPurchasePrice() {
        var totalPurchasePrice = 0;

        // Loop through each row in the components table
        $('tbody .y-group').each(function() {
            var row = $(this);
            var total = parseFloat(row.find('input[name="total_purchase_price[]"]').val()) || 0;
            totalPurchasePrice += total; // Sum all total purchase prices from the components
        });

        // Set the total purchase price in the 'purchase_price' field
        $('#purchase_price').val(totalPurchasePrice.toFixed(2));
    }

    // Reuse the setPurchasePrice and calculateTotal functions from previous script
    function setPurchasePrice() {
        var selectedOption = $(this).find('option:selected');
        var purchasePrice = selectedOption.data('purchase_price');

        // Find the corresponding row and set the purchase price
        var row = $(this).closest('tr');
        row.find('input[name="one_purchase_price[]"]').val(parseFloat(purchasePrice).toFixed(2));

        // Recalculate the total price after setting the purchase price
        calculateTotal.call(row.find('input[name="amount[]"]')[0]);

        // After recalculating, we also recalculate the total purchase price for all components
        calculateTotalPurchasePrice();
    }

    function calculateTotal() {
        var row = $(this).closest('tr'); // Find the closest <tr> element (row)

        var amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
        var price = parseFloat(row.find('input[name="one_purchase_price[]"]').val()) || 0;
        var total = amount * price;

        // Set the total in the total price input field within the same row
        row.find('input[name="total_purchase_price[]"]').val(total.toFixed(2));

        // After recalculating the total for this row, recalculate the total purchase price
        calculateTotalPurchasePrice();
    }

    // Call handleTypeChange on page load in case type is pre-selected
    $(document).ready(function() {
        handleTypeChange();
    });
</script>

<script>
    $(document).ajaxComplete(function() {
        $('#json_array').multifield();
    });
        $(document).ready(function() {
            $.validate({
                ignore: 'input[type=hidden]',
                modules: 'date, security',
                lang: '{{\App::currentLocale()}}',
                validateOnEvent: true
            });
        });
        // Toggle visibility of tax fields based on the selected radio button
        function toggleTaxFields() {
            var yesRadio = document.getElementById('is_tax_yes');
            var taxFields = document.getElementById('tax-fields');

            if (yesRadio.checked) {
                taxFields.style.display = 'block';
            } else {
                taxFields.style.display = 'none';
                clearTaxFields(); // Clear tax fields when tax is not applicable
            }
            calculateTax();
        }

        // Set tax rate and account ID based on the selected tax setting
        function setTaxRate() {
            var selectedOption = $('#tax_setting_id option:selected');
            var dataValue = selectedOption.data('value'); // Get tax rate percentage
            var accountId = selectedOption.data('account-id'); // Get account ID
            $('#tax_account_id').val(accountId); // Set the tax account ID in the hidden field
            $('#tax_rate').val(dataValue); // Set the tax rate in the tax rate input field
            calculateTax(); // Recalculate tax based on new tax rate
        }

        // Calculate tax amount based on sale price and tax rate
        function calculateTax() {
            var salePrice = parseFloat($('#sale_price').val()) || 0; // Get the sale price
            var taxRate = parseFloat($('#tax_rate').val()) || 0; // Get the tax rate percentage
            var taxAmount = salePrice - (salePrice / (1 + (taxRate / 100))); // Calculate tax amount
            $('#tax_amount').val(taxAmount.toFixed(2)); // Display tax amount in the input field
        }

        // Clear tax fields when tax is not applicable
        function clearTaxFields() {
            $('#tax_setting_id').val('');
            $('#tax_rate').val('');
            $('#tax_amount').val('');
            $('#tax_account_id').val('');
        }

        // Event listeners
        $('#tax_setting_id').on('change', function() {
            setTaxRate();
        });

        $('#sale_price').on('input', function() {
            calculateTax();
        });

        // Initialize fields based on current selection
        toggleTaxFields();
        setTaxRate();

        // Get Sub Category From Category && Get code From Category and Sub Category
        $(document).ready(function () {
            const mainCategorySelect = $('#main_category_id');
            const subCategorySelect = $('#sub_category_id');
            const codeInput = $('#itemCode');

            // عندما تتغير الفئة الرئيسية
            mainCategorySelect.on('change', function () {
                const mainCategoryId = $(this).val();

                // جلب الأكواد بناءً على الفئة الرئيسية
                if (mainCategoryId) {
                    fetchSubCategories(mainCategoryId);
                    fetchCode('mainCategories', mainCategoryId);
                } else {
                    subCategorySelect.html('<option value="">{{ __("finance::finance.choose") }}</option>');
                }
            });

            // عندما تتغير الفئة الفرعية
            subCategorySelect.on('change', function () {
                const subCategoryId = $(this).val();

                // جلب الأكواد بناءً على الفئة الفرعية
                if (subCategoryId) {
                    fetchCode('subCategories', subCategoryId);
                }
            });

            // دالة لجلب الفئات الفرعية بناءً على الفئة الرئيسية
            function fetchSubCategories(mainCategoryId) {
                const url = `{{ route('finance.categories.show', ':id') }}`.replace(':id', mainCategoryId);

                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        subCategorySelect.html('<option value="">{{ __("finance::finance.choose") }}</option>');
                        $.each(data, function (index, item) {
                            subCategorySelect.append(`<option value="${item.id}">${item.title}</option>`);
                        });
                    },
                    error: function (error) {
                        console.error('Error fetching subcategories:', error);
                    }
                });
            }

            // دالة لجلب الأكواد بناءً على الفئة الرئيسية أو الفرعية
            function fetchCode(type, categoryId) {
                const url = `{{ route('finance.get-code-by-category.store') }}`;

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        type: type,
                        category_id: categoryId,
                        _token: '{{ csrf_token() }}'  // توكن الحماية
                    },
                    success: function (response) {
                        if (response.code) {
                            codeInput.val(response.code);
                        }
                    },
                    error: function (error) {
                        console.error('Error fetching code:', error);
                    }
                });
            }
        });


        // Get Blades From Type
        $(document).ready(function () {
            const typeInput = $('#type');
            const bladeContainer = $('#blade-container');

            typeInput.on('change', function () {
                const selectedType = $(this).val();

                if (selectedType) {
                    loadBladeView(selectedType);
                } else {
                    bladeContainer.html('');
                }
            });

            function loadBladeView(type) {
                const url = `{{ route('finance.load-blade-view.show', ':id') }}`.replace(':id', type);

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function (response) {
                        bladeContainer.html(response);
                    },
                    error: function (error) {
                        console.error('Error loading Blade view:', error);
                    }
                });
            }
        });

</script>
@endsection

@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')

    <form class="row g-3 from-submit-global" method="post"
        enctype="multipart/form-data">
        @method('put')
        <div class="col-md-4">
            <label for="name" class="form-label">{{ __("finance::finance.name") }}</label>
            <input type="text" disabled name="name" value="{{ $obj->name }}" id="name" class="form-control" data-validation="required">
        </div>

        <div class="col-md-4">
            <label for="main_category_id" class="form-label">{{ __("finance::finance.main_categories") }}</label>
            <select disabled name="main_category_id" id="main_category_id" class="form-control" data-validation="required">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($main_categories as $item)
                    <option value="{{ $item->id }}" data-type="{{ $item->type }}"
                    {{ $obj->main_category_id == $item->id ? 'selected' : '' }} data-code="{{ $item->type }}">{{ $item->title }}</option>
                @endforeach
            </select>
            <input type="hidden" readonly name="type" value="{{ $obj->type }}" id="type" class="form-control mt-2">
        </div>

        <div class="col-md-4">
            <label for="sub_category_id" class="form-label">{{ __("finance::finance.sub_categories") }}</label>
            <select disabled name="sub_category_id" id="sub_category_id" class="form-control">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($sub_categories as $item)
                    <option value="{{ $item->id }}" {{ $obj->sub_category_id == $item->id ? 'selected' : '' }}>
                        {{ $item->title }}
                    </option>
                @endforeach
                <!-- سيتم تعبئة هذه الخيارات عبر JavaScript -->
            </select>
        </div>

        <div class="col-md-3">
            <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
            <input type="text" name="code" value="{{ $obj->code_string }}{{ $obj->code }}" data-validation="required" readonly id="code" class="form-control" data-validation="required">
        </div>

        <div class="col-md-3">
            <label for="sale_account_id" class="form-label">{{ __("finance::finance.sale_account") }}:</label>
             {!! addUpButton(route('finance.accounts.create'),'sale_account_id' ,false, 'name',true) !!}
            <select disabled name="sale_account_id" id="sale_account_id" class="form-control" data-validation="required">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($sale_accounts as $item)
                    <option value="{{ $item->id }}" {{ $obj->sale_account_id == $item->id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="purchase_price" class="form-label">{{ __("finance::finance.purchase price") }}:</label>
            <input disabled type="number" name="purchase_price" value="{{ $obj->purchase_price }}" id="purchase_price" class="form-control" data-validation="required">
        </div>

        <div class="col-md-3">
            <label for="sale_price" class="form-label">{{ __("finance::finance.sale price") }}:</label>
            <input disabled type="number" name="sale_price" value="{{ $obj->sale_price }}" id="sale_price" class="form-control" data-validation="required">
        </div>

        <div class="col-md-4">
            <label for="tax_setting_id" class="form-label">{{ __("finance::finance.Tax Account") }}:</label>
             {!! addUpButton(route('finance.tax-setting.create'),'tax_setting_id' ,false, 'name',true) !!}
            <select disabled name="tax_setting_id" id="tax_setting_id" class="form-control" data-validation="required">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($tax_settings as $item)
                    <option value="{{ $item->id }}" {{ $obj->itemTax->tax_setting_id == $item->id ? 'selected' : '' }}
                        data-value="{{ $item->value }}"
                         data-account-id="{{ $item->account_id }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
            <input  type="hidden" id="tax_account_id" value="{{ @$obj->itemTax->tax_account_id }}" name="tax_account_id" value="{{ $obj->tax_account_id }}" data-validation="required">
        </div>
        <div class="col-md-4">
            <label for="tax_rate" class="form-label">{{ __("finance::finance.Tax rate") }}:</label>
            <input disabled type="number" name="tax_rate" value="{{ @$obj->itemTax->tax_rate }}" value="" readonly id="tax_rate" class="form-control" oninput="calculateTax()">
        </div>

        <div class="col-md-4">
            <label for="tax_amount" class="form-label">{{ __("finance::finance.Tax amount") }}:</label>
            <input disabled type="number" name="tax_amount" value="{{ @$obj->itemTax->tax_amount }}" id="tax_amount" class="form-control" readonly>
        </div>

        <div id="blade-container">
        </div>

        <div class="col-md 12">
            <label for="details" class="form-label">{{ __("finance::finance.description") }}</label>
            <textarea disabled name="details" id="details" class="form-control" data-validation="required">
                {{ $obj->details }}
            </textarea>
        </div>
        {{-- <div class="col-12 modal-footer">
            {!! submitButton($updateRoute) !!}
        </div> --}}

    </form>


@endsection
@section('js')
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
        // Function to set the tax rate and tax account when the tax setting is changed
        function setTaxRate() {
            var selectedOption = $('#tax_setting_id option:selected');
            var dataValue = selectedOption.data('value'); // Get tax rate percentage
            var accountId = selectedOption.data('account-id'); // Get account ID
            $('#tax_account_id').val(accountId); // Set the tax account ID in the hidden field
            $('#tax_rate').val(dataValue); // Set the tax rate in the tax rate input field
            calculateTax(); // Recalculate tax based on new tax rate
        }

        // Event listener for when the tax setting dropdown changes
        $('#tax_setting_id').on('change', function() {
            setTaxRate();
        });

        // Initial setting of tax rate when page loads (if any tax is already selected)
        setTaxRate();

        // Function to calculate the tax amount based on the sale price and tax rate
        function calculateTax() {
            var salePrice = parseFloat($('#sale_price').val()) || 0; // Get the sale price
            var taxRate = parseFloat($('#tax_rate').val()) || 0; // Get the tax rate percentage
            var taxAmount = 0;

            // Calculate tax amount using the formula (assuming tax rate is inclusive in sale price)
            taxAmount = salePrice - (salePrice / (1 + (taxRate / 100)));

            // Set the tax amount in the tax_amount input field, formatted to 2 decimal places
            $('#tax_amount').val(taxAmount.toFixed(2));
        }

        // Event listener for changes in the sale price input to recalculate the tax
        $('#sale_price').on('input', function() {
            calculateTax();
        });
    });

    $(document).ready(function () {
    const mainCategorySelect = $('#main_category_id');
    const typeInput = $('#type');
    const subCategorySelect = $('#sub_category_id');
    const bladeContainer = $('#blade-container');  // Placeholder for dynamic Blade content
    const codeInput = $('#code');

    // الحصول على oldType و oldId من الصفحة إذا كان متوفرًا
    const oldType = "{{ $obj->type }}";  // قم بتعديل هذا ليكون قيمة oldType الفعلية من الـ Blade
    const oldId = "{{ $obj->id }}";     // الحصول على قيمة oldId

    // تحميل الـ Blade View إذا كان oldType و oldId موجودين
    if (oldType && oldId) {
        loadBladeView(oldType, oldId);
    }

    // When the main category changes
    mainCategorySelect.on('change', function () {
        const selectedMainOption = $(this).find('option:selected');
        const mainType = selectedMainOption.data('type');
        const mainCategoryId = $(this).val();

        // Set the type based on the main category
        typeInput.val(mainType);

        // Fetch subcategories based on the selected main category
        if (mainCategoryId) {
            fetchSubCategories(mainCategoryId);
            loadBladeView(mainType, oldId);  // Dynamically load the corresponding blade
        } else {
            subCategorySelect.html('<option value="">{{ __("finance::finance.choose") }}</option>');
            bladeContainer.html('');  // Clear the Blade view if no main category is selected
        }
        fetchCode('mainCategories', mainCategoryId);
    });

    // When the subcategory changes
    subCategorySelect.on('change', function () {
        const selectedSubOption = $(this).find('option:selected');
        const subType = selectedSubOption.data('type');
        const subCategoryId = $(this).val();

        // If subcategory has a type, use it; otherwise keep the main category's type
        if (subType) {
            typeInput.val(subType);
        }

        loadBladeView(subType || typeInput.val(), oldId);  // Load blade based on subcategory or main category type

        fetchCode('subCategories', subCategoryId);
    });

    // Function to fetch subcategories based on the main category ID
    function fetchSubCategories(mainCategoryId) {
        const url = `{{ route('finance.categories.show', ':id') }}`.replace(':id', mainCategoryId);

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                subCategorySelect.html('<option value="">{{ __("finance::finance.choose") }}</option>');
                $.each(data, function (index, item) {
                    // Assuming 'type' field also exists for subcategories
                    subCategorySelect.append(`<option value="${item.id}" data-type="${item.type}">${item.title}</option>`);
                });
            },
            error: function (error) {
                console.error('Error fetching subcategories:', error);
            }
        });
    }

    // Function to load the corresponding Blade view based on type and id
    function loadBladeView(type, id) {
        const url = `{{ route('finance.load-blade-edit-view.show', [':type', ':id']) }}`
            .replace(':type', type)
            .replace(':id', id);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                bladeContainer.html(response);  // Replace content with the loaded Blade view
            },
            error: function (error) {
                console.error('Error loading Blade view:', error);
            }
        });
    }

    // Function to fetch code based on category type and ID
    function fetchCode(type, categoryId) {
        const url = `{{ route('finance.get-code-by-category.store') }}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                type: type,
                category_id: categoryId,
                _token: '{{ csrf_token() }}'  // CSRF token for security
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


</script>
@endsection

@extends('click.layout.indexs.index')
@section('style')
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="mb-3">
        <a href="{{ route('finance.items.index') }}" class="btn btn-primary">
            <i class="fa fa-arrow-right"></i> {{ __('finance::finance.items') }}
        </a>
        <a href="{{ asset('items_data.xlsx') }}" class="btn btn-success">
            <i class="fa fa-download"></i> {{ __('finance::finance.download_sample_excel') }}
        </a>

    </div>

    <form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
        <div class="col-md-3">
            <label for="title" class="form-label">{{ __('finance::finance.type') }}</label>
            @php
                $category_types = array_filter($category_types, function ($case) {
                    return !in_array($case->value, ['finished_product', 'boxs']);
                });
            @endphp
            <select name="type" id="type" class="form-control" data-validation="required"
                onchange="handleTypeChange()">
                {!! getEnumData($category_types) !!}
            </select>
        </div>

        <div class="col-md-3">
            <label for="main_category_id" class="form-label">{{ __('finance::finance.main_categories') }}</label>
            {!! addUpButton(route('finance.categories.create'), 'main_category_id', '', 'title', true) !!}
            <select name="main_category_id" id="main_category_id" class="form-control" data-validation="required">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($main_categories as $item)
                    <option value="{{ $item->id }}" data-type="{{ $item->type }}" data-code="{{ $item->type }}">
                        {{ $item->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="sub_category_id" class="form-label">{{ __('finance::finance.sub_categories') }}</label>
            {!! addUpButton(route('finance.categories.create'), 'sub_category_id', '', 'title', true) !!}
            <select name="sub_category_id" id="sub_category_id" class="form-control">
                <option value="">{{ __('finance::finance.choose') }}</option>
                <!-- سيتم تعبئة هذه الخيارات عبر JavaScript -->
            </select>
            {{-- <input type="hidden" name="code" value="{{ $code }}" data-validation="required" readonly id="itemCode" class="form-control" data-validation="required"> --}}
            <input type="hidden" id="is_tax_no" value="0" name="is_tax" onchange="toggleTaxFields()">

        </div>


        <div class="col-md-3">
            <label for="sale_account_id" class="form-label">{{ __('finance::finance.sale_account') }}:</label>
            {!! addUpButton(route('finance.accounts.create'), 'sale_account_id', '', 'name', true) !!}
            <select name="sale_account_id" id="sale_account_id" class="form-control" data-validation="required">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($sale_accounts as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- <div id="tax-fields" style="display: none;" class="col-12">
            <div class="row">
                <div class="col-md-4">
                    <label for="tax_setting_id" class="form-label">{{ __("finance.Tax Account") }}:</label>
                    <select name="tax_setting_id" id="tax_setting_id" class="form-control" data-validation="required">
                        <option value="">{{ __("finance.choose") }}</option>
                        @foreach ($tax_settings as $item)
                            <option value="{{ $item->id }}" data-value="{{ $item->value }}" data-account-id="{{ $item->account_id }}">
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" id="tax_account_id" name="tax_account_id" value="" data-validation="required">
                </div>
                <div class="col-md-4">
                    <label for="tax_rate" class="form-label">{{ __("finance.Tax rate") }}:</label>
                    <input type="number" name="tax_rate" value="" readonly id="tax_rate" class="form-control">
                </div>

                <div class="col-md-4">
                    <label for="tax_amount" class="form-label">{{ __("finance.Tax amount") }}:</label>
                    <input type="number" name="tax_amount" id="tax_amount" class="form-control" readonly>
                </div>
            </div>
        </div> --}}

        <div id="blade-container"></div>

        <div class="form-group">
            <label for="file">{{ __('finance::finance.upload_excel_file') }}</label>
            <input type="file" name="file" class="form-control" data-validation="required">
        </div>
        <div class="col-12 modal-footer">
            {!! submitButton($storeRoute) !!}
            {{-- {!! submitNewButton($storeRoute) !!} --}}
        </div>

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
                lang: '{{ \App::currentLocale() }}',
                validateOnEvent: true
            });
        });
        // Get Blades From Type
        $(document).ready(function() {
            const typeInput = $('#type');
            const bladeContainer = $('#blade-container');

            typeInput.on('change', function() {
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
                    success: function(response) {
                        bladeContainer.html(response);
                    },
                    error: function(error) {
                        console.error('Error loading Blade view:', error);
                    }
                });
            }
        });
    </script>

    <script>
        // Get Sub Category From Category && Get code From Category and Sub Category
        $(document).ready(function() {
            const mainCategorySelect = $('#main_category_id');
            const subCategorySelect = $('#sub_category_id');

            // عندما تتغير الفئة الرئيسية
            mainCategorySelect.on('change', function() {
                const mainCategoryId = $(this).val();

                // جلب الأكواد بناءً على الفئة الرئيسية
                if (mainCategoryId) {
                    fetchSubCategories(mainCategoryId);
                    fetchCode('mainCategories', mainCategoryId);
                } else {
                    subCategorySelect.html(
                        '<option value="">{{ __('finance::finance.choose') }}</option>');
                }
            });

            // دالة لجلب الفئات الفرعية بناءً على الفئة الرئيسية
            function fetchSubCategories(mainCategoryId) {
                const url = `{{ route('finance.categories.show', ':id') }}`.replace(':id', mainCategoryId);

                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        subCategorySelect.html(
                            '<option value="">{{ __('finance::finance.choose') }}</option>');
                        $.each(data, function(index, item) {
                            subCategorySelect.append(
                                `<option value="${item.id}">${item.title}</option>`);
                        });
                    },
                    error: function(error) {
                        console.error('Error fetching subcategories:', error);
                    }
                });
            }
        });
    </script>
@endsection

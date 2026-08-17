@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ __('finance::finance.refused_catch_checks') }}</li>
@endsection
@section('content')
    @php
        $setting_count = $setting_code->count();
        $items_count = $items->count();
        $items_main_category_ids = $items->pluck('main_category_id')->toArray();
        $items_sub_category_ids = $items->pluck('sub_category_id')->toArray();
    @endphp
    <div class="clearfix"></div>
    <form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

        <div class="col-4">
            <label for="address" class="form-label">{{ __('finance::finance.Numbering by') }}</label>
            <select id="type" name="type" class="form-control" @if ($items_count != 0) disabled @endif>
                @foreach (Nami\Finance\Enums\ProductNumSettingEnum::cases() as $case)
                    <option value="{{ $case->value }}"
                        {{ $setting_code->count() > 0 && $case->value == $setting_code->first()->type ? 'selected' : '' }}>
                        {{ $case->lang() }}</option>
                @endforeach
            </select>
            @if ($items_count != 0)
                <input type="hidden" name="type" value="{{ $setting_code->first()->type ?? '' }}">
            @endif
        </div>
        <hr>

        <div class="col-4 inputField code">
            <label for="code" class="form-label">{{ __('finance::finance.code') }} </label>
            <input type="text" name="code" id="code" class="form-control myInput code_input"
                data-validation="length" data-validation-length="max255">
        </div>
        <div class="col-4 inputField code">
            <label for="code_string" class="form-label">{{ __('finance::finance.code_string') }} </label>
            <input type="text" name="code_string" id="code_string" class="form-control myInput code_string"
                data-validation="length" data-validation-length="max255">
        </div>
        <div class="col-4 inputField code">
            <label for="code_string" class="form-label">{{ __('finance::finance.final_code') }} </label>
            <input type="text" id="final_code" class="form-control final_code" readonly>
        </div>

        <div class="col-12 inputField mianCategories">
            <label for="mianCategories" class="form-label">{{ __('finance::finance.MainCategories') }} </label>
            <table class="table  table-bordered">
                <thead>
                    <tr>
                        <th scope="col">{{ __('finance::finance.category') }}</th>
                        <th scope="col">{{ __('finance::finance.code') }}</th>
                        <th scope="col">{{ __('finance::finance.code_string') }}</th>
                        <th scope="col">{{ __('finance::finance.final_code') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mainCategories as $category)
                        <tr>
                            <th scope="row">
                                {{ $category->title }}
                            </th>
                            <td>
                                <input type="number" id="code_{{ $category->id }}"
                                    name="mainCategories[{{ $category->id }}][code]"
                                    class="form-control myInput index-plus ms-2 main_code_{{ $category->id }}"
                                    data-validation="number,length" data-validation-length="max255"
                                    onkeyup="mainConcatenateInputs({{ $category->id }})">
                                <!-- استدعاء الدالة عند كل كتابة -->
                            </td>
                            <td>
                                <input type="text" id="code_string_{{ $category->id }}"
                                    name="mainCategories[{{ $category->id }}][code_string]"
                                    class="form-control myInput index-plus ms-2 main_code_string_{{ $category->id }}"
                                    data-validation="length,alphanumeric" data-validation-length="max255"
                                    onkeyup="mainConcatenateInputs({{ $category->id }})">
                                <!-- استدعاء الدالة عند كل كتابة -->
                            </td>
                            <td>
                                <input type="text" id="final_code_{{ $category->id }}"
                                    class="form-control ms-2 main_final_code_{{ $category->id }}" readonly>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="col-12 inputField subCategories">
            <label for="subCategories" class="form-label">{{ __('finance::finance.SubCategories') }} </label>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col">{{ __('finance::finance.category') }}</th>
                        <th scope="col">{{ __('finance::finance.code') }}</th>
                        <th scope="col">{{ __('finance::finance.code_string') }}</th>
                        <th scope="col">{{ __('finance::finance.final_code') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subCategories as $category)
                        <tr>
                            <th scope="row">
                                {{ $category->title }}
                            </th>
                            <td>
                                <input type="number" id="code_{{ $category->id }}"
                                    name="subCategories[{{ $category->id }}][code]"
                                    class="form-control myInput index-plus ms-2 sub_code_{{ $category->id }}"
                                    data-validation="number,length" data-validation-length="max255"
                                    onkeyup="concatenateInputs({{ $category->id }})">
                            </td>
                            <td>
                                <input type="text" id="code_string_{{ $category->id }}"
                                    name="subCategories[{{ $category->id }}][code_string]"
                                    class="form-control myInput index-plus ms-2 sub_code_string_{{ $category->id }}"
                                    data-validation="length,alphanumeric" data-validation-length="max255"
                                    onkeyup="concatenateInputs({{ $category->id }})">
                            </td>
                            <td>
                                <input type="text" id="final_code_{{ $category->id }}"
                                    class="form-control ms-2 sub_final_code_{{ $category->id }}" readonly>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="col-12 modal-footer" id="submitButton">
            {!! submitButton($storeRoute) !!}
        </div>
    </form>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Initially hide all input fields
            $('.inputField').hide();

            var parent = "{{ $setting_type }}";
            var setting_count = "{{ $setting_count }}"
            var items_count = "{{ $items_count }}";
            if (parent == 'allProducts') {
                $('.code').show();
                if (setting_count > 0) {
                    var code = "{{ $setting_code->first() ? $setting_code->first()->code : '' }}";
                    var codeString = "{{ $setting_code->first() ? $setting_code->first()->code_string : '' }}";
                    $('#code').val(code);
                    $('#code_string').val(codeString);
                    $('#final_code').val(codeString + code);
                }
                if (items_count > 0) {
                    $('#code').prop('readonly', true);
                    $('#code_string').prop('readonly', true);
                    $('#submitButton').hide();
                }

            } else if (parent == 'mainCategories') {
                $('.mianCategories').show();
                var settingsArray = {!! json_encode($setting_code->toArray()) !!};
                var mainCategories = @json($mainCategories);
                var items_main_category_ids = @json($items_main_category_ids);
                settingsArray.forEach(function(element) {
                    $('#code_' + element.category_id).val(element.code || '');
                    $('#code_string_' + element.category_id).val(element.code_string || '');
                    $('#final_code_' + element.category_id).val((element.code_string || '') + (element
                        .code || ''));
                    if (items_main_category_ids.includes(element.category_id)) {
                        $('#code_' + element.category_id).prop('readonly', true);
                        $('#code_string_' + element.category_id).prop('readonly', true);
                    }
                    if (mainCategories.length == items_main_category_ids.length) {
                        $('#submitButton').hide();
                    }
                });
            } else if (parent == 'subCategories') {
                $('.subCategories').show();
                var settingsArray = {!! json_encode($setting_code->toArray()) !!};
                var subCategories = @json($subCategories);
                var items_sub_category_ids = @json($items_sub_category_ids);
                settingsArray.forEach(function(element) {
                    $('.sub_code_' + element.category_id).val(element.code || '');
                    $('.sub_code_string_' + element.category_id).val(element.code_string || '');
                    $('.sub_final_code_' + element.category_id).val((element.code_string || '') + (element
                        .code || ''));
                    if (items_sub_category_ids.includes(element.category_id)) {
                        $('.sub_code_' + element.category_id).prop('readonly', true);
                        $('.sub_code_string_' + element.category_id).prop('readonly', true);
                    }
                    if (subCategories.length == items_sub_category_ids.length) {
                        $('#submitButton').hide();
                    }
                });
            }

            $(document).on('change', '#type', function(e) {
                var selectedValue = $(this).val();

                $('.myInput').val('');

                // Hide all input fields
                $('.inputField').hide();

                // Show input fields based on the selected value
                if (selectedValue == 'allProducts') {
                    $('.code').show();
                } else if (selectedValue == 'mainCategories') {
                    $('.mianCategories').show();
                } else if (selectedValue == 'subCategories') {
                    $('.subCategories').show();
                }
            });
        });

        $(document).ready(function() {
            // Function to concatenate the values of the first two inputs
            function concatenateInputs() {
                var code = $('#code').val(); // Get value from the first input
                var codeString = $('#code_string').val(); // Get value from the second input
                var finalCode = codeString + code; // Concatenate the values
                $('#final_code').val(finalCode); // Set the concatenated value to the third input
            }

            // Attach the function to the 'input' event of the first two inputs
            $('#code, #code_string').on('input', function() {
                concatenateInputs();
            });
        });

        function mainConcatenateInputs(categoryId) {
            var codeElement = document.querySelector('.main_code_' + categoryId);
            var codeStringElement = document.querySelector('.main_code_string_' + categoryId);
            var finalCodeElement = document.querySelector('.main_final_code_' + categoryId);

            var code = codeElement ? codeElement.value : '';
            var codeString = codeStringElement ? codeStringElement.value : '';
            var finalCode = codeString + code;

            if (finalCodeElement) {
                finalCodeElement.value = finalCode;
            }
        }

        function concatenateInputs(categoryId) {
            var codeElement = document.querySelector('.sub_code_' + categoryId);
            var codeStringElement = document.querySelector('.sub_code_string_' + categoryId);
            var finalCodeElement = document.querySelector('.sub_final_code_' + categoryId);

            var code = codeElement ? codeElement.value : '';
            var codeString = codeStringElement ? codeStringElement.value : '';
            var finalCode = codeString + code;

            if (finalCodeElement) {
                finalCodeElement.value = finalCode;
            }
        }


        // $(document).ready(function() {
        //     function concatenateInputs(categoryId) {
        //         var code = $('#code_' + categoryId).val() || '';
        //         var codeString = $('#code_string_' + categoryId).val() || '';
        //         var finalCode = codeString + code;
        //         $('#final_code_' + categoryId).val(finalCode);
        //     }

        //     $(document).on('input', '[id^=code_], [id^=code_string_]', function() {
        //         var inputId = $(this).attr('id');
        //         var array = inputId.split('_');
        //         var categoryId = array[array.length - 1];
        //         concatenateInputs(categoryId);
        //     });
        // });
    </script>
@endsection

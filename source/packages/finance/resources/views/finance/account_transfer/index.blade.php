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
    <div class="form-check form-switch">
        <label class="form-check-label" id="switchFilterLabel"
            for="switchFilter">{{ __('finance::finance.advanced_search') }}</label>
        <input class="form-check-input" type="checkbox" role="switch" id="switchFilter">
    </div>
    <div class="row mb-3 filter_data" style="display: none">
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.from_date') }}</label>
                <input class="form-control" name="from_date" id="from_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.to_date') }}</label>
                <input class="form-control" name="to_date" id="to_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.cost_center') }}</label>
                <select name="cost_center_id" id="cost_center_id" class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($cost_centers as $cost_center)
                        <option value="{{ $cost_center->id }}">{{ $cost_center->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.From Account') }}</label>
                <select name="credit_account_id" id="credit_account_id" class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($banks_boxes as $banks_box)
                        <option value="{{ $banks_box->id }}">{{ $banks_box->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.To Account') }}</label>
                <select name="debit_account_id" id="debit_account_id" class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($banks_boxes as $banks_box)
                        <option value="{{ $banks_box->id }}">{{ $banks_box->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {!! addButton($createRoute, $addButtonText) !!}

    <table class="table" id="dataTableObject">
        <thead class="thead-inverse">
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th> {{ __('finance::finance.From Account') }}</th>
                <th>{{ __('finance::finance.To Account') }}</th>
                <th> {{ __('finance::finance.value') }}</th>
                <th>{{ __('finance::finance.date') }}</th>
                <th>{{ __('finance::finance.actions') }}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>
    <script>
        // Custom validation rule for checking minimum value
        $.formUtils.addValidator({
            name: 'min_value',
            validatorFunction: function(value, $el, config, language, $form) {
                return parseFloat(value) >= 1;
            },
            errorMessage: '{{ __('finance::finance.value_min_error') }}',
            errorMessageKey: 'min_value'
        });

        $(document).ready(function() {
            $.validate({
                ignore: 'input[type=hidden]',
                modules: 'date, security',
                lang: '{{ \App::currentLocale() }}',
                validateOnEvent: true,
                onModulesLoaded: function() {
                    $('input[name="value"]').attr('data-validation', 'required,min_value');
                }
            });
        });

        function updateOptions(select1, select2) {
            $(select2).find('option').prop('disabled', false);
            var selectedValue = $(select1).val();
            if (selectedValue) {
                $(select2).find('option[value="' + selectedValue + '"]').prop('disabled', true);
            }
            $(select2).select2({
                dropdownParent: $('#createModal .modal-body'),
            });
        }
        $(document).ready(function() {
            let table = $("#dataTableObject").DataTable({
                responsive: true,
                processing: true,
                serverSide: false,
                ordering: true,
                searching: true,
                iDisplayLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "الكل"]
                ],
                ajax: {
                    url: "{{ $dataTableRoute }}",
                    data: function(response) {
                        response.from_date = $('#from_date').val();
                        response.to_date = $('#to_date').val();
                        response.cost_center_id = $('#cost_center_id').val();
                        response.debit_account_id = $('#debit_account_id').val();
                        response.credit_account_id = $('#credit_account_id').val();
                    }
                },
                columns: [{
                        "data": 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "credit_account",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "debit_account",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "value",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "date",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "actions",
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    sProcessing: "{{ trans('dataTable.sProcessing') }}",
                    sLengthMenu: "{{ trans('dataTable.sLengthMenu') }}",
                    sZeroRecords: "{{ trans('dataTable.sZeroRecords') }}",
                    sInfo: "{{ trans('dataTable.sInfo') }}",
                    sInfoEmpty: "{{ trans('dataTable.sInfoEmpty') }}",
                    sInfoFiltered: "{{ trans('dataTable.sInfoFiltered') }}",
                    sSearch: "{{ trans('dataTable.sSearch') }}:",
                    oPaginate: {
                        sFirst: "{{ trans('dataTable.sFirst') }}",
                        sPrevious: "{{ trans('dataTable.sPrevious') }}",
                        sNext: "{{ trans('dataTable.sNext') }}",
                        sLast: "{{ trans('dataTable.sLast') }}"
                    }
                },
                order: [
                    [2, "desc"]
                ]
            });
            $('#from_date, #to_date , #cost_center_id,#debit_account_id,#credit_account_id').change(function() {
                table.ajax.reload();
            });
        })
    </script>

    <script>
        $(document).on('click', '.confirmButton', function(e) {
            e.preventDefault()
            let buttonObj = $(this);
            let confirmRoute = buttonObj.attr("confirm-route");
            let bondId = buttonObj.attr("confirm-id");
            let postData = {
                "bond_id": bondId
            };
            swal.fire({
                title: "{{ __('buttons.Are You Sure') }}",
                text: "{{ __('buttons.You Can not to rollback') }}",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "{{ __('buttons.accept') }}",
                cancelButtonText: "{{ __('buttons.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: confirmRoute,
                        data: postData,
                        type: 'get',
                        beforeSend: function() {},
                        complete: function() {},
                        success: function(data) {
                            Swal.fire({
                                icon: 'success',
                                title: "{{ __('buttons.Confirm successfully') }}",
                                confirmButtonText: "{{ __('buttons.close') }}",
                            });
                            reloadDataTable();
                        },
                        error: function(errorObj, errorText, errorThrown) {
                            handleErrorResponce(errorObj);
                        }
                    });
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $(document).on('click', '.deleteImagesButton', function(e) {
                e.preventDefault();
                let buttonObj = $(this);
                let deleteRoute = buttonObj.attr("delete-route");
                let uploadedImageDiv = buttonObj.closest('.uploadedImage');

                swal.fire({
                    title: "{{ __('buttons.Are You Sure') }}",
                    text: "{{ __('buttons.You Can not to rollback') }}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "{{ __('buttons.accept') }}",
                    cancelButtonText: "{{ __('buttons.cancel') }}",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteRoute,
                            type: 'DELETE',
                            beforeSend: function() {},
                            complete: function() {},
                            success: function(data) {
                                Swal.fire({
                                    icon: 'success',
                                    title: "{{ __('buttons.Deleted successfully') }}",
                                    confirmButtonText: "{{ __('buttons.close') }}",
                                });
                                uploadedImageDiv.remove();
                            },
                            error: function(errorObj, errorText, errorThrown) {
                                handleErrorResponce(errorObj);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection

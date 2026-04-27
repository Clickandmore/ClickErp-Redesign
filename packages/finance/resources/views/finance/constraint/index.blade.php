@extends('click.layout.indexs.index')
@section("style")
    <style>
        .constraint-statement-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 320px;
        }

        .constraint-statement-cell strong {
            white-space: normal;
            line-height: 1.8;
        }

        .constraint-statement-cell small {
            color: #6c757d;
            font-size: 12px;
        }
    </style>
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
@section('content')
    <div class="d-flex mb-3 justify-content-end">
        <a href="{{url('finance/add-constraint/create')}}" class="btn btn-success float-start  mx-1">
        <i class="fa fa-plus-circle"></i> {{ __('buttons.add button'). $addButtonText}}
        </a>
    </div>
{{--    {!! addButton($createRoute , $addButtonText) !!}--}}
    <div class="row my-2">
        <div class="col-md-3">
            <label>{{ __('finance::finance.filter_by_type') }}</label>
            <select class="form-control" id="type_filter" name="type_filter">
                <option value="">{{ __('finance::finance.choose') }}</option>
                <option value="debtor">{{ __('finance::finance.debtor') }}</option>
                <option value="creditor">{{ __('finance::finance.creditor') }}</option>
            </select>
        </div>
        <div class="col-md-3 float-start">
            <label for="TextInput" class="form-label">{{ __("finance::finance.from_date") }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control" required>
        </div>
        <div class="col-md-3 float-start">
            <label for="TextInput" class="form-label">{{ __("finance::finance.to_date") }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control" required>
        </div>
    </div>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>رقم القيد</th>
            <th>{{ __('finance::finance.statement') }}</th>
            <th>{{ __('finance::finance.value') }}</th>
            <th>{{ __('finance::finance.date') }}</th>
            <th>مدينون</th>
            <th>دائنون</th>
            <th>{{ __('finance::finance.print') }}</th>
            <th>المستند</th>
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
            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "الكل"] ],
                "processing": true,
                "serverSide": false,
                "searching": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function (d) {
                        d.from_date = $('#from_date').val()
                        d.to_date = $('#to_date').val()
                         d.type_filter = $('#type_filter').val()

                    }
                },
                "columns": [
                    {"data": "code_link", orderable: false, searchable: true},
                    {"data": "statement_preview", orderable: false, searchable: true},
                    {"data": "value", orderable: false, searchable: true},
                    {"data": "date", orderable: false, searchable: true},
                    {"data": "debitors_total", orderable: false, searchable: true},
                    {"data": "creditors_total", orderable: false, searchable: true},
                    {"data": "print_action", orderable: false, searchable: false},
                    {"data": "document_action", orderable: false, searchable: false}
                ],
            });

            $('#filter').click(function() {
                table.ajax.reload();
            });

            $('#from_date').change(function () {
                table.ajax.reload();

            });
            $('#to_date').change(function () {
                table.ajax.reload();

            });
            $('#type_filter').change(function () {
                table.ajax.reload();

            });
        });
    </script>


    <script>

        $(document).on('click', '.add_debtor', function (e) {
            e.preventDefault();
            var HtmlDebtorAppend = $("#debtor_append").clone();
            HtmlDebtorAppend.find('i').addClass('delete-row');
            let newSelect = HtmlDebtorAppend.find(".select-unique-option");
            newSelect.select2('destroy');
            HtmlDebtorAppend.appendTo('.table-body');
            disableNewOptions(newSelect);
        });
        $(document).on('click', '.add_creditor', function (e) {
            e.preventDefault();
            var HtmlCreditorAppend = $("#creditor_append").clone();
            HtmlCreditorAppend.find('i').addClass('delete-row');
            let newSelect = HtmlCreditorAppend.find(".select-unique-option");
            newSelect.select2('destroy');
            HtmlCreditorAppend.appendTo('.table-body');
            disableNewOptions(newSelect);
        });

        /************************************************************************************/
        $(document).on('change', '.select-unique-option', function () {
            $('option', '.select-unique-option').prop('disabled', false);
            $('.select-unique-option').each(function () {
                var val = this.value;
                $('.select-unique-option').not(this).find('option').filter(function () {
                    return this.value === val;
                }).prop('disabled', true);
            });
        }).change();

        function disableNewOptions(newSelect) {
            $('.select2').select2({
                dropdownParent: $('#createModal .modal-body'),
            });
            $('select.select-unique-option').not(newSelect).each(function () {
                if ($("option:selected", this).val() != "") {
                    newSelect.find('option[value="' + $("option:selected", this).val() + '"]').attr("disabled", true);
                }
            });
        }


        $(document).on('keyup', ['.debtor', '.creditor'], function (e) {
            e.preventDefault();
            var debtor = $('.debtor');
            var creditor = $('.creditor');
            var creditor_total_value = 0;
            var debtor_total_value = 0;
            debtor.each(function (e) {
                debtor_total_value += parseFloat($(this).val()) || 0;
            })
            $('#debtor_footer_value').val(debtor_total_value);
            creditor.each(function (e) {
                creditor_total_value += parseFloat($(this).val());
            })
            $('#creditor_footer_value').val(creditor_total_value);
            if (debtor_total_value.toFixed(2) == creditor_total_value) {
                $('.debtor').removeClass('input-error');
                $('.creditor').removeClass('input-error');
                $('.error-span').attr('hidden', true);
                var value = $('#debtor_footer_value').val();
                $('.debtor__value').append(`<input type="hidden" name="value" value="${value}" class="form-control form-control-lg mb-1">`);
            } else {
                $('.debtor').addClass('input-error');
                $('.creditor').addClass('input-error');
                $('.error-span').removeAttr('hidden');
                $('.debtor__value').children().remove();
            }
        });

        $(document).on('click', ['.add_creditor','.remove_creditor'], function () {
            var debtor = $('.debtor');
            var creditor = $('.creditor');
            var creditor_total_value = 0;
            var debtor_total_value = 0;
            debtor.each(function (e) {
                debtor_total_value += parseFloat($(this).val()) || 0;
            })
            $('#debtor_footer_value').val(debtor_total_value);
            creditor.each(function (e) {
                creditor_total_value += parseFloat($(this).val());
            })
            $('#creditor_footer_value').val(creditor_total_value);
            if (debtor_total_value.toFixed(2) == creditor_total_value) {
                $('.debtor').removeClass('input-error');
                $('.creditor').removeClass('input-error');
                $('.error-span').attr('hidden', true);
                var value = $('#debtor_footer_value').val();
                $('.debtor__value').append(`<input type="hidden" name="value" value="${value}" class="form-control form-control-lg mb-1">`);
            } else {
                $('.debtor').addClass('input-error');
                $('.creditor').addClass('input-error');
                $('.error-span').removeAttr('hidden');
                $('.debtor__value').children().remove();
            }
        });

        $(document).on('click', '.delete-row', function (e) {
            e.preventDefault();
            let obj = $(this)
            let parentTr = obj.closest("tr");
            let deleteSelect = $('select.select-unique-option option:selected', parentTr);
            let deletedVal = parseInt(deleteSelect.val()) || 0;
            $('select.select-unique-option').each(function () {
                $(this).find('option[value="' + deletedVal + '"]').attr("disabled", false);
            });
            parentTr.remove();
        })


    </script>

    <script>
        $(document).ready(function () {
            $(document).on('click', '.deleteImagesButton', function (e) {
                e.preventDefault();
                let buttonObj = $(this);
                let deleteRoute = buttonObj.attr("delete-route");
                let uploadedImageDiv = buttonObj.closest('.uploadedImage');

                swal.fire({
                    title: "{{__('buttons.Are You Sure')}}",
                    text: "{{__('buttons.You Can not to rollback')}}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "{{__('buttons.accept')}}",
                    cancelButtonText: "{{__('buttons.cancel')}}",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteRoute,
                            type: 'DELETE',
                            beforeSend: function () {},
                            complete: function () {},
                            success: function (data) {
                                Swal.fire({
                                    icon: 'success',
                                    title: "{{__('buttons.Deleted successfully')}}",
                                    confirmButtonText: "{{__('buttons.close')}}",
                                });
                                uploadedImageDiv.remove();
                            },
                            error: function (errorObj, errorText, errorThrown) {
                                handleErrorResponce(errorObj);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection

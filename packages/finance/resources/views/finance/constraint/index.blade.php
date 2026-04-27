@extends('click.layout.indexs.index')
@section('style')
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

        .constraints-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            padding: 16px;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        }

        .constraints-toolbar__title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #202736;
        }

        .constraints-toolbar__subtitle {
            margin: 4px 0 0;
            color: #6c757d;
            font-size: 13px;
        }

        .constraints-toolbar__actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .constraints-filter-panel {
            margin-bottom: 18px;
            padding: 16px;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            background: #fbfcfe;
        }

        .constraints-table-wrap {
            padding: 14px;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            background: #fff;
        }

        .constraints-table-wrap .table thead th {
            white-space: nowrap;
            color: #475467;
            background: #f8fafc;
            border-bottom: 1px solid #e9eef5;
        }

        .constraint-account-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 190px;
        }

        .constraint-account-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #edf0f4;
            line-height: 1.6;
        }

        .constraint-account-line:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .constraint-account-line strong {
            direction: ltr;
            white-space: nowrap;
            color: #1f2937;
        }

        .constraint-approval-cell {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 150px;
        }

        .constraint-approval-cell small {
            color: #6c757d;
            font-size: 12px;
        }

        @media (max-width: 767.98px) {
            .constraints-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .constraints-toolbar__actions {
                justify-content: stretch;
            }

            .constraints-toolbar__actions .btn {
                flex: 1 1 auto;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    @php($approvalMode = $approvalMode ?? 'approved')
    <div class="constraints-toolbar">
        <div>
            <h2 class="constraints-toolbar__title">{{ $bladeTitle }}</h2>
            <p class="constraints-toolbar__subtitle">
                {{ $approvalMode === 'pending' ? __('finance::finance.pending_constraints_approval_hint') : __('finance::finance.approved_constraints_hint') }}
            </p>
        </div>
        <div class="constraints-toolbar__actions">
            <a href="{{ $approvedRoute }}" class="btn {{ $approvalMode === 'approved' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fa fa-check-circle"></i> {{ __('finance::finance.approved_constraints') }}
            </a>
            <a href="{{ $pendingRoute }}" class="btn {{ $approvalMode === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                <i class="fa fa-clock-o"></i> {{ __('finance::finance.pending_constraints_approval') }}
            </a>
            @if ($approvalMode === 'approved')
                <a href="{{ $exportPdfRoute }}" id="exportPdf" class="btn btn-outline-danger">
                    <i class="fa fa-file-pdf-o"></i> PDF
                </a>
                <a href="{{ $exportExcelRoute }}" id="exportExcel" class="btn btn-outline-success">
                    <i class="fa fa-file-excel-o"></i> Excel
                </a>
            @endif
            <a href="{{ url('finance/add-constraint/create') }}" class="btn btn-success">
                <i class="fa fa-plus-circle"></i> {{ __('buttons.add button') . $addButtonText }}
            </a>
        </div>
    </div>
    {{--    {!! addButton($createRoute , $addButtonText) !!} --}}
    <div class="constraints-filter-panel">
        <div class="row">
            <div class="col-md-3">
                <label>{{ __('finance::finance.filter_by_type') }}</label>
                <select class="form-control" id="type_filter" name="type_filter">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    <option value="debtor">{{ __('finance::finance.debtor') }}</option>
                    <option value="creditor">{{ __('finance::finance.creditor') }}</option>
                </select>
            </div>
            <div class="col-md-3 float-start">
                <label for="TextInput" class="form-label">{{ __('finance::finance.from_date') }}</label>
                <input type="date" name="from_date" id="from_date" class="form-control" required>
            </div>
            <div class="col-md-3 float-start">
                <label for="TextInput" class="form-label">{{ __('finance::finance.to_date') }}</label>
                <input type="date" name="to_date" id="to_date" class="form-control" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" id="clearFilters" class="btn btn-light border w-100">
                    <i class="fa fa-refresh"></i> مسح الفلاتر
                </button>
            </div>
        </div>
    </div>

    <div class="constraints-table-wrap">
        <table id="dataTableObject" class="table display dataTable table-hover align-middle" style="width:100%">
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
                    <th>{{ __('finance::finance.actions') }}</th>
                    @if ($approvalMode === 'pending')
                        <th>{{ __('finance::finance.approval_status') }}</th>
                        <th>{{ __('finance::finance.approval_action') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            function currentFilters() {
                return {
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    type_filter: $('#type_filter').val()
                };
            }

            function buildExportUrl(baseUrl) {
                var params = $.param(currentFilters());
                return params ? baseUrl + '?' + params : baseUrl;
            }

            function syncExportLinks() {
                @if ($approvalMode === 'approved')
                    $('#exportPdf').attr('href', buildExportUrl("{{ $exportPdfRoute }}"));
                    $('#exportExcel').attr('href', buildExportUrl("{{ $exportExcelRoute }}"));
                @endif
            }

            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "الكل"]
                ],
                "processing": true,
                "serverSide": false,
                "searching": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function(d) {
                        $.extend(d, currentFilters());

                    }
                },
                "columns": [{
                        "data": "code_link",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "statement_preview",
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
                        "data": "debitors_total",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "creditors_total",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "print_action",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "document_action",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "actions",
                        orderable: false,
                        searchable: false
                    }
                    @if ($approvalMode === 'pending')
                        ,
                        {
                            "data": "approval_status",
                            orderable: false,
                            searchable: false
                        },
                        {
                            "data": "approval_action",
                            orderable: false,
                            searchable: false
                        }
                    @endif
                ],
            });

            $('#filter').click(function() {
                table.ajax.reload();
            });

            $('#from_date').change(function() {
                syncExportLinks();
                table.ajax.reload();

            });
            $('#to_date').change(function() {
                syncExportLinks();
                table.ajax.reload();

            });
            $('#type_filter').change(function() {
                syncExportLinks();
                table.ajax.reload();

            });
            $('#clearFilters').click(function() {
                $('#from_date, #to_date').val('');
                $('#type_filter').val('');
                syncExportLinks();
                table.ajax.reload();
            });
            syncExportLinks();

            $(document).on('click', '.approveConstraintButton', function(e) {
                e.preventDefault();
                var buttonObj = $(this);
                var approveRoute = buttonObj.data('action');

                swal.fire({
                    title: "{{ __('buttons.Are You Sure') }}",
                    text: "{{ __('finance::finance.approve_constraint_confirmation') }}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: "#198754",
                    confirmButtonText: "{{ __('buttons.accept') }}",
                    cancelButtonText: "{{ __('buttons.cancel') }}",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: approveRoute,
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: "{{ __('finance::finance.constraint_approved_successfully') }}",
                                    confirmButtonText: "{{ __('buttons.close') }}",
                                });
                                table.ajax.reload();
                            },
                            error: function(errorObj) {
                                handleErrorResponce(errorObj);
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.deleteConstraintButton', function(e) {
                e.preventDefault();
                var buttonObj = $(this);
                var deleteRoute = buttonObj.data('action');

                swal.fire({
                    title: "{{ __('buttons.Are You Sure') }}",
                    text: "{{ __('finance::finance.constraint_delete_confirmation') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "{{ __('buttons.accept') }}",
                    cancelButtonText: "{{ __('buttons.cancel') }}",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteRoute,
                            type: 'DELETE',
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: "{{ __('finance::finance.constraint_deleted_successfully') }}",
                                    confirmButtonText: "{{ __('buttons.close') }}",
                                });
                                table.ajax.reload();
                            },
                            error: function(errorObj) {
                                handleErrorResponce(errorObj);
                            }
                        });
                    }
                });
            });
        });
    </script>


    <script>
        $(document).on('click', '.add_debtor', function(e) {
            e.preventDefault();
            var HtmlDebtorAppend = $("#debtor_append").clone();
            HtmlDebtorAppend.find('i').addClass('delete-row');
            HtmlDebtorAppend.find('.constraint-old-id').remove();
            let newSelect = HtmlDebtorAppend.find(".select-unique-option");
            newSelect.select2('destroy');
            HtmlDebtorAppend.appendTo('.table-body');
            disableNewOptions(newSelect);
        });
        $(document).on('click', '.add_creditor', function(e) {
            e.preventDefault();
            var HtmlCreditorAppend = $("#creditor_append").clone();
            HtmlCreditorAppend.find('i').addClass('delete-row');
            HtmlCreditorAppend.find('.constraint-old-id').remove();
            let newSelect = HtmlCreditorAppend.find(".select-unique-option");
            newSelect.select2('destroy');
            HtmlCreditorAppend.appendTo('.table-body');
            disableNewOptions(newSelect);
        });

        /************************************************************************************/
        $(document).on('change', '.select-unique-option', function() {
            $('option', '.select-unique-option').prop('disabled', false);
            $('.select-unique-option').each(function() {
                var val = this.value;
                $('.select-unique-option').not(this).find('option').filter(function() {
                    return this.value === val;
                }).prop('disabled', true);
            });
        }).change();

        function disableNewOptions(newSelect) {
            $('.select2').select2({
                dropdownParent: $('#createModal .modal-body'),
            });
            $('select.select-unique-option').not(newSelect).each(function() {
                if ($("option:selected", this).val() != "") {
                    newSelect.find('option[value="' + $("option:selected", this).val() + '"]').attr("disabled",
                        true);
                }
            });
        }


        $(document).on('keyup', ['.debtor', '.creditor'], function(e) {
            e.preventDefault();
            var debtor = $('.debtor');
            var creditor = $('.creditor');
            var creditor_total_value = 0;
            var debtor_total_value = 0;
            debtor.each(function(e) {
                debtor_total_value += parseFloat($(this).val()) || 0;
            })
            $('#debtor_footer_value').val(debtor_total_value);
            creditor.each(function(e) {
                creditor_total_value += parseFloat($(this).val());
            })
            $('#creditor_footer_value').val(creditor_total_value);
            if (debtor_total_value.toFixed(2) == creditor_total_value) {
                $('.debtor').removeClass('input-error');
                $('.creditor').removeClass('input-error');
                $('.error-span').attr('hidden', true);
                var value = $('#debtor_footer_value').val();
                $('.debtor__value').append(
                    `<input type="hidden" name="value" value="${value}" class="form-control form-control-lg mb-1">`
                );
            } else {
                $('.debtor').addClass('input-error');
                $('.creditor').addClass('input-error');
                $('.error-span').removeAttr('hidden');
                $('.debtor__value').children().remove();
            }
        });

        $(document).on('click', ['.add_creditor', '.remove_creditor'], function() {
            var debtor = $('.debtor');
            var creditor = $('.creditor');
            var creditor_total_value = 0;
            var debtor_total_value = 0;
            debtor.each(function(e) {
                debtor_total_value += parseFloat($(this).val()) || 0;
            })
            $('#debtor_footer_value').val(debtor_total_value);
            creditor.each(function(e) {
                creditor_total_value += parseFloat($(this).val());
            })
            $('#creditor_footer_value').val(creditor_total_value);
            if (debtor_total_value.toFixed(2) == creditor_total_value) {
                $('.debtor').removeClass('input-error');
                $('.creditor').removeClass('input-error');
                $('.error-span').attr('hidden', true);
                var value = $('#debtor_footer_value').val();
                $('.debtor__value').append(
                    `<input type="hidden" name="value" value="${value}" class="form-control form-control-lg mb-1">`
                );
            } else {
                $('.debtor').addClass('input-error');
                $('.creditor').addClass('input-error');
                $('.error-span').removeAttr('hidden');
                $('.debtor__value').children().remove();
            }
        });

        $(document).on('click', '.delete-row', function(e) {
            e.preventDefault();
            let obj = $(this)
            let parentTr = obj.closest("tr");
            let deleteSelect = $('select.select-unique-option option:selected', parentTr);
            let deletedVal = parseInt(deleteSelect.val()) || 0;
            $('select.select-unique-option').each(function() {
                $(this).find('option[value="' + deletedVal + '"]').attr("disabled", false);
            });
            parentTr.remove();
        })
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

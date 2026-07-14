@extends('click.layout.indexs.index')
@section('style')
    <style>
        .constraint-statement-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 360px;
            max-width: 520px;
        }

        .constraint-statement-cell strong {
            white-space: normal;
            line-height: 1.8;
            overflow-wrap: anywhere;
            word-break: break-word;
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

        .constraints-filter-grid {
            display: grid;
            grid-template-columns: minmax(260px, 1.4fr) minmax(210px, 1fr) minmax(170px, .8fr) minmax(170px, .8fr);
            gap: 12px;
            align-items: end;
        }

        .constraints-filter-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #e9eef5;
        }

        .constraints-filter-actions__main,
        .constraints-filter-actions__export {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .constraints-search-input {
            direction: rtl;
        }

        .constraints-filter-panel label {
            margin-bottom: 6px;
            color: #344054;
            font-weight: 600;
        }

        .constraints-table-wrap {
            padding: 14px;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            background: #fff;
            overflow-x: auto;
        }

        .constraints-table-wrap .table {
            table-layout: fixed;
            min-width: 1320px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .constraints-table-wrap .table thead th {
            white-space: nowrap;
            color: #475467;
            background: #f8fafc;
            border-bottom: 1px solid #e9eef5;
        }

        .constraints-table-wrap .table tbody td {
            vertical-align: top;
            white-space: normal;
            line-height: 1.7;
        }

        .constraint-account-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 240px;
            max-width: 320px;
        }

        .constraint-account-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(92px, auto);
            align-items: start;
            gap: 10px;
            padding-bottom: 4px;
            border-bottom: 1px dashed #edf0f4;
            line-height: 1.6;
        }

        .constraint-account-name {
            min-width: 0;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .constraint-account-line:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .constraint-account-line--last {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .constraint-account-line strong {
            direction: ltr;
            white-space: nowrap;
            color: #1f2937;
            text-align: left;
        }

        .constraint-account-more {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            margin-top: 2px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475467;
            font-size: 12px;
            line-height: 1.6;
        }

        #dataTableObject th:nth-child(1),
        #dataTableObject td:nth-child(1) {
            width: 95px;
        }

        #dataTableObject th:nth-child(2),
        #dataTableObject td:nth-child(2) {
            width: 390px;
        }

        #dataTableObject th:nth-child(3),
        #dataTableObject td:nth-child(3),
        #dataTableObject th:nth-child(4),
        #dataTableObject td:nth-child(4) {
            width: 125px;
        }

        #dataTableObject th:nth-child(5),
        #dataTableObject td:nth-child(5),
        #dataTableObject th:nth-child(6),
        #dataTableObject td:nth-child(6) {
            width: 285px;
            vertical-align: top;
        }

        #dataTableObject th:nth-child(7),
        #dataTableObject td:nth-child(7) {
            width: 135px;
            text-align: center;
        }

        #dataTableObject th:nth-child(8),
        #dataTableObject td:nth-child(8) {
            width: 175px;
        }

        #dataTableObject th:nth-child(9),
        #dataTableObject td:nth-child(9) {
            width: 155px;
            text-align: center;
        }

        .constraint-actions-dropdown {
            display: inline-flex;
            justify-content: center;
        }

        .constraint-actions-menu {
            display: none;
            position: fixed !important;
            z-index: 1065;
            min-width: 190px;
            padding: 6px;
            text-align: right;
        }

        .constraint-actions-menu.show {
            display: block;
        }

        .constraint-actions-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 6px;
            font-size: 13px;
        }

        .constraint-actions-menu .dropdown-item i {
            width: 16px;
            text-align: center;
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

            .constraints-filter-grid {
                grid-template-columns: 1fr;
            }

            .constraints-filter-actions,
            .constraints-filter-actions__main,
            .constraints-filter-actions__export {
                align-items: stretch;
                flex-direction: column;
            }

            .constraints-filter-actions .btn {
                width: 100%;
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
            <a href="{{ url('finance/add-constraint/create') }}" class="btn btn-success">
                <i class="fa fa-plus-circle"></i> {{ __('buttons.add button') . $addButtonText }}
            </a>
        </div>
    </div>
    {{--    {!! addButton($createRoute , $addButtonText) !!} --}}
    <form class="constraints-filter-panel" id="constraintsFilterForm">
        <div class="constraints-filter-grid">
            <div>
                <label for="constraint_code">رقم القيد</label>
                <input type="search" inputmode="numeric" class="form-control constraints-search-input"
                    id="constraint_code" name="constraint_code" placeholder="مثال: 6052" autocomplete="off">
            </div>
            <div>
                <label for="document_number">رقم السند أو الفاتورة</label>
                <input type="search" inputmode="numeric" class="form-control constraints-search-input"
                    id="document_number" name="document_number" placeholder="رقم المستند المرتبط" autocomplete="off">
            </div>
            <div>
                <label for="keyword">بحث في البيان أو الحساب</label>
                <input type="search" class="form-control constraints-search-input" id="keyword" name="keyword"
                    placeholder="البيان، الحساب، القيمة أو التاريخ" autocomplete="off">
            </div>
            <div>
                <label>{{ __('finance::finance.filter_by_type') }}</label>
                <select class="form-control" id="type_filter" name="type_filter">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach(($typeOptions ?? []) as $typeOption)
                        <option value="{{ $typeOption->value }}">{{ $typeOption->lang() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from_date" class="form-label">{{ __('finance::finance.from_date') }}</label>
                <input type="date" name="from_date" id="from_date" class="form-control">
            </div>
            <div>
                <label for="to_date" class="form-label">{{ __('finance::finance.to_date') }}</label>
                <input type="date" name="to_date" id="to_date" class="form-control">
            </div>
        </div>
        <div class="constraints-filter-actions">
            <div class="constraints-filter-actions__main">
                <button type="submit" id="applyFilters" class="btn btn-primary">
                    <i class="fa fa-search"></i> بحث وتطبيق الفلاتر
                </button>
                <button type="button" id="clearFilters" class="btn btn-light border">
                    <i class="fa fa-refresh"></i> مسح الفلاتر
                </button>
            </div>
            @if ($approvalMode === 'approved')
                <div class="constraints-filter-actions__export">
                    <span class="text-muted small">تصدير النتائج الحالية:</span>
                    <a href="{{ $exportPdfRoute }}" id="exportPdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </a>
                    <a href="{{ $exportExcelRoute }}" id="exportExcel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </a>
                </div>
            @endif
        </div>
    </form>

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
                    <th>المستند المرتبط</th>
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
                    constraint_code: $('#constraint_code').val(),
                    document_number: $('#document_number').val(),
                    keyword: $('#keyword').val(),
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
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                "processing": true,
                "serverSide": true,
                "searching": false,
                "sort": false,
                "autoWidth": false,
                "scrollX": true,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function(d) {
                        $.extend(d, currentFilters());

                    }
                },
                "columns": [{
                        "data": "code_link",
                        "name": "fn_constraints.code",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "statement_preview",
                        "name": "fn_constraints.statement",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "value",
                        "name": "fn_constraints.value",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "date",
                        "name": "fn_constraints.date",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "debitors_total",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "creditors_total",
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

            var openConstraintMenu = null;
            var openConstraintMenuOwner = null;

            function closeConstraintActionsMenu() {
                if (!openConstraintMenu) {
                    return;
                }

                openConstraintMenu.removeClass('show').hide().removeAttr('style');
                if (openConstraintMenuOwner && openConstraintMenuOwner.length) {
                    openConstraintMenuOwner.append(openConstraintMenu);
                    openConstraintMenuOwner.find('.constraint-actions-toggle').attr('aria-expanded', 'false');
                }
                openConstraintMenu = null;
                openConstraintMenuOwner = null;
            }

            function positionConstraintActionsMenu($button, $menu) {
                var buttonRect = $button[0].getBoundingClientRect();
                var menuWidth = $menu.outerWidth();
                var menuHeight = $menu.outerHeight();
                var spacing = 6;

                var top = buttonRect.bottom + spacing;
                if (top + menuHeight > window.innerHeight - spacing) {
                    top = Math.max(spacing, buttonRect.top - menuHeight - spacing);
                }

                var left = buttonRect.right - menuWidth;
                left = Math.max(spacing, Math.min(left, window.innerWidth - menuWidth - spacing));

                $menu.css({
                    top: top + 'px',
                    left: left + 'px',
                    display: 'block'
                });
            }

            $(document).on('click', '.constraint-actions-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $button = $(this);
                var $owner = $button.closest('.constraint-actions-dropdown');
                var $menu = $owner.find('.constraint-actions-menu').first();

                if (openConstraintMenu && openConstraintMenu[0] === $menu[0]) {
                    closeConstraintActionsMenu();
                    return;
                }

                closeConstraintActionsMenu();

                openConstraintMenu = $menu.appendTo('body').addClass('show').show();
                openConstraintMenuOwner = $owner;
                $button.attr('aria-expanded', 'true');
                positionConstraintActionsMenu($button, openConstraintMenu);
            });

            $(document).on('click', '.constraint-actions-menu', function(e) {
                e.stopPropagation();
            });

            $(document).on('click', '.constraint-actions-menu .dropdown-item', function() {
                setTimeout(closeConstraintActionsMenu, 0);
            });

            $(document).on('click', function() {
                closeConstraintActionsMenu();
            });

            $(window).on('scroll resize', closeConstraintActionsMenu);
            table.on('draw.dt', closeConstraintActionsMenu);

            $('#constraintsFilterForm').on('submit', function(e) {
                e.preventDefault();
                syncExportLinks();
                table.ajax.reload();
            });

            $('#from_date, #to_date, #type_filter').on('change', syncExportLinks);
            $('#keyword, #constraint_code, #document_number').on('input', syncExportLinks);

            $('#clearFilters').click(function() {
                $('#keyword, #constraint_code, #document_number, #from_date, #to_date').val('');
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
        }).change();

        function disableNewOptions(newSelect) {
            $('.select2').select2({
                dropdownParent: $('#createModal .modal-body'),
            });
            $('option', '.select-unique-option').prop('disabled', false);
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

@extends('click.layout.indexs.index')
@section('style')
    <style>
        .required-asterisk {
            color: red;
        }

        .opening-balance-group-row {
            background: #f7f9fb;
            cursor: pointer;
            font-weight: 700;
        }

        .opening-balance-group-row .fa {
            width: 16px;
        }

        .input-error {
            border-color: #dc3545 !important;
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
    <form id="formFilter" class="to_hide">
        <div class="row mb-2 p-2 rounded-3">

            <div class="col-3">
                <div class="form-group">
                    <label for="year_id" class="form-label">{{ __('finance::finance.Select the Annual Year') }} <span
                            class="required-asterisk" id="spanYear">*</span></label>
                    <select id="year_id" name="year_id" class="form-control select2">
                        {!! showSelectElement($years, 'title') !!}
                    </select>
                </div>
            </div>

            <div class="col-3">
                <div class="form-group">
                    <label for="keyword" class="form-label">بحث برقم أو اسم الحساب</label>
                    <input type="text" id="keyword" name="keyword" class="form-control"
                        placeholder="مثال: 1201 أو العملاء">
                </div>
            </div>

            <div class="col-2">
                <div class="form-group">
                    <label for="level" class="form-label">المستوى</label>
                    <select id="level" name="level" class="form-control">
                        <option value="">كل المستويات</option>
                        @foreach ($accountLevels as $level)
                            <option value="{{ $level }}">المستوى {{ $level }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-4">
                <button type="button" id="FilterBtn" class="btn mt-4 btn-success"
                    disabled>{{ __('dataTable.sSearch') }}</button>
            </div>
        </div>
    </form>

    <div id="resultTable">

    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            let openingBalanceTotals = {
                debtor: 0,
                creditor: 0,
                invalidRows: new Set(),
            };

            $('#year_id').change(function() {
                if ($(this).val() !== '') {
                    $('#FilterBtn').prop('disabled', false);
                    $('#spanYear').css('display', 'none');
                } else {
                    $('#FilterBtn').prop('disabled', true);
                    $('#spanYear').css('display', 'block');
                }
            });

            $('#FilterBtn').click(function(e) {
                e.preventDefault();

                // Validate form input
                if ($('#year_id').val() === '') {
                    alert("{{ __('finance::finance.Select the Year') }}");
                    return false;
                }

                // Serialize form data
                var formData = $('#formFilter').serialize();

                // AJAX request
                $.ajax({
                    url: "{{ route('finance.opening-balance.index') }}",
                    type: 'GET',
                    data: formData,
                    beforeSend: function() {
                        // Before send actions
                        $('#resultTable').html('');
                        $('#FilterBtn').html(
                            '<span class="spinner-border spinner-border-sm mt-1" role="status" aria-hidden="true"></span> '
                        );
                    },
                    success: function(data) {
                        // On success actions
                        $('#resultTable').html(data.data.html);
                        initOpeningBalanceState();

                        // Append print and export buttons
                        var printButtonHtml =
                            '<button onclick="window.print()" class="btn btn-primary float-right mt-5"><i class="fa fa-print"></i> {{ __('dataTable.print') }}</button>';
                        var exportButtonHtml =
                            '<button id="export-button" class="btn btn-success float-right mt-2 mt-5"><i class="fa fa-file-excel-o"></i> {{ __('dataTable.Export to Excel') }}</button>';
                        $('#resultTable').prepend(printButtonHtml);
                        $('#resultTable').prepend(exportButtonHtml);

                        // Export to Excel functionality
                        $('#export-button').click(function() {
                            var wb = XLSX.utils.book_new();
                            var table = document.querySelector('#resultTable table');
                            var ws = XLSX.utils.table_to_sheet(table);
                            XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
                            XLSX.writeFile(wb, 'double_account_statement.xlsx');
                        });
                    },
                    error: function(xhr, status, error) {
                        console.log(error);
                    },
                    complete: function() {
                        // On complete actions
                        $('#FilterBtn').html('{{ __('dataTable.sSearch') }}');
                    }
                });
            });

            function numericValue(value) {
                let parsedValue = parseFloat(value);
                return isNaN(parsedValue) ? 0 : parsedValue;
            }

            function initOpeningBalanceState() {
                openingBalanceTotals.debtor = 0;
                openingBalanceTotals.creditor = 0;
                openingBalanceTotals.invalidRows = new Set();

                $('.debtor-input').each(function() {
                    let value = numericValue($(this).val());
                    $(this).data('last-value', value);
                    openingBalanceTotals.debtor += value;
                });

                $('.creditor-input').each(function() {
                    let value = numericValue($(this).val());
                    $(this).data('last-value', value);
                    openingBalanceTotals.creditor += value;
                });

                $('.debtor-input').each(function() {
                    updateRowValidity($(this).data('account-id'));
                });

                updateBalanceState();
            }

            function updateBalanceState() {
                let totalValue = openingBalanceTotals.debtor - openingBalanceTotals.creditor;
                $('#total_value').val(openingBalanceTotals.debtor);
                $('#balance').val(totalValue.toFixed(2));
                $('[data-total-debtor]').text(openingBalanceTotals.debtor.toFixed(2));
                $('[data-total-creditor]').text(openingBalanceTotals.creditor.toFixed(2));
                $('[data-total-difference]').text(totalValue.toFixed(2));

                if (Math.abs(totalValue) > 0.00001 || openingBalanceTotals.invalidRows.size > 0) {
                    $('#addOrUpdateConstraints').prop('disabled', true);
                    $('#openingBalanceSummary').removeClass('alert-info alert-success').addClass('alert-warning');
                } else {
                    $('#addOrUpdateConstraints').prop('disabled', false);
                    $('#openingBalanceSummary').removeClass('alert-info alert-warning').addClass('alert-success');
                }
            }

            function updateRowValidity(accountId) {
                let debtorInput = $('.debtor-input[data-account-id="' + accountId + '"]');
                let creditorInput = $('.creditor-input[data-account-id="' + accountId + '"]');
                let debtorValue = numericValue(debtorInput.val());
                let creditorValue = numericValue(creditorInput.val());
                let rowHasBothSides = debtorValue > 0 && creditorValue > 0;

                if (rowHasBothSides) {
                    openingBalanceTotals.invalidRows.add(String(accountId));
                    debtorInput.addClass('input-error').siblings('.error-span').removeAttr('hidden');
                    creditorInput.addClass('input-error').siblings('.error-span').removeAttr('hidden');
                } else {
                    openingBalanceTotals.invalidRows.delete(String(accountId));
                    debtorInput.removeClass('input-error').siblings('.error-span').attr('hidden', true);
                    creditorInput.removeClass('input-error').siblings('.error-span').attr('hidden', true);
                }
            }

            $(document).on('input change', '.debtor-input, .creditor-input', function() {
                let input = $(this);
                let currentValue = numericValue(input.val());
                let lastValue = numericValue(input.data('last-value'));
                let diff = currentValue - lastValue;

                input.data('last-value', currentValue);
                input.attr('data-edited', 'true');

                if (input.hasClass('debtor-input')) {
                    openingBalanceTotals.debtor += diff;
                } else {
                    openingBalanceTotals.creditor += diff;
                }

                updateRowValidity(input.data('account-id'));
                updateBalanceState();
            });

            $(document).on('click', '.opening-balance-group-row', function() {
                let groupTarget = $(this).data('group-target');
                let rows = $('.' + groupTarget);
                let icon = $(this).find('.fa').first();

                rows.toggle();
                icon.toggleClass('fa-folder-open fa-folder');
            });

            // Form submission handling
            $(document).on('submit', '#formOfFirstBalance', function(e) {
                e.preventDefault();

                let buttonObj = $('#addOrUpdateConstraints');
                let isValid = true;
                updateBalanceState();
                let totalValue = parseFloat($('#balance').val());
                let statement = $('#statement').val();
                let date = $('#date').val();
                let value = $('#total_value').val();
                let year_id = $('#year_id').val();

                // Validation example
                if (isNaN(totalValue) || Math.abs(totalValue) > 0.00001) {
                    isValid = false;
                    alert('يرجي ادخال بيانات صحيحة للدائن والمدين');
                }

                // Initialize arrays for the required JSON structure
                let accountIds = [];
                let accountStatuses = [];
                let debtorValues = [];
                let creditorValues = [];
                let visibleAccountIds = [];
                let hasRowWithBothSides = false;

                // Collect the current non-zero opening balances so existing rows are preserved on update.
                $('.debtor-input').each(function() {
                    let accountId = $(this).data('account-id');
                    let accountStatus = $(this).data('account-status');
                    let debtorValue = $('.debtor-input[data-account-id="' + accountId + '"]').val() || 0;
                    let creditorValue = $('.creditor-input[data-account-id="' + accountId + '"]')
                        .val() || 0;
                    let debtorNumber = numericValue(debtorValue);
                    let creditorNumber = numericValue(creditorValue);

                    if (debtorNumber > 0 && creditorNumber > 0) {
                        hasRowWithBothSides = true;
                    }

                    if (visibleAccountIds.indexOf(accountId) === -1) {
                        visibleAccountIds.push(accountId);
                    }

                    if (accountIds.indexOf(accountId) === -1 && (debtorNumber > 0 || creditorNumber > 0)) {
                        accountIds.push(accountId);
                        accountStatuses.push(accountStatus);
                        debtorValues.push(debtorValue);
                        creditorValues.push(creditorValue);
                    }
                });

                if (hasRowWithBothSides) {
                    isValid = false;
                    alert('لا يمكن إدخال مدين ودائن لنفس الحساب في نفس الصف');
                }

                if (!accountIds.length) {
                    isValid = false;
                    alert('يرجي إدخال رصيد افتتاحي لحساب واحد على الأقل');
                }

                // Prepare the form data
                let formData = {
                    value: value,
                    date: date,
                    statement: statement,
                    account_id: accountIds,
                    account_type: accountStatuses,
                    debtor_value: debtorValues,
                    creditor_value: creditorValues,
                    year_id: year_id,
                    visible_account_id: visibleAccountIds,
                };

                // Continue with form submission if valid
                if (isValid) {
                    // Perform AJAX request
                    $.ajax({
                        url: "{{ route('finance.opening-balance.store') }}",
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify(formData),
                        beforeSend: function() {
                            buttonObj.html(
                                '<span class="spinner-border spinner-border-sm mt-1" role="status" aria-hidden="true"></span>'
                            );
                        },
                        success: function(data) {
                            successToster('{{ __('auth.done successfully') }}');
                            window.location.reload();
                            console.log('Success:', data);
                        },
                        error: function(xhr, status, error) {
                            // Handle error response here
                            console.log('Error:', error);
                            console.log('Status:', status);
                            console.dir(xhr);
                            handleErrorResponce(xhr);
                        },
                        complete: function() {
                            buttonObj.html('{{ __('web.Confirm') }}');
                        }
                    });
                }
            });


            $(document).on('click', '.deleteFirstBalance', function(e) {
                e.preventDefault()
                let buttonObj = $(this);
                let confirmRoute = buttonObj.data("action");
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
                            type: 'DELETE',
                            complete: function() {},
                            success: function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: "{{ __('buttons.Confirm successfully') }}",
                                    confirmButtonText: "{{ __('buttons.close') }}",
                                });
                                window.location.reload();
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportTable(btnId, tableId, excelFileName) {
            // Export to Excel functionality
            const exportButton = document.getElementById(btnId);

            if (!exportButton) {
                return;
            }

            exportButton.addEventListener('click', function() {
                var wb = XLSX.utils.book_new();
                var table = document.getElementById(tableId);
                var ws = XLSX.utils.table_to_sheet(table);
                XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
                XLSX.writeFile(wb, excelFileName);
            });
        }
    </script>
    <script>
        exportTable('export-button', 'table-to-export', '{{ __('finance::finance.opening-balance') }}.xlsx');
    </script>
@endsection

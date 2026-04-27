@extends('click.layout.indexs.index')
@section('style')
    <style>
        .required-asterisk {
            color: red;
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

            <div class="col-4">
                <div class="form-group">
                    <label for="year_id" class="form-label">{{ __('finance::finance.Select the Annual Year') }} <span
                            class="required-asterisk" id="spanYear">*</span></label>
                    <select id="year_id" name="year_id" class="form-control select2">
                        {!! showSelectElement($years, 'title') !!}
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

            $(document).on('change', 'input[type="number"]', function() {
                $(this).attr('data-edited', 'true');
                recalculateTotal();
            });

            function recalculateTotal() {
                let totalDebtor = 0;
                let totalCreditor = 0;

                // Calculate total debtor value
                $('.debtor-input').each(function() {
                    let value = parseFloat($(this).val());
                    if (!isNaN(value)) {
                        totalDebtor += value;
                    }
                });

                // Calculate total creditor value
                $('.creditor-input').each(function() {
                    let value = parseFloat($(this).val());
                    if (!isNaN(value)) {
                        totalCreditor += value;
                    }
                });
                $('#total_value').val(totalDebtor);
                // Calculate total value
                let totalValue = totalDebtor - totalCreditor;

                // Set total value in the input field
                $('#balance').val(totalValue.toFixed(2));

                // Check validity and handle UI accordingly
                if (totalValue !== 0) {
                    $('#addOrUpdateConstraints').prop('disabled', true);
                    $('.error-span').removeAttr('hidden');
                    $('.debtor-input').addClass('input-error');
                    $('.creditor-input').addClass('input-error');
                } else {
                    $('#addOrUpdateConstraints').prop('disabled', false);
                    $('.error-span').attr('hidden', true);
                    $('.debtor-input').removeClass('input-error');
                    $('.creditor-input').removeClass('input-error');
                }
            }

            // Form submission handling
            $(document).on('submit', '#formOfFirstBalance', function(e) {
                e.preventDefault();

                let buttonObj = $('#addOrUpdateConstraints');
                let isValid = true;
                let totalValue = parseFloat($('#balance').val());
                let statement = $('#statement').val();
                let date = $('#date').val();
                let value = $('#total_value').val();
                let year_id = $('#year_id').val();

                // Validation example
                if (isNaN(totalValue) || totalValue !== 0) {
                    isValid = false;
                    alert('يرجي ادخال بيانات صحيحة للدائن والمدين');
                }

                // Initialize arrays for the required JSON structure
                let accountIds = [];
                let accountStatuses = [];
                let debtorValues = [];
                let creditorValues = [];

                // Collect only edited account_id, debtor_value, and creditor_value
                $('input[data-edited="true"]').each(function() {
                    let accountId = $(this).data('account-id');
                    let accountStatus = $(this).data('account-status');
                    let debtorValue = $('.debtor-input[data-account-id="' + accountId + '"]').val();
                    let creditorValue = $('.creditor-input[data-account-id="' + accountId + '"]')
                        .val();

                    if (accountIds.indexOf(accountId) === -1) {
                        accountIds.push(accountId);
                        accountStatuses.push(accountStatus);
                        debtorValues.push(debtorValue);
                        creditorValues.push(creditorValue);
                    }
                });

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
                            // Optionally, handle error UI feedback
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
            document.getElementById(btnId).addEventListener('click', function() {
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

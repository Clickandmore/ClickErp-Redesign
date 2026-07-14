<script>
    // $(document).ajaxComplete(function() {
    //     function setTaxRate() {
    //         var selectedOption = $('#tax_setting_id option:selected');
    //         var dataValue = selectedOption.data('value');
    //         var accountId = selectedOption.data('account-id');
    //         $('#tax_account_id').val(accountId);
    //         $('#tax_rate').val(dataValue);
    //         calculateTax();
    //     }

    //     $('#tax_setting_id').on('change', function() {
    //         setTaxRate();
    //     });
    //     setTaxRate();
    // });
        function getPaymentTypeHtml(type) {
            let obj = $("#payment_type");
            let paymentTypeValue = obj.find(":selected").val();
            let url = '{{ route('finance.bonds-payment.show', ':id') }}';
            url = url.replace(':id', paymentTypeValue);
            url += "?type=" + type;
            $.ajax({
                url: url,
                type: 'GET',
                beforeSend: function () {
                    $("#content-div").html("");
                },
                complete: function () {
                },
                success: function (result) {
                    $("#content-div").html(result.data.html);
                },
                error: function (errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        }


    function toggleTaxFields() {
        var yesRadio = document.getElementById('is_tax_yes');
        var taxFields = document.getElementById('tax-fields');

        if (yesRadio.checked) {
            taxFields.style.display = 'block';
        } else {
            taxFields.style.display = 'none';
        }
        calculateTax();
    }

    function calculateTax() {
        var isTaxChecked = document.getElementById('is_tax_yes').checked;
        var value = parseFloat(document.getElementById('value').value) || 0;
        var taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
        var taxAmount = 0;
        var net = value;

        if (isTaxChecked) {
            taxAmount = value - ((value * 100) / (taxRate + 100));
            net = (value * 100) / (taxRate + 100);
        }

        document.getElementById('tax_amount').value = taxAmount.toFixed(2);
        document.getElementById('net').value = net.toFixed(2);
    }


    $(document).on('click', '.confirmButton', function (e) {
        e.preventDefault()
        let buttonObj = $(this);
        let confirmRoute = buttonObj.attr("confirm-route");
        let bondId = buttonObj.attr("confirm-id");
        let postData = {"bond_id": bondId};
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
                    url: confirmRoute,
                    data: postData,
                    type: 'POST',
                    beforeSend: function () {
                    },
                    complete: function () {
                    },
                    success: function (data) {
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('buttons.Confirm successfully')}}",
                            confirmButtonText: "{{__('buttons.close')}}",
                        });
                        reloadDataTable();
                    },
                    error: function (errorObj, errorText, errorThrown) {
                        handleErrorResponce(errorObj);
                    }
                });
            }
        });
    });

        $(document).ready(function () {
            const bondTableType = @json($bondTableType ?? null);
            const isManagedBondTable = ['sarf', 'qabd'].includes(bondTableType);
            let table = $("#dataTableObject").DataTable({
                responsive: true,
                scrollX: true,
                processing: true,
                serverSide: isManagedBondTable,
                ordering: !isManagedBondTable,
                searching: true,
            searchDelay: 400,
            pageLength: 25,
            lengthMenu: isManagedBondTable ? [[10, 25, 50, 100], [10, 25, 50, 100]] : [[5, 10, 25, 50, -1], [5, 10, 25, 50, "الكل"]],
            ajax: {
                url: "{{ $dataTableRoute }}",
                data: function (response) {
                    response.bond_search = $('#bond_search').val();
                    response.from_date = $('#from_date').val();
                    response.to_date = $('#to_date').val();
                    response.cost_center_id = $('#cost_center_id').val();
                    response.debit_account_id = $('#debit_account_id').val();
                    response.credit_account_id = $('#credit_account_id').val();
                    response.payment_type = $('#payment_type_filter').val();
                    response.is_confirm = $('#is_confirm_filter').val();
                }
                },
                columns: [
                    @if (in_array(($bondTableType ?? null), ['sarf', 'qabd'], true))
                    {
                        "data": 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "bond_num",
                        name: 'code',
                        orderable: true,
                        searchable: true
                    },
                    {
                        "data": "date",
                        name: 'date',
                        orderable: true,
                        searchable: true
                    },
                    {
                        "data": "{{ ($bondTableType ?? null) === 'qabd' ? 'credit_account' : 'debit_account' }}",
                        name: '{{ ($bondTableType ?? null) === 'qabd' ? 'creditAccount.name' : 'debitAccount.name' }}',
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "{{ ($bondTableType ?? null) === 'qabd' ? 'debit_account' : 'credit_account' }}",
                        name: '{{ ($bondTableType ?? null) === 'qabd' ? 'debitAccount.name' : 'creditAccount.name' }}',
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "value",
                        name: 'value',
                        orderable: true,
                        searchable: true
                    },
                    {
                        "data": "status",
                        name: 'is_confirm',
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "constraint",
                        name: 'constraint_id',
                        orderable: true,
                        searchable: true
                    },
                    {
                        "data": "actions",
                        orderable: false,
                        searchable: false
                    }
                    @else
                    {
                        "data": "id",
                        searchable: true,
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return parseInt(data, 10) || 0;
                            }

                            return row.identifier;
                        }
                    },
                    {"data": "request_number", searchable: true},
                    {"data": "account_summary", orderable: false, searchable: true},
                    {"data": "required_amount", searchable: true},
                    {"data": "received_amount", searchable: true},
                    {"data": "financial_movement", searchable: true},
                    {"data": "created_by_name", searchable: true},
                    {"data": "date", searchable: true},
                    {"data": "print_action", orderable: false, searchable: false, className: "receipt-print"},
                    {"data": "actions", orderable: false, searchable: false, className: "receipt-actions"}
                    @endif
                ],
            language: {
                sProcessing: "{{trans('dataTable.sProcessing')}}",
                sLengthMenu: "{{trans('dataTable.sLengthMenu')}}",
                sZeroRecords: "{{trans('dataTable.sZeroRecords')}}",
                sInfo: "{{trans('dataTable.sInfo')}}",
                sInfoEmpty: "{{trans('dataTable.sInfoEmpty')}}",
                sInfoFiltered: "{{trans('dataTable.sInfoFiltered')}}",
                sSearch: "{{trans('dataTable.sSearch')}}:",
                oPaginate: {
                    sFirst: "{{trans('dataTable.sFirst')}}",
                    sPrevious: "{{trans('dataTable.sPrevious')}}",
                    sNext: "{{trans('dataTable.sNext')}}",
                    sLast: "{{trans('dataTable.sLast')}}"
                }
            },
                order: isManagedBondTable ? [] : [[0, "desc"]],
                drawCallback: function () {
                    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
                        if (!bootstrap.Tooltip.getInstance(element)) {
                            new bootstrap.Tooltip(element);
                        }
                    });
                }
            });
        if (isManagedBondTable) {
            const reloadSarfTable = function () { table.ajax.reload(null, true); };
            let searchTimer;

            $('#applySarfFilters, #applyBondFilters').on('click', reloadSarfTable);
            $('#bond_search').on('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(reloadSarfTable, 450);
            }).on('keypress', function (event) {
                if (event.which === 13) {
                    event.preventDefault();
                    clearTimeout(searchTimer);
                    reloadSarfTable();
                }
            });
            $('#from_date, #to_date, #cost_center_id, #debit_account_id, #credit_account_id, #payment_type_filter, #is_confirm_filter')
                .on('change', reloadSarfTable);
            $('#resetSarfFilters, #resetBondFilters').on('click', function () {
                $('#bond_search, #from_date, #to_date').val('');
                $('#cost_center_id, #debit_account_id, #credit_account_id, #payment_type_filter, #is_confirm_filter')
                    .val('').trigger('change.select2');
                table.search('').ajax.reload(null, true);
            });
        } else {
            $('#from_date, #to_date, #cost_center_id, #debit_account_id, #credit_account_id, #payment_type_filter').change(function () {
                table.ajax.reload();
            });
        }
    })

</script>

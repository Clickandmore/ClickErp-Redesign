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
            let table = $("#dataTableObject").DataTable({
                responsive: true,
                scrollX: true,
                processing: true,
                serverSide: false,
                ordering: true,
                searching: true,
            iDisplayLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "الكل"]],
            ajax: {
                url: "{{ $dataTableRoute }}",
                data: function (response) {
                    response.from_date = $('#from_date').val();
                    response.to_date = $('#to_date').val();
                    response.cost_center_id = $('#cost_center_id').val();
                    response.debit_account_id = $('#debit_account_id').val();
                    response.credit_account_id = $('#credit_account_id').val();
                    response.payment_type = $('#payment_type_filter').val();
                }
                },
                columns: [
                    @if (($bondTableType ?? null) === 'sarf')
                    {
                        "data": 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "bond_num",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "date",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "debit_account",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "credit_account",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "value",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "payment_type_name",
                        orderable: false,
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
                order: [[0, "desc"]]
            });
        $('#from_date, #to_date , #cost_center_id,#debit_account_id,#credit_account_id,#payment_type_filter').change(function () {
            table.ajax.reload();
        });
    })

</script>

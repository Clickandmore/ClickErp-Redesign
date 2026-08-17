@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    {!! addButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th> {{ __('finance::finance.principle') }} </th>
                <th> {{ __('finance::finance.debit_account') }} </th>
                <th> {{ __('finance::finance.sales_value') }} </th>
                <th> {{ __('finance::finance.financial_value') }} </th>
                <th> {{ __('finance::finance.date') }} </th>
                <th>{{ __('finance::finance.actions') }}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            let columns = [{
                    "data": 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "principle_id",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "debit_account_id",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "sales_value",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "financial_value",
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
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>
    <script>
        $(document).ajaxComplete(function() {
            function calculateTotalTax() {
                var taxPercentage = parseFloat($('#tax_setting_value').val()) || 0;
                var totalBeforeDiscount = parseFloat($('#principle_value').val()) || 0;

                // Calculate total tax as a percentage of the total before discount
                var totalTax = (totalBeforeDiscount * taxPercentage) / 100;

                // Update the total tax value field
                $('#total_tax_value').val(totalTax.toFixed(2));

                return totalTax;
            }

            function updateFees() {
                var totalTax = calculateTotalTax();
                var totalBeforeDiscount = parseFloat($('#sales_value').val()) || 0;

                // Calculate total fees by adding total tax to the total before discount
                var totalFees = totalBeforeDiscount + totalTax;

                // Set the calculated fees
                $('#total_price').val(totalFees.toFixed(2));
            }

            // Trigger calculation when tax setting is changed
            $('#tax_setting_id').change(function() {
                var selectedOption = $(this).find(':selected');
                var taxValue = parseFloat(selectedOption.data('value')) || 0;
                var accountId = selectedOption.data('account-id') || "";

                // Update tax_setting_value and tax_account_id based on selected option
                $('#tax_setting_value').val(taxValue);
                $('#tax_account_id').val(accountId);

                // Recalculate the total tax and fees
                updateFees();
            });

            // Trigger calculation on change in the principle value or tax setting value
            $('#sales_value, #tax_setting_value').on('input', function() {
                updateFees();
            });

            // Initial fees calculation on page load
            updateFees();
        });
    </script>
    <script>
        $(document).ajaxComplete(function() {
            $('#principle_id').change(function() {
                // Get the purchase value and financial value from the selected option's data attributes
                var purchaseValue = parseFloat($(this).find(':selected').data('purchase_value')) || 0;
                var constraintValue = parseFloat($(this).find(':selected').data('financial_value')) || 0;

                // Update the purchase value input field
                $('#principle_value').val(purchaseValue.toFixed(2));

                // Calculate and update the financial value (purchase value - constraint value)
                var financialValue = purchaseValue - constraintValue;
                $('#financial_value').val(financialValue.toFixed(2));
            });
        });
    </script>
@endsection

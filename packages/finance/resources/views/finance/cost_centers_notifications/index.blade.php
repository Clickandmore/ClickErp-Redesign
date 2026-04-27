@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('content')

    <div class="card-header bg-success text-white mb-3">
        {{ __("finance::finance.stocks") }}
    </div>
    <div class="row mb-3">
        <div class="col-md-6 mx-auto">
            <div class="form-group">
                <label class="form-label d-block mb-2 text-center fw-bold">
                    {{ __("finance::finance.have_cost_center") }}
                </label>
                <div class="d-flex justify-content-center gap-4">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="stocks_has_cost_center"
                               id="yesOption_stocks" value="1">
                        <label class="form-check-label" for="yesOption_stocks">{{ __("finance::finance.yes") }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="stocks_has_cost_center"
                               id="noOption_stocks" value="0">
                        <label class="form-check-label" for="noOption_stocks">{{ __("finance::finance.no") }}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table id="dataTableObject-stocks" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{ __('finance::finance.code') }}</th>
            <th>{{ __('finance::finance.name') }}</th>
            <th>{{ __('finance::finance.phone') }}</th>
            <th>{{ __('finance::finance.address') }}</th>
            <th>{{ __('finance::finance.stockKeeper') }}</th>
            <th>{{ __('finance::finance.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

    <hr>

{{--    <div class="card-header bg-success text-white mb-3">--}}
{{--        {{ __("finance::finance.employees") }}--}}
{{--    </div>--}}
{{--    <div class="row mb-3">--}}
{{--        <div class="col-md-6 mx-auto">--}}
{{--            <div class="form-group">--}}
{{--                <label class="form-label d-block mb-2 text-center fw-bold">--}}
{{--                    {{ __("finance::finance.have_cost_center") }}--}}
{{--                </label>--}}
{{--                <div class="d-flex justify-content-center gap-4">--}}
{{--                    <div class="form-check form-check-inline">--}}
{{--                        <input class="form-check-input" type="radio" name="employees_has_cost_center"--}}
{{--                               id="yesOption_employees" value="1">--}}
{{--                        <label class="form-check-label" for="yesOption_employees">{{ __("finance::finance.yes") }}</label>--}}
{{--                    </div>--}}
{{--                    <div class="form-check form-check-inline">--}}
{{--                        <input class="form-check-input" type="radio" name="employees_has_cost_center"--}}
{{--                               id="noOption_employees" value="0">--}}
{{--                        <label class="form-check-label" for="noOption_employees">{{ __("finance::finance.no") }}</label>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--    <table id="dataTableObject-employees" class="table display dataTable table-hover" style="width:100%">--}}
{{--        <thead>--}}
{{--        <tr>--}}
{{--            <th>{{ __('hr.SL') }}</th>--}}
{{--            <th>{{ __("hr.image") }}</th>--}}
{{--            <th>{{ __("hr.name") }}</th>--}}
{{--            <th>{{ __("hr.Employee ID number") }}</th>--}}
{{--            <th>{{ __("hr.Date of hiring") }}</th>--}}
{{--            <th>{{ __("hr.jobs") }}</th>--}}
{{--            <th>{{ __("hr.department") }}</th>--}}
{{--            <th>{{__("hr.actions")}}</th>--}}
{{--        </tr>--}}
{{--        </thead>--}}
{{--        <tbody>--}}

{{--        </tbody>--}}
{{--    </table>--}}
@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "code", orderable: false, searchable: true},
                {"data": "name", orderable: false, searchable: true},
                {"data": "phone", orderable: false, searchable: true},
                {"data": "address", orderable: false, searchable: false},
                {"data": "stockKeeper", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute1 }}", columns, '-stocks', 5);
        });
    </script>
{{--    <script>--}}
{{--        $(document).ready(function () {--}}
{{--            let columns = [--}}
{{--                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},--}}
{{--                {"data": "image", orderable: false, searchable: false},--}}
{{--                {"data": "name", orderable: false, searchable: true},--}}
{{--                {"data": "national_num", orderable: false, searchable: true},--}}
{{--                {"data": "hired_date", orderable: false, searchable: true},--}}
{{--                {"data": "job_id", orderable: false, searchable: true},--}}
{{--                {"data": "deparment_id", orderable: false, searchable: true},--}}
{{--                {"data": "actions", orderable: false, searchable: false}--}}
{{--            ];--}}
{{--            showDataTable("{{$dataTableRoute2}}", columns, '-employees', 5);--}}
{{--        });--}}
{{--    </script>--}}
    <script>
        $(document).ready(function () {
            $("input[name='stocks_has_cost_center']").on('change', function () {
                var stocks_has_cost_center = $(this).val();
                var baseUrl = "{{ route('finance.stocks.index') }}";
                var url = baseUrl + "?notification=true&stocks_has_cost_center=" + stocks_has_cost_center;
                $.ajax({
                    url: url,
                    method: "Get",
                    success: function () {
                        $("#dataTableObject-stocks").DataTable().ajax.url(url).load();
                    }
                })
            });
            {{--$("input[name='employees_has_cost_center']").on('change', function () {--}}
            {{--    var employees_has_cost_center = $(this).val()--}}
            {{--    --}}{{--var baseUrl = "{{ route('employees.index') }}";--}}
            {{--    var url = baseUrl + "?notification=true&employees_has_cost_center=" + employees_has_cost_center;--}}
            {{--    $.ajax({--}}
            {{--        url: url,--}}
            {{--        method: "Get",--}}
            {{--        success: function () {--}}
            {{--            $("#dataTableObject-employees").DataTable().ajax.url(url).load();--}}
            {{--        }--}}
            {{--    })--}}

            {{--});--}}
        });
    </script>
@endsection

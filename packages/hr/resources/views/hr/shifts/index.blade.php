@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" hr::href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.shifts')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("hr::hr.clients")}}</li>
@endsection
{{--@section('up-card')--}}
{{--    @include('click.layout.inc._nav_tabs')--}}
{{--@endsection--}}
@section('content')
    @include('hr::hr._nav_tabs')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_shifts-create')) --}}
{!! addButton(route("shifts.create") , __('hr::hr.shifts')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.shift_name") }}</th>
            <th> {{ __("hr::hr.From the hour") }}</th>
            <th>{{ __("hr::hr.Until now") }}</th>
            <th>{{ __("hr::hr.Number of days in a month") }}</th>
            <th> {{ __("hr::hr.The penalty for absence") }}</th>
            <th>{{ __("hr::hr.status") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
  $('#json_array').multifield();

        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "name", orderable: false, searchable: true},
                {"data": "from_hour", orderable: false, searchable: true},
                {"data": "to_hour", orderable: false, searchable: true},
                {"data": "month_days", orderable: false, searchable: true},
                {"data": "absence_sanctions_id", orderable: false, searchable: false},
                {"data": "status", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("shifts.index")}}", columns);
        });
    </script>
    <script>
        function getTypeHtml() {
            var selectedOption = document.getElementById("allowance-type").value;
            document.getElementById("day-field").style.display = (selectedOption == 1) ? "block" : "none";
            document.getElementById("field").style.display = (selectedOption == 1 || selectedOption == 2 || selectedOption == 3) ? "block" : "none";
            document.getElementById("value-field").style.display = (selectedOption == 2) ? "block" : "none";
            document.getElementById("percentage-field").style.display = (selectedOption == 3) ? "block" : "none";
        }
        $(document).on('click', '.toggle-status', function () {
            var btn = $(this);
            var id = btn.data('id');
            var tableName = btn.data('table');
            var status = btn.hasClass('btn-success');
            var confirmMsg = 'هل أنت متأكد من أنك تريد ' + (status ? 'تعطيل' : 'تنشيط') + ' هذا العنصر؟';

            Swal.fire({
                title: 'تأكيد',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم',
                cancelButtonText: 'الغاء',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{route('toggleStatus')}}',
                        type: 'POST',
                        data: {table_name: tableName, id: id},
                        success: function (response) {
                            console.log(200);
                            handleSuccessResponce(response);
                            var statusText = response.data.status;
                            console.log(statusText);
                            btn.toggleClass('btn-secondary btn-success').text(statusText);
                        },
                        error: function (errorObj, errorText, errorThrown) {
                            console.log(500);
                            handleErrorResponce(errorObj);
                        }
                    });
                }
            });
        });
    </script>
@endsection

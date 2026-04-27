@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.tax')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
{{--@section('up-card')--}}
{{--    @include('click.layout.inc._nav_tabs')--}}
{{--@endsection--}}
@section('content')
    @include('hr::hr._nav_tabs')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_tax-create')) --}}
{!! addButton(route("tax.create") , __('hr::hr.one_tax')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.name_of_the_slice") }}</th>
            <th>{{ __("hr::hr.Tax rate") }}</th>
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
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "title", orderable: false, searchable: true},
                {"data": "value", orderable: false, searchable: true},
                {"data": "status", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("tax.index")}}", columns);
        });
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

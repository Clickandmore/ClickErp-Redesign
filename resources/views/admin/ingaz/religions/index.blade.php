@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.religions")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::RELIGIONS_VIEW))
    @if (@AdminGuardHasPerm(\App\PermKeys::RELIGIONS_CONTROL))
        {!! addButton(route("religions.create") ,helperTrans("ingaz.religion")) !!}
    @endif
    <p>{{helperTrans('ingaz.click in the show button to change appearance status')}}</p>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.is_show")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "is_show", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("religions.index")}}", columns);
        });

        // ِChange Status Using Ajax
        $(document).on('click', '.update-status', function (event) {
            var rowId = $(this).data('id');
            var url = "{{ route('religions.show', ':id') }}";
            url = url.replace(':id', rowId);
            $.ajax({
                type: 'Get',
                url: url,
                success: function (data) {
                    if (data.success) {
                        reloadDataTable();
                        $.toast({
                            heading: 'نجاح',
                            text: 'تم تغيير الحالة بنجاح!',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });
                    }
                    else {
                        alert('فشل في تحديث الحالة.');
                    }
                }
            });
        });

    </script>
@endsection

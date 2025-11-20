@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{helperTrans("ingaz.contact")}}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_VIEW))

        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th>#</th>
                <th>{{helperTrans("ingaz.name")}}</th>
                <th>{{helperTrans("ingaz.phone")}}</th>
                <th>{{helperTrans("ingaz.email")}}</th>
                <th>{{helperTrans("ingaz.subject")}}</th>
                <th>{{helperTrans("ingaz.message")}}</th>
                <th>{{helperTrans("web.is_contacted")}}</th>
                <th>{{ helperTrans('ingaz.created_at') }}</th>
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
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "name", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "email", orderable: true, searchable: true},
                {"data": "subject", orderable: true, searchable: true},
                {"data": "message", orderable: true, searchable: true},
                {"data": "is_contacted", orderable: true, searchable: true},
                {"data": "created_at", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("contact.index")}}", columns);
        });
        $(document).on('click', '.confirm_contacted', function () {

            var rowId = $(this).data('id');
            var url = "{{ route('contact.update', ':id') }}".replace(':id', rowId);

            $.ajax({
                url: url,
                type: "Put",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    reloadDataTable()
                }
            });

        });


    </script>
@endsection

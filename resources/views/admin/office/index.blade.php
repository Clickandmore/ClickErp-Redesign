@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <style>
        /* خلّي القائمة تظهر فوق كل عناصر الجدول */
        .table .dropdown-menu {
            z-index: 2000 !important;
        }

        /* لو الـ wrapper بيقصّ المحتوى */
        .dataTables_wrapper {
            overflow: visible !important;
        }

    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.offices")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_VIEW))
        @if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_CONTROL))
            <div class="d-flex mb-3 justify-content-end">
                <a href="{{route("offices.create")}}">
                    <button type="button" class="btn btn-success float-start  mx-1">
                        <i class="fa fa-plus-circle"></i> {{helperTrans("ingaz.add_office")}}
                    </button>
                </a>
            </div>

        @endif
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th>#</th>
                <th>{{helperTrans("ingaz.title")}}</th>
                <th>{{helperTrans("ingaz.manager_email")}}</th>
                <th>{{helperTrans("ingaz.office_type")}}</th>
                <th>{{helperTrans("ingaz.nationality")}}</th>
                <th>{{helperTrans("ingaz.status")}}</th>
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
                {"data": "manager_email", orderable: true, searchable: true},
                {"data": "office_type", orderable: true, searchable: true},
                {"data": "nationality_id", orderable: true, searchable: true},
                {"data": "status", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("offices.index")}}", columns);
        });
        $(document).on('click', '.toggle-status', function () {
            const id = $(this).data('id');
            var url = "{{route('offices-status.update',"officeId")}}".replace('officeId', id);
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "PUT",
                },
                success: function (res) {
                    $('#dataTableObject').DataTable().ajax.reload();
                },
            });

        });
        $(document).on('click', '.sendEmailResetPassword', function () {
            var $btn = $(this);
            var id = $btn.data('id');
            var email = $btn.data('email');
            var originalHtml = $btn.html();
            var url = "{{route('send-email-reset-password.store')}}";
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> {{ __("ingaz.sending") }}');
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    office_id: id,
                    email: email,
                },
                success: function (response) {
                    if (response.code && response.code === 422) {
                        validToster('{{ __("auth.an error occurred") }}', response.message);
                    } else {
                        successToster('{{__("ingaz.success")}}', '{{__("ingaz.mail_sent_successfully")}}');
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        var json = xhr.responseJSON;
                        var allErrors = [];
                        if (json.errors) {
                            Object.values(json.errors).forEach(function (messages) {
                                allErrors = allErrors.concat(messages);
                            });
                        }
                        var msg = allErrors.join('<br>');
                        validToster('{{ __("auth.an error occurred") }}', msg);
                    } else {
                        validToster('{{ __("auth.an error occurred") }}',
                            '{{__("ingaz.this_email_is_invalid")}}');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

    </script>
@endsection

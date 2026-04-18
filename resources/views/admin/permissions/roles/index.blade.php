@extends('admin.layout.indexs.index')

@section('content')

    <div class="page_content">
        <div class="card">
            <div class="row card-body py-10">

                <div class="col-lg-4 col-md-6">

                    <div class="card-body">
                        @if (@AdminGuardHasPerm(\App\PermKeys::ROLES_CONTROL))
                            <form method="post" action='{{ route('dash.roles.store') }}' enctype="multipart/form-data">
                                {{ csrf_field() }}

                                <div><b>{{ @$editRole ? __('admin.edit') : __('admin.add') }}</b></div>
                                <hr>

                                <div class="alert alert-light border text-muted small">
                                    يتم إنشاء وتعديل أدوار لوحة الإدارة فقط على الحارس <code>admin</code>.
                                </div>

                                <div class="form-group mb-5">
                                    <label for="name" class="form-label">{{ __('admin.roles_name') }}</label>
                                    <input id="name" name="name" type="text" class="form-control"
                                        value="@if (@$editRole) {{ @$editRole->name }} @endif" required>
                                </div>

                                @if (@$editRole)
                                    <input type="hidden" name="edit_id" value="{{ @$editRole->id }}">
                                @endif

                                <div class="text-center mt-5">
                                    @if (@$editRole)
                                        <a href="{{ route('dash.roles.index') }}"
                                            class="btn btn-secondary">{{ __('admin.cancel') }}</a>
                                    @endif
                                    <button type="submit" class="btn btn-primary">{{ __('admin.confirm') }}</button>
                                </div>
                            </form>
                        @else
                            @include('components.no_permissions')
                        @endif
                    </div>
                </div>


                <div class="col-lg-8 col-md-6">

                    <div class="text-center">
                        @if (@AdminGuardHasPerm(\App\PermKeys::ROLES_VIEW))
                            @include('components.session-messages')
                            <div class="alert alert-info text-end">
                                <strong>لوحة الأدوار</strong>
                                <br>
                                يتم عرض الأدوار الإدارية فقط، مع عدد المشرفين المرتبطين وعدد الصلاحيات لكل دور.
                            </div>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="" style="color: #fff">
                                        <th style="width:5%">#</th>
                                        <th>{{ __('admin.roles_name') }}</th>
                                        <th style="width:14%">المشرفون</th>
                                        <th style="width:14%">الصلاحيات</th>
                                        <th style="width:12%">{{ __('admin.actions') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @if (!empty(@$roles))
                                        @foreach ($roles as $k => $singleRole)
                                            <tr>
                                                <td>{{ $singleRole->id }}</td>
                                                <td class="text-end">
                                                    <div class="fw-bold">{{ $singleRole->name }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">{{ $singleRole->linked_admins_count ?? 0 }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-dark">{{ $singleRole->permissions_count ?? 0 }}</span>
                                                </td>
                                                <td class="d-flex">
                                                    <a class="text-warning mx-2"
                                                        href="{{ route('dash.perms.setPerms', ['id' => $singleRole->id]) }}"
                                                        title="إدارة الصلاحيات">
                                                        <li class="fa fa-cog"></li>
                                                    </a>

                                                    <a class=" mx-2"
                                                        href="{{ route('dash.roles.index', ['id' => $singleRole->id]) }}"
                                                        title="تعديل الدور">
                                                        <li class="fa fa-edit"></li>
                                                    </a>

                                                    @if (($singleRole->is_protected ?? false) || (($singleRole->linked_admins_count ?? 0) > 0))
                                                        <a class="mx-2 text-muted"
                                                            title="@if ($singleRole->is_protected ?? false) هذا دور أساسي @else لا يمكن حذف دور مرتبط بمشرفين @endif">
                                                            <li class="fa fa-ban"></li>
                                                        </a>
                                                    @else
                                                        <form class=" mx-2"
                                                            action="{{ route('dash.roles.delete', ['id' => $singleRole->id]) }}"
                                                            method="post">
                                                            {!! csrf_field() !!}
                                                            <input type="hidden" name="_method" value="delete">
                                                            <a onclick="delete_confirm($(this),event)" class=""
                                                                style="color:#dd2c2c" title="حذف الدور">
                                                                <li class="fa fa-trash"></li>
                                                            </a>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function delete_confirm(element, event) {
            var form = $(element).closest("form");
            event.preventDefault();
            swal.fire({
                title: `{!! __('admin.confirm') !!}`,
                text: `{!! __('admin.confirm_delete') !!}`,
                icon: "warning",
                buttons: true,
                dangerMode: true,
                showCloseButton: true,
            }).then((willDelete) => {
                if (willDelete.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>

@endsection

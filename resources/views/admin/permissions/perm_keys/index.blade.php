@extends('admin.layout.indexs.index')

@section('content')

    @if (@AdminGuardHasPerm(\App\PermKeys::DEVELOPERS_PERMS))

    <div class="page_content">
        <div class="card">
            <div class="row card-body ">

                <div class="alert alert-warning text-center">
                    <b>{{__('admin.perms_keys_devs_only')}}</b>
                </div>

                <div class="alert alert-info text-center">
                    يمكنك الآن إيقاف الصلاحية مؤقتاً دون حذفها. الصلاحية المعطلة لا تظهر في شاشة تعيين الصلاحيات ولا تُستخدم في التحقق حتى تتم إعادة تفعيلها.
                </div>

                <div class="col-md-4">
                    <div class="card-body">

                        <form method="post" action='{{ route("dash.developers.perms.store") }}'
                            enctype="multipart/form-data">
                            {{ csrf_field() }}

                            <div><b>{{ @$editData ? __('admin.edit') : __('admin.add') }}</b></div>
                            <hr>

                            <div class="row col-md-12 mb-3">
                                <div class="col-md-2">
                                    <label for="name" class="active mt-3">{{__('admin.perms_name')}}</label>
                                </div>
                                <div class="col-md-10">
                                    <input id="name" name="name" type="text" class="form-control cu-form-design"
                                    @if (@$editData) value="{{ @$editData->name }}" @endif required>
                                </div>
                                @if (@$editData)
                                    <input type="hidden" name="edit_id"
                                        @if (@$editData) value="{{ @$editData->id }}" @endif>
                                @endif
                            </div>

                            <div class="row col-md-12 mb-3">
                                <div class="col-md-2">
                                    <label for="group_name" class="active mt-3">{{__('admin.perms_group_name')}}</label>
                                </div>
                                <div class="col-md-10">
                                    <input id="group_name" name="group_name" type="text" class="form-control cu-form-design"
                                    @if (@$editData) value="{{ @$editData->group_name }}" @endif required>
                                </div>
                            </div>


                            <div class="row col-md-12 mb-3">
                                <div class="col-md-2">
                                    <label for="section_name" class="active mt-3">{{__('admin.perms_section_name')}}</label>
                                </div>
                                <div class="col-md-10">
                                    <input id="section_name" name="section_name" type="text" class="form-control cu-form-design"
                                    @if (@$editData) value="{{ @$editData->section_name }}" @endif required>
                                </div>
                            </div>

                            <div class="row mb-3 col-md-12 text-center mt-5">
                                <div class="col-sm-12 text-sm-end text-center mt-10">
                                    @if(@$editData)
                                        <a href="{{ route('dash.developers.perms.index') }}" class="btn btn-fr-bg-light">{{ __('admin.cancel') }}</a>
                                    @endif
                                    <button type="submit" class="btn btn-fr-bg">{{ __('admin.confirm') }}</button>
                                </div>
                            </div>

                        </form>



                    </div>


                </div>


                <div class="col-md-8">

                    <div class="text-center card-body border " style="border-radius: 20px">
                        @include('components.session-messages')
                        <table class="table border table-rounded table-striped table-row-bordered cu-table-adj">
                            <thead>
                            <tr class="seal-dark-bg">
                                    <th style="width:5%">#</th>
                                    <th>{{ __('admin.perms_name') }}</th>
                                    <th>{{ __('admin.perms_group_name') }}</th>
                                    <th>{{ __('admin.perms_section_name') }}</th>
                                    <th>{{ __('admin.perms_translation') }}</th>
                                    <th>الحالة</th>
                                    <th>تعطيل مؤقت</th>
                                    <th style="width:5%">{{ __('admin.edit') }}</th>
                                    <th style="width:5%">{{ __('admin.delete') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @if (!empty(@$data))
                                    @foreach ($data as $k => $singleRow)
                                        <tr class="{{ $singleRow->is_active ? '' : 'table-warning' }}">
                                            <td>{{ @$singleRow->id }}</td>
                                            <td>{{ @$singleRow->name }}</td>
                                            <td>{{ @$singleRow->display_group_name ?? @$singleRow->group_name }}</td>
                                            <td>{{ @$singleRow->display_section_name ?? @$singleRow->section_name }}</td>
                                            <td>{{ @$singleRow->translated_name ?? __("permissions.$singleRow->name") }}</td>
                                            <td>
                                                @if ($singleRow->is_active)
                                                    <span class="badge bg-success">مفعلة</span>
                                                @else
                                                    <span class="badge bg-secondary">معطلة</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form action="{{ route('dash.developers.perms.toggleStatus', ['id' => $singleRow->id]) }}"
                                                    method="post">
                                                    {!! csrf_field() !!}
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="btn btn-sm {{ $singleRow->is_active ? 'btn-warning' : 'btn-success' }}">
                                                        {{ $singleRow->is_active ? 'إيقاف مؤقت' : 'إعادة تفعيل' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td><a href="{{ route('dash.developers.perms.index', ['id' => $singleRow->id]) }}">
                                                    <li class="fa fa-edit"></li>
                                                </a>
                                            </td>
                                            <td>
                                                <form
                                                    action="{{ route('dash.developers.perms.delete', ['id' => $singleRow->id]) }}"
                                                    method="post">
                                                    {!! csrf_field() !!}
                                                    <input type="hidden" name="_method" value="delete">
                                                    <a onclick="delete_confirm($(this),event)" class=""
                                                        style="color:#dd2c2c">
                                                        <li class="fa fa-trash"></li>
                                                    </a>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>

                        </table>
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
                text: `{!! __('admin.perms_keys_devs_only') !!}`,
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

    @else
        @include('components.no_permissions')
    @endif

@endsection

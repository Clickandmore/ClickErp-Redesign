@extends('admin.layout.indexs.index')
@section('title')
    {{ helperTrans('ingaz.edit role') }}
@endsection
@section('links')
    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ helperTrans('ingaz.edit role') }}</a></li>
@endsection
@section('page-title')
    {{helperTrans("ingaz.Roles")}}
@endsection

@section('content')

    <form method="POST" action="{{ route('roles.update', $role->id) }}" id="editForm">
        @csrf
        @method('PUT') {{-- Use PUT method for updating --}}
        <div class="row">
            <div class="card mg-b-20">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <h4 class="card-title">{{helperTrans('ingaz.permission name')}}</h4>
                                <input type="text" name="name" class="form-control" value="{{ $role->name }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <p class="card-title mt-2 mb-2">{{ helperTrans('ingaz.permission display_name') }}</p>
                                <input type="text" name="display_name" class="form-control"
                                       value="{{ $role->display_name }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <p class="card-title mt-2 mb-2">{{ helperTrans('ingaz.permission description') }}</p>
                                <input type="text" name="description" class="form-control"
                                       value="{{ $role->description }}">
                            </div>
                        </div>

                        <h4>{{helperTrans('ingaz.select the permissions that this person will able to control')}}</h4>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-check custom-checkbox mb-3 checkbox-info">
                                    <input type="checkbox" class="form-check-input" id="chooseAll">
                                    <label class="form-check-label"
                                           for="chooseAll">{{helperTrans('ingaz.choose all')}}</label>
                                </div>
                            </div>

                            @foreach($permissions as $permission)
                                <div class="col-3">
                                    <div class="form-check custom-checkbox mb-3 checkbox-info">
                                        <input type="checkbox" class="form-check-input permission-checkbox"
                                               name="permission[]" id="{{ 'permission_' . $permission->id }}"
                                               value="{{ $permission->id }}"
                                               @if(in_array($permission->id, $rolePermissions)) checked @endif>
                                        <label class="form-check-label"
                                               for="{{ 'permission_' . $permission->id }}">{{ $permission->name }}</label>
                                    </div>
                                </div>
                            @endforeach

                            <!-- col -->

                            <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                                <button type="button" id="submitForm"
                                        class="btn btn-md btn-outline-primary">{{helperTrans('ingaz.save')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
@section('js')

    <script>
        $(document).ready(function () {
            $('#chooseAll').click(function () {
                $('.name').prop('checked', $(this).prop('checked'));
            });

            $('#submitForm').click(function () {
                $.ajax({
                    type: 'POST',
                    url: $('#editForm').attr('action'), // Use the correct form ID
                    data: $('#editForm').serialize(),
                    success: function (response) {
                        if (response.code === 200) {
                            successToster('{{ helperTrans("ingaz.done successfully") }}', response.message);
                            setTimeout(function () {
                                window.location.href = response.url;
                            }, 1000);
                        } else if (response.code === 422) {
                            handleErrorResponce(response)
                        }
                    },
                    error: function (response) {
                        handleErrorResponce(response)
                    }
                });
            });
        });


    </script>
@endsection

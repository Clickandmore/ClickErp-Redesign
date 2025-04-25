@extends('admin.layout.indexs.index')

@section('page-title')
    {{__('admin.permissions')}}
@endsection

@section('css')
<script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
@endsection
@section('content')

    @if (@AdminGuardHasPerm(\App\PermKeys::SET_PERMS))

    <div class="page_content">
        <div class="card">
            <div class="row card-body">

                @if(Session::has('msg'))
                    <div class="alert alert-sm alert-success text-center">{{ Session::get('msg') }}</div>
                @endif

                <div class="col-md-4">

                    <div class="pb-10 mb-2">
                        <h3>{{ __('admin.permissions') }}</h3>
                    </div>
                    <div class="card-body border " style="border-radius: 20px">
                        @if(@AdminGuardHasPerm(\App\PermKeys::TEST_PERM))
                            @if (@$availableRoles)
                                @foreach ($availableRoles as $k => $singleRole)
                                    <div class="py-2 px-4 pt-3 @if($singleRole->id == $role->id) cu-roles-fr @endif"
                                        >
                                        <h5>
                                            <a style=""
                                                href="{{ route('dash.perms.setPerms', ['id' => $singleRole->id]) }}">{{ $singleRole->name }}</a>
                                            @if ($singleRole->id == $role->id)
                                                - <small>{{ __('admin.current_role') }}</small>
                                            @endif
                                        </h5>
                                    </div>
                                @endforeach
                            @endif
                        @else
                            @include('components.no_permissions')
                        @endif
                    </div>

                </div>

                <div class="col-md-8">
                    <div class="text-center card-body border " style="border-radius: 20px">

                        <div class="pb-10 mb-2 text-start">
                            <span>{{ __('admin.perms_assign_now') }}</span>
                            <!-- button for assign all permissions -->
                            <button class="btn btn-sm btn-success mb-0" onclick="assignAllPerms()">{{ __('admin.assign_all_perms') }}</button>
                        </div>

                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            @foreach ($data as $sectionName => $groupNamesArray)
                                <li class="nav-item" role="presentation">
                                    <button data-name="trgt-{{$sectionName}}" class="nav-link @if ($sectionName == 'general') active @endif"
                                        id="{{ $sectionName }}-tab" data-bs-toggle="tab"
                                        data-bs-target="#{{ $sectionName }}" type="button" role="tab"
                                        aria-controls="{{ $sectionName }}" aria-selected="true">
                                        <div>{{ __('permissions.' . $sectionName) }}</div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <table class="table">

                            <thead>
                                <tr class="674" style="color:#fff">
                                    <th style="width:5%">#</th>
                                    <th></th>
                                    <th>{{ __('admin.perms_name') }}</th>
                                    <th style="width:10%">{{ __('admin.perms_assign') }}</th>
                                </tr>
                            </thead>

                            <form id="storePermForm" method="post"
                                action='{{ route("dash.perms.storePerms") }}' enctype="multipart/form-data">
                                {{ csrf_field() }}
                                <input type="hidden" name="role_id" value="{{ @$role->id }}">

                                @foreach ($data as $sectionName => $groupNamesArray)
                                    <tbody class="tab-pane fade @if ($sectionName == 'general') show active @endif trgt-{{$sectionName}}"
                                    @if ($sectionName != 'general') style="display:none" @endif
                                    id="{{ $sectionName }}" role="tabpanel"
                                    aria-labelledby="{{ $sectionName }}-tab">
                                        @if (!empty(@$groupNamesArray))
                                            @foreach ($groupNamesArray as $groupName => $arrayOfPerms)
                                                <tr style="background-color: #C8D3D5" style="color:#fff">
                                                    <th></th>
                                                    <th>
                                                        <h5>{{ __('permissions.' . $groupName) }}</h5>
                                                    </th>
                                                    <th colspan="2"></th>
                                                </tr>
                                                @foreach ($arrayOfPerms as $k => $permRow)
                                                    <tr>
                                                        <td>{{ @$loop->iteration }}</td>
                                                        <td></td>
                                                        <td>{{ __('permissions.' . $permRow['name']) }}</td>
                                                        <td>

                                                            <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                                                <input class="form-check-input" name="perms[]" type="checkbox" value="{{ $permRow['name'] }}"
                                                                       {{-- @if ($role->id == 1 && @$role->hasPermissionTo($permRow['name']))
                                                                           checked="checked" onclick="return false;"
                                                                       @else --}}
                                                                       @if(@$role->hasPermissionTo($permRow['name']))
                                                                           checked="checked"
                                                                        @endif
                                                                    >
                                                            </label>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @endif
                                    </tbody>
                                @endforeach

                            </form>

                        </table>

                        <div class="row mb-3 col-md-12 text-center mt-5">
                            <div class="col-sm-12 text-sm-end text-center mt-10">
                                <a href="{{ route('dash.roles.index') }}" class="btn btn-danger">{{ __('admin.back') }}</a>
                                <a  class="btn btn-success" onclick="confirm($(this),event)">{{ __('admin.confirm') }}</a>
                            </div>
                        </div>



                    </div>


                </div>

            </div>
        </div>
    </div>


    <script>
        function confirm(element, event) {
            var form = $('#storePermForm');
            event.preventDefault();
            swal.fire({
                title: `{!! __('admin.confirm') !!}`,
                text: `{!! __('admin.confirm_note') !!}`,
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

    <script>
        $("body").on('click','.nav-link',function(){
            let name = $(this).attr("data-name");
            $('.tab-pane').css('display','none');
            $('.' + name).css('display','table-row-group');
        });
    </script>

    <script>
        function assignAllPerms() {
            var form = $('#storePermForm');
            event.preventDefault();
            swal.fire({
                title: `{!! __('admin.confirm') !!}`,
                text: `{!! __('admin.confirm_note') !!}`,
                icon: "warning",
                buttons: true,
                dangerMode: true,
                showCloseButton: true,
            }).then((willDelete) => {
                if (willDelete.isConfirmed) {
                    $('input[type="checkbox"]').prop('checked', true);
                    form.submit();
                }
            });
        }
    </script>



    @else
        @include('components.no_permissions')
    @endif


@endsection

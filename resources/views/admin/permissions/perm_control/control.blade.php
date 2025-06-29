@extends('admin.layout.indexs.index')

@section('page-title')
    {{__('admin.permissions')}}
@endsection

@section('css')
<script src="https://code.jquery.com/jquery-3.6.4.min.js" crossorigin="anonymous"></script>
<style>
    .permissions-container {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .roles-sidebar {
        background: #f8f9fa;
        border-right: 1px solid #dee2e6;
        padding: 20px;
        min-height: 600px;
    }

    .role-item {
        background: #ffffff;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        display: block;
    }

    .role-item:hover {
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
    }

    .role-item.active {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }

    .role-item.active a {
        color: white !important;
    }

    .role-item h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .role-item small {
        color: #6c757d;
        font-size: 12px;
    }

    .role-item.active small {
        color: #ffffff;
    }

    .permissions-content {
        padding: 20px;
    }

    .permissions-header {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-tabs {
        background: #ffffff;
        border-bottom: 1px solid #dee2e6;
        padding: 0 20px;
    }

    .nav-tabs {
        border: none;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .nav-tabs .nav-link {
        border: none;
        border-radius: 8px 8px 0 0;
        margin-right: 0;
        padding: 12px 20px;
        font-weight: 600;
        color: #007bff;
        background: #f8f9fa;
        transition: all 0.3s ease;
        font-size: 17px;
        letter-spacing: 0.5px;
    }

    .nav-tabs .nav-link.active {
        background: #007bff;
        color: #fff;
        border: none;
        font-size: 18px;
        font-weight: bold;
        box-shadow: 0 2px 8px rgba(0,123,255,0.08);
    }

    .permissions-body {
        padding: 20px;
        max-height: 500px;
        overflow-y: auto;
    }

    .permission-category {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .category-title {
        color: #495057;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #007bff;
    }

    .permission-group {
        background: #ffffff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
    }

    .group-title {
        color: #6c757d;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .permission-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f3f4;
    }

    .permission-item:last-child {
        border-bottom: none;
    }

    .permission-name {
        font-size: 14px;
        color: #495057;
        font-weight: 500;
    }

    .form-check-input {
        width: 20px;
        height: 20px;
    }

    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }

    .permissions-footer {
        background: #f8f9fa;
        padding: 15px 20px;
        border-top: 1px solid #dee2e6;
        text-align: center;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        margin: 0 5px;
    }

    .btn-success {
        background: #28a745;
        border-color: #28a745;
    }

    .btn-danger {
        background: #dc3545;
        border-color: #dc3545;
    }

    .assign-all-btn {
        background: #17a2b8;
        border-color: #17a2b8;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
    }

    .assign-all-btn:hover {
        background: #138496;
        border-color: #138496;
        color: white;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }
</style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SET_PERMS))
        <div class="page_content">
            <div class="permissions-container">
                <div class="row g-0">
                    <!-- Sidebar للأدوار -->
                    <div class="col-md-4">
                        <div class="roles-sidebar">
                            <h3 class="mb-4">{{ __('admin.permissions') }}</h3>

                            @if(@AdminGuardHasPerm(\App\PermKeys::TEST_PERM))
                                @if (@$availableRoles)
                                    @foreach ($availableRoles as $k => $singleRole)
                                        <a href="{{ route('dash.perms.setPerms', ['id' => $singleRole->id]) }}"
                                           class="role-item @if($singleRole->id == $role->id) active @endif">
                                            <h5>{{ $singleRole->name }}</h5>
                                            @if ($singleRole->id == $role->id)
                                                <small>{{ __('admin.current_role') }}</small>
                                            @endif
                                        </a>
                                    @endforeach
                                @endif
                            @else
                                @include('components.no_permissions')
                            @endif
                        </div>
                    </div>

                    <!-- محتوى الصلاحيات -->
                    <div class="col-md-8">
                        <div class="permissions-content">
                            <!-- Header -->
                            <div class="permissions-header">
                                <div>
                                    <h4 class="mb-0">{{ __('admin.perms_assign_now') }}</h4>
                                    <small class="text-muted">الدور المحدد: <strong>{{ $role->name }}</strong></small>
                                </div>
                                <button class="assign-all-btn" onclick="assignAllPerms()">
                                    <i class="fas fa-check-double"></i> {{ __('admin.assign_all_perms') }}
                                </button>
                            </div>

                            <!-- Tabs -->
                            <div class="section-tabs">
                                <ul class="nav nav-tabs" id="permissionsTab" role="tablist">
                                    @foreach ($data as $sectionName => $groupNamesArray)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link @if ($loop->first) active @endif"
                                                    id="{{ $sectionName }}-tab"
                                                    data-section="{{ $sectionName }}"
                                                    type="button"
                                                    role="tab">
                                                {{ __("permissions.$sectionName") !== "permissions.$sectionName" ? __("permissions.$sectionName") : $sectionName }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <!-- Permissions Form -->
                            <form id="storePermForm" method="post" action='{{ route("dash.perms.storePerms") }}' enctype="multipart/form-data">
                                {{ csrf_field() }}
                                <input type="hidden" name="role_id" value="{{ @$role->id }}">

                                <div class="permissions-body">
                                    @foreach ($data as $sectionName => $groupNamesArray)
                                        <div class="tab-pane @if ($loop->first) active @endif" id="{{ $sectionName }}" role="tabpanel">
                                            <div class="permission-category">
                                                <h4 class="category-title">{{ __("permissions.$sectionName") !== "permissions.$sectionName" ? __("permissions.$sectionName") : $sectionName }}</h4>

                                                @if (!empty(@$groupNamesArray))
                                                    @foreach ($groupNamesArray as $groupName => $arrayOfPerms)
                                                        <div class="permission-group">
                                                            <h5 class="group-title">{{ __("permissions.$groupName") !== "permissions.$groupName" ? __("permissions.$groupName") : $groupName }}</h5>
                                                            @foreach ($arrayOfPerms as $k => $permRow)
                                                                <div class="permission-item">
                                                                    <span class="permission-name">{{ __("permissions.".$permRow['name']) !== "permissions.".$permRow['name'] ? __("permissions.".$permRow['name']) : $permRow['name'] }}</span>
                                                                    <label class="form-check form-switch form-check-custom form-check-solid">
                                                                        <input class="form-check-input"
                                                                               name="perms[]"
                                                                               type="checkbox"
                                                                               value="{{ $permRow['name'] }}"
                                                                               @php
                                                                                   try {
                                                                                       if($role->name === 'super_admin') {
                                                                                           echo 'checked="checked"';
                                                                                       } else {
                                                                                           if(@$role->hasPermissionTo($permRow['name'])) {
                                                                                               echo 'checked="checked"';
                                                                                           }
                                                                                       }
                                                                                   } catch (\Exception $e) {
                                                                                       // Ignore permission not found error
                                                                                   }
                                                                               @endphp
                                                                        >
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </form>

                            <!-- Footer -->
                            <div class="permissions-footer">
                                <a href="{{ route('dash.roles.index') }}" class="btn btn-danger">
                                    <i class="fas fa-arrow-left"></i> {{ __('admin.back') }}
                                </a>
                                <button class="btn btn-success" onclick="confirmSave()">
                                    <i class="fas fa-save"></i> {{ __('admin.confirm') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function confirmSave() {
                var form = $('#storePermForm');
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

            function assignAllPerms() {
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
                        $('#storePermForm').submit();
                    }
                });
            }

            // تبويبات بدون سلايدر
            $(document).ready(function() {
                $(".nav-link").on("click", function() {
                    var section = $(this).data("section");
                    $(".nav-link").removeClass("active");
                    $(this).addClass("active");
                    $(".tab-pane").removeClass("active");
                    $("#" + section).addClass("active");
                });
                // تمييز الصلاحيات المحددة
                $('.form-check-input:checked').closest('.permission-item').addClass('bg-light');
                $('.form-check-input').change(function() {
                    if ($(this).is(':checked')) {
                        $(this).closest('.permission-item').addClass('bg-light');
                    } else {
                        $(this).closest('.permission-item').removeClass('bg-light');
                    }
                });
            });
        </script>
    @else
        @include('components.no_permissions')
    @endif
@endsection

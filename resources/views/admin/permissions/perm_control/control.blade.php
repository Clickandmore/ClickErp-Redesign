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
        position: relative;
    }

    .permissions-body .tab-pane {
        position: relative;
        min-height: 200px;
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

    .group-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .group-title {
        color: #495057;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .select-all-group {
        background: #17a2b8;
        border-color: #17a2b8;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .select-all-group:hover {
        background: #138496;
        color: white;
    }

    .select-all-group.partial {
        background: #ffc107;
        border-color: #ffc107;
        color: #212529;
    }

    .select-all-group.partial:hover {
        background: #e0a800;
        border-color: #d39e00;
        color: #212529;
    }

    .select-all-group.all-selected {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }

    .select-all-group.all-selected:hover {
        background: #218838;
        border-color: #1e7e34;
        color: white;
    }

    .permission-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f3f4;
        transition: all 0.3s ease;
    }

    .permission-item:last-child {
        border-bottom: none;
    }

    .permission-item:hover {
        background: #f8f9fa;
        border-radius: 6px;
        padding-left: 10px;
        padding-right: 10px;
    }

    .permission-info {
        flex: 1;
    }

    .permission-name {
        font-size: 14px;
        color: #495057;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .permission-description {
        font-size: 12px;
        color: #6c757d;
        margin: 0;
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
        display: none !important;
    }

    .tab-pane.active {
        display: block !important;
    }

    /* إصلاح التبويبات */
    .tab-pane:not(.active) {
        display: none !important;
    }

    .tab-pane.active {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .permission-stats {
        background: #e9ecef;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 12px;
        color: #495057;
        margin-left: 10px;
    }

    .group-stats {
        background: #007bff;
        color: white;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 11px;
        margin-left: 10px;
    }

    .btn-outline-secondary {
        border-color: #6c757d;
        color: #6c757d;
        background: transparent;
    }

    .btn-outline-secondary:hover {
        background: #6c757d;
        border-color: #6c757d;
        color: white;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .text-info {
        color: #17a2b8 !important;
    }

    .text-success {
        color: #28a745 !important;
    }

    .badge {
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 20px;
        margin-left: 8px;
    }

    .badge-primary {
        background-color: #007bff;
        color: white;
    }

    .badge-info {
        background-color: #17a2b8;
        color: white;
    }

    .badge-light {
        background-color: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
    }

    .section-stats {
        display: flex;
        gap: 8px;
    }

    .role-permissions-count {
        margin-left: auto;
    }

    .permission-item.selected {
        background: #e3f2fd !important;
        border-left: 4px solid #2196f3;
    }

    .permission-item.selected .permission-name {
        color: #1976d2;
        font-weight: 600;
    }

    .debug-info {
        background: #f8f9fa;
        border: 2px solid #007bff;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        font-family: monospace;
        font-size: 12px;
        max-height: 300px;
        overflow-y: auto;
    }
    .debug-info h4 {
        color: #007bff;
        margin-bottom: 10px;
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
                                        @php
                                            $rolePermissionsCount = $singleRole->permissions->count();
                                        @endphp
                                        <a href="{{ route('dash.perms.setPerms', ['id' => $singleRole->id]) }}"
                                           class="role-item @if($singleRole->id == $role->id) active @endif">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5>{{ $singleRole->name }}</h5>
                                                    @if ($singleRole->id == $role->id)
                                                        <small>{{ __('admin.current_role') }}</small>
                                                    @endif
                                                </div>
                                                <div class="role-permissions-count">
                                                    <span class="badge badge-light">{{ $rolePermissionsCount }} صلاحيات</span>
                                                </div>
                                            </div>
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
                                    <br>
                                    <small class="text-info">

                                     يمكنك اختيار كل صلاحيات الوحدة مرة واحدة باستخدام زر "اختيار الكل"
                                    </small>
                                    <br>
                                    {{-- <small class="text-success">
                                        <i class="fas fa-chart-bar"></i>
                                        إجمالي الأقسام: <strong>{{ count($organizedPermissions) }}</strong> |
                                        إجمالي المجموعات: <strong>{{ collect($organizedPermissions)->sum(function($section) { return count($section['groups']); }) }}</strong> |
                                        إجمالي الصلاحيات: <strong>{{ collect($organizedPermissions)->sum(function($section) { return collect($section['groups'])->sum(function($group) { return count($group['permissions']); }); }) }}</strong>
                                    </small> --}}
                                </div>
                                <div>
                                    <button class="assign-all-btn" onclick="assignAllPerms()">
                                        {{ __('admin.assign_all_perms') }}
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="clearAllPerms()">
                                        إلغاء الكل
                                    </button>
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="section-tabs">
                                <ul class="nav nav-tabs" id="permissionsTab" role="tablist">
                                    @foreach ($formattedPermissions as $sectionName => $sectionData)
                                        @php
                                            $totalGroups = count($sectionData['groups']);
                                            $totalPermissions = collect($sectionData['groups'])->sum(function($group) {
                                                return count($group['permissions']);
                                            });
                                        @endphp
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link @if ($loop->first) active @endif"
                                                    id="{{ $sectionName }}-tab"
                                                    data-section="{{ $sectionName }}"
                                                    type="button"
                                                    role="tab">
                                                @php
                                                    $translationKey = 'admin.' . $sectionName;
                                                    $translatedTitle = __($translationKey);
                                                    echo $translatedTitle !== $translationKey ? $translatedTitle : ucfirst(str_replace('_', ' ', $sectionName));
                                                @endphp
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
                                    @foreach ($formattedPermissions as $sectionName => $sectionData)
                                        @php
                                            $totalGroups = count($sectionData['groups']);
                                            $totalPermissions = collect($sectionData['groups'])->sum(function($group) {
                                                return count($group['permissions']);
                                            });
                                        @endphp
                                        <div class="tab-pane @if ($loop->first) active @endif" id="{{ $sectionName }}" role="tabpanel">
                                            <div class="permission-category">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="category-title mb-0">
                                                        @php
                                                            $translationKey = 'admin.' . $sectionName;
                                                            $translatedTitle = __($translationKey);
                                                            echo $translatedTitle !== $translationKey ? $translatedTitle : ucfirst(str_replace('_', ' ', $sectionName));
                                                        @endphp
                                                    </h4>
                                                    <div class="section-stats">
                                                        <span class="badge badge-primary">{{ $totalGroups }} مجموعات</span>
                                                        <span class="badge badge-info">{{ $totalPermissions }} صلاحيات</span>
                                                    </div>
                                                </div>

                                                @foreach ($sectionData['groups'] as $groupName => $groupData)
                                                    <div class="permission-group" data-group="{{ $groupName }}">
                                                        <div class="group-header">
                                                            <h5 class="group-title">{{ $groupData['title'] }}</h5>
                                                            <div>
                                                                {{-- <span class="group-stats">{{ count($groupData['permissions']) }} صلاحيات</span> --}}
                                                                <button type="button" class="select-all-group" onclick="selectAllGroup('{{ $groupName }}')">
                                                                     اختيار الكل
                                                                </button>
                                                            </div>
                                                        </div>

                                                        @foreach ($groupData['permissions'] as $permission)
                                                            <div class="permission-item">
                                                                <div class="permission-info">
                                                                    <div class="permission-name">{{ $permission['display_name'] }}</div>
                                                                    <div class="permission-description">{{ $permission['description'] }}</div>
                                                                </div>
                                                                <label class="form-check form-switch form-check-custom form-check-solid">
                                                                    <input class="form-check-input permission-checkbox"
                                                                           name="perms[]"
                                                                           type="checkbox"
                                                                           value="{{ $permission['name'] }}"
                                                                           data-group="{{ $groupName }}"
                                                                           @php
                                                                               try {
                                                                                   if($role->name === 'super_admin') {
                                                                                       echo 'checked="checked"';
                                                                                   } else {
                                                                                       if(@$role->hasPermissionTo($permission['name'])) {
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
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </form>

                            <!-- Footer -->
                            <div class="permissions-footer">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="current-selection-stats">
                                        <small class="text-muted">
                                            <i class="fas fa-check-circle"></i>
                                            الصلاحيات المحددة: <strong id="selectedCount">0</strong> من أصل <strong id="totalCount">0</strong>
                                        </small>
                                    </div>
                                    <div class="selection-percentage">
                                        <small class="text-muted">
                                            النسبة: <strong id="selectionPercentage">0%</strong>
                                        </small>
                                    </div>
                                </div>
                                <div>
                                    <a href="{{ route('dash.roles.index') }}" class="btn btn-danger">
                                       {{ __('admin.back') }}
                                    </a>
                                    <button class="btn btn-success" onclick="confirmSave()">
                                        {{ __('admin.confirm') }}
                                    </button>
                                </div>
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
                        $('.permission-item').addClass('selected');
                        $('#storePermForm').submit();
                    }
                });
            }

            function clearAllPerms() {
                swal.fire({
                    title: 'تأكيد إلغاء الكل',
                    text: 'هل أنت متأكد من إلغاء تحديد جميع الصلاحيات؟',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                    showCloseButton: true,
                }).then((willDelete) => {
                    if (willDelete.isConfirmed) {
                        $('input[type="checkbox"]').prop('checked', false);
                        $('.permission-item').removeClass('selected');

                        // تحديث حالة جميع أزرار المجموعات
                        $('.permission-group').each(function() {
                            var groupName = $(this).data('group');
                            updateGroupButtonState(groupName);
                        });

                        // تحديث الإحصائيات
                        updateSelectionStats();
                    }
                });
            }

            function selectAllGroup(groupName) {
                var checkboxes = $('input[data-group="' + groupName + '"]');
                var allChecked = checkboxes.length === checkboxes.filter(':checked').length;

                if (allChecked) {
                    // إلغاء تحديد الكل
                    checkboxes.prop('checked', false);
                    checkboxes.closest('.permission-item').removeClass('selected');
                } else {
                    // تحديد الكل
                    checkboxes.prop('checked', true);
                    checkboxes.closest('.permission-item').addClass('selected');
                }

                // تحديث حالة الأزرار
                updateGroupButtonState(groupName);

                // تحديث الإحصائيات
                updateSelectionStats();
            }

            function updateGroupButtonState(groupName) {
                var checkboxes = $('input[data-group="' + groupName + '"]');
                var checkedCount = checkboxes.filter(':checked').length;
                var totalCount = checkboxes.length;
                var button = $('button[onclick="selectAllGroup(\'' + groupName + '\')"]');

                // إزالة جميع الألوان
                button.removeClass('partial all-selected');

                if (checkedCount === 0) {
                    button.html('<i class="fas fa-check-square"></i> اختيار الكل');
                } else if (checkedCount === totalCount) {
                    button.html('<i class="fas fa-square"></i> إلغاء الكل');
                    button.addClass('all-selected');
                } else {
                    button.html('<i class="fas fa-check-square"></i> اختيار الكل (' + checkedCount + '/' + totalCount + ')');
                    button.addClass('partial');
                }
            }

            // تبويبات بدون سلايدر
            $(document).ready(function() {
                                // إضافة معالج النقر للتبويبات
                $(".nav-link").on("click", function(e) {
                    e.preventDefault();
                    var section = $(this).data("section");
                    console.log("تم النقر على التبويب:", section);

                    // إزالة الفئة النشطة من جميع التبويبات
                    $(".nav-link").removeClass("active");
                    // إضافة الفئة النشطة للتبويب المحدد
                    $(this).addClass("active");

                                        // إخفاء جميع المحتويات
                    $(".tab-pane").removeClass("active").hide();

                    // إظهار المحتوى المحدد - تحديد العنصر الصحيح
                    var targetPane = $(".tab-pane#" + section);
                    if (targetPane.length === 0) {
                        targetPane = $("#" + section + ".tab-pane");
                    }
                    targetPane.addClass("active").show();

                    // إضافة تأخير صغير للتأكد من إظهار المحتوى
                    setTimeout(function() {
                        targetPane.show();
                        console.log("تم تفعيل المحتوى:", section);
                        console.log("عدد الصلاحيات في التبويب:", targetPane.find('.permission-item').length);
                        console.log("HTML للتبويب:", targetPane.html().substring(0, 500) + "...");
                        console.log("عدد المجموعات في التبويب:", targetPane.find('.permission-group').length);
                        console.log("المجموعات الموجودة:", targetPane.find('.permission-group').map(function() { return $(this).data('group'); }).get());
                    }, 100);
                });

                // تمييز الصلاحيات المحددة
                $('.form-check-input:checked').closest('.permission-item').addClass('selected');

                $('.form-check-input').change(function() {
                    if ($(this).is(':checked')) {
                        $(this).closest('.permission-item').addClass('selected');
                    } else {
                        $(this).closest('.permission-item').removeClass('selected');
                    }

                    // تحديث حالة أزرار المجموعات
                    var groupName = $(this).data('group');
                    updateGroupButtonState(groupName);

                    // تحديث الإحصائيات
                    updateSelectionStats();
                });

                // تحديث حالة جميع أزرار المجموعات عند التحميل
                $('.permission-group').each(function() {
                    var groupName = $(this).data('group');
                    updateGroupButtonState(groupName);
                });

                // تحديث الإحصائيات عند التحميل
                updateSelectionStats();

                // إظهار التبويب الأول افتراضياً
                var firstTab = $(".nav-link.active");
                var firstSection = firstTab.data("section");
                console.log("تم تحميل الصفحة، التبويب النشط:", firstSection);

                // التأكد من إظهار المحتوى الأول
                if (firstSection) {
                    $("#" + firstSection).addClass("active").show();
                }
            });

            function updateSelectionStats() {
                var totalCheckboxes = $('input[type="checkbox"]').length;
                var checkedCheckboxes = $('input[type="checkbox"]:checked').length;
                var percentage = totalCheckboxes > 0 ? Math.round((checkedCheckboxes / totalCheckboxes) * 100) : 0;

                $('#totalCount').text(totalCheckboxes);
                $('#selectedCount').text(checkedCheckboxes);
                $('#selectionPercentage').text(percentage + '%');
            }
        </script>
    @else
        @include('components.no_permissions')
    @endif
@endsection

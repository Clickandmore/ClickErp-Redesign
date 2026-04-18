<?php

namespace App\Http\Controllers\Permissions;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permissions_view,admin', ['only' => ['setPerms']]);
        $this->middleware('permission:permissions_assign,admin', ['only' => ['storePerms']]);
        $this->middleware('permission:permissions_create,admin', ['only' => ['store']]);
        $this->middleware('permission:permissions_delete,admin', ['only' => ['delete']]);
    }

    public function setPerms($id){
        $role = $this->findAdminRole($id);
        if (!$role) {
            return redirect(route('dash.roles.index'))->with('error', 'لا يمكن إدارة صلاحيات دور غير تابع لحارس الإدارة.');
        }

        $data = Permission::query()
            ->where('guard_name', 'admin')
            ->orderBy('section_name')
            ->orderBy('group_name')
            ->orderBy('name')
            ->get();

        // تنظيم الصلاحيات بطريقة جديدة
        $organizedPermissions = $this->sortOrganizedPermissions($this->organizePermissions($data));

        // تحويل البيانات إلى الشكل المطلوب لملف العرض
        $formattedPermissions = [];
        foreach ($organizedPermissions as $sectionName => $sectionGroups) {
            $formattedPermissions[$sectionName] = [
                'groups' => []
            ];

            foreach ($sectionGroups as $groupName => $permissions) {
                $formattedPermissions[$sectionName]['groups'][$groupName] = [
                    'title' => $this->getGroupTitle($groupName),
                    'permissions' => []
                ];

                foreach ($permissions as $permission) {
                    $formattedPermissions[$sectionName]['groups'][$groupName]['permissions'][] = [
                        'name' => $permission->name,
                        'display_name' => $this->getPermissionDisplayName($permission),
                        'description' => $this->getPermissionDescription($permission)
                    ];
                }
            }
        }

        $availableRoles = $this->adminRolesQuery()
            ->withCount(['permissions', 'users'])
            ->orderByRaw("CASE WHEN name IN ('Administrator', 'super_admin') THEN 0 ELSE 1 END")
            ->orderByDesc('users_count')
            ->orderBy('name')
            ->get();

        $adminsByRole = $this->getAdminsCountByRole();
        $availableRoles->each(function (Role $availableRole) use ($adminsByRole) {
            $availableRole->setAttribute('linked_admins_count', max(
                (int) ($availableRole->users_count ?? 0),
                (int) ($adminsByRole[$availableRole->id] ?? 0)
            ));
            $availableRole->setAttribute('is_protected', $this->isProtectedRole($availableRole));
        });

        $assignedPermissionNames = $role->permissions()->pluck('name')->all();

        return view('admin.permissions.perm_control.control', compact(
            'role',
            'formattedPermissions',
            'availableRoles',
            'assignedPermissionNames'
        ));
    }

    /**
     * تنظيم الصلاحيات حسب الوحدات مع إمكانية اختيار كل الوحدة
     */
    private function organizePermissions($permissions)
    {
        $organized = [];

        foreach ($permissions as $permission) {
            ['section' => $section, 'group' => $group] = $this->resolvePermissionPlacement($permission->name);

            if (!isset($organized[$section])) {
                $organized[$section] = [];
            }
            if (!isset($organized[$section][$group])) {
                $organized[$section][$group] = [];
            }

            $organized[$section][$group][] = $permission;
        }

        return $organized;
    }

    private function sortOrganizedPermissions(array $organized): array
    {
        $sectionOrder = array_flip([
            'dashboard',
            'users_management',
            'clients',
            'cv_management',
            'orders',
            'recruitment_contracts',
            'rent_contracts',
            'shelter_management',
            'travel_management',
            'complaints',
            'additional_services',
            'maids_settings',
            'content_management',
            'settings',
            'modules',
            'other',
        ]);

        uksort($organized, function (string $first, string $second) use ($sectionOrder) {
            $firstOrder = $sectionOrder[$first] ?? 999;
            $secondOrder = $sectionOrder[$second] ?? 999;

            if ($firstOrder !== $secondOrder) {
                return $firstOrder <=> $secondOrder;
            }

            return strnatcasecmp($this->getSectionTitle($first), $this->getSectionTitle($second));
        });

        foreach ($organized as &$groups) {
            uksort($groups, function (string $first, string $second) {
                return strnatcasecmp($this->getGroupTitle($first), $this->getGroupTitle($second));
            });

            foreach ($groups as &$permissions) {
                usort($permissions, function ($first, $second) {
                    $firstPriority = $this->getPermissionSortPriority($first->name);
                    $secondPriority = $this->getPermissionSortPriority($second->name);

                    if ($firstPriority !== $secondPriority) {
                        return $firstPriority <=> $secondPriority;
                    }

                    return strnatcasecmp(
                        $this->getPermissionDisplayName($first),
                        $this->getPermissionDisplayName($second)
                    );
                });
            }
            unset($permissions);
        }
        unset($groups);

        return $organized;
    }

    private function getPermissionSortPriority(string $permissionName): int
    {
        $exactOrder = [
            'dashboard_view' => 10,
            'dashboard_view_statistics' => 20,
            'dashboard_analytics' => 30,
            'dashboard_export' => 40,
            'dashboard_manage' => 50,
        ];

        if (isset($exactOrder[$permissionName])) {
            return $exactOrder[$permissionName];
        }

        $suffixes = [
            'view' => 100,
            'show' => 110,
            'create' => 200,
            'edit' => 300,
            'update' => 310,
            'delete' => 400,
            'control' => 500,
            'manage' => 510,
            'assign' => 520,
            'approve' => 600,
            'reject' => 610,
            'change_status' => 700,
            'toggle_status' => 710,
            'toggle_pin' => 720,
            'export' => 800,
            'export_pdf' => 810,
            'export_excel' => 820,
            'import' => 830,
            'print' => 840,
        ];

        foreach ($suffixes as $suffix => $priority) {
            if ($permissionName === $suffix || str_ends_with($permissionName, '_' . $suffix)) {
                return $priority;
            }
        }

        return 999;
    }

    /**
     * الحصول على عنوان القسم
     */
    private function getSectionTitle($sectionName)
    {
        $titles = [
            'dashboard' => 'لوحة التحكم',
            'clients' => 'إدارة العملاء',
            'cv_management' => 'إدارة السير الذاتية',
            'orders' => 'الطلبات',
            'recruitment_contracts' => 'عقود الاستقدام ',
            'rent_contracts' => 'عقود الإيجار',
            'shelter_management' => 'إدارة الإيواء',
            'travel_management' => 'الرحلات',
            'contracts' => 'العقود',
            'complaints' => 'الشكاوى',
            'additional_services' => 'خدمات إضافية',
            'settings' => 'الإعدادات',
            'maids_settings' => 'إعدادات السير الذاتية',
            'users_management' => 'إدارة المستخدمين',
            'content_management' => 'إدارة المحتوى',
            'organization_management' => 'إدارة المنظمة',
            'customer_service' => 'خدمة العملاء',
            'reports' => 'التقارير',
            'statistics' => 'الإحصائيات',
            'process_management' => 'إدارة العمليات',
            'communications' => 'الاتصالات',
            'system_management' => 'إدارة النظام',
            'order_management' => 'إدارة الطلبات',
            'contract_management' => 'إدارة العقود',
            'complaint_management' => 'إدارة الشكاوى',
            'service_management' => 'إدارة الخدمات',
            'user_management' => 'إدارة المستخدمين',
            'role_management' => 'إدارة الأدوار',
            'permission_management' => 'إدارة الصلاحيات',
            'setting_management' => 'إدارة الإعدادات',
            'activity_management' => 'إدارة النشاطات',
            'backup_management' => 'إدارة النسخ الاحتياطية',
            'modules' => 'الأنظمة',
        ];

        return $titles[$sectionName] ?? $sectionName;
    }

    /**
     * الحصول على عنوان المجموعة
     */
    private function getGroupTitle($groupName)
    {
        $titles = [
            'dashboard' => 'لوحة التحكم',
            'clients' => 'العملاء',
            'maids' => 'السير الذاتية',
            'social_status' => 'الحالة الاجتماعية',
            'orders' => 'الطلبات',
            'return_request' => 'طلبات الاسترجاع',
            'rec_contract' => 'عقود التوظيف',
            'rec_contracts' => 'عقود الاستقدام',
            'cancel_contract' => 'عقود الإلغاء',
            'rent_contract' => 'عقود الإيجار',
            'shelter_contract' => 'عقود الإيواء',
            'shelter_workers' => 'عمال الإيواء',
            'shelter_places' => 'أماكن الإيواء',
            'travel' => 'الرحلات',
            'rec_offices' => 'مكاتب التوظيف',
            'complaint' => 'الشكاوى',
            'complaint_type' => 'أنواع الشكاوى',
            'sponsor_transfer' => 'تحويل الكفالة',
            'settings' => 'الإعدادات',
            'activity_log' => 'سجل النشاط',
            'job_titles' => 'المسميات الوظيفية',
            'packages' => 'الباقات',
            'nationalities' => 'الجنسيات',
            'religions' => 'الأديان',
            'skills' => 'المهارات',
            'countries' => 'الدول',
            'users' => 'المستخدمين',
            'roles' => 'الأدوار',
            'permissions' => 'الصلاحيات',
            'statistics' => 'الإحصائيات',
            'notifications' => 'الإشعارات',
            'sliders' => 'الشرائح',
            'faq' => 'الأسئلة الشائعة',
            'about_us' => 'من نحن',
            'contact' => 'اتصل بنا',
            'steps' => 'الخطوات',
            'services' => 'الخدمات',
            'content' => 'المحتوى',
            'organization' => 'المنظمة',
            'customer_service' => 'خدمة العملاء',
            'reports' => 'التقارير',
            'process' => 'العمليات',
            'communications' => 'الاتصالات',
            'system' => 'النظام',
            'backup' => 'النسخ الاحتياطية',
            'contract_settings' => 'إعدادات العقد',
            'activity' => 'النشاطات',
            'job_title' => 'المسميات الوظيفية',
            'package' => 'الباقات',
            'nationality' => 'الجنسيات',
            'religion' => 'الأديان',
            'skill' => 'المهارات',
            'country' => 'الدول',
            'user' => 'المستخدمين',
            'role' => 'الأدوار',
            'permission' => 'الصلاحيات',
            'statistic' => 'الإحصائيات',
            'notification' => 'الإشعارات',
            'slider' => 'الشرائح',
            'about' => 'من نحن',
            'step' => 'الخطوات',
            'service' => 'الخدمات',
            'report' => 'التقارير',
            'crm' => 'CRM',
            'hr' => 'الموارد البشرية',
            'finance' => 'المالية',
        ];

        return $titles[$groupName] ?? $groupName;
    }

    /**
     * الحصول على اسم عرض الصلاحية
     */
    private function getPermissionDisplayName($permission)
    {
        // إذا كان هناك display_name محفوظ في قاعدة البيانات، استخدمه
        if (!empty($permission->display_name)) {
            return $permission->display_name;
        }

        // إذا لم يكن هناك display_name، استخدم الترجمات من ملفات الترجمة
        $permissionName = $permission->name;

        // محاولة الحصول على الترجمة من ملف permissions
        $translation = __('permissions.' . $permissionName);

        // إذا كانت الترجمة موجودة ولا تساوي اسم الصلاحية، استخدمها
        if ($translation !== 'permissions.' . $permissionName) {
            return $translation;
        }

        // إذا لم توجد ترجمة، استخدم القيم الافتراضية
        $displayNames = [
            'dashboard_view' => 'عرض لوحة التحكم',
            'dashboard_view_statistics' => 'عرض إحصائيات لوحة التحكم',
            'dashboard_analytics' => 'إحصائيات لوحة التحكم',
            'dashboard_export' => 'تصدير بيانات لوحة التحكم',
            'dashboard_manage' => 'إدارة لوحة التحكم',
            'dashboard_clients_count' => 'عرض عدد العملاء',
            'dashboard_contacts_count' => 'عرض عدد رسائل التواصل',
            'dashboard_current_orders_count' => 'عرض عدد الطلبات الحالية',
            'dashboard_cv_count' => 'عرض عدد السير الذاتية',
            'dashboard_employees_count' => 'عرض عدد الموظفين',
            'dashboard_completed_cv_count' => 'عرض عدد السير الذاتية المكتملة',
            'dashboard_orders_statistics' => 'عرض إحصائيات الطلبات',
            'dashboard_top_customer_services' => 'عرض أفضل خدمة عملاء',
            'dashboard_orders_types' => 'عرض أنواع الطلبات',
            'set_perms' => 'تعيين الصلاحيات',
            'developers_perms' => 'صلاحيات المطور',
            'full_contract' => 'العقد الكامل',
            'contract_settings' => 'إعدادات العقد',
            'complaints-read' => 'قراءة الشكاوى',
        ];

        if (isset($displayNames[$permissionName])) {
            return $displayNames[$permissionName];
        }

        return $this->buildPermissionDisplayName($permissionName);
    }

    /**
     * الحصول على وصف الصلاحية
     */
    private function getPermissionDescription($permission)
    {
        // إذا كان هناك description محفوظ في قاعدة البيانات، استخدمه
        if (!empty($permission->description)) {
            return $permission->description;
        }

        // إذا لم يكن هناك description، استخدم القيم الافتراضية
        $permissionName = $permission->name;

        $descriptions = [
            'dashboard_view' => 'إمكانية عرض لوحة التحكم الرئيسية',
            'dashboard_view_statistics' => 'إمكانية عرض إحصائيات لوحة التحكم',
            'dashboard_analytics' => 'إمكانية عرض الإحصائيات والتحليلات',
            'dashboard_export' => 'إمكانية تصدير بيانات لوحة التحكم',
            'dashboard_manage' => 'إمكانية إدارة جميع مكونات لوحة التحكم',
            'dashboard_clients_count' => 'إمكانية عرض عدد العملاء في لوحة التحكم',
            'dashboard_contacts_count' => 'إمكانية عرض عدد رسائل التواصل في لوحة التحكم',
            'dashboard_current_orders_count' => 'إمكانية عرض عدد الطلبات الحالية في لوحة التحكم',
            'dashboard_cv_count' => 'إمكانية عرض عدد السير الذاتية في لوحة التحكم',
            'dashboard_employees_count' => 'إمكانية عرض عدد الموظفين في لوحة التحكم',
            'dashboard_completed_cv_count' => 'إمكانية عرض عدد السير الذاتية المكتملة في لوحة التحكم',
            'dashboard_orders_statistics' => 'إمكانية عرض إحصائيات الطلبات في لوحة التحكم',
            'dashboard_top_customer_services' => 'إمكانية عرض أفضل خدمة عملاء في لوحة التحكم',
            'dashboard_orders_types' => 'إمكانية عرض أنواع الطلبات في لوحة التحكم',
        ];

        if (isset($descriptions[$permissionName])) {
            return $descriptions[$permissionName];
        }

        return 'يمنح الوصول إلى ' . $this->getPermissionDisplayName($permission);
    }

    private function resolvePermissionPlacement(string $permissionName): array
    {
        $normalizedName = str_replace('-', '_', $permissionName);

        $map = [
            'dashboard_' => ['section' => 'dashboard', 'group' => 'dashboard'],
            'clients_' => ['section' => 'clients', 'group' => 'clients'],
            'maids_' => ['section' => 'cv_management', 'group' => 'maids'],
            'cv_' => ['section' => 'cv_management', 'group' => 'maids'],
            'orders_' => ['section' => 'orders', 'group' => 'orders'],
            'return_request_' => ['section' => 'orders', 'group' => 'return_request'],
            'rec_contract_' => ['section' => 'recruitment_contracts', 'group' => 'rec_contracts'],
            'rec_contracts_' => ['section' => 'recruitment_contracts', 'group' => 'rec_contracts'],
            'cancel_contract_' => ['section' => 'recruitment_contracts', 'group' => 'cancel_contract'],
            'rent_contract_' => ['section' => 'rent_contracts', 'group' => 'rent_contract'],
            'shelter_contract_' => ['section' => 'shelter_management', 'group' => 'shelter_contract'],
            'shelter_workers_' => ['section' => 'shelter_management', 'group' => 'shelter_workers'],
            'shelter_places_' => ['section' => 'shelter_management', 'group' => 'shelter_places'],
            'travel_' => ['section' => 'travel_management', 'group' => 'travel'],
            'rec_offices_' => ['section' => 'maids_settings', 'group' => 'rec_offices'],
            'recruitment_offices_' => ['section' => 'maids_settings', 'group' => 'rec_offices'],
            'complaint_type_' => ['section' => 'complaints', 'group' => 'complaint_type'],
            'complaint_' => ['section' => 'complaints', 'group' => 'complaint'],
            'complaints_' => ['section' => 'complaints', 'group' => 'complaint'],
            'sponsor_transfer_' => ['section' => 'additional_services', 'group' => 'sponsor_transfer'],
            'settings_' => ['section' => 'settings', 'group' => 'settings'],
            'activity_' => ['section' => 'settings', 'group' => 'activity_log'],
            'job_titles_' => ['section' => 'maids_settings', 'group' => 'job_titles'],
            'social_status_' => ['section' => 'maids_settings', 'group' => 'social_status'],
            'packages_' => ['section' => 'maids_settings', 'group' => 'packages'],
            'nationalities_' => ['section' => 'maids_settings', 'group' => 'nationalities'],
            'religions_' => ['section' => 'maids_settings', 'group' => 'religions'],
            'relegions_' => ['section' => 'maids_settings', 'group' => 'religions'],
            'skills_' => ['section' => 'maids_settings', 'group' => 'skills'],
            'countries_' => ['section' => 'maids_settings', 'group' => 'countries'],
            'users_' => ['section' => 'users_management', 'group' => 'users'],
            'roles_' => ['section' => 'users_management', 'group' => 'roles'],
            'permissions_' => ['section' => 'users_management', 'group' => 'permissions'],
            'statistics_' => ['section' => 'content_management', 'group' => 'statistics'],
            'stat_' => ['section' => 'content_management', 'group' => 'statistics'],
            'notifications_' => ['section' => 'content_management', 'group' => 'notifications'],
            'sliders_' => ['section' => 'content_management', 'group' => 'sliders'],
            'faq_' => ['section' => 'content_management', 'group' => 'faq'],
            'about_us_' => ['section' => 'content_management', 'group' => 'about_us'],
            'contact_' => ['section' => 'content_management', 'group' => 'contact'],
            'steps_' => ['section' => 'content_management', 'group' => 'steps'],
            'services_' => ['section' => 'content_management', 'group' => 'services'],
            'crm_' => ['section' => 'modules', 'group' => 'crm'],
            'hr_' => ['section' => 'modules', 'group' => 'hr'],
            'finance_' => ['section' => 'modules', 'group' => 'finance'],
        ];

        foreach ($map as $prefix => $placement) {
            if (str_starts_with($normalizedName, $prefix)) {
                return $placement;
            }
        }

        if (in_array($normalizedName, ['contract_settings', 'full_contract', 'set_perms', 'developers_perms', 'test_perm'], true)) {
            return ['section' => 'settings', 'group' => 'contract_settings'];
        }

        return ['section' => 'other', 'group' => 'other'];
    }

    private function buildPermissionDisplayName(string $permissionName): string
    {
        $specialNames = [
            'set_perms' => 'تعيين الصلاحيات',
            'developers_perms' => 'صلاحيات المطور',
            'full_contract' => 'العقد الكامل',
            'contract_settings' => 'إعدادات العقد',
            'complaints-read' => 'قراءة الشكاوى',
        ];

        if (isset($specialNames[$permissionName])) {
            return $specialNames[$permissionName];
        }

        ['group' => $group] = $this->resolvePermissionPlacement($permissionName);
        $resourceTitle = $this->getGroupTitle($group);
        $actionTitle = $this->getPermissionActionTitle($permissionName);

        if ($actionTitle && $resourceTitle !== 'other') {
            return trim($actionTitle . ' ' . $resourceTitle);
        }

        return str_replace('_', ' ', str_replace('-', ' ', $permissionName));
    }

    private function getPermissionActionTitle(string $permissionName): ?string
    {
        $normalizedName = str_replace('-', '_', $permissionName);

        $suffixes = [
            'change_status' => 'تغيير حالة',
            'toggle_status' => 'تفعيل أو تعطيل',
            'export_excel' => 'تصدير Excel',
            'export_pdf' => 'تصدير PDF',
            'toggle_pin' => 'تثبيت',
            'download' => 'تنزيل',
            'upload' => 'رفع',
            'convert' => 'تحويل',
            'analytics' => 'تحليلات',
            'reports' => 'تقارير',
            'approve' => 'اعتماد',
            'reject' => 'رفض',
            'assign' => 'تعيين',
            'manage' => 'إدارة',
            'control' => 'إدارة',
            'create' => 'إضافة',
            'edit' => 'تعديل',
            'update' => 'تحديث',
            'delete' => 'حذف',
            'export' => 'تصدير',
            'import' => 'استيراد',
            'print' => 'طباعة',
            'view' => 'عرض',
            'show' => 'عرض',
            'read' => 'قراءة',
            'pin' => 'تثبيت',
            'unpin' => 'إلغاء تثبيت',
        ];

        foreach ($suffixes as $suffix => $title) {
            if ($normalizedName === $suffix || str_ends_with($normalizedName, '_' . $suffix)) {
                return $title;
            }
        }

        return null;
    }

    public function storePerms(Request $request){
        if(isset($request->role_id)){
            $role = $this->findAdminRole($request->role_id);
            if (!$role) {
                return redirect(route('dash.roles.index'))->with('error', 'لا يمكن تحديث صلاحيات دور غير إداري.');
            }

            $requestedPermissions = collect($request->input('perms', []))
                ->filter(fn ($permissionName) => is_string($permissionName) && $permissionName !== '')
                ->unique()
                ->values();

            $permissions = $requestedPermissions->isEmpty()
                ? collect()
                : Permission::query()
                    ->where('guard_name', 'admin')
                    ->whereIn('name', $requestedPermissions)
                    ->get();

            $role->syncPermissions($permissions);
            $this->forgetPermissionCache();

            $missingPermissions = $requestedPermissions->diff($permissions->pluck('name'));
            if ($missingPermissions->isNotEmpty()) {
                return redirect()->back()->with(
                    'warning',
                    'تم تحديث الصلاحيات وتجاهل العناصر غير المتاحة: ' . $missingPermissions->implode(' , ')
                );
            }

            return redirect()->back()->with('msg',__('admin.success'));
        }
        return redirect(route('dash.roles.index'));
    }

    public function index($id = null){


        $data = Permission::withoutGlobalScopes()
            ->where('guard_name', 'admin')
            ->orderByDesc('is_active')
            ->orderBy('section_name')
            ->orderBy('group_name')
            ->orderBy('name')
            ->get();
        $editData = null;
        if($id != null){
            $editData = Permission::withoutGlobalScopes()
                ->where('guard_name', 'admin')
                ->find($id);
        }
        return view('admin.permissions.perm_keys.index',compact('data','editData'));
    }

    public function store(Request $request){
        $id = null;
        if(isset($request->edit_id)){
            $id = $request->edit_id;
        }
        $rules = [
            'name' => [
                'required',
                Rule::unique('permissions', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'admin'))
                    ->ignore($id),
            ],
        ];
        $validator = Validator::make($request->all(),$rules);
        if($validator->fails())
            return redirect()->back()->with('error',__('admin.roles_name_validation'));

        $existingPermission = $id
            ? Permission::withoutGlobalScopes()->where('guard_name', 'admin')->find($id)
            : null;

        $perm = Permission::withoutGlobalScopes()->updateOrCreate([
                'id' => $id
            ],[
                'name' => $request->name,
                'group_name' => $request->group_name,
                'section_name' => $request->section_name,
                'guard_name' => 'admin',
                'is_active' => $existingPermission?->is_active ?? true,
        ]);

        $this->forgetPermissionCache();

        return redirect(route('dash.developers.perms.index'))->with('msg',__('admin.success'));
    }

    public function delete(Request $request){
        $perm = Permission::withoutGlobalScopes()
            ->where('guard_name', 'admin')
            ->find($request->id);
        if($perm){
            $perm->delete();
            $this->forgetPermissionCache();
        }
        return redirect()->back()->with('msg', __('admin.success'));
    }

    public function toggleStatus($id)
    {
        $permission = Permission::withoutGlobalScopes()
            ->where('guard_name', 'admin')
            ->find($id);

        if (!$permission) {
            return redirect()->back()->with('error', 'الصلاحية المطلوبة غير موجودة.');
        }

        $permission->is_active = ! (bool) $permission->is_active;
        $permission->save();
        $this->forgetPermissionCache();

        return redirect()->back()->with('msg', __('admin.success'));
    }

    private function adminRolesQuery()
    {
        return Role::query()->where('guard_name', 'admin');
    }

    private function findAdminRole($id): ?Role
    {
        return $this->adminRolesQuery()->find($id);
    }

    private function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, ['Administrator', 'super_admin'], true);
    }

    private function getAdminsCountByRole(): Collection
    {
        return collect(\DB::table('admins')
            ->select('role_id', \DB::raw('count(*) as total'))
            ->whereNotNull('role_id')
            ->groupBy('role_id')
            ->pluck('total', 'role_id'));
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

}

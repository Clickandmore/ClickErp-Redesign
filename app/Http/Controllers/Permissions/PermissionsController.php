<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\admin\Controller;
use App\Models\Branch;
use App\Models\Permission;
use App\Support\AdminPermissionNavigation;
use App\Support\PermissionLabelDictionary;
use App\Support\BranchScopedPermissions;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Role;
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

    public function setPerms(Request $request, $id){
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

        // إخفاء صلاحيات نظام الساعات عندما تكون الوحدة غير مفعلة
        if (!SystemModuleManager::isEnabled('hourly_rental')) {
            $data = $data->reject(fn ($permission) => $this->isHourlyPermission($permission->name))->values();
        }

        // تنظيم الصلاحيات بطريقة جديدة
        $organizedPermissions = $this->sortOrganizedPermissions($this->organizePermissions($data));

        $permissionMatrixSections = $this->buildPermissionMatrixSections($organizedPermissions);
        $permissionMatrixLanes = $this->buildPermissionMatrixLanes($permissionMatrixSections);

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

        $branchesEnabled = SystemModuleManager::isEnabled('branches');
        $branches = $branchesEnabled
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
            : collect();
        $selectedBranchId = $branchesEnabled ? $this->resolveSelectedBranchId($request, $branches) : null;
        $branchPermissionsConfigured = false;

        if ($selectedBranchId) {
            $branchPermissionNames = BranchScopedPermissions::permissionNamesForRoleBranch(
                (int) $role->id,
                (int) $selectedBranchId
            );
            $branchPermissionsConfigured = BranchScopedPermissions::hasConfiguredBranchPermissions(
                collect([(int) $role->id]),
                (int) $selectedBranchId
            );
            $assignedPermissionNames = $branchPermissionsConfigured
                ? $branchPermissionNames->all()
                : $role->permissions()->pluck('name')->all();
        } else {
            $assignedPermissionNames = $role->permissions()->pluck('name')->all();
        }

        return view('admin.permissions.perm_control.control', compact(
            'role',
            'permissionMatrixSections',
            'permissionMatrixLanes',
            'availableRoles',
            'assignedPermissionNames',
            'branchesEnabled',
            'branches',
            'selectedBranchId',
            'branchPermissionsConfigured'
        ));
    }

    /**
     * تنظيم الصلاحيات حسب الوحدات مع إمكانية اختيار كل الوحدة
     */
    private function organizePermissions($permissions)
    {
        $organized = [];

        foreach ($permissions as $permission) {
            $section = $this->normalizePermissionPlacementKey($permission->section_name ?? null);
            $group = $this->normalizePermissionPlacementKey($permission->group_name ?? null);

            if (!$section || !$group) {
                ['section' => $section, 'group' => $group] = $this->resolvePermissionPlacement($permission->name);
            }

            ['section' => $section, 'group' => $group] = $this->overridePermissionPlacement(
                $permission->name,
                $section,
                $group
            );

            // طبّق الخريطة المركزية دائماً، حتى على البيانات القديمة التي تحمل
            // section_name/group_name متعارضين أو أسماء مجموعات تاريخية.
            ['section' => $section, 'group' => $group] = AdminPermissionNavigation::placementForPermission(
                $permission->name,
                $section,
                $group
            );

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
        $sectionOrder = array_flip(AdminPermissionNavigation::sectionOrder());
        $groupOrder = array_flip(AdminPermissionNavigation::groupOrder());

        uksort($organized, function (string $first, string $second) use ($sectionOrder) {
            $firstOrder = $sectionOrder[$first] ?? 999;
            $secondOrder = $sectionOrder[$second] ?? 999;

            if ($firstOrder !== $secondOrder) {
                return $firstOrder <=> $secondOrder;
            }

            return strnatcasecmp($this->getSectionTitle($first), $this->getSectionTitle($second));
        });

        foreach ($organized as &$groups) {
            uksort($groups, function (string $first, string $second) use ($groupOrder) {
                $firstOrder = $groupOrder[$first] ?? 999;
                $secondOrder = $groupOrder[$second] ?? 999;

                if ($firstOrder !== $secondOrder) {
                    return $firstOrder <=> $secondOrder;
                }

                return strnatcasecmp($this->getGroupTitle($first), $this->getGroupTitle($second));
            });

            foreach ($groups as &$permissions) {
                usort($permissions, function ($first, $second) {
                    $firstResource = $this->getPermissionResourceSortTitle($first->name);
                    $secondResource = $this->getPermissionResourceSortTitle($second->name);

                    if ($firstResource !== $secondResource) {
                        return strnatcasecmp($firstResource, $secondResource);
                    }

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
        $normalizedName = str_replace(['.', '-'], '_', $permissionName);

        $exactOrder = [
            'dashboard_view' => 10,
            'dashboard_view_statistics' => 20,
            'dashboard_analytics' => 30,
            'dashboard_export' => 40,
            'dashboard_manage' => 50,
            'dashboard_recruitment_orders_count' => 60,
            'dashboard_recruitment_contracts_count' => 70,
            'dashboard_clients_count' => 80,
            'dashboard_cv_count' => 90,
            'dashboard_rental_contracts_count' => 100,
            'dashboard_rental_orders_count' => 110,
            'dashboard_sponsorship_transfers_count' => 120,
            'dashboard_sponsorship_transfer_requests_count' => 130,
            'dashboard_shelter_count' => 140,
            'dashboard_operations_overview' => 150,
            'dashboard_contracts_overview' => 160,
            'dashboard_nationalities_statistics' => 170,
            'dashboard_offices_statistics' => 180,
            'dashboard_recent_activities' => 190,
            'dashboard_calendar_events' => 200,
            'dashboard_employee_performance' => 210,
        ];

        if (isset($exactOrder[$normalizedName])) {
            return $exactOrder[$normalizedName];
        }

        $suffixes = [
            'view' => 100,
            'show' => 110,
            'create' => 200,
            'edit' => 300,
            'update' => 310,
            'response' => 320,
            'delete' => 400,
            'control' => 500,
            'manage' => 510,
            'assign' => 520,
            'confirm' => 530,
            'approve' => 600,
            'reject' => 610,
            'activate' => 620,
            'deactivate' => 630,
            'change_status' => 700,
            'toggle_status' => 710,
            'toggle_pin' => 720,
            'export' => 800,
            'export_pdf' => 810,
            'export_excel' => 820,
            'import' => 830,
            'print' => 840,
            'download' => 850,
            'upload' => 860,
            'reports' => 870,
            'analytics' => 880,
        ];

        foreach ($suffixes as $suffix => $priority) {
            if ($normalizedName === $suffix || str_ends_with($normalizedName, '_' . $suffix)) {
                return $priority;
            }
        }

        return 999;
    }

    private function normalizePermissionPlacementKey(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $this->normalizeLegacyPermissionPlacementKey(str_replace('-', '_', $value));
    }

    private function normalizeLegacyPermissionPlacementKey(string $value): string
    {
        return match ($value) {
            'shelter_contracts' => 'shelter_contract',
            default => $value,
        };
    }

    /**
     * الحصول على عنوان القسم
     */
    private function getSectionTitle($sectionName)
    {
        return AdminPermissionNavigation::sectionTitle((string) $sectionName);
    }

    /**
     * الحصول على عنوان المجموعة
     */
    private function getGroupTitle($groupName)
    {
        return AdminPermissionNavigation::groupTitle((string) $groupName);
    }

    /**
     * الحصول على اسم عرض الصلاحية
     */
    private function getPermissionDisplayName($permission)
    {
        if (str_starts_with($permission->name, 'hourly_')) {
            return PermissionLabelDictionary::label($permission->name);
        }

        // استخدم المسمى المحفوظ ما دام عربياً بالكامل. الصفوف القديمة تحمل نصاً
        // نصفه إنجليزي («إضافة الحساب statement») فنتجاوزها إلى القاموس.
        if (!empty($permission->display_name)
            && !PermissionLabelDictionary::needsTranslation($permission->display_name)) {
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

        $adminTranslation = __('admin.' . $permissionName);

        if ($adminTranslation !== 'admin.' . $permissionName) {
            return $adminTranslation;
        }

        // إذا لم توجد ترجمة، استخدم القيم الافتراضية
        $displayNames = [
            'dashboard_view' => 'عرض لوحة التحكم',
            'dashboard_view_statistics' => 'عرض إحصائيات لوحة التحكم',
            'dashboard_analytics' => 'إحصائيات لوحة التحكم',
            'dashboard_export' => 'تصدير بيانات لوحة التحكم',
            'dashboard_manage' => 'إدارة لوحة التحكم',
            'dashboard_recruitment_orders_count' => 'عرض طلبات الاستقدام',
            'dashboard_recruitment_contracts_count' => 'عرض عقود الاستقدام',
            'dashboard_clients_count' => 'عرض عدد العملاء',
            'dashboard_contacts_count' => 'عرض عدد رسائل التواصل',
            'dashboard_current_orders_count' => 'عرض عدد الطلبات الحالية',
            'dashboard_cv_count' => 'عرض عدد السير الذاتية',
            'dashboard_employees_count' => 'عرض عدد الموظفين',
            'dashboard_completed_cv_count' => 'عرض عدد السير الذاتية المكتملة',
            'dashboard_rental_contracts_count' => 'عرض عقود الإيجار',
            'dashboard_rental_orders_count' => 'عرض طلبات الإيجار من الموقع',
            'dashboard_sponsorship_transfers_count' => 'عرض معاملات نقل الخدمات',
            'dashboard_sponsorship_transfer_requests_count' => 'عرض طلبات نقل الخدمات من الموقع',
            'dashboard_shelter_count' => 'عرض إحصائيات الإيواء',
            'dashboard_orders_statistics' => 'عرض إحصائيات الطلبات',
            'dashboard_operations_overview' => 'عرض مؤشرات التشغيل السريعة',
            'dashboard_contracts_overview' => 'عرض نظرة العقود التحليلية',
            'dashboard_nationalities_statistics' => 'عرض إحصائيات الجنسيات',
            'dashboard_offices_statistics' => 'عرض إحصائيات المكاتب الخارجية',
            'dashboard_recent_activities' => 'عرض آخر الأنشطة',
            'dashboard_calendar_events' => 'عرض الجدول الزمني والتذكيرات',
            'dashboard_employee_performance' => 'عرض أداء طاقم العمل',
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

        $built = $this->buildPermissionDisplayName($permissionName);

        return PermissionLabelDictionary::needsTranslation($built)
            ? PermissionLabelDictionary::label($permissionName)
            : $built;
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
            'dashboard_recruitment_orders_count' => 'إمكانية عرض بطاقة طلبات الاستقدام في لوحة التحكم',
            'dashboard_recruitment_contracts_count' => 'إمكانية عرض بطاقة عقود الاستقدام في لوحة التحكم',
            'dashboard_clients_count' => 'إمكانية عرض عدد العملاء في لوحة التحكم',
            'dashboard_contacts_count' => 'إمكانية عرض عدد رسائل التواصل في لوحة التحكم',
            'dashboard_current_orders_count' => 'إمكانية عرض عدد الطلبات الحالية في لوحة التحكم',
            'dashboard_cv_count' => 'إمكانية عرض عدد السير الذاتية في لوحة التحكم',
            'dashboard_employees_count' => 'إمكانية عرض عدد الموظفين في لوحة التحكم',
            'dashboard_completed_cv_count' => 'إمكانية عرض عدد السير الذاتية المكتملة في لوحة التحكم',
            'dashboard_rental_contracts_count' => 'إمكانية عرض بطاقة عقود الإيجار في لوحة التحكم',
            'dashboard_rental_orders_count' => 'إمكانية عرض بطاقة طلبات الإيجار من الموقع في لوحة التحكم',
            'dashboard_sponsorship_transfers_count' => 'إمكانية عرض بطاقة معاملات نقل الخدمات في لوحة التحكم',
            'dashboard_sponsorship_transfer_requests_count' => 'إمكانية عرض بطاقة طلبات نقل الخدمات من الموقع في لوحة التحكم',
            'dashboard_shelter_count' => 'إمكانية عرض بطاقة الإيواء في لوحة التحكم',
            'dashboard_orders_statistics' => 'إمكانية عرض إحصائيات الطلبات في لوحة التحكم',
            'dashboard_operations_overview' => 'إمكانية عرض قسم مؤشرات التشغيل السريعة في لوحة التحكم',
            'dashboard_contracts_overview' => 'إمكانية عرض قسم نظرة العقود التحليلية في لوحة التحكم',
            'dashboard_nationalities_statistics' => 'إمكانية عرض قسم إحصائيات الجنسيات في لوحة التحكم',
            'dashboard_offices_statistics' => 'إمكانية عرض قسم المكاتب الخارجية في لوحة التحكم',
            'dashboard_recent_activities' => 'إمكانية عرض قسم آخر الأنشطة في لوحة التحكم',
            'dashboard_calendar_events' => 'إمكانية عرض قسم الجدول الزمني والتذكيرات في لوحة التحكم',
            'dashboard_employee_performance' => 'إمكانية عرض قسم أداء طاقم العمل في لوحة التحكم',
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
        $normalizedName = str_replace(['.', '-'], '_', $permissionName);

        $map = [
            'dashboard_' => ['section' => 'dashboard', 'group' => 'dashboard'],
            'clients_' => ['section' => 'clients', 'group' => 'clients'],
            'maids_rental_' => ['section' => 'cv_management', 'group' => 'maids_rental'],
            'maids_backout_' => ['section' => 'cv_management', 'group' => 'maids_backout'],
            'maids_deleted_' => ['section' => 'cv_management', 'group' => 'maids_deleted'],
            'maids_' => ['section' => 'cv_management', 'group' => 'maids'],
            'cv_' => ['section' => 'cv_management', 'group' => 'maids'],
            'orders_' => ['section' => 'orders', 'group' => 'orders'],
            'return_request_' => ['section' => 'orders', 'group' => 'return_request'],
            'professional_application_' => ['section' => 'orders', 'group' => 'professional_application'],
            'recruitment_requests_' => ['section' => 'orders', 'group' => 'recruitment_requests'],
            'renew_contracts_' => ['section' => 'orders', 'group' => 'renew_contracts'],
            'financial_requests_' => ['section' => 'operations', 'group' => 'financial_requests'],
            'rec_contract_' => ['section' => 'recruitment_contracts', 'group' => 'rec_contracts'],
            'rec_contracts_' => ['section' => 'recruitment_contracts', 'group' => 'rec_contracts'],
            'ingaz_delegations_' => ['section' => 'recruitment_contracts', 'group' => 'ingaz_delegations'],
            'invoices_' => ['section' => 'recruitment_contracts', 'group' => 'invoices'],
            'musaned_transfers_' => ['section' => 'recruitment_contracts', 'group' => 'musaned_transfers'],
            'cancel_contract_' => ['section' => 'recruitment_contracts', 'group' => 'cancel_contract'],
            'rent_contract_' => ['section' => 'rent_contracts', 'group' => 'rent_contract'],
            'rental_orders_' => ['section' => 'rent_contracts', 'group' => 'rental_orders'],
            'shelter_contract_' => ['section' => 'shelter_management', 'group' => 'shelter_contract'],
            'shelter_workers_' => ['section' => 'shelter_management', 'group' => 'shelter_workers'],
            'shelter_places_' => ['section' => 'shelter_management', 'group' => 'shelter_places'],
            'travel_' => ['section' => 'travel_management', 'group' => 'travel'],
            'rec_offices_' => ['section' => 'maids_settings', 'group' => 'rec_offices'],
            'recruitment_offices_' => ['section' => 'maids_settings', 'group' => 'rec_offices'],
            'office_' => ['section' => 'maids_settings', 'group' => 'external_agents'],
            'offices_' => ['section' => 'maids_settings', 'group' => 'external_agents'],
            'external_agents_' => ['section' => 'maids_settings', 'group' => 'external_agents'],
            'complaint_type_' => ['section' => 'complaints', 'group' => 'complaint_type'],
            'complaint_' => ['section' => 'complaints', 'group' => 'complaint'],
            'complaints_' => ['section' => 'complaints', 'group' => 'complaint'],
            'sponsor_transfer_' => ['section' => 'additional_services', 'group' => 'sponsor_transfer'],
            'sponsor_transfer_web_requests_' => ['section' => 'additional_services', 'group' => 'sponsor_transfer_web_requests'],
            'moyasar_settings' => ['section' => 'settings', 'group' => 'moyasar_settings'],
            'settings_' => ['section' => 'settings', 'group' => 'settings'],
            'activity_' => ['section' => 'users_management', 'group' => 'activity_log'],
            'job_titles_' => ['section' => 'maids_settings', 'group' => 'job_titles'],
            'social_status_' => ['section' => 'maids_settings', 'group' => 'social_status'],
            'packages_' => ['section' => 'packages_management', 'group' => 'packages'],
            'nationalities_' => ['section' => 'maids_settings', 'group' => 'nationalities'],
            'religions_' => ['section' => 'maids_settings', 'group' => 'religions'],
            'relegions_' => ['section' => 'maids_settings', 'group' => 'religions'],
            'skills_' => ['section' => 'maids_settings', 'group' => 'skills'],
            'countries_' => ['section' => 'maids_settings', 'group' => 'countries'],
            'users_' => ['section' => 'users_management', 'group' => 'users'],
            'admins_' => ['section' => 'users_management', 'group' => 'admins'],
            'roles_' => ['section' => 'users_management', 'group' => 'roles'],
            'permissions_' => ['section' => 'users_management', 'group' => 'permissions'],
            'role_permissions_' => ['section' => 'users_management', 'group' => 'role_permissions'],
            'statistics_' => ['section' => 'content_management', 'group' => 'statistics'],
            'stat_' => ['section' => 'content_management', 'group' => 'statistics'],
            'notifications_' => ['section' => 'content_management', 'group' => 'notifications'],
            'sliders_' => ['section' => 'content_management', 'group' => 'sliders'],
            'offers_' => ['section' => 'content_management', 'group' => 'offers'],
            'faq_' => ['section' => 'content_management', 'group' => 'faq'],
            'about_us_' => ['section' => 'content_management', 'group' => 'about_us'],
            'musaned_section_' => ['section' => 'content_management', 'group' => 'musaned_section'],
            'home_sections_' => ['section' => 'content_management', 'group' => 'home_sections'],
            'home_hero_' => ['section' => 'content_management', 'group' => 'home_hero'],
            'guidelines_' => ['section' => 'content_management', 'group' => 'guidelines'],
            'contact_' => ['section' => 'orders', 'group' => 'contact'],
            'steps_' => ['section' => 'content_management', 'group' => 'steps'],
            'services_' => ['section' => 'content_management', 'group' => 'services'],
            'crm_' => ['section' => 'clients', 'group' => 'crm'],
            'hr_' => ['section' => 'modules', 'group' => 'hr'],
            'finance_' => ['section' => 'modules', 'group' => 'finance'],
            'employee_' => ['section' => 'modules', 'group' => 'employee'],
            'zatca_' => ['section' => 'modules', 'group' => 'zatca'],
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

    private function overridePermissionPlacement(string $permissionName, ?string $section, ?string $group): array
    {
        $normalizedName = str_replace(['.', '-'], '_', $permissionName);
        $section = $section ? $this->normalizeLegacyPermissionPlacementKey($section) : $section;
        $group = $group ? $this->normalizeLegacyPermissionPlacementKey($group) : $group;

        if (
            in_array($group, ['shelter_contract', 'shelter_workers', 'shelter_places'], true)
            && in_array($section, ['contract_management', 'shelter_management', null, ''], true)
        ) {
            return [
                'section' => 'shelter_management',
                'group' => $group,
            ];
        }

        if ($group === 'cv' || str_starts_with($normalizedName, 'cv_')) {
            return [
                'section' => 'cv_management',
                'group' => 'cv_legacy',
            ];
        }

        if ($group === 'packages' || str_starts_with($normalizedName, 'packages_')) {
            return [
                'section' => 'packages_management',
                'group' => 'packages',
            ];
        }

        if ($group === 'crm' || str_starts_with($normalizedName, 'crm_')) {
            return [
                'section' => 'clients',
                'group' => 'crm',
            ];
        }

        if ($group === 'travel' || str_starts_with($normalizedName, 'travel_')) {
            return [
                'section' => 'travel_management',
                'group' => 'travel',
            ];
        }

        if (
            in_array($group, ['sponsor_transfer', 'sponsor_transfer_web_requests'], true) ||
            preg_match('/^sponsor_transfer(_web_requests)?_/', $normalizedName)
        ) {
            return [
                'section' => 'additional_services',
                'group' => $group ?: $this->resolvePermissionPlacement($permissionName)['group'],
            ];
        }

        // اشتقاق المجموعة من الاسم يعطي office_suspend لصلاحية تعليق سير المكتب،
        // ومكانها الصحيح بطاقة الوكلاء الخارجيين نفسها لا بطاقة مستقلة.
        if ($group === 'office_suspend' || $normalizedName === 'office_suspend_cvs') {
            return [
                'section' => 'modules',
                'group' => 'office',
            ];
        }

        if (
            $section === 'modules' ||
            in_array($group, ['hr', 'finance', 'employee', 'zatca'], true) ||
            preg_match('/^(hr|finance|employee|zatca)_/', $normalizedName)
        ) {
            return [
                'section' => 'modules',
                'group' => $group ?: $this->resolvePermissionPlacement($permissionName)['group'],
            ];
        }

        return AdminPermissionNavigation::canonicalPlacement(
            (string) ($section ?: 'settings'),
            (string) ($group ?: 'other')
        );
    }

    private function buildPermissionDisplayName(string $permissionName): string
    {
        $specialNames = [
            'set_perms' => 'تعيين الصلاحيات',
            'developers_perms' => 'صلاحيات المطور',
            'full_contract' => 'العقد الكامل',
            'contract_settings' => 'إعدادات العقد',
            'complaints-read' => 'قراءة الشكاوى',
            'crm_module' => 'الوصول إلى نظام إدارة العملاء',
            'hr_module' => 'الوصول إلى نظام الموارد البشرية',
            'finance_module' => 'الوصول إلى النظام المحاسبي',
            'finance.constraints-approve' => 'اعتماد القيود المحاسبية',
        ];

        if (isset($specialNames[$permissionName])) {
            return $specialNames[$permissionName];
        }

        $resourceTitle = $this->getPermissionResourceTitle($permissionName);
        $actionTitle = $this->getPermissionActionTitle($permissionName);

        if ($actionTitle && $resourceTitle !== '') {
            return trim($actionTitle . ' ' . $resourceTitle);
        }

        return $resourceTitle !== '' ? $resourceTitle : str_replace('_', ' ', str_replace('-', ' ', $permissionName));
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
            'view_statistics' => 'عرض الإحصائيات',
            'create_recruitment' => 'إنشاء طلب استقدام',
            'add_contract' => 'إضافة عقد',
            'view_contract' => 'عرض العقد',
            'change_maid' => 'تغيير العاملة',
            'change_responsible' => 'تغيير المسؤول',
            'mark_in_progress' => 'تحديد كقيد التنفيذ',
            'mark_cancelled' => 'تحديد كملغي',
            'mark_completed' => 'تحديد كمكتمل',
            'change_to_msaned' => 'التحويل إلى مساند',
            'change_to_tafwed' => 'التحويل إلى التفويض',
            'change_to_taffez' => 'التحويل إلى التحفيز',
            'change_to_tazker' => 'التحويل إلى التذاكر',
            'close_after_warranty' => 'إغلاق بعد الضمان',
            'complete_travel' => 'استكمال الرحلة',
            'add_travel' => 'إضافة رحلة',
            'edit_travel' => 'تعديل الرحلة',
            'delete_travel' => 'حذف الرحلة',
            'view_travel' => 'عرض الرحلة',
            'cancel_approve' => 'اعتماد الإلغاء',
            'cancel_reject' => 'رفض الإلغاء',
            'return_manage' => 'إدارة المرتجعات',
            'return_view' => 'عرض المرتجعات',
            'warranty_manage' => 'إدارة الضمان',
            'warranty_view' => 'عرض الضمان',
            'download' => 'تنزيل',
            'upload' => 'رفع',
            'convert' => 'تحويل',
            'analytics' => 'تحليلات',
            'reports' => 'تقارير',
            'response' => 'الرد على',
            'confirm' => 'تأكيد',
            'approve' => 'اعتماد',
            'reject' => 'رفض',
            'activate' => 'تفعيل',
            'deactivate' => 'تعطيل',
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

    private function getPermissionResourceSortTitle(string $permissionName): string
    {
        return $this->getPermissionResourceTitle($permissionName);
    }

    private function getPermissionResourceTitle(string $permissionName): string
    {
        if (str_starts_with($permissionName, 'hourly_')) {
            return PermissionLabelDictionary::resourceLabel(PermissionLabelDictionary::split($permissionName)['resource']);
        }

        ['group' => $group] = $this->resolvePermissionPlacement($permissionName);
        ['resource' => $resource] = $this->splitPermissionName($permissionName);

        if ($resource === '') {
            return '';
        }

        $candidates = $this->buildResourceTranslationCandidates($resource, $group);

        foreach ($candidates as $candidate) {
            $translated = $this->translateResourceLabel($candidate, $group);

            if ($translated !== null) {
                return $translated;
            }
        }

        $fallback = end($candidates) ?: $resource;
        $humanized = $this->humanizePermissionResource($fallback, $group);

        return PermissionLabelDictionary::needsTranslation($humanized)
            ? PermissionLabelDictionary::resourceLabel($resource)
            : $humanized;
    }

    private function splitPermissionName(string $permissionName): array
    {
        $normalizedName = str_replace('-', '_', $permissionName);

        if (preg_match('/^rec_contracts_cancel_(approve|reject)$/', $normalizedName, $matches)) {
            return [
                'resource' => 'rec_contracts_cancel',
                'action' => $matches[1],
            ];
        }

        $suffixes = [
            'change_status',
            'toggle_status',
            'export_excel',
            'export_pdf',
            'toggle_pin',
            'mark_in_progress',
            'mark_cancelled',
            'mark_completed',
            'view_statistics',
            'create_recruitment',
            'add_contract',
            'view_contract',
            'change_maid',
            'change_responsible',
            'change_to_msaned',
            'change_to_tafwed',
            'change_to_taffez',
            'change_to_tazker',
            'close_after_warranty',
            'complete_travel',
            'add_travel',
            'edit_travel',
            'delete_travel',
            'view_travel',
            'cancel_approve',
            'cancel_reject',
            'return_manage',
            'return_view',
            'warranty_manage',
            'warranty_view',
            'response',
            'confirm',
            'activate',
            'deactivate',
            'download',
            'upload',
            'convert',
            'analytics',
            'reports',
            'approve',
            'reject',
            'assign',
            'manage',
            'control',
            'create',
            'edit',
            'update',
            'delete',
            'export',
            'import',
            'print',
            'view',
            'show',
            'read',
            'pin',
            'unpin',
        ];

        foreach ($suffixes as $suffix) {
            if ($normalizedName === $suffix || str_ends_with($normalizedName, '_' . $suffix)) {
                return [
                    'resource' => preg_replace('/_' . preg_quote($suffix, '/') . '$/', '', $normalizedName) ?: '',
                    'action' => $suffix,
                ];
            }
        }

        return [
            'resource' => $normalizedName,
            'action' => null,
        ];
    }

    private function translateResourceLabel(string $resourceKey, ?string $group = null): ?string
    {
        $resourceKey = trim(str_replace('-', '_', $resourceKey));

        if ($resourceKey === '') {
            return null;
        }

        $aliases = [
            'cv' => 'السير الذاتية القديمة',
            'about_us' => 'صفحة من نحن',
            'contact' => 'طلبات التواصل',
            'sliders' => 'سلايدر الموقع',
            'services' => 'خدمات الموقع',
            'steps' => 'خطوات الموقع',
            'maids_rental' => 'سير عاملات الإيجار',
            'maids_backout' => 'عاملات باك أوت',
            'maids_deleted' => 'السير الذاتية المحذوفة',
            'crm_module' => 'نظام إدارة العملاء',
            'hr_module' => 'نظام الموارد البشرية',
            'finance_module' => 'النظام المحاسبي',
            'finance_constraints' => 'القيود المحاسبية',
            'finance_procurement' => 'المشتريات',
            'procurement' => 'المشتريات',
            'rec_contracts' => 'عقود الاستقدام',
            'ingaz_delegations' => 'تفاويض الإنجاز',
            'invoices' => 'الفواتير',
            'musaned_transfers' => 'تحويلات مساند',
            'rec_contracts_cancel' => 'طلبات إلغاء عقود الاستقدام',
            'rec_contracts_return' => 'مرتجعات عقود الاستقدام',
            'rec_contracts_warranty' => 'ضمان عقود الاستقدام',
            'professional_application' => 'طلبات العمالة المهنية',
            'recruitment_requests' => 'الطلبات الخاصة',
            'renew_contracts' => 'طلبات تجديد العقود',
            'rental_orders' => 'طلبات الإيجار',
            'shelter_contract' => 'عقود الإيواء',
            'shelter_workers' => 'عمال الإيواء',
            'shelter_places' => 'مقرات الإيواء',
            'shelter_contract_addition' => 'الإضافة',
            'shelter_contract_inside' => 'داخل الإيواء',
            'shelter_contract_available_for_transfer' => 'متاح للنقل',
            'shelter_contract_deportation_stage' => 'مرحلة الترحيل',
            'shelter_contract_deported' => 'تم الترحيل',
            'shelter_contract_deportation_procedures' => 'إجراءات الترحيل',
            'shelter_contract_work_desire' => 'رغبة العمل',
            'shelter_contract_quick_tags' => 'التصنيفات السريعة',
            'shelter_contract_instant_receipt' => 'الاستلام الفوري',
            'shelter_contract_remove_from_shelter' => 'حذف من الإيواء',
            'sponsor_transfer_web_requests' => 'طلبات الموقع الخارجي لنقل الخدمات',
            'confirm_clients' => 'تأكيد العملاء المحتملين',
            'messages_reports' => 'رسائل الموقع',
            'send_poll' => 'إرسال الاستبيانات للعملاء المحتملين',
            'send_poll_user' => 'إرسال الاستبيانات للمستخدمين',
            'print_price_offers' => 'طباعة عروض الأسعار',
            'clients_report' => 'تقارير العملاء المحتملين',
            'follow_clients_categories' => 'أنواع متابعة العملاء',
            'my_followups' => 'متابعاتي',
            'follow_clients' => 'متابعة العملاء',
            'social_contacts' => 'مصادر التواصل',
            'attachment_categories' => 'أنواع الملفات',
            'client_attachments' => 'ملفات العملاء',
            'employee_profile' => 'الملف الشخصي للموظف',
            'daily_work_reports' => 'التقارير اليومية',
            'follow_daily_reports' => 'متابعة التقارير اليومية',
            'complaint_replies' => 'ردود الشكاوى',
            'tasks_replies' => 'ردود المهام',
            'hr_task_replies' => 'ردود مهام الموارد البشرية',
            'my_complaints' => 'شكاوى الموظف',
            'my_tasks' => 'مهام الموظف',
            'permissions_settings' => 'إعدادات الأذونات',
            'vacations_return_date' => 'مباشرة الإجازات',
            'toggle_status_employees' => 'حالة الموظفين',
            'toggle_status_employee_vacation' => 'حالة طلبات الإجازة',
            'employee_execel_file' => 'رفع ملف الحضور',
            'mluti_employee' => 'الحضور الجماعي',
            'share_invoice' => 'مشاركة الفواتير مع الهيئة',
            'zatca_settings' => 'إعدادات هيئة الزكاة',
        ];

        if (isset($aliases[$resourceKey])) {
            return $aliases[$resourceKey];
        }

        foreach ($this->getResourceTranslationSources($group) as $file) {
            $translation = __($file . '.' . $resourceKey);

            if ($translation !== $file . '.' . $resourceKey) {
                return $translation;
            }
        }

        return null;
    }

    private function humanizePermissionResource(string $resourceKey, ?string $group = null): string
    {
        $resourceKey = trim(str_replace('-', '_', $resourceKey));

        if ($resourceKey === '') {
            return '';
        }

        $prefixPatterns = [
            'toggle_status_' => 'حالة %s',
            'print_' => 'طباعة %s',
            'send_' => 'إرسال %s',
            'confirm_' => 'تأكيد %s',
            'share_' => 'مشاركة %s',
            'load_' => 'تحميل %s',
            'get_' => 'جلب %s',
            'add_' => 'إضافة %s',
            'convert_' => 'تحويل %s',
            'back_' => 'مرتجعات %s',
        ];

        foreach ($prefixPatterns as $prefix => $template) {
            if (str_starts_with($resourceKey, $prefix)) {
                $base = substr($resourceKey, strlen($prefix));

                if ($base !== '') {
                    return sprintf($template, $this->resolveNestedPermissionTitle($base, $group));
                }
            }
        }

        $suffixPatterns = [
            '_files' => 'ملفات %s',
            '_attachments' => 'مرفقات %s',
            '_replies' => 'ردود %s',
            '_balances' => 'أرصدة %s',
            '_settings' => 'إعدادات %s',
            '_statement' => 'كشف حساب %s',
            '_report' => 'تقرير %s',
            '_reports' => 'تقارير %s',
        ];

        foreach ($suffixPatterns as $suffix => $template) {
            if (str_ends_with($resourceKey, $suffix)) {
                $base = preg_replace('/' . preg_quote($suffix, '/') . '$/', '', $resourceKey) ?: '';

                if ($base !== '') {
                    return sprintf($template, $this->resolveNestedPermissionTitle($base, $group));
                }
            }
        }

        $dictionary = [
            'home' => 'الرئيسية',
            'dashboard' => 'لوحة التحكم',
            'module' => 'النظام',
            'reports' => 'التقارير',
            'report' => 'التقرير',
            'settings' => 'الإعدادات',
            'setting' => 'الإعداد',
            'account' => 'الحساب',
            'accounts' => 'الحسابات',
            'balance' => 'الرصيد',
            'balances' => 'الأرصدة',
            'transfer' => 'التحويل',
            'transfers' => 'التحويلات',
            'statement' => 'كشف الحساب',
            'statements' => 'كشوف الحسابات',
            'clients' => 'العملاء',
            'client' => 'العميل',
            'confirm' => 'التأكيد',
            'share' => 'المشاركة',
            'suppliers' => 'الموردين',
            'supplier' => 'المورد',
            'banks' => 'البنوك',
            'bank' => 'البنك',
            'boxes' => 'الصناديق',
            'box' => 'الصندوق',
            'checks' => 'الشيكات',
            'check' => 'الشيك',
            'bonds' => 'السندات',
            'bond' => 'السند',
            'cash' => 'النقدية',
            'years' => 'السنوات',
            'year' => 'السنة',
            'status' => 'الحالة',
            'items' => 'الأصناف',
            'item' => 'الصنف',
            'inventories' => 'المخزون',
            'inventory' => 'المخزون',
            'stocks' => 'المخازن',
            'stock' => 'المخزن',
            'cost' => 'التكلفة',
            'centers' => 'المراكز',
            'center' => 'المركز',
            'categories' => 'التصنيفات',
            'category' => 'التصنيف',
            'notifications' => 'الإشعارات',
            'notification' => 'الإشعار',
            'employees' => 'الموظفين',
            'employee' => 'الموظف',
            'permissions' => 'الصلاحيات',
            'permission' => 'الصلاحية',
            'profile' => 'الملف الشخصي',
            'daily' => 'اليومي',
            'work' => 'العمل',
            'task' => 'المهمة',
            'tasks' => 'المهام',
            'reply' => 'الرد',
            'replies' => 'الردود',
            'vacation' => 'الإجازة',
            'vacations' => 'الإجازات',
            'reward' => 'المكافأة',
            'rewards' => 'المكافآت',
            'advance' => 'السلفة',
            'advances' => 'السلف',
            'complaint' => 'الشكوى',
            'complaints' => 'الشكاوى',
            'salary' => 'الراتب',
            'salaries' => 'الرواتب',
            'shift' => 'الوردية',
            'shifts' => 'الورديات',
            'attendance' => 'الحضور',
            'attendances' => 'الحضور والانصراف',
            'official' => 'الرسمية',
            'offers' => 'العروض',
            'offer' => 'العرض',
            'price' => 'السعر',
            'questions' => 'الأسئلة',
            'question' => 'السؤال',
            'answers' => 'الإجابات',
            'answer' => 'الإجابة',
            'areas' => 'المناطق',
            'area' => 'المنطقة',
            'polls' => 'الاستبيانات',
            'poll' => 'الاستبيان',
            'messages' => 'الرسائل',
            'message' => 'الرسالة',
            'files' => 'الملفات',
            'attachments' => 'المرفقات',
            'attachment' => 'المرفق',
            'social' => 'التواصل',
            'follow' => 'المتابعة',
            'followups' => 'المتابعات',
            'return' => 'المرتجعات',
            'warranty' => 'الضمان',
            'shelter' => 'الإيواء',
            'inside' => 'داخل',
            'available' => 'المتاح',
            'deportation' => 'الترحيل',
            'stage' => 'المرحلة',
            'procedures' => 'الإجراءات',
            'quick' => 'السريعة',
            'tags' => 'التصنيفات',
            'desire' => 'الرغبة',
            'instant' => 'الفوري',
            'receipt' => 'الاستلام',
            'remove' => 'إزالة',
            'addition' => 'الإضافة',
            'places' => 'المقرات',
            'workers' => 'العمال',
            'travel' => 'الرحلات',
            'print' => 'الطباعة',
            'send' => 'الإرسال',
            'invoice' => 'الفاتورة',
            'zatca' => 'هيئة الزكاة',
            'tax' => 'الضريبة',
            'declaration' => 'الإقرار',
            'purchase' => 'الشراء',
            'purchases' => 'المشتريات',
            'sale' => 'البيع',
            'sales' => 'المبيعات',
            'bill' => 'الفاتورة',
            'bills' => 'الفواتير',
            'guide' => 'الدليل',
            'management' => 'الإدارة',
            'general' => 'العام',
            'double' => 'المزدوج',
            'movement' => 'الحركة',
            'movements' => 'الحركات',
            'treasury' => 'الخزينة',
            'manual' => 'اليدوي',
            'principle' => 'الأصل',
            'principles' => 'الأصول',
            'unit' => 'الوحدة',
            'units' => 'الوحدات',
            'download' => 'التنزيل',
            'upload' => 'الرفع',
            'analytics' => 'التحليلات',
            'contact' => 'التواصل',
            'about' => 'حول',
        ];

        $words = array_filter(explode('_', str_replace('-', '_', $resourceKey)));
        $translatedWords = array_map(function (string $word) use ($dictionary) {
            return $dictionary[$word] ?? $word;
        }, $words);

        return trim(implode(' ', $translatedWords));
    }

    private function getResourceTranslationSources(?string $group): array
    {
        return match ($group) {
            'crm' => ['crm::crm', 'permissions', 'admin', 'ingaz'],
            'hr' => ['hr::hr', 'permissions', 'admin', 'ingaz'],
            'finance' => ['finance::finance', 'permissions', 'admin', 'ingaz'],
            'zatca' => ['finance::zatca', 'finance::finance', 'permissions', 'admin', 'ingaz'],
            default => ['permissions', 'admin', 'ingaz', 'crm::crm', 'hr::hr', 'finance::finance', 'finance::zatca'],
        };
    }

    private function buildResourceTranslationCandidates(string $resource, ?string $group): array
    {
        $baseCandidates = [$resource];

        if ($group !== null && in_array($group, ['finance', 'hr', 'crm', 'employee', 'zatca'], true)) {
            $prefix = $group . '_';

            if (str_starts_with($resource, $prefix)) {
                $baseCandidates[] = substr($resource, strlen($prefix));
            }
        }

        $expanded = [];

        foreach (array_unique(array_filter($baseCandidates)) as $candidate) {
            foreach ($this->expandResourceLookupKeys($candidate) as $lookupKey) {
                $expanded[] = $lookupKey;
            }
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    private function expandResourceLookupKeys(string $resourceKey): array
    {
        $resourceKey = trim(str_replace('-', '_', $resourceKey));

        if ($resourceKey === '') {
            return [];
        }

        $variants = [
            $resourceKey,
            str_replace('_', '-', $resourceKey),
        ];

        $pluralVariant = $this->pluralizeResourceKey($resourceKey);

        if ($pluralVariant !== null) {
            $variants[] = $pluralVariant;
            $variants[] = str_replace('_', '-', $pluralVariant);
        }

        return array_values(array_unique($variants));
    }

    private function pluralizeResourceKey(string $resourceKey): ?string
    {
        $segments = array_values(array_filter(explode('_', $resourceKey)));

        if ($segments === []) {
            return null;
        }

        $lastSegment = array_pop($segments);
        $pluralMap = [
            'category' => 'categories',
            'company' => 'companies',
            'reply' => 'replies',
            'city' => 'cities',
            'box' => 'boxes',
            'offer' => 'offers',
            'attachment' => 'attachments',
            'client' => 'clients',
            'user' => 'users',
            'poll' => 'polls',
            'question' => 'questions',
            'answer' => 'answers',
            'permission' => 'permissions',
            'task' => 'tasks',
            'vacation' => 'vacations',
            'check' => 'checks',
            'account' => 'accounts',
            'supplier' => 'suppliers',
            'balance' => 'balances',
            'inventory' => 'inventories',
            'country' => 'countries',
            'notification' => 'notifications',
            'service' => 'services',
            'item' => 'items',
            'year' => 'years',
            'bond' => 'bonds',
            'bank' => 'banks',
            'area' => 'areas',
            'contact' => 'contacts',
            'report' => 'reports',
            'file' => 'files',
            'setting' => 'settings',
        ];

        $plural = $pluralMap[$lastSegment] ?? null;

        if ($plural === null) {
            if (str_ends_with($lastSegment, 's')) {
                return null;
            }

            $plural = $lastSegment . 's';
        }

        $segments[] = $plural;

        return implode('_', $segments);
    }

    private function resolveNestedPermissionTitle(string $resourceKey, ?string $group): string
    {
        foreach ($this->buildResourceTranslationCandidates($resourceKey, $group) as $candidate) {
            $translated = $this->translateResourceLabel($candidate, $group);

            if ($translated !== null) {
                return $translated;
            }
        }

        return $this->humanizePermissionResource($resourceKey, $group);
    }

    private function buildPermissionMatrixSections(array $organizedPermissions): array
    {
        $sections = [];

        foreach ($organizedPermissions as $sectionName => $sectionGroups) {
            $groups = [];
            $sectionPermissionsCount = 0;

            foreach ($sectionGroups as $groupName => $permissions) {
                $rows = [];

                foreach ($permissions as $permission) {
                    $entry = $this->buildPermissionMatrixEntry($permission);
                    $rowKey = $entry['resource_key'];

                    if (!isset($rows[$rowKey])) {
                        $rows[$rowKey] = [
                            'key' => $rowKey,
                            'title' => $entry['resource_title'],
                            'crud' => [
                                'create' => null,
                                'read' => null,
                                'update' => null,
                                'delete' => null,
                            ],
                            'extras' => [],
                            'permissions' => [],
                        ];
                    }

                    $rows[$rowKey]['permissions'][$entry['name']] = true;

                    if ($entry['matrix_column'] !== null && $rows[$rowKey]['crud'][$entry['matrix_column']] === null) {
                        $rows[$rowKey]['crud'][$entry['matrix_column']] = $entry;
                    } else {
                        $rows[$rowKey]['extras'][] = $entry;
                    }
                }

                uasort($rows, function (array $first, array $second) {
                    $firstPriority = $this->getPermissionRowSortPriority($first['key']);
                    $secondPriority = $this->getPermissionRowSortPriority($second['key']);

                    if ($firstPriority !== $secondPriority) {
                        return $firstPriority <=> $secondPriority;
                    }

                    return strnatcasecmp($first['title'], $second['title']);
                });

                foreach ($rows as &$row) {
                    $row['permissions'] = array_keys($row['permissions']);
                    $row['permissions_count'] = count($row['permissions']);

                    usort($row['extras'], function (array $first, array $second) {
                        return strnatcasecmp($first['extra_label'], $second['extra_label']);
                    });
                }
                unset($row);

                $groupRows = array_values($rows);

                if ($groupRows === []) {
                    continue;
                }

                $groupPermissionsCount = array_sum(array_map(
                    fn (array $row) => (int) ($row['permissions_count'] ?? 0),
                    $groupRows
                ));

                $groups[] = [
                    'key' => $groupName,
                    'title' => $this->getGroupTitle($groupName),
                    'rows' => $groupRows,
                    'permissions_count' => $groupPermissionsCount,
                ];

                $sectionPermissionsCount += $groupPermissionsCount;
            }

            if ($groups === []) {
                continue;
            }

            $sections[] = [
                'key' => $sectionName,
                'title' => $this->getSectionTitle($sectionName),
                'groups' => $groups,
                'permissions_count' => $sectionPermissionsCount,
            ];
        }

        return $sections;
    }

    private function buildPermissionMatrixLanes(array $sections): array
    {
        $laneConfig = AdminPermissionNavigation::units();

        $lanes = [];

        foreach ($laneConfig as $laneKey => $config) {
            $lanes[$laneKey] = [
                'key' => $laneKey,
                'title' => $config['title'],
                'description' => $config['description'],
                'tone' => $config['tone'],
                'sections' => [],
                'sections_count' => 0,
                'groups_count' => 0,
                'permissions_count' => 0,
            ];
        }

        foreach ($sections as $section) {
            $laneKey = AdminPermissionNavigation::unitForSection($section['key']);

            $lanes[$laneKey]['sections'][] = $section;
            $lanes[$laneKey]['sections_count']++;
            $lanes[$laneKey]['groups_count'] += count($section['groups'] ?? []);
            $lanes[$laneKey]['permissions_count'] += (int) ($section['permissions_count'] ?? 0);
        }

        return array_values(array_filter($lanes, fn (array $lane) => $lane['sections'] !== []));
    }

    private function getPermissionRowSortPriority(string $resourceKey): int
    {
        $resourceKey = str_replace('-', '_', trim($resourceKey));

        $priorities = [
            'rec_contracts' => 10,
            'ingaz_delegations' => 15,
            'rec_contracts_return' => 20,
            'rec_contracts_warranty' => 30,
            'rec_contracts_cancel' => 40,
            'shelter_contract_addition' => 10,
            'shelter_contract_inside' => 20,
            'shelter_contract_available_for_transfer' => 30,
            'shelter_contract_deportation_stage' => 40,
            'shelter_contract_deported' => 50,
            'shelter_contract_deportation_procedures' => 60,
            'shelter_agent_cost_recovery' => 65,
            'shelter_places' => 70,
            'shelter_contract_work_desire' => 80,
            'shelter_contract_quick_tags' => 90,
            'shelter_contract_instant_receipt' => 100,
            'shelter_contract_remove_from_shelter' => 110,
            'shelter_contract' => 120,
            'shelter_workers' => 130,
        ];

        return $priorities[$resourceKey] ?? 999;
    }

    private function buildPermissionMatrixEntry(Permission $permission): array
    {
        if ($permission->name === \App\PermKeys::ORDERS_CHANGE_CLIENT) {
            return [
                'name' => $permission->name, 'display_name' => 'تغيير العميل في الطلب',
                'description' => 'تغيير عميل طلب جديد أو قيد التنفيذ غير مرتبط بعقد أو مدفوعات.',
                'resource_key' => 'orders', 'resource_title' => 'الطلبات',
                'matrix_column' => null, 'extra_label' => 'تغيير العميل في الطلب',
            ];
        }
        if ($permission->name === \App\PermKeys::ORDERS_DELETE_OWN) {
            return [
                'name' => $permission->name,
                'display_name' => 'حذف طلباتي',
                'description' => 'حذف واستعادة الطلبات والحجوزات التي أنشأها المستخدم فقط.',
                'resource_key' => 'orders', 'resource_title' => 'الطلبات',
                'matrix_column' => null, 'extra_label' => 'حذف طلباتي',
            ];
        }
        if ($permission->name === \App\NewPermKeys::REC_CONTRACTS_LATE_FINANCE) {
            $label = 'استكمال العمليات المالية لعقد الاستقدام بعد الوصول';
            return [
                'name' => $permission->name, 'display_name' => $label,
                'description' => 'إصدار فاتورة وتسجيل الإرسالية وتكلفة التفويض وتحويل مساند لعقد وصول أو مكتمل من صفحة مالية مستقلة.',
                'resource_key' => 'rec_contracts', 'resource_title' => 'عقود الاستقدام',
                'matrix_column' => null, 'extra_label' => $label,
            ];
        }
        if (in_array($permission->name, [\App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE, \App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_APPROVE], true)) {
            $label = $permission->name === \App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE
                ? 'تعديل تسوية إنهاء عقد الإيجار' : 'اعتماد تسوية إنهاء عقد الإيجار';
            return [
                'name' => $permission->name, 'display_name' => $label,
                'description' => $this->getPermissionDescription($permission),
                'resource_key' => 'rent_contract_closure_adjustment',
                'resource_title' => 'تسوية إنهاء عقد الإيجار',
                'matrix_column' => null, 'extra_label' => $label,
            ];
        }
        if (str_starts_with($permission->name, 'hourly_') && ($page = \App\Support\HourlyPermissionCatalog::page($permission->name))) {
            $action = \App\Support\HourlyPermissionCatalog::action($permission->name);
            $label = \App\Support\HourlyPermissionCatalog::label($permission->name) ?? $this->getPermissionDisplayName($permission);
            return [
                'name' => $permission->name, 'display_name' => $label,
                'description' => $label,
                'resource_key' => $page, 'resource_title' => \App\Support\HourlyPermissionCatalog::PAGES[$page],
                'matrix_column' => $this->resolvePermissionMatrixColumn($action),
                'extra_label' => \App\Support\HourlyPermissionCatalog::ACTIONS[$action] ?? $label,
            ];
        }

        if (in_array($permission->name, [\App\PermKeys::SHELTER_AGENT_COST_RECOVERY, \App\PermKeys::SHELTER_AGENT_COST_RECOVERY_REVERSE], true)) {
            $label = $permission->name === \App\PermKeys::SHELTER_AGENT_COST_RECOVERY
                ? 'استرداد تكلفة الوكيل من الإيواء' : 'عكس استرداد تكلفة الوكيل';
            return [
                'name' => $permission->name, 'display_name' => $label,
                'description' => $this->getPermissionDescription($permission),
                'resource_key' => 'shelter_agent_cost_recovery', 'resource_title' => 'استرداد تكلفة الوكيل',
                'matrix_column' => null, 'extra_label' => $label,
            ];
        }

        ['resource' => $resource, 'action' => $action] = $this->splitPermissionName($permission->name);

        $resourceKey = $resource !== '' ? $resource : str_replace('-', '_', $permission->name);
        $resourceTitle = trim($this->getPermissionResourceTitle($permission->name));
        $displayName = $this->getPermissionDisplayName($permission);

        if ($resourceTitle === '') {
            $resourceTitle = $displayName;
        }

        return [
            'name' => $permission->name,
            'display_name' => $displayName,
            'description' => $this->getPermissionDescription($permission),
            'resource_key' => $resourceKey,
            'resource_title' => $resourceTitle,
            'matrix_column' => $this->resolvePermissionMatrixColumn($action),
            'extra_label' => $this->resolvePermissionMatrixExtraLabel($permission->name, $displayName, $action),
        ];
    }

    private function resolvePermissionMatrixColumn(?string $action): ?string
    {
        return match ($action) {
            'create', 'create_recruitment', 'add_contract' => 'create',
            'view', 'show', 'read', 'view_contract', 'view_statistics' => 'read',
            'edit', 'update' => 'update',
            'delete' => 'delete',
            default => null,
        };
    }

    private function resolvePermissionMatrixExtraLabel(string $permissionName, string $displayName, ?string $action): string
    {
        $actionLabel = $action !== null ? $this->getPermissionActionTitle($permissionName) : null;

        if ($actionLabel !== null) {
            return $actionLabel;
        }

        return $displayName;
    }

    private function normalizeSubmittedPermissionNames($permissionNames): Collection
    {
        if (is_string($permissionNames)) {
            $decodedPermissionNames = json_decode($permissionNames, true);
            $permissionNames = is_array($decodedPermissionNames)
                ? $decodedPermissionNames
                : [$permissionNames];
        }

        return collect($permissionNames)
            ->filter(fn ($permissionName) => is_string($permissionName) && trim($permissionName) !== '')
            ->map(fn ($permissionName) => trim($permissionName))
            ->unique()
            ->values();
    }

    private function syncRolePermissionsWithinScope(Role $role, Collection $requestedPermissionNames, Collection $scopePermissionNames): array
    {
        $scopePermissions = $scopePermissionNames->isEmpty()
            ? collect()
            : Permission::query()
                ->where('guard_name', 'admin')
                ->whereIn('name', $scopePermissionNames)
                ->get();

        $validScopePermissionNames = $scopePermissions->pluck('name')->values();
        $requestedPermissionNames = $requestedPermissionNames
            ->intersect($validScopePermissionNames)
            ->values();

        $requestedPermissions = $requestedPermissionNames->isEmpty()
            ? collect()
            : Permission::query()
                ->where('guard_name', 'admin')
                ->whereIn('name', $requestedPermissionNames)
                ->get();

        $assignedPermissionNames = $role->permissions()
            ->where('guard_name', 'admin')
            ->pluck('name');

        $retainedPermissionNames = $assignedPermissionNames
            ->diff($validScopePermissionNames)
            ->values();

        $finalPermissionNames = $retainedPermissionNames
            ->merge($requestedPermissions->pluck('name'))
            ->unique()
            ->values();

        $finalPermissions = $finalPermissionNames->isEmpty()
            ? collect()
            : Permission::query()
                ->where('guard_name', 'admin')
                ->whereIn('name', $finalPermissionNames)
                ->get();

        $role->syncPermissions($finalPermissions);

        return [
            'scope_permissions' => $validScopePermissionNames,
            'requested_permissions' => $requestedPermissions->pluck('name')->values(),
            'missing_scope_permissions' => $scopePermissionNames->diff($validScopePermissionNames)->values(),
            'missing_requested_permissions' => $requestedPermissionNames->diff($requestedPermissions->pluck('name'))->values(),
        ];
    }

    private function syncRoleBranchPermissionsWithinScope(Role $role, int $branchId, Collection $requestedPermissionNames, Collection $scopePermissionNames): array
    {
        $scopePermissions = $scopePermissionNames->isEmpty()
            ? collect()
            : Permission::query()
                ->where('guard_name', 'admin')
                ->whereIn('name', $scopePermissionNames)
                ->get();

        $validScopePermissionNames = $scopePermissions->pluck('name')->values();
        $requestedPermissionNames = $requestedPermissionNames
            ->intersect($validScopePermissionNames)
            ->values();

        $configuredPermissionNames = BranchScopedPermissions::permissionNamesForRoleBranch((int) $role->id, $branchId);
        $assignedPermissionNames = $configuredPermissionNames->isNotEmpty()
            ? $configuredPermissionNames
            : $role->permissions()->where('guard_name', 'admin')->pluck('name');

        $retainedPermissionNames = $assignedPermissionNames
            ->diff($validScopePermissionNames)
            ->values();

        $finalPermissionNames = $retainedPermissionNames
            ->merge($requestedPermissionNames)
            ->unique()
            ->values();

        $syncedPermissionNames = BranchScopedPermissions::syncRoleBranchPermissions(
            (int) $role->id,
            $branchId,
            $finalPermissionNames
        );

        return [
            'scope_permissions' => $validScopePermissionNames,
            'requested_permissions' => $syncedPermissionNames->intersect($requestedPermissionNames)->values(),
            'missing_scope_permissions' => $scopePermissionNames->diff($validScopePermissionNames)->values(),
            'missing_requested_permissions' => $requestedPermissionNames->diff($syncedPermissionNames)->values(),
        ];
    }

    public function storePerms(Request $request){
        if(isset($request->role_id)){
            $role = $this->findAdminRole($request->role_id);
            if (!$role) {
                return redirect(route('dash.roles.index'))->with('error', 'لا يمكن تحديث صلاحيات دور غير إداري.');
            }

            $requestedPermissions = $this->normalizeSubmittedPermissionNames(
                $request->input('perms_json', $request->input('perms', []))
            );
            $scopePermissions = $this->normalizeSubmittedPermissionNames(
                $request->input('scope_permissions_json', $request->input('scope_permissions', []))
            );
            $scopeLabel = trim((string) $request->input('scope_label', ''));
            $branchId = $this->resolveSubmittedBranchId($request);

            if ($scopePermissions->isEmpty()) {
                $requestedPermissions = $this->preserveHiddenHourlyPermissions($role, $requestedPermissions, $branchId);
            }

            if ($branchId) {
                if ($scopePermissions->isNotEmpty()) {
                    $branchResult = $this->syncRoleBranchPermissionsWithinScope(
                        $role,
                        $branchId,
                        $requestedPermissions,
                        $scopePermissions
                    );
                    $roleResult = $this->syncRolePermissionsWithinScope(
                        $role,
                        $requestedPermissions,
                        $scopePermissions
                    );
                    $missingPermissions = $branchResult['missing_scope_permissions']
                        ->merge($branchResult['missing_requested_permissions'])
                        ->merge($roleResult['missing_scope_permissions'])
                        ->merge($roleResult['missing_requested_permissions'])
                        ->unique()
                        ->values();
                } else {
                    $syncedPermissionNames = BranchScopedPermissions::syncRoleBranchPermissions(
                        (int) $role->id,
                        $branchId,
                        $requestedPermissions
                    );
                    $permissions = $requestedPermissions->isEmpty()
                        ? collect()
                        : Permission::query()
                            ->where('guard_name', 'admin')
                            ->whereIn('name', $requestedPermissions)
                            ->get();

                    $role->syncPermissions($permissions);

                    $missingPermissions = $requestedPermissions
                        ->diff($syncedPermissionNames)
                        ->merge($requestedPermissions->diff($permissions->pluck('name')))
                        ->unique()
                        ->values();
                }

                $this->forgetPermissionCache();

                if ($missingPermissions->isNotEmpty()) {
                    return redirect()->back()->with(
                        'warning',
                        'تم تحديث صلاحيات الفرع وتجاهل العناصر غير المتاحة: ' . $missingPermissions->implode(' , ')
                    );
                }

                $successMessage = $scopePermissions->isNotEmpty()
                    ? ($scopeLabel !== '' ? 'تم تحديث صلاحيات ' . $scopeLabel . ' للفرع والدور بنجاح.' : 'تم تحديث صلاحيات القائمة المحددة للفرع والدور بنجاح.')
                    : 'تم تحديث صلاحيات الفرع والدور بنجاح.';

                return redirect()->back()->with('msg', $successMessage);
            }

            if ($scopePermissions->isNotEmpty()) {
                $result = $this->syncRolePermissionsWithinScope($role, $requestedPermissions, $scopePermissions);
                $missingPermissions = $result['missing_scope_permissions']->merge($result['missing_requested_permissions'])->unique()->values();
            } else {
                $permissions = $requestedPermissions->isEmpty()
                    ? collect()
                    : Permission::query()
                        ->where('guard_name', 'admin')
                        ->whereIn('name', $requestedPermissions)
                        ->get();

                $role->syncPermissions($permissions);
                $missingPermissions = $requestedPermissions->diff($permissions->pluck('name'))->values();
            }

            $this->forgetPermissionCache();

            if ($missingPermissions->isNotEmpty()) {
                return redirect()->back()->with(
                    'warning',
                    'تم تحديث الصلاحيات وتجاهل العناصر غير المتاحة: ' . $missingPermissions->implode(' , ')
                );
            }

            $successMessage = __('admin.success');

            if ($scopePermissions->isNotEmpty()) {
                $successMessage = $scopeLabel !== ''
                    ? 'تم تحديث صلاحيات ' . $scopeLabel . ' فقط بنجاح.'
                    : 'تم تحديث صلاحيات القائمة المحددة فقط بنجاح.';
            }

            return redirect()->back()->with('msg', $successMessage);
        }
        return redirect(route('dash.roles.index'));
    }

    public function index($id = null){
        $data = $this->buildDeveloperPermissionsIndexData();
        $editData = null;
        if($id != null){
            $editData = Permission::withoutGlobalScopes()
                ->where('guard_name', 'admin')
                ->find($id);

            if ($editData) {
                $editData = $this->decoratePermissionForDeveloperIndex($editData);

                if (blank($editData->group_name)) {
                    $editData->group_name = $editData->display_group_name;
                }

                if (blank($editData->section_name)) {
                    $editData->section_name = $editData->display_section_name;
                }
            }
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

    private function isHourlyPermission(string $permissionName): bool
    {
        return str_starts_with($permissionName, 'hourly_');
    }

    /**
     * صلاحيات الساعات مخفية من الشاشة عند تعطيل الوحدة، فلا تُرسل مع النموذج.
     * نحتفظ بالمسند منها للدور حتى لا يمسحها الحفظ الكامل.
     */
    private function preserveHiddenHourlyPermissions(Role $role, Collection $requestedPermissions, ?int $branchId): Collection
    {
        if (SystemModuleManager::isEnabled('hourly_rental')) {
            return $requestedPermissions;
        }

        $existingPermissions = $role->permissions()->pluck('name');

        if ($branchId) {
            $existingPermissions = $existingPermissions->merge(
                BranchScopedPermissions::permissionNamesForRoleBranch((int) $role->id, $branchId)
            );
        }

        $hiddenHourlyPermissions = collect($existingPermissions)
            ->filter(fn ($name) => $this->isHourlyPermission((string) $name));

        return $requestedPermissions
            ->reject(fn ($name) => $this->isHourlyPermission((string) $name))
            ->merge($hiddenHourlyPermissions)
            ->unique()
            ->values();
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function resolveSelectedBranchId(Request $request, Collection $branches): ?int
    {
        if ($branches->isEmpty()) {
            return null;
        }

        $requestedBranchId = (int) $request->query('branch_id', 0);

        if ($requestedBranchId > 0 && $branches->contains('id', $requestedBranchId)) {
            return $requestedBranchId;
        }

        return (int) $branches->first()->id;
    }

    private function resolveSubmittedBranchId(Request $request): ?int
    {
        if (!SystemModuleManager::isEnabled('branches')) {
            return null;
        }

        $branchId = (int) $request->input('branch_id', 0);

        if ($branchId <= 0) {
            return null;
        }

        return Branch::query()->whereKey($branchId)->exists() ? $branchId : null;
    }

    private function buildDeveloperPermissionsIndexData(): Collection
    {
        $permissions = Permission::withoutGlobalScopes()
            ->where('guard_name', 'admin')
            ->get();

        $organizedPermissions = $this->sortOrganizedPermissions($this->organizePermissions($permissions));
        $flattenedPermissions = collect();

        foreach ($organizedPermissions as $sectionName => $groups) {
            foreach ($groups as $groupName => $groupPermissions) {
                foreach ($groupPermissions as $permission) {
                    $flattenedPermissions->push(
                        $this->decoratePermissionForDeveloperIndex($permission, $sectionName, $groupName)
                    );
                }
            }
        }

        return $flattenedPermissions->values();
    }

    private function decoratePermissionForDeveloperIndex(
        Permission $permission,
        ?string $resolvedSection = null,
        ?string $resolvedGroup = null
    ): Permission {
        if ($resolvedSection === null || $resolvedGroup === null) {
            ['section' => $resolvedSection, 'group' => $resolvedGroup] = $this->resolvePermissionPlacementForIndex($permission);
        }

        $permission->setAttribute('display_section_name', $resolvedSection);
        $permission->setAttribute('display_group_name', $resolvedGroup);
        $permission->setAttribute('translated_name', $this->getPermissionDisplayName($permission));

        return $permission;
    }

    private function resolvePermissionPlacementForIndex(Permission $permission): array
    {
        $section = $this->normalizePermissionPlacementKey($permission->section_name ?? null);
        $group = $this->normalizePermissionPlacementKey($permission->group_name ?? null);

        if (!$section || !$group) {
            ['section' => $section, 'group' => $group] = $this->resolvePermissionPlacement($permission->name);
        }

        return $this->overridePermissionPlacement($permission->name, $section, $group);
    }

}

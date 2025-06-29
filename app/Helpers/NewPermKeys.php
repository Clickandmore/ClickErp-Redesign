<?php

namespace App;

/**
 * نظام الصلاحيات المحسن للوحة التحكم
 *
 * هذا الملف يحتوي على جميع الصلاحيات المنظمة حسب الأقسام
 * مع نظام تسمية متسق ومرن
 */
class NewPermKeys
{
    // ========================================
    // الصلاحيات الأساسية (Basic Permissions)
    // ========================================
    const VIEW = '_view';
    const CREATE = '_create';
    const EDIT = '_edit';
    const DELETE = '_delete';
    const EXPORT = '_export';
    const IMPORT = '_import';

    // ========================================
    // الصلاحيات المتقدمة (Advanced Permissions)
    // ========================================
    const APPROVE = '_approve';
    const REJECT = '_reject';
    const ASSIGN = '_assign';
    const TRANSFER = '_transfer';
    const PUBLISH = '_publish';
    const UNPUBLISH = '_unpublish';

    // ========================================
    // الصلاحيات الإدارية (Administrative Permissions)
    // ========================================
    const MANAGE = '_manage';
    const CONFIGURE = '_configure';
    const AUDIT = '_audit';
    const BACKUP = '_backup';
    const RESTORE = '_restore';

    // ========================================
    // لوحة التحكم (Dashboard)
    // ========================================
    const DASHBOARD_VIEW = 'dashboard_view';
    const DASHBOARD_ANALYTICS = 'dashboard_analytics';
    const DASHBOARD_EXPORT = 'dashboard_export';
    const DASHBOARD_MANAGE = 'dashboard_manage';

    // ========================================
    // إدارة المستخدمين (Users Management)
    // ========================================
    const USERS_VIEW = 'users_view';
    const USERS_CREATE = 'users_create';
    const USERS_EDIT = 'users_edit';
    const USERS_DELETE = 'users_delete';
    const USERS_EXPORT = 'users_export';
    const USERS_IMPORT = 'users_import';
    const USERS_MANAGE = 'users_manage';
    const USERS_ACTIVATE = 'users_activate';
    const USERS_DEACTIVATE = 'users_deactivate';

    // ========================================
    // إدارة العملاء (Clients Management)
    // ========================================
    const CLIENTS_VIEW = 'clients_view';
    const CLIENTS_CREATE = 'clients_create';
    const CLIENTS_EDIT = 'clients_edit';
    const CLIENTS_DELETE = 'clients_delete';
    const CLIENTS_EXPORT = 'clients_export';
    const CLIENTS_IMPORT = 'clients_import';
    const CLIENTS_MANAGE = 'clients_manage';
    const CLIENTS_ACTIVATE = 'clients_activate';
    const CLIENTS_DEACTIVATE = 'clients_deactivate';

    // ========================================
    // إدارة العقود (Contracts Management)
    // ========================================
    const CONTRACTS_VIEW = 'contracts_view';
    const CONTRACTS_CREATE = 'contracts_create';
    const CONTRACTS_EDIT = 'contracts_edit';
    const CONTRACTS_DELETE = 'contracts_delete';
    const CONTRACTS_APPROVE = 'contracts_approve';
    const CONTRACTS_REJECT = 'contracts_reject';
    const CONTRACTS_ASSIGN = 'contracts_assign';
    const CONTRACTS_EXPORT = 'contracts_export';
    const CONTRACTS_MANAGE = 'contracts_manage';

    // عقود التوظيف (Recruitment Contracts)
    const REC_CONTRACTS_VIEW = 'rec_contracts_view';
    const REC_CONTRACTS_CREATE = 'rec_contracts_create';
    const REC_CONTRACTS_EDIT = 'rec_contracts_edit';
    const REC_CONTRACTS_DELETE = 'rec_contracts_delete';
    const REC_CONTRACTS_APPROVE = 'rec_contracts_approve';
    const REC_CONTRACTS_REJECT = 'rec_contracts_reject';
    const REC_CONTRACTS_MANAGE = 'rec_contracts_manage';

    // عقود الإيجار (Rent Contracts)
    const RENT_CONTRACTS_VIEW = 'rent_contracts_view';
    const RENT_CONTRACTS_CREATE = 'rent_contracts_create';
    const RENT_CONTRACTS_EDIT = 'rent_contracts_edit';
    const RENT_CONTRACTS_DELETE = 'rent_contracts_delete';
    const RENT_CONTRACTS_APPROVE = 'rent_contracts_approve';
    const RENT_CONTRACTS_REJECT = 'rent_contracts_reject';
    const RENT_CONTRACTS_MANAGE = 'rent_contracts_manage';

    // عقود الإيواء (Shelter Contracts)
    const SHELTER_CONTRACTS_VIEW = 'shelter_contracts_view';
    const SHELTER_CONTRACTS_CREATE = 'shelter_contracts_create';
    const SHELTER_CONTRACTS_EDIT = 'shelter_contracts_edit';
    const SHELTER_CONTRACTS_DELETE = 'shelter_contracts_delete';
    const SHELTER_CONTRACTS_APPROVE = 'shelter_contracts_approve';
    const SHELTER_CONTRACTS_REJECT = 'shelter_contracts_reject';
    const SHELTER_CONTRACTS_MANAGE = 'shelter_contracts_manage';

    // عقود الإلغاء (Cancel Contracts)
    const CANCEL_CONTRACTS_VIEW = 'cancel_contracts_view';
    const CANCEL_CONTRACTS_CREATE = 'cancel_contracts_create';
    const CANCEL_CONTRACTS_EDIT = 'cancel_contracts_edit';
    const CANCEL_CONTRACTS_DELETE = 'cancel_contracts_delete';
    const CANCEL_CONTRACTS_APPROVE = 'cancel_contracts_approve';
    const CANCEL_CONTRACTS_REJECT = 'cancel_contracts_reject';
    const CANCEL_CONTRACTS_MANAGE = 'cancel_contracts_manage';

    // ========================================
    // إدارة الطلبات (Orders Management)
    // ========================================
    const ORDERS_VIEW = 'orders_view';
    const ORDERS_CREATE = 'orders_create';
    const ORDERS_EDIT = 'orders_edit';
    const ORDERS_DELETE = 'orders_delete';
    const ORDERS_APPROVE = 'orders_approve';
    const ORDERS_REJECT = 'orders_reject';
    const ORDERS_ASSIGN = 'orders_assign';
    const ORDERS_EXPORT = 'orders_export';
    const ORDERS_MANAGE = 'orders_manage';

    // طلبات الاسترجاع (Return Requests)
    const RETURN_REQUESTS_VIEW = 'return_requests_view';
    const RETURN_REQUESTS_CREATE = 'return_requests_create';
    const RETURN_REQUESTS_EDIT = 'return_requests_edit';
    const RETURN_REQUESTS_DELETE = 'return_requests_delete';
    const RETURN_REQUESTS_APPROVE = 'return_requests_approve';
    const RETURN_REQUESTS_REJECT = 'return_requests_reject';
    const RETURN_REQUESTS_MANAGE = 'return_requests_manage';

    // ========================================
    // إدارة الخادمات (Maids Management)
    // ========================================
    const MAIDS_VIEW = 'maids_view';
    const MAIDS_CREATE = 'maids_create';
    const MAIDS_EDIT = 'maids_edit';
    const MAIDS_DELETE = 'maids_delete';
    const MAIDS_APPROVE = 'maids_approve';
    const MAIDS_REJECT = 'maids_reject';
    const MAIDS_ASSIGN = 'maids_assign';
    const MAIDS_EXPORT = 'maids_export';
    const MAIDS_IMPORT = 'maids_import';
    const MAIDS_MANAGE = 'maids_manage';

    // ========================================
    // إدارة الباقات (Packages Management)
    // ========================================
    const PACKAGES_VIEW = 'packages_view';
    const PACKAGES_CREATE = 'packages_create';
    const PACKAGES_EDIT = 'packages_edit';
    const PACKAGES_DELETE = 'packages_delete';
    const PACKAGES_PUBLISH = 'packages_publish';
    const PACKAGES_UNPUBLISH = 'packages_unpublish';
    const PACKAGES_MANAGE = 'packages_manage';

    // ========================================
    // إدارة المحتوى (Content Management)
    // ========================================
    const CONTENT_VIEW = 'content_view';
    const CONTENT_CREATE = 'content_create';
    const CONTENT_EDIT = 'content_edit';
    const CONTENT_DELETE = 'content_delete';
    const CONTENT_PUBLISH = 'content_publish';
    const CONTENT_UNPUBLISH = 'content_unpublish';
    const CONTENT_MANAGE = 'content_manage';

    // الشرائح (Sliders)
    const SLIDERS_VIEW = 'sliders_view';
    const SLIDERS_CREATE = 'sliders_create';
    const SLIDERS_EDIT = 'sliders_edit';
    const SLIDERS_DELETE = 'sliders_delete';
    const SLIDERS_PUBLISH = 'sliders_publish';
    const SLIDERS_UNPUBLISH = 'sliders_unpublish';
    const SLIDERS_MANAGE = 'sliders_manage';

    // الأسئلة الشائعة (FAQ)
    const FAQ_VIEW = 'faq_view';
    const FAQ_CREATE = 'faq_create';
    const FAQ_EDIT = 'faq_edit';
    const FAQ_DELETE = 'faq_delete';
    const FAQ_PUBLISH = 'faq_publish';
    const FAQ_UNPUBLISH = 'faq_unpublish';
    const FAQ_MANAGE = 'faq_manage';

    // من نحن (About Us)
    const ABOUT_US_VIEW = 'about_us_view';
    const ABOUT_US_CREATE = 'about_us_create';
    const ABOUT_US_EDIT = 'about_us_edit';
    const ABOUT_US_DELETE = 'about_us_delete';
    const ABOUT_US_PUBLISH = 'about_us_publish';
    const ABOUT_US_UNPUBLISH = 'about_us_unpublish';
    const ABOUT_US_MANAGE = 'about_us_manage';

    // ========================================
    // إدارة المنظمة (Organization Management)
    // ========================================
    const ORGANIZATION_VIEW = 'organization_view';
    const ORGANIZATION_MANAGE = 'organization_manage';

    // المسميات الوظيفية (Job Titles)
    const JOB_TITLES_VIEW = 'job_titles_view';
    const JOB_TITLES_CREATE = 'job_titles_create';
    const JOB_TITLES_EDIT = 'job_titles_edit';
    const JOB_TITLES_DELETE = 'job_titles_delete';
    const JOB_TITLES_MANAGE = 'job_titles_manage';

    // الجنسيات (Nationalities)
    const NATIONALITIES_VIEW = 'nationalities_view';
    const NATIONALITIES_CREATE = 'nationalities_create';
    const NATIONALITIES_EDIT = 'nationalities_edit';
    const NATIONALITIES_DELETE = 'nationalities_delete';
    const NATIONALITIES_MANAGE = 'nationalities_manage';

    // الأديان (Religions)
    const RELIGIONS_VIEW = 'religions_view';
    const RELIGIONS_CREATE = 'religions_create';
    const RELIGIONS_EDIT = 'religions_edit';
    const RELIGIONS_DELETE = 'religions_delete';
    const RELIGIONS_MANAGE = 'religions_manage';

    // المهارات (Skills)
    const SKILLS_VIEW = 'skills_view';
    const SKILLS_CREATE = 'skills_create';
    const SKILLS_EDIT = 'skills_edit';
    const SKILLS_DELETE = 'skills_delete';
    const SKILLS_MANAGE = 'skills_manage';

    // عمال الإيواء (Shelter Workers)
    const SHELTER_WORKERS_VIEW = 'shelter_workers_view';
    const SHELTER_WORKERS_CREATE = 'shelter_workers_create';
    const SHELTER_WORKERS_EDIT = 'shelter_workers_edit';
    const SHELTER_WORKERS_DELETE = 'shelter_workers_delete';
    const SHELTER_WORKERS_ASSIGN = 'shelter_workers_assign';
    const SHELTER_WORKERS_MANAGE = 'shelter_workers_manage';

    // أماكن الإيواء (Shelter Places)
    const SHELTER_PLACES_VIEW = 'shelter_places_view';
    const SHELTER_PLACES_CREATE = 'shelter_places_create';
    const SHELTER_PLACES_EDIT = 'shelter_places_edit';
    const SHELTER_PLACES_DELETE = 'shelter_places_delete';
    const SHELTER_PLACES_MANAGE = 'shelter_places_manage';

    // مكاتب التوظيف (Recruitment Offices)
    const REC_OFFICES_VIEW = 'rec_offices_view';
    const REC_OFFICES_CREATE = 'rec_offices_create';
    const REC_OFFICES_EDIT = 'rec_offices_edit';
    const REC_OFFICES_DELETE = 'rec_offices_delete';
    const REC_OFFICES_MANAGE = 'rec_offices_manage';

    // السفر (Travel)
    const TRAVEL_VIEW = 'travel_view';
    const TRAVEL_CREATE = 'travel_create';
    const TRAVEL_EDIT = 'travel_edit';
    const TRAVEL_DELETE = 'travel_delete';
    const TRAVEL_APPROVE = 'travel_approve';
    const TRAVEL_REJECT = 'travel_reject';
    const TRAVEL_MANAGE = 'travel_manage';

    // تحويل الكفالة (Sponsor Transfer)
    const SPONSOR_TRANSFER_VIEW = 'sponsor_transfer_view';
    const SPONSOR_TRANSFER_CREATE = 'sponsor_transfer_create';
    const SPONSOR_TRANSFER_EDIT = 'sponsor_transfer_edit';
    const SPONSOR_TRANSFER_DELETE = 'sponsor_transfer_delete';
    const SPONSOR_TRANSFER_APPROVE = 'sponsor_transfer_approve';
    const SPONSOR_TRANSFER_REJECT = 'sponsor_transfer_reject';
    const SPONSOR_TRANSFER_MANAGE = 'sponsor_transfer_manage';

    // ========================================
    // خدمة العملاء (Customer Service)
    // ========================================
    const CUSTOMER_SERVICE_VIEW = 'customer_service_view';
    const CUSTOMER_SERVICE_MANAGE = 'customer_service_manage';

    // الشكاوى (Complaints)
    const COMPLAINTS_VIEW = 'complaints_view';
    const COMPLAINTS_CREATE = 'complaints_create';
    const COMPLAINTS_EDIT = 'complaints_edit';
    const COMPLAINTS_DELETE = 'complaints_delete';
    const COMPLAINTS_ASSIGN = 'complaints_assign';
    const COMPLAINTS_RESOLVE = 'complaints_resolve';
    const COMPLAINTS_EXPORT = 'complaints_export';
    const COMPLAINTS_MANAGE = 'complaints_manage';

    // التواصل (Contacts)
    const CONTACTS_VIEW = 'contacts_view';
    const CONTACTS_CREATE = 'contacts_create';
    const CONTACTS_EDIT = 'contacts_edit';
    const CONTACTS_DELETE = 'contacts_delete';
    const CONTACTS_ASSIGN = 'contacts_assign';
    const CONTACTS_RESPOND = 'contacts_respond';
    const CONTACTS_EXPORT = 'contacts_export';
    const CONTACTS_MANAGE = 'contacts_manage';

    // ========================================
    // التقارير والإحصائيات (Reports & Statistics)
    // ========================================
    const REPORTS_VIEW = 'reports_view';
    const REPORTS_CREATE = 'reports_create';
    const REPORTS_EDIT = 'reports_edit';
    const REPORTS_DELETE = 'reports_delete';
    const REPORTS_EXPORT = 'reports_export';
    const REPORTS_GENERATE = 'reports_generate';
    const REPORTS_SCHEDULE = 'reports_schedule';
    const REPORTS_MANAGE = 'reports_manage';

    // الإحصائيات (Statistics)
    const STATISTICS_VIEW = 'statistics_view';
    const STATISTICS_CREATE = 'statistics_create';
    const STATISTICS_EDIT = 'statistics_edit';
    const STATISTICS_DELETE = 'statistics_delete';
    const STATISTICS_EXPORT = 'statistics_export';
    const STATISTICS_MANAGE = 'statistics_manage';

    // ========================================
    // إدارة العمليات (Process Management)
    // ========================================
    const PROCESS_VIEW = 'process_view';
    const PROCESS_MANAGE = 'process_manage';

    // الخطوات (Steps)
    const STEPS_VIEW = 'steps_view';
    const STEPS_CREATE = 'steps_create';
    const STEPS_EDIT = 'steps_edit';
    const STEPS_DELETE = 'steps_delete';
    const STEPS_MANAGE = 'steps_manage';

    // الخدمات (Services)
    const SERVICES_VIEW = 'services_view';
    const SERVICES_CREATE = 'services_create';
    const SERVICES_EDIT = 'services_edit';
    const SERVICES_DELETE = 'services_delete';
    const SERVICES_PUBLISH = 'services_publish';
    const SERVICES_UNPUBLISH = 'services_unpublish';
    const SERVICES_MANAGE = 'services_manage';

    // ========================================
    // الاتصالات (Communications)
    // ========================================
    const COMMUNICATIONS_VIEW = 'communications_view';
    const COMMUNICATIONS_MANAGE = 'communications_manage';

    // الإشعارات (Notifications)
    const NOTIFICATIONS_VIEW = 'notifications_view';
    const NOTIFICATIONS_CREATE = 'notifications_create';
    const NOTIFICATIONS_EDIT = 'notifications_edit';
    const NOTIFICATIONS_DELETE = 'notifications_delete';
    const NOTIFICATIONS_SEND = 'notifications_send';
    const NOTIFICATIONS_SCHEDULE = 'notifications_schedule';
    const NOTIFICATIONS_MANAGE = 'notifications_manage';

    // ========================================
    // إدارة النظام (System Management)
    // ========================================
    const SYSTEM_VIEW = 'system_view';
    const SYSTEM_MANAGE = 'system_manage';

    // الإعدادات (Settings)
    const SETTINGS_VIEW = 'settings_view';
    const SETTINGS_EDIT = 'settings_edit';
    const SETTINGS_MANAGE = 'settings_manage';

    // إعدادات العقود (Contract Settings)
    const CONTRACT_SETTINGS_VIEW = 'contract_settings_view';
    const CONTRACT_SETTINGS_EDIT = 'contract_settings_edit';
    const CONTRACT_SETTINGS_MANAGE = 'contract_settings_manage';

    // إدارة الأدوار (Roles Management)
    const ROLES_VIEW = 'roles_view';
    const ROLES_CREATE = 'roles_create';
    const ROLES_EDIT = 'roles_edit';
    const ROLES_DELETE = 'roles_delete';
    const ROLES_ASSIGN_PERMISSIONS = 'roles_assign_permissions';
    const ROLES_MANAGE = 'roles_manage';

    // إدارة الصلاحيات (Permissions Management)
    const PERMISSIONS_VIEW = 'permissions_view';
    const PERMISSIONS_CREATE = 'permissions_create';
    const PERMISSIONS_EDIT = 'permissions_edit';
    const PERMISSIONS_DELETE = 'permissions_delete';
    const PERMISSIONS_ASSIGN = 'permissions_assign';
    const PERMISSIONS_MANAGE = 'permissions_manage';

    // سجل النشاط (Activity Log)
    const ACTIVITY_LOG_VIEW = 'activity_log_view';
    const ACTIVITY_LOG_EXPORT = 'activity_log_export';
    const ACTIVITY_LOG_MANAGE = 'activity_log_manage';

    // النسخ الاحتياطي (Backup)
    const BACKUP_CREATE = 'backup_create';
    const BACKUP_DOWNLOAD = 'backup_download';
    const BACKUP_RESTORE = 'backup_restore';
    const BACKUP_MANAGE = 'backup_manage';

    // ========================================
    // الصلاحيات المركبة (Composite Permissions)
    // ========================================

    /**
     * الحصول على جميع صلاحيات العرض
     */
    public static function getAllViewPermissions(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::USERS_VIEW,
            self::CLIENTS_VIEW,
            self::CONTRACTS_VIEW,
            self::ORDERS_VIEW,
            self::MAIDS_VIEW,
            self::PACKAGES_VIEW,
            self::CONTENT_VIEW,
            self::SLIDERS_VIEW,
            self::FAQ_VIEW,
            self::ABOUT_US_VIEW,
            self::ORGANIZATION_VIEW,
            self::JOB_TITLES_VIEW,
            self::NATIONALITIES_VIEW,
            self::RELIGIONS_VIEW,
            self::SKILLS_VIEW,
            self::SHELTER_WORKERS_VIEW,
            self::SHELTER_PLACES_VIEW,
            self::REC_OFFICES_VIEW,
            self::TRAVEL_VIEW,
            self::SPONSOR_TRANSFER_VIEW,
            self::CUSTOMER_SERVICE_VIEW,
            self::COMPLAINTS_VIEW,
            self::CONTACTS_VIEW,
            self::REPORTS_VIEW,
            self::STATISTICS_VIEW,
            self::PROCESS_VIEW,
            self::STEPS_VIEW,
            self::SERVICES_VIEW,
            self::COMMUNICATIONS_VIEW,
            self::NOTIFICATIONS_VIEW,
            self::SYSTEM_VIEW,
            self::SETTINGS_VIEW,
            self::CONTRACT_SETTINGS_VIEW,
            self::ROLES_VIEW,
            self::PERMISSIONS_VIEW,
            self::ACTIVITY_LOG_VIEW,
        ];
    }

    /**
     * الحصول على جميع صلاحيات الإدارة
     */
    public static function getAllManagePermissions(): array
    {
        return [
            self::DASHBOARD_MANAGE,
            self::USERS_MANAGE,
            self::CLIENTS_MANAGE,
            self::CONTRACTS_MANAGE,
            self::ORDERS_MANAGE,
            self::MAIDS_MANAGE,
            self::PACKAGES_MANAGE,
            self::CONTENT_MANAGE,
            self::SLIDERS_MANAGE,
            self::FAQ_MANAGE,
            self::ABOUT_US_MANAGE,
            self::ORGANIZATION_MANAGE,
            self::JOB_TITLES_MANAGE,
            self::NATIONALITIES_MANAGE,
            self::RELIGIONS_MANAGE,
            self::SKILLS_MANAGE,
            self::SHELTER_WORKERS_MANAGE,
            self::SHELTER_PLACES_MANAGE,
            self::REC_OFFICES_MANAGE,
            self::TRAVEL_MANAGE,
            self::SPONSOR_TRANSFER_MANAGE,
            self::CUSTOMER_SERVICE_MANAGE,
            self::COMPLAINTS_MANAGE,
            self::CONTACTS_MANAGE,
            self::REPORTS_MANAGE,
            self::STATISTICS_MANAGE,
            self::PROCESS_MANAGE,
            self::STEPS_MANAGE,
            self::SERVICES_MANAGE,
            self::COMMUNICATIONS_MANAGE,
            self::NOTIFICATIONS_MANAGE,
            self::SYSTEM_MANAGE,
            self::SETTINGS_MANAGE,
            self::CONTRACT_SETTINGS_MANAGE,
            self::ROLES_MANAGE,
            self::PERMISSIONS_MANAGE,
            self::ACTIVITY_LOG_MANAGE,
            self::BACKUP_MANAGE,
        ];
    }

    /**
     * الحصول على جميع الصلاحيات
     */
    public static function getAllPermissions(): array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        // إزالة الصلاحيات الأساسية (التي تحتوي على _ فقط)
        return array_filter($constants, function($value) {
            return !in_array($value, ['_view', '_create', '_edit', '_delete', '_export', '_import', '_approve', '_reject', '_assign', '_transfer', '_publish', '_unpublish', '_manage', '_configure', '_audit', '_backup', '_restore']);
        });
    }

    /**
     * الحصول على صلاحيات قسم معين
     */
    public static function getSectionPermissions(string $section): array
    {
        $allPermissions = self::getAllPermissions();
        $sectionPermissions = [];

        foreach ($allPermissions as $permission) {
            if (strpos($permission, $section) === 0) {
                $sectionPermissions[] = $permission;
            }
        }

        return $sectionPermissions;
    }
}

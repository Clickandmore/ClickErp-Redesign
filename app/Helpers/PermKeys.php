<?php
namespace App;

class PermKeys
{

    // DO NOT CHANGES VALUES UNLESS YOU KNOW WHAT YOUR DOING
    // SOME FUNCTIONALITY DEPENDS ON THESE VALUES
    // RANDOM CHANGES MIGHT BREAK FUNCTIONALITY OF THE CODE

    // ========================================
    // لوحة التحكم
    // ========================================
    const DASHBOARD_VIEW = 'dashboard_view';
    const DASHBOARD_ANALYTICS = 'dashboard_analytics';
    const DASHBOARD_EXPORT = 'dashboard_export';

    // ========================================
    // العملاء والخادمات
    // ========================================
    const CLIENTS_VIEW = 'clients_view';
    const CLIENTS_CREATE = 'clients_create';
    const CLIENTS_EDIT = 'clients_edit';
    const CLIENTS_DELETE = 'clients_delete';
    const CLIENTS_EXPORT = 'clients_export';

    // إدارة السير الذاتية
    const MAIDS_VIEW = 'maids_view';
    const MAIDS_CREATE = 'maids_create';
    const MAIDS_EDIT = 'maids_edit';
    const MAIDS_DELETE = 'maids_delete';
    const MAIDS_EXPORT = 'maids_export';
    const MAIDS_CONTROL = 'maids_control';

    // ========================================
    // الطلبات
    // ========================================
    const ORDERS_VIEW = 'orders_view';
    const ORDERS_CREATE = 'orders_create';
    const ORDERS_EDIT = 'orders_edit';
    const ORDERS_DELETE = 'orders_delete';
    const ORDERS_EXPORT = 'orders_export';
    const ORDERS_CONTROL = 'orders_control';

    // طلبات الاسترجاع
    const RETURN_REQUEST_VIEW = 'return_request_view';
    const RETURN_REQUEST_CREATE = 'return_request_create';
    const RETURN_REQUEST_EDIT = 'return_request_edit';
    const RETURN_REQUEST_DELETE = 'return_request_delete';
    const RETURN_REQUEST_CONTROL = 'return_request_control';

    // ========================================
    // العقود
    // ========================================
    // عقود التوظيف
    const REC_CONTRACT_VIEW = 'rec_contract_view';
    const REC_CONTRACT_CREATE = 'rec_contract_create';
    const REC_CONTRACT_EDIT = 'rec_contract_edit';
    const REC_CONTRACT_DELETE = 'rec_contract_delete';
    const REC_CONTRACT_CONTROL = 'rec_contract_control';

    // عقود الإلغاء
    const CANCEL_CONTRACT_VIEW = 'cancel_contract_view';
    const CANCEL_CONTRACT_CREATE = 'cancel_contract_create';
    const CANCEL_CONTRACT_EDIT = 'cancel_contract_edit';
    const CANCEL_CONTRACT_DELETE = 'cancel_contract_delete';

    // عقود الإيجار
    const RENT_CONTRACT_VIEW = 'rent_contract_view';
    const RENT_CONTRACT_CREATE = 'rent_contract_create';
    const RENT_CONTRACT_EDIT = 'rent_contract_edit';
    const RENT_CONTRACT_DELETE = 'rent_contract_delete';
    const RENT_CONTRACT_CONTROL = 'rent_contract_control';

    // عقود الإيواء
    const SHELTER_CONTRACT_VIEW = 'shelter_contract_view';
    const SHELTER_CONTRACT_CREATE = 'shelter_contract_create';
    const SHELTER_CONTRACT_EDIT = 'shelter_contract_edit';
    const SHELTER_CONTRACT_DELETE = 'shelter_contract_delete';
    const SHELTER_CONTRACT_CONTROL = 'shelter_contract_control';

    // عمال الإيواء
    const SHELTER_WORKERS_VIEW = 'shelter_workers_view';
    const SHELTER_WORKERS_CREATE = 'shelter_workers_create';
    const SHELTER_WORKERS_EDIT = 'shelter_workers_edit';
    const SHELTER_WORKERS_DELETE = 'shelter_workers_delete';
    const SHELTER_WORKERS_CONTROL = 'shelter_workers_control';

    // أماكن الإيواء
    const SHELTER_PLACES_VIEW = 'shelter_places_view';
    const SHELTER_PLACES_CREATE = 'shelter_places_create';
    const SHELTER_PLACES_EDIT = 'shelter_places_edit';
    const SHELTER_PLACES_DELETE = 'shelter_places_delete';
    const SHELTER_PLACES_CONTROL = 'shelter_places_control';

    // السفر
    const TRAVEL_VIEW = 'travel_view';
    const TRAVEL_CREATE = 'travel_create';
    const TRAVEL_EDIT = 'travel_edit';
    const TRAVEL_DELETE = 'travel_delete';
    const TRAVEL_CONTROL = 'travel_control';

    // مكاتب التوظيف
    const REC_OFFICES_VIEW = 'rec_offices_view';
    const REC_OFFICES_CREATE = 'rec_offices_create';
    const REC_OFFICES_EDIT = 'rec_offices_edit';
    const REC_OFFICES_DELETE = 'rec_offices_delete';
    const REC_OFFICES_CONTROL = 'rec_offices_control';

    // إعدادات العقد
    const CONTRACT_SETTINGS = 'contract_settings';

    // ========================================
    // الشكاوى
    // ========================================
    const COMPLAINT_VIEW = 'complaint_view';
    const COMPLAINT_CREATE = 'complaint_create';
    const COMPLAINT_EDIT = 'complaint_edit';
    const COMPLAINT_DELETE = 'complaint_delete';
    const COMPLAINT_CONTROL = 'complaint_control';

    // أنواع الشكاوى
    const COMPLAINT_TYPE_VIEW = 'complaint_type_view';
    const COMPLAINT_TYPE_CREATE = 'complaint_type_create';
    const COMPLAINT_TYPE_EDIT = 'complaint_type_edit';
    const COMPLAINT_TYPE_DELETE = 'complaint_type_delete';

    // ========================================
    // خدمات إضافية
    // ========================================
    const SPONSOR_TRANSFER_VIEW = 'sponsor_transfer_view';
    const SPONSOR_TRANSFER_CREATE = 'sponsor_transfer_create';
    const SPONSOR_TRANSFER_EDIT = 'sponsor_transfer_edit';
    const SPONSOR_TRANSFER_DELETE = 'sponsor_transfer_delete';
    const SPONSOR_TRANSFER_CONTROL = 'sponsor_transfer_control';

    // ========================================
    // الإعدادات
    // ========================================
    const SETTINGS_VIEW = 'settings_view';
    const SETTINGS_EDIT = 'settings_edit';
    const SETTINGS_CONTROL = 'settings_control';

    // سجل النشاط
    const ACTIVITY_VIEW = 'activity_view';
    const ACTIVITY_EXPORT = 'activity_export';

    // ========================================
    // إعدادات الخادمات
    // ========================================
    const JOB_TITLES_VIEW = 'job_titles_view';
    const JOB_TITLES_CREATE = 'job_titles_create';
    const JOB_TITLES_EDIT = 'job_titles_edit';
    const JOB_TITLES_DELETE = 'job_titles_delete';
    const JOB_TITLES_CONTROL = 'job_titles_control';

    const PACKAGES_VIEW = 'packages_view';
    const PACKAGES_CREATE = 'packages_create';
    const PACKAGES_EDIT = 'packages_edit';
    const PACKAGES_DELETE = 'packages_delete';
    const PACKAGES_CONTROL = 'packages_control';

    const NATIONALITIES_VIEW = 'nationalities_view';
    const NATIONALITIES_CREATE = 'nationalities_create';
    const NATIONALITIES_EDIT = 'nationalities_edit';
    const NATIONALITIES_DELETE = 'nationalities_delete';
    const NATIONALITIES_CONTROL = 'nationalities_control';

    const RELIGIONS_VIEW = 'religions_view';
    const RELIGIONS_CREATE = 'religions_create';
    const RELIGIONS_EDIT = 'religions_edit';
    const RELIGIONS_DELETE = 'religions_delete';
    const RELIGIONS_CONTROL = 'religions_control';

    const SKILLS_VIEW = 'skills_view';
    const SKILLS_CREATE = 'skills_create';
    const SKILLS_EDIT = 'skills_edit';
    const SKILLS_DELETE = 'skills_delete';
    const SKILLS_CONTROL = 'skills_control';

    const COUNTRIES_VIEW = 'countries_view';
    const COUNTRIES_CREATE = 'countries_create';
    const COUNTRIES_EDIT = 'countries_edit';
    const COUNTRIES_DELETE = 'countries_delete';
    const COUNTRIES_CONTROL = 'countries_control';

    // ========================================
    // إدارة المستخدمين
    // ========================================
    const USERS_VIEW = 'users_view';
    const USERS_CREATE = 'users_create';
    const USERS_EDIT = 'users_edit';
    const USERS_DELETE = 'users_delete';
    const USERS_CONTROL = 'users_control';

    const ROLES_VIEW = 'roles_view';
    const ROLES_CREATE = 'roles_create';
    const ROLES_EDIT = 'roles_edit';
    const ROLES_DELETE = 'roles_delete';
    const ROLES_CONTROL = 'roles_control';
    const ROLES_ASSIGN_PERMISSIONS = 'roles_assign_permissions';

    const PERMISSIONS_VIEW = 'permissions_view';
    const PERMISSIONS_CREATE = 'permissions_create';
    const PERMISSIONS_EDIT = 'permissions_edit';
    const PERMISSIONS_DELETE = 'permissions_delete';
    const PERMISSIONS_ASSIGN = 'permissions_assign';

    // ========================================
    // إدارة المحتوى
    // ========================================
    const STATISTICS_VIEW = 'statistics_view';
    const STATISTICS_CREATE = 'statistics_create';
    const STATISTICS_EDIT = 'statistics_edit';
    const STATISTICS_DELETE = 'statistics_delete';
    const STATISTICS_CONTROL = 'statistics_control';

    const NOTIFICATIONS_VIEW = 'notifications_view';
    const NOTIFICATIONS_CREATE = 'notifications_create';
    const NOTIFICATIONS_EDIT = 'notifications_edit';
    const NOTIFICATIONS_DELETE = 'notifications_delete';
    const NOTIFICATIONS_CONTROL = 'notifications_control';

    const SLIDERS_VIEW = 'sliders_view';
    const SLIDERS_CREATE = 'sliders_create';
    const SLIDERS_EDIT = 'sliders_edit';
    const SLIDERS_DELETE = 'sliders_delete';
    const SLIDERS_CONTROL = 'sliders_control';

    const FAQ_VIEW = 'faq_view';
    const FAQ_CREATE = 'faq_create';
    const FAQ_EDIT = 'faq_edit';
    const FAQ_DELETE = 'faq_delete';
    const FAQ_CONTROL = 'faq_control';

    const ABOUT_US_VIEW = 'about_us_view';
    const ABOUT_US_CREATE = 'about_us_create';
    const ABOUT_US_EDIT = 'about_us_edit';
    const ABOUT_US_DELETE = 'about_us_delete';
    const ABOUT_US_CONTROL = 'about_us_control';

    const CONTACT_VIEW = 'contact_view';
    const CONTACT_CREATE = 'contact_create';
    const CONTACT_EDIT = 'contact_edit';
    const CONTACT_DELETE = 'contact_delete';
    const CONTACT_CONTROL = 'contact_control';

    const STEPS_VIEW = 'steps_view';
    const STEPS_CREATE = 'steps_create';
    const STEPS_EDIT = 'steps_edit';
    const STEPS_DELETE = 'steps_delete';
    const STEPS_CONTROL = 'steps_control';

    const SERVICES_VIEW = 'services_view';
    const SERVICES_CREATE = 'services_create';
    const SERVICES_EDIT = 'services_edit';
    const SERVICES_DELETE = 'services_delete';
    const SERVICES_CONTROL = 'services_control';

    // ========================================
    // صلاحيات خاصة
    // ========================================
    const FULL_CONTRACT = 'full_contract';
    const SET_PERMS = 'set_perms';
    const DEVELOPERS_PERMS = 'developers_perms';
    const TEST_PERM = 'test_perm';

    // ========================================
    // صلاحيات قديمة (للتوافق مع النظام الحالي)
    // ========================================
    // هذه الصلاحيات موجودة للتوافق مع النظام الحالي
    // يمكن إزالتها لاحقاً بعد التأكد من عدم استخدامها
    const RELEGIONS_VIEW = 'religions_view'; // تم تغييرها إلى RELIGIONS_VIEW
    const RELEGIONS_CONTROL = 'religions_control'; // تم تغييرها إلى RELIGIONS_CONTROL
    const RELEGIONS_DELETE = 'religions_delete'; // تم تغييرها إلى RELIGIONS_DELETE
    const STAT_VIEW = 'statistics_view'; // تم تغييرها إلى STATISTICS_VIEW
    const STAT_CONTROL = 'statistics_control'; // تم تغييرها إلى STATISTICS_CONTROL
    const STAT_DELETE = 'statistics_delete'; // تم تغييرها إلى STATISTICS_DELETE
    const REC_OFFIICES_VIEW = 'rec_offices_view'; // تم تصحيحها إلى REC_OFFICES_VIEW
    const REC_OFFIICES_CONTROL = 'rec_offices_control'; // تم تصحيحها إلى REC_OFFICES_CONTROL
    const REC_OFFIICES_DELETE = 'rec_offices_delete'; // تم تصحيحها إلى REC_OFFICES_DELETE
}

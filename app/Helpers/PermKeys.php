<?php
namespace App;

class PermKeys
{

    // DO NOT CHANGES VALUES UNLESS YOU KNOW WHAT YOUR DOING
    // SOME FUNCTIONALITY DEPENDS ON THESE VALUES
    // RANDOM CHANGES MIGHT BREAK FUNCTIONALITY OF THE CODE
Const PARTENR_CREATE = 'partenr_create';
Const PARTENR_EDIT = 'partenr_edit';
Const PARTENR_DELETE = 'partenr_delete';
Const PARTENR_VIEW = 'partenr_view';
const PARTENR_CONTROL = 'partenr_control';

    // ========================================
    // لوحة التحكم
    // ========================================
    const DASHBOARD_VIEW = 'dashboard_view';
    const DASHBOARD_ANALYTICS = 'dashboard_analytics';
    const DASHBOARD_EXPORT = 'dashboard_export';
    const DASHBOARD_VIEW_STATISTICS = 'dashboard_view_statistics';
    const DASHBOARD_MANAGE = 'dashboard_manage';

    // صلاحيات أقسام الصفحة الرئيسية
    const DASHBOARD_RECRUITMENT_ORDERS_COUNT = 'dashboard_recruitment_orders_count';
    const DASHBOARD_RECRUITMENT_CONTRACTS_COUNT = 'dashboard_recruitment_contracts_count';
    const DASHBOARD_CLIENTS_COUNT = 'dashboard_clients_count';
    const DASHBOARD_CONTACTS_COUNT = 'dashboard_contacts_count';
    const DASHBOARD_CURRENT_ORDERS_COUNT = 'dashboard_current_orders_count';
    const DASHBOARD_CV_COUNT = 'dashboard_cv_count';
    const DASHBOARD_EMPLOYEES_COUNT = 'dashboard_employees_count';
    const DASHBOARD_COMPLETED_CV_COUNT = 'dashboard_completed_cv_count';
    const DASHBOARD_RENTAL_CONTRACTS_COUNT = 'dashboard_rental_contracts_count';
    const DASHBOARD_RENTAL_ORDERS_COUNT = 'dashboard_rental_orders_count';
    const DASHBOARD_SPONSORSHIP_TRANSFERS_COUNT = 'dashboard_sponsorship_transfers_count';
    const DASHBOARD_SPONSORSHIP_TRANSFER_REQUESTS_COUNT = 'dashboard_sponsorship_transfer_requests_count';
    const DASHBOARD_SHELTER_COUNT = 'dashboard_shelter_count';
    const DASHBOARD_ORDERS_STATISTICS = 'dashboard_orders_statistics';
    const DASHBOARD_OPERATIONS_OVERVIEW = 'dashboard_operations_overview';
    const DASHBOARD_CONTRACTS_OVERVIEW = 'dashboard_contracts_overview';
    const DASHBOARD_NATIONALITIES_STATISTICS = 'dashboard_nationalities_statistics';
    const DASHBOARD_OFFICES_STATISTICS = 'dashboard_offices_statistics';
    const DASHBOARD_RECENT_ACTIVITIES = 'dashboard_recent_activities';
    const DASHBOARD_CALENDAR_EVENTS = 'dashboard_calendar_events';
    const DASHBOARD_EMPLOYEE_PERFORMANCE = 'dashboard_employee_performance';
    const DASHBOARD_TOP_CUSTOMER_SERVICES = 'dashboard_top_customer_services';
    const DASHBOARD_ORDERS_TYPES = 'dashboard_orders_types';

    // ========================================
    // إدارة العملاء (Clients Management)
    // ========================================
    const CLIENTS_VIEW = 'clients_view';
    const CLIENTS_CREATE = 'clients_create';
    const CLIENTS_EDIT = 'clients_edit';
    const CLIENTS_DELETE = 'clients_delete';
    const CLIENTS_EXPORT = 'clients_export';
    const CLIENTS_EXPORT_PDF = 'clients_export_pdf';
    const CLIENTS_EXPORT_EXCEL = 'clients_export_excel';
    const CLIENTS_TOGGLE_STATUS = 'clients_toggle_status';
    const CLIENTS_SHOW = 'clients_show';
    const CLIENTS_CREATE_RECRUITMENT = 'clients_create_recruitment';
    const CLIENTS_SETTINGS = 'clients_settings';
    const CLIENTS_VIEW_ALL_BRANCHES = 'clients_view_all_branches';

    // ========================================
    // إدارة السير الذاتية (CV Management)
    // ========================================
    const MAIDS_VIEW = 'maids_view';
    const MAIDS_CREATE = 'maids_create';
    const MAIDS_EDIT = 'maids_edit';
    const MAIDS_DELETE = 'maids_delete';
    const MAIDS_EXPORT = 'maids_export';
    const MAIDS_EXPORT_PDF = 'maids_export_pdf';
    const MAIDS_EXPORT_EXCEL = 'maids_export_excel';
    const MAIDS_SHOW = 'maids_show';
    const MAIDS_TOGGLE_STATUS = 'maids_toggle_status';
    const MAIDS_BULK_VISIBILITY = 'maids_bulk_visibility';
    const MAIDS_TOGGLE_PIN = 'maids_toggle_pin';
    const MAIDS_CONTROL = 'maids_control';
    const MAIDS_RENTAL_VIEW = 'maids_rental_view';
    const MAIDS_BACKOUT_VIEW = 'maids_backout_view';
    const MAIDS_DELETED_VIEW = 'maids_deleted_view';

    // ========================================
    // إدارة الطلبات (Orders Management)
    // ========================================
    // الصلاحيات الأساسية للطلبات
    const ORDERS_VIEW = 'orders_view';
    const ORDERS_CREATE = 'orders_create';
    const ORDERS_EDIT = 'orders_edit';
    const ORDERS_DELETE = 'orders_delete';
    const ORDERS_EXPORT = 'orders_export';

    // صلاحيات تغيير حالة الطلب
    const ORDERS_CHANGE_STATUS = 'orders_change_status';
    const ORDERS_MARK_IN_PROGRESS = 'orders_mark_in_progress';
    const ORDERS_MARK_CANCELLED = 'orders_mark_cancelled';
    const ORDERS_MARK_COMPLETED = 'orders_mark_completed';

    // صلاحيات إدارة العقود
    const ORDERS_ADD_CONTRACT = 'orders_add_contract';
    const ORDERS_VIEW_CONTRACT = 'orders_view_contract';

    // صلاحيات تغيير بيانات الطلب
    const ORDERS_CHANGE_MAID = 'orders_change_maid';
    const ORDERS_CHANGE_RESPONSIBLE = 'orders_change_responsible';

    // صلاحيات التقارير
    const ORDERS_REPORTS = 'orders_reports';
    const ORDERS_ANALYTICS = 'orders_analytics';

    // صلاحيات الإدارة
    const ORDERS_CONTROL = 'orders_control';

    // إدارة الطلبات المالية
    const FINANCIAL_REQUESTS_VIEW = 'financial_requests_view';
    const FINANCIAL_REQUESTS_CREATE = 'financial_requests_create';
    const FINANCIAL_REQUESTS_EDIT = 'financial_requests_edit';
    const FINANCIAL_REQUESTS_DELETE = 'financial_requests_delete';
    const FINANCIAL_REQUESTS_CHANGE_STATUS = 'financial_requests_change_status';
    const FINANCIAL_REQUESTS_ESCALATE = 'financial_requests_escalate';
    const FINANCIAL_REQUESTS_APPROVE = 'financial_requests_approve';
    const FINANCIAL_REQUESTS_REQUEST_CLOSING = 'financial_requests_request_closing';
    const FINANCIAL_REQUESTS_CLOSE = 'financial_requests_close';
    const FINANCIAL_REQUESTS_EXPORT = 'financial_requests_export';
    const FINANCIAL_REQUESTS_CONTROL = 'financial_requests_control';

    // صلاحيات قوائم الطلبات الفرعية في السايدبار
    const PROFESSIONAL_APPLICATION_VIEW = 'professional_application_view';
    const RECRUITMENT_REQUESTS_VIEW = 'recruitment_requests_view';
    const RENEW_CONTRACTS_VIEW = 'renew_contracts_view';
    const RENTAL_ORDERS_VIEW = 'rental_orders_view';
    const SPONSOR_TRANSFER_WEB_REQUESTS_VIEW = 'sponsor_transfer_web_requests_view';

    // ========================================
    // طلبات الاسترجاع (Return Requests)
    // ========================================
    // الصلاحيات الأساسية لطلبات الاسترجاع
    const RETURN_REQUEST_VIEW = 'return_request_view';
    const RETURN_REQUEST_CREATE = 'return_request_create';
    const RETURN_REQUEST_EDIT = 'return_request_edit';
    const RETURN_REQUEST_DELETE = 'return_request_delete';
    const RETURN_REQUEST_EXPORT = 'return_request_export';

    // صلاحيات تغيير حالة طلب الاسترجاع
    const RETURN_REQUEST_CHANGE_STATUS = 'return_request_change_status';
    const RETURN_REQUEST_APPROVE = 'return_request_approve';
    const RETURN_REQUEST_REJECT = 'return_request_reject';

    // صلاحيات التقارير
    const RETURN_REQUEST_REPORTS = 'return_request_reports';

    // صلاحيات الإدارة
    const RETURN_REQUEST_CONTROL = 'return_request_control';

    // ========================================
    // العقود
    // ========================================
    // عقود الاستقدام (الجديدة فقط)
    const REC_CONTRACTS_VIEW = 'rec_contracts_view';
    const REC_CONTRACTS_CREATE = 'rec_contracts_create';
    const REC_CONTRACTS_EDIT = 'rec_contracts_edit';
    const REC_CONTRACTS_DELETE = 'rec_contracts_delete';
    const REC_CONTRACTS_EXPORT = 'rec_contracts_export';
    const REC_CONTRACTS_PRINT = 'rec_contracts_print';
    const REC_CONTRACTS_MANAGE = 'rec_contracts_manage';

    // تفاويض الإنجاز
    const INGAZ_DELEGATIONS_VIEW = 'ingaz_delegations_view';
    const INGAZ_DELEGATIONS_CREATE = 'ingaz_delegations_create';
    const INGAZ_DELEGATIONS_EDIT = 'ingaz_delegations_edit';
    const INGAZ_DELEGATIONS_DELETE = 'ingaz_delegations_delete';
    const INGAZ_DELEGATIONS_PRINT = 'ingaz_delegations_print';
    const INGAZ_DELEGATIONS_CHANGE_STATUS = 'ingaz_delegations_change_status';
    const INGAZ_DELEGATIONS_CONTROL = 'ingaz_delegations_control';

    // فواتير عقود الاستقدام وتفاويض الإنجاز
    const INVOICES_VIEW = 'invoices_view';
    const INVOICES_CREATE = 'invoices_create';
    const INVOICES_EXPORT = 'invoices_export';
    const INVOICES_PRINT = 'invoices_print';
    const INVOICES_CONTROL = 'invoices_control';

    // تحويلات مساند
    const MUSANED_TRANSFERS_VIEW = 'musaned_transfers_view';
    const MUSANED_TRANSFERS_CREATE = 'musaned_transfers_create';
    const MUSANED_TRANSFERS_CONTROL = 'musaned_transfers_control';

    // ========================================
    // الشكاوى
    // ========================================
    const COMPLAINT_VIEW = 'complaint_view';
    const COMPLAINT_CREATE = 'complaint_create';
    const COMPLAINT_EDIT = 'complaint_edit';
    const COMPLAINT_DELETE = 'complaint_delete';
    const COMPLAINT_CONTROL = 'complaint_control';
    const COMPLAINT_RESPONSE = 'complaint_response';

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
    const SETTINGS_CV_TEMPLATE = 'settings_cv_template';
    const MOYASAR_SETTINGS = 'moyasar_settings';

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

    const SOCIAL_STATUS_VIEW = 'social_status_view';
    const SOCIAL_STATUS_CREATE = 'social_status_create';
    const SOCIAL_STATUS_EDIT = 'social_status_edit';
    const SOCIAL_STATUS_DELETE = 'social_status_delete';
    const SOCIAL_STATUS_CONTROL = 'social_status_control';

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

    const BRANCHES_VIEW = 'branches_view';
    const BRANCHES_CREATE = 'branches_create';
    const BRANCHES_EDIT = 'branches_edit';
    const BRANCHES_DELETE = 'branches_delete';
    const BRANCHES_CONTROL = 'branches_control';
    const BRANCHES_VIEW_ALL = 'branches_view_all';

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

    const OFFERS_VIEW = 'offers_view';
    const OFFERS_CREATE = 'offers_create';
    const OFFERS_EDIT = 'offers_edit';
    const OFFERS_DELETE = 'offers_delete';
    const OFFERS_CONTROL = 'offers_control';

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

    const OFFICE_VIEW = 'office_view';
    const OFFICE_CREATE = 'office_create';
    const OFFICE_EDIT = 'office_edit';
    const OFFICE_DELETE = 'office_delete';
    const OFFICE_CONTROL = 'office_control';
    const EXTERNAL_AGENTS_VIEW = 'external_agents_view';
    const EXTERNAL_AGENTS_CREATE = 'external_agents_create';
    const EXTERNAL_AGENTS_EDIT = 'external_agents_edit';
    const EXTERNAL_AGENTS_DELETE = 'external_agents_delete';
    const EXTERNAL_AGENTS_CONTROL = 'external_agents_control';
    const EXTERNAL_AGENTS_MANAGE = 'external_agents_manage';

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

    const WHY_CHOOSE_US_VIEW = 'why_choose_us_view';
    const WHY_CHOOSE_US_CREATE = 'why_choose_us_create';
    const WHY_CHOOSE_US_EDIT = 'why_choose_us_edit';
    const WHY_CHOOSE_US_DELETE = 'why_choose_us_delete';
    const WHY_CHOOSE_US_CONTROL = 'why_choose_us_control';

    const MUSANED_SECTION_VIEW = 'musaned_section_view';
    const MUSANED_SECTION_EDIT = 'musaned_section_edit';
    const MUSANED_SECTION_CONTROL = 'musaned_section_control';

    const HOME_HERO_VIEW = 'home_hero_view';
    const HOME_HERO_CREATE = 'home_hero_create';
    const HOME_HERO_EDIT = 'home_hero_edit';
    const HOME_HERO_DELETE = 'home_hero_delete';
    const HOME_HERO_CONTROL = 'home_hero_control';

    const GUIDELINES_VIEW = 'guidelines_view';
    const GUIDELINES_CREATE = 'guidelines_create';
    const GUIDELINES_EDIT = 'guidelines_edit';
    const GUIDELINES_DELETE = 'guidelines_delete';
    const GUIDELINES_CONTROL = 'guidelines_control';

    const FIND_US_VIEW = 'find_us_view';
    const FIND_US_CREATE = 'find_us_create';
    const FIND_US_EDIT = 'find_us_edit';
    const FIND_US_DELETE = 'find_us_delete';
    const FIND_US_CONTROL = 'find_us_control';

    const RECRUITMENT_REQUIREMENTS_VIEW = 'recruitment_requirements_view';
    const RECRUITMENT_REQUIREMENTS_CREATE = 'recruitment_requirements_create';
    const RECRUITMENT_REQUIREMENTS_EDIT = 'recruitment_requirements_edit';
    const RECRUITMENT_REQUIREMENTS_DELETE = 'recruitment_requirements_delete';
    const RECRUITMENT_REQUIREMENTS_CONTROL = 'recruitment_requirements_control';

    // ========================================
    // صلاحيات خاصة
    // ========================================
    const FULL_CONTRACT = 'full_contract';
    const SET_PERMS = 'set_perms';
    const DEVELOPERS_PERMS = 'developers_perms';
    const TEST_PERM = 'test_perm';

    // ========================================
    // Package Modules Permissions
    // ========================================
    const CRM_MODULE = 'crm_module';
    const HR_MODULE = 'hr_module';
    const FINANCE_MODULE = 'finance_module';

    // ========================================
    // صلاحيات قديمة (للتوافق مع النظام الحالي)
    // ========================================
    // هذه الصلاحيات موجودة للتوافق مع النظام الحالي
    // يمكن إزالتها لاحقاً بعد التأكد من عدم استخدامها
    const STAT_VIEW = 'statistics_view'; // تم تغييرها إلى STATISTICS_VIEW
    const STAT_CONTROL = 'statistics_control'; // تم تغييرها إلى STATISTICS_CONTROL
    const STAT_DELETE = 'statistics_delete'; // تم تغييرها إلى STATISTICS_DELETE
    const REC_OFFICES_VIEW = 'rec_offices_view';
    const REC_OFFICES_CONTROL = 'rec_offices_control';
    const REC_OFFICES_DELETE = 'rec_offices_delete';

    // ========================================
    // صلاحيات إضافية مطلوبة للقائمة الجانبية
    // ========================================
    // إدارة الشكاوى
    const COMPLAINT_TYPES_VIEW = 'complaint_types_view';
    const COMPLAINT_TYPES_CREATE = 'complaint_types_create';
    const COMPLAINT_TYPES_EDIT = 'complaint_types_edit';
    const COMPLAINT_TYPES_DELETE = 'complaint_types_delete';
    const COMPLAINT_TYPES_CONTROL = 'complaint_types_control';

    // إدارة مكاتب التوظيف
    const RECRUITMENT_OFFICES_VIEW = 'recruitment_offices_view';
    const RECRUITMENT_OFFICES_CREATE = 'recruitment_offices_create';
    const RECRUITMENT_OFFICES_EDIT = 'recruitment_offices_edit';
    const RECRUITMENT_OFFICES_DELETE = 'recruitment_offices_delete';
    const RECRUITMENT_OFFICES_CONTROL = 'recruitment_offices_control';

    // ========================================
    // عقود الإيجار (Rent Contracts)
    // ========================================
    const RENT_CONTRACT_VIEW = 'rent_contract_view';
    const RENT_CONTRACT_CREATE = 'rent_contract_create';
    const RENT_CONTRACT_EDIT = 'rent_contract_edit';
    const RENT_CONTRACT_DELETE = 'rent_contract_delete';
    const RENT_CONTRACT_CONTROL = 'rent_contract_control';
    const RENT_CONTRACT_TERMS_VIEW = 'rent_contract_terms_view';
    const RENT_CONTRACT_TERMS_CREATE = 'rent_contract_terms_create';
    const RENT_CONTRACT_TERMS_EDIT = 'rent_contract_terms_edit';
    const RENT_CONTRACT_TERMS_DELETE = 'rent_contract_terms_delete';

    // ========================================
    // إعدادات العقود (Contract Settings)
    // ========================================
    const CONTRACT_SETTINGS = 'contract_settings';

    // ========================================
    // عقود الإيواء (Shelter Contracts)
    // ========================================
    const SHELTER_CONTRACT_VIEW = 'shelter_contract_view';
    const SHELTER_CONTRACT_CREATE = 'shelter_contract_create';
    const SHELTER_CONTRACT_EDIT = 'shelter_contract_edit';
    const SHELTER_CONTRACT_DELETE = 'shelter_contract_delete';
    const SHELTER_CONTRACT_CONTROL = 'shelter_contract_control';
    const SHELTER_CONTRACT_ADDITION_CREATE = 'shelter_contract_addition_create';
    const SHELTER_CONTRACT_INSIDE_VIEW = 'shelter_contract_inside_view';
    const SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW = 'shelter_contract_available_for_transfer_view';
    const SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW = 'shelter_contract_deportation_stage_view';
    const SHELTER_CONTRACT_DEPORTED_VIEW = 'shelter_contract_deported_view';
    const SHELTER_CONTRACT_DEPORTATION_PROCEDURES_VIEW = 'shelter_contract_deportation_procedures_view';
    const SHELTER_CONTRACT_DEPORTATION_PROCEDURES_EDIT = 'shelter_contract_deportation_procedures_edit';
    const SHELTER_CONTRACT_WORK_DESIRE_EDIT = 'shelter_contract_work_desire_edit';
    const SHELTER_CONTRACT_QUICK_TAGS_EDIT = 'shelter_contract_quick_tags_edit';
    const SHELTER_CONTRACT_INSTANT_RECEIPT_EDIT = 'shelter_contract_instant_receipt_edit';
    const SHELTER_CONTRACT_REMOVE_FROM_SHELTER_EDIT = 'shelter_contract_remove_from_shelter_edit';

    // ========================================
    // عمال الإيواء (Shelter Workers)
    // ========================================
    const SHELTER_WORKERS_VIEW = 'shelter_workers_view';
    const SHELTER_WORKERS_CREATE = 'shelter_workers_create';
    const SHELTER_WORKERS_EDIT = 'shelter_workers_edit';
    const SHELTER_WORKERS_DELETE = 'shelter_workers_delete';
    const SHELTER_WORKERS_CONTROL = 'shelter_workers_control';

    // ========================================
    // أماكن الإيواء (Shelter Places)
    // ========================================
    const SHELTER_PLACES_VIEW = 'shelter_places_view';
    const SHELTER_PLACES_CREATE = 'shelter_places_create';
    const SHELTER_PLACES_EDIT = 'shelter_places_edit';
    const SHELTER_PLACES_DELETE = 'shelter_places_delete';
    const SHELTER_PLACES_CONTROL = 'shelter_places_control';

    // ========================================
    // السفر (Travel)
    // ========================================
    const TRAVEL_VIEW = 'travel_view';
    const TRAVEL_CREATE = 'travel_create';
    const TRAVEL_EDIT = 'travel_edit';
    const TRAVEL_DELETE = 'travel_delete';
    const TRAVEL_CONTROL = 'travel_control';


    const RELEGIONS_DELETE = 'relegions_delete';
    const RELEGIONS_CONTROL = 'relegions_control';

    // ========================================
    // نظام العمولات (Commissions)
    // ========================================
    const COMMISSIONS_VIEW        = 'commissions_view';
    const COMMISSIONS_CALCULATE   = 'commissions_calculate';
    const COMMISSIONS_PAY         = 'commissions_pay';
    const COMMISSIONS_SETTINGS    = 'commissions_settings';
}

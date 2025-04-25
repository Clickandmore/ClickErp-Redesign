<?php

return [
    [
        'name' => 'dashboard_view',
        'group_name' => 'dashboard',
        'section_name' => 'general',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'test_perm',
        'group_name' => 'dashboard',
        'section_name' => 'general',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'users_view',
        'group_name' => 'users',
        'section_name' => 'users',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'users_control',
        'group_name' => 'users',
        'section_name' => 'users',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'settings_view',
        'group_name' => 'settings',
        'section_name' => 'settings',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'settings_control',
        'group_name' => 'settings',
        'section_name' => 'settings',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'roles_view',
        'group_name' => 'permissions',
        'section_name' => 'permissions',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'roles_control',
        'group_name' => 'permissions',
        'section_name' => 'permissions',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'set_perms',
        'group_name' => 'permissions',
        'section_name' => 'permissions',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'developers_perms',
        'group_name' => 'permissions',
        'section_name' => 'permissions',
        'guard_name' => 'admin',
    ],
    // [
    //     'name' => 'activity_view',
    //     'group_name' => 'activity_log',
    //     'section_name' => 'settings',
    //     'guard_name' => 'admin',
    // ],

    //******************************** */

    // Clients Permissions
    [
        'name' => 'clients_view',
        'group_name' => 'clients',
        'section_name' => 'users_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'clients_delete',
        'group_name' => 'clients',
        'section_name' => 'users_management',
        'guard_name' => 'admin',
    ],

    // Contracts Permissions
    [
        'name' => 'full_contract',
        'group_name' => 'contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'maids_view',
        'group_name' => 'maids',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'maids_control',
        'group_name' => 'maids',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'maids_delete',
        'group_name' => 'maids',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],

    // Job Titles Permissions
    [
        'name' => 'job_titles_view',
        'group_name' => 'job_titles',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'job_titles_control',
        'group_name' => 'job_titles',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'job_titles_delete',
        'group_name' => 'job_titles',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Packages Permissions
    [
        'name' => 'packages_view',
        'group_name' => 'packages',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'packages_control',
        'group_name' => 'packages',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'packages_delete',
        'group_name' => 'packages',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],

    // Nationalities Permissions
    [
        'name' => 'nationalities_view',
        'group_name' => 'nationalities',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'nationalities_control',
        'group_name' => 'nationalities',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'nationalities_delete',
        'group_name' => 'nationalities',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Religions Permissions
    [
        'name' => 'relegions_view',
        'group_name' => 'religions',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'relegions_control',
        'group_name' => 'religions',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'relegions_delete',
        'group_name' => 'religions',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Orders Permissions
    [
        'name' => 'orders_view',
        'group_name' => 'orders',
        'section_name' => 'order_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'orders_control',
        'group_name' => 'orders',
        'section_name' => 'order_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'orders_delete',
        'group_name' => 'orders',
        'section_name' => 'order_management',
        'guard_name' => 'admin',
    ],

    // Return Request Permissions
    [
        'name' => 'return_request_view',
        'group_name' => 'return_requests',
        'section_name' => 'order_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'return_request_control',
        'group_name' => 'return_requests',
        'section_name' => 'order_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'return_request_delete',
        'group_name' => 'return_requests',
        'section_name' => 'order_management',
        'guard_name' => 'admin',
    ],

    // Rec Contract Permissions
    [
        'name' => 'rec_contract_view',
        'group_name' => 'rec_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'rec_contract_control',
        'group_name' => 'rec_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'rec_contract_delete',
        'group_name' => 'rec_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],

    // Cancel Contract Permissions
    [
        'name' => 'cancel_contract_view',
        'group_name' => 'cancel_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],

    // Rent Contract Permissions
    [
        'name' => 'rent_contract_view',
        'group_name' => 'rent_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'rent_contract_control',
        'group_name' => 'rent_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'rent_contract_delete',
        'group_name' => 'rent_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'contract_settings',
        'group_name' => 'contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],

    // Shelter Contract Permissions
    [
        'name' => 'shelter_contract_view',
        'group_name' => 'shelter_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'shelter_contract_control',
        'group_name' => 'shelter_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'shelter_contract_delete',
        'group_name' => 'shelter_contracts',
        'section_name' => 'contract_management',
        'guard_name' => 'admin',
    ],

    // Shelter Workers Permissions
    [
        'name' => 'shelter_workers_view',
        'group_name' => 'shelter_workers',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'shelter_workers_control',
        'group_name' => 'shelter_workers',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Travel Permissions
    [
        'name' => 'travel_view',
        'group_name' => 'travel',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'travel_control',
        'group_name' => 'travel',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Recruitment Offices Permissions
    [
        'name' => 'rec_offices_view',
        'group_name' => 'rec_offices',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'rec_offices_control',
        'group_name' => 'rec_offices',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'rec_offices_delete',
        'group_name' => 'rec_offices',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Shelter Places Permissions
    [
        'name' => 'shelter_places_view',
        'group_name' => 'shelter_places',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'shelter_places_control',
        'group_name' => 'shelter_places',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'shelter_places_delete',
        'group_name' => 'shelter_places',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Sponsor Transfer Permissions
    [
        'name' => 'sponsor_transfer_view',
        'group_name' => 'sponsor_transfer',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'sponsor_transfer_control',
        'group_name' => 'sponsor_transfer',
        'section_name' => 'organization_management',
        'guard_name' => 'admin',
    ],

    // Notifications Permissions
    [
        'name' => 'notifications_control',
        'group_name' => 'notifications',
        'section_name' => 'communications',
        'guard_name' => 'admin',
    ],

    // Sliders Permissions
    [
        'name' => 'sliders_view',
        'group_name' => 'sliders',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'sliders_control',
        'group_name' => 'sliders',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'sliders_delete',
        'group_name' => 'sliders',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],

    // FAQ Permissions
    [
        'name' => 'faq_view',
        'group_name' => 'faq',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'faq_control',
        'group_name' => 'faq',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'faq_delete',
        'group_name' => 'faq',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],

    // Complaint Permissions
    [
        'name' => 'complaint_view',
        'group_name' => 'complaints',
        'section_name' => 'customer_service',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'complaint_delete',
        'group_name' => 'complaints',
        'section_name' => 'customer_service',
        'guard_name' => 'admin',
    ],

    // About Us Permissions
    [
        'name' => 'about_us_view',
        'group_name' => 'about_us',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'about_us_control',
        'group_name' => 'about_us',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'about_us_delete',
        'group_name' => 'about_us',
        'section_name' => 'content_management',
        'guard_name' => 'admin',
    ],

    // Contact Permissions
    [
        'name' => 'contact_view',
        'group_name' => 'contacts',
        'section_name' => 'customer_service',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'contact_delete',
        'group_name' => 'contacts',
        'section_name' => 'customer_service',
        'guard_name' => 'admin',
    ],

    // Statistics Permissions
    [
        'name' => 'stat_view',
        'group_name' => 'statistics',
        'section_name' => 'reporting',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'stat_control',
        'group_name' => 'statistics',
        'section_name' => 'reporting',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'stat_delete',
        'group_name' => 'statistics',
        'section_name' => 'reporting',
        'guard_name' => 'admin',
    ],

    // Steps Permissions
    [
        'name' => 'steps_view',
        'group_name' => 'steps',
        'section_name' => 'process_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'steps_control',
        'group_name' => 'steps',
        'section_name' => 'process_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'steps_delete',
        'group_name' => 'steps',
        'section_name' => 'process_management',
        'guard_name' => 'admin',
    ],

    // Services Permissions
    [
        'name' => 'services_view',
        'group_name' => 'services',
        'section_name' => 'service_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'services_control',
        'group_name' => 'services',
        'section_name' => 'service_management',
        'guard_name' => 'admin',
    ],
    [
        'name' => 'services_delete',
        'group_name' => 'services',
        'section_name' => 'service_management',
        'guard_name' => 'admin',
    ],

];

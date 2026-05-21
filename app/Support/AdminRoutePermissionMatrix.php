<?php

namespace App\Support;

use App\NewPermKeys;
use App\PermKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminRoutePermissionMatrix
{
    public static function dashboardRoutePermissions(): array
    {
        return [
            PermKeys::DASHBOARD_VIEW,
        ];
    }

    public static function dashboardAccessPermissions(): array
    {
        return [
            PermKeys::DASHBOARD_VIEW,
            PermKeys::DASHBOARD_VIEW_STATISTICS,
            PermKeys::DASHBOARD_ANALYTICS,
            PermKeys::DASHBOARD_MANAGE,
            PermKeys::DASHBOARD_RECRUITMENT_ORDERS_COUNT,
            PermKeys::DASHBOARD_RECRUITMENT_CONTRACTS_COUNT,
            PermKeys::DASHBOARD_CLIENTS_COUNT,
            PermKeys::DASHBOARD_CONTACTS_COUNT,
            PermKeys::DASHBOARD_CURRENT_ORDERS_COUNT,
            PermKeys::DASHBOARD_CV_COUNT,
            PermKeys::DASHBOARD_EMPLOYEES_COUNT,
            PermKeys::DASHBOARD_COMPLETED_CV_COUNT,
            PermKeys::DASHBOARD_RENTAL_CONTRACTS_COUNT,
            PermKeys::DASHBOARD_RENTAL_ORDERS_COUNT,
            PermKeys::DASHBOARD_SPONSORSHIP_TRANSFERS_COUNT,
            PermKeys::DASHBOARD_SPONSORSHIP_TRANSFER_REQUESTS_COUNT,
            PermKeys::DASHBOARD_SHELTER_COUNT,
            PermKeys::DASHBOARD_ORDERS_STATISTICS,
            PermKeys::DASHBOARD_OPERATIONS_OVERVIEW,
            PermKeys::DASHBOARD_CONTRACTS_OVERVIEW,
            PermKeys::DASHBOARD_NATIONALITIES_STATISTICS,
            PermKeys::DASHBOARD_OFFICES_STATISTICS,
            PermKeys::DASHBOARD_RECENT_ACTIVITIES,
            PermKeys::DASHBOARD_CALENDAR_EVENTS,
            PermKeys::DASHBOARD_EMPLOYEE_PERFORMANCE,
            PermKeys::DASHBOARD_TOP_CUSTOMER_SERVICES,
            PermKeys::DASHBOARD_ORDERS_TYPES,
        ];
    }

    public static function primaryRouteNames(): array
    {
        return [
            'dashboard.index',
            'clients.index',
            'maids.index',
            'noneAvailableMaids',
            'maids.deleted_cvs',
            'orders.index',
            'return_requests.index',
            'professional_application.index',
            'recruitment_requests.index',
            'renew-contracts.index',
            'known-service-requests.index',
            'contact.index',
            'RecruitmentContract.index',
            'RecruitmentContract.create',
            'RecruitmentContract.externalOfficeDispatch.index',
            'RentContract.index',
            'RentContract.create',
            'rental-orders.index',
            'rent-contract.settings',
            'rent-contract-terms.index',
            'ingaz-delegations.index',
            'ingaz-delegations.create',
            'invoices.index',
            'invoices.create',
            'musaned-transfers.index',
            'musaned-transfers.create',
            'ShelterContract.index',
            'ShelterContract.create',
            'ShelterContract.availableForTransfer',
            'ShelterContract.deportationStage',
            'ShelterContract.outside',
            'ShelterContract.deported',
            'shelter_workers',
            'shelter-places.index',
            'DeportationTravel.index',
            'travel',
            'complaints.index',
            'complaints.create',
            'complaint-types.index',
            'SponsorshipTransfer.index',
            'SponsorshipTransfer.webRequests',
            'notifications.index',
            'whatsapp.notifications.index',
            'messages.index',
            'settings.section',
            'sliders.index',
            'about-us.index',
            'questions.index',
            'services.index',
            'statistics.index',
            'steps.index',
            'partenrs.index',
            'nationalities.index',
            'job-titles.index',
            'religions.index',
            'skills.index',
            'social-statuses.index',
            'countries.index',
            'cities.index',
            'packages.index',
            'offices.index',
            'dash.settings',
            'dash.roles.index',
            'dash.perms.setPerms',
            'dash.activity.log',
        ];
    }

    public static function firstAccessibleRouteNameForAdmin($admin, array $excludedRouteNames = []): ?string
    {
        if (!$admin) {
            return null;
        }

        foreach (self::primaryRouteNames() as $routeName) {
            if (in_array($routeName, $excludedRouteNames, true) || !Route::has($routeName)) {
                continue;
            }

            $permissions = self::resolve($routeName, request());

            if ($permissions !== [] && self::adminHasAnyPermission($admin, $permissions)) {
                return $routeName;
            }
        }

        return null;
    }

    public static function firstAccessibleUrlForAdmin($admin, array $excludedRouteNames = []): ?string
    {
        $routeName = self::firstAccessibleRouteNameForAdmin($admin, $excludedRouteNames);

        return $routeName ? route($routeName) : null;
    }

    public static function adminHasAnyPermission($admin, array $permissions): bool
    {
        if (!$admin) {
            return false;
        }

        if (method_exists($admin, 'hasAnyPermission') && $admin->hasAnyPermission($permissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function resolve(?string $routeName, ?Request $request = null): array
    {
        if (!$routeName) {
            return [];
        }

        foreach (self::definitions() as $pattern => $resolver) {
            if (!Str::is($pattern, $routeName)) {
                continue;
            }

            $permissions = is_callable($resolver)
                ? $resolver($request, $routeName)
                : $resolver;

            return array_values(array_unique(array_filter((array) $permissions)));
        }

        return [];
    }

    public static function allPermissions(): array
    {
        $permissions = [];

        foreach (self::definitions() as $resolver) {
            if (is_callable($resolver)) {
                continue;
            }

            $permissions = array_merge($permissions, (array) $resolver);
        }

        return array_values(array_unique(array_merge($permissions, [
            PermKeys::ROLES_CREATE,
            PermKeys::ROLES_EDIT,
        ])));
    }

    private static function definitions(): array
    {
        return array_merge(
            self::dashboardDefinitions(),
            self::permissionsDefinitions(),
            self::clientsDefinitions(),
            self::maidsDefinitions(),
            self::ordersDefinitions(),
            self::recruitmentContractsDefinitions(),
            self::rentContractsDefinitions(),
            self::shelterDefinitions(),
            self::travelDefinitions(),
            self::complaintsDefinitions(),
            self::transfersDefinitions(),
            self::settingsDefinitions(),
            self::contentDefinitions(),
            self::referenceDataDefinitions()
        );
    }

    private static function dashboardDefinitions(): array
    {
        return [
            'dashboard.index' => self::dashboardRoutePermissions(),
            'dashboard.professional' => self::dashboardRoutePermissions(),
            'dashboard.old' => self::dashboardRoutePermissions(),
            'dashboard.employees.show' => [
                PermKeys::DASHBOARD_VIEW,
                PermKeys::DASHBOARD_EMPLOYEE_PERFORMANCE,
                PermKeys::DASHBOARD_MANAGE,
                PermKeys::DASHBOARD_ANALYTICS,
            ],
        ];
    }

    private static function permissionsDefinitions(): array
    {
        return [
            'dash.settings' => [PermKeys::SETTINGS_VIEW],
            'dash.settings.announcements' => [PermKeys::SETTINGS_VIEW],
            'dash.settings.store' => [PermKeys::SETTINGS_CONTROL],
            'dash.roles.index' => [PermKeys::ROLES_VIEW],
            'dash.roles.store' => static fn (?Request $request) => filled($request?->input('edit_id'))
                ? [PermKeys::ROLES_EDIT]
                : [PermKeys::ROLES_CREATE],
            'dash.roles.delete' => [PermKeys::ROLES_DELETE],
            'dash.perms.setPerms' => [PermKeys::PERMISSIONS_VIEW],
            'dash.perms.storePerms' => [PermKeys::PERMISSIONS_ASSIGN],
            'dash.developers.perms.index' => [PermKeys::DEVELOPERS_PERMS],
            'dash.developers.perms.store' => [PermKeys::PERMISSIONS_CREATE],
            'dash.developers.perms.toggleStatus' => [PermKeys::DEVELOPERS_PERMS],
            'dash.developers.perms.delete' => [PermKeys::PERMISSIONS_DELETE],
            'dash.activity.log' => [PermKeys::ACTIVITY_VIEW],
        ];
    }

    private static function clientsDefinitions(): array
    {
        return array_merge(
            self::resource('clients', [
                'index' => [PermKeys::CLIENTS_VIEW],
                'show' => [PermKeys::CLIENTS_SHOW, PermKeys::CLIENTS_VIEW],
                'create' => [PermKeys::CLIENTS_CREATE],
                'edit' => [PermKeys::CLIENTS_EDIT],
                'destroy' => [PermKeys::CLIENTS_DELETE],
            ]),
            [
                'clients.export.pdf' => [PermKeys::CLIENTS_EXPORT_PDF, PermKeys::CLIENTS_EXPORT],
                'clients.export.excel' => [PermKeys::CLIENTS_EXPORT_EXCEL, PermKeys::CLIENTS_EXPORT],
                'clients.attachments.store' => [PermKeys::CLIENTS_EDIT],
                'clients.attachments.download' => [PermKeys::CLIENTS_SHOW, PermKeys::CLIENTS_VIEW],
                'clients.attachments.view' => [PermKeys::CLIENTS_SHOW, PermKeys::CLIENTS_VIEW],
                'clients.attachments.download.single' => [PermKeys::CLIENTS_SHOW, PermKeys::CLIENTS_VIEW],
                'clients.attachments.delete' => [PermKeys::CLIENTS_EDIT],
            ]
        );
    }

    private static function maidsDefinitions(): array
    {
        return [
            'maids.index' => [PermKeys::MAIDS_VIEW],
            'maids.create' => [PermKeys::MAIDS_CREATE],
            'maids.rental.index' => [PermKeys::MAIDS_RENTAL_VIEW],
            'maids.attachments.store' => [PermKeys::MAIDS_EDIT],
            'maids.attachments.download' => [PermKeys::MAIDS_VIEW, PermKeys::MAIDS_SHOW, PermKeys::MAIDS_RENTAL_VIEW],
            'maids.attachments.view' => [PermKeys::MAIDS_VIEW, PermKeys::MAIDS_SHOW, PermKeys::MAIDS_RENTAL_VIEW],
            'maids.attachments.download.single' => [PermKeys::MAIDS_VIEW, PermKeys::MAIDS_SHOW, PermKeys::MAIDS_RENTAL_VIEW],
            'maids.attachments.delete' => [PermKeys::MAIDS_EDIT],
            'noneAvailableMaids' => [PermKeys::MAIDS_BACKOUT_VIEW],
            'maids.deleted_cvs' => [PermKeys::MAIDS_DELETED_VIEW],
        ];
    }

    private static function ordersDefinitions(): array
    {
        return array_merge(
            self::resource('orders', [
                'index' => [PermKeys::ORDERS_VIEW],
                'show' => [PermKeys::ORDERS_VIEW],
                'create' => [PermKeys::ORDERS_CREATE],
                'edit' => [PermKeys::ORDERS_EDIT],
                'destroy' => [PermKeys::ORDERS_DELETE],
            ]),
            self::resource('return_requests', [
                'index' => [PermKeys::RETURN_REQUEST_VIEW],
                'show' => [PermKeys::RETURN_REQUEST_VIEW],
                'create' => [PermKeys::RETURN_REQUEST_CREATE],
                'edit' => [PermKeys::RETURN_REQUEST_EDIT, PermKeys::RETURN_REQUEST_CONTROL],
                'destroy' => [PermKeys::RETURN_REQUEST_DELETE, PermKeys::RETURN_REQUEST_CONTROL],
            ]),
            [
                'orders.current' => [PermKeys::ORDERS_VIEW],
                'orders.ended' => [PermKeys::ORDERS_VIEW],
                'orders.completed' => [PermKeys::ORDERS_VIEW],
                'orders.export.pdf' => [PermKeys::ORDERS_EXPORT],
                'orders.export.excel' => [PermKeys::ORDERS_EXPORT],
                'orders.completeOrder' => [PermKeys::ORDERS_EDIT],
                'orders.quickRecruitment' => [PermKeys::ORDERS_CREATE],
                'orders.restore' => [PermKeys::ORDERS_DELETE],
                'orders.changeMaid' => [PermKeys::ORDERS_CHANGE_MAID, PermKeys::ORDERS_EDIT],
                'orders.changeMaidById' => [PermKeys::ORDERS_CHANGE_MAID, PermKeys::ORDERS_EDIT],
                'orders.changeResponsible' => [PermKeys::ORDERS_CHANGE_RESPONSIBLE, PermKeys::ORDERS_EDIT],
                'orders.changeResponsibleById' => [PermKeys::ORDERS_CHANGE_RESPONSIBLE, PermKeys::ORDERS_EDIT],
                'orders.changeStatus' => [PermKeys::ORDERS_CHANGE_STATUS, PermKeys::ORDERS_EDIT],
                'orders.refuseReturn' => [PermKeys::ORDERS_EDIT],
                'showSingleOrder' => [PermKeys::ORDERS_VIEW],
                'showDataOfOrder' => [PermKeys::ORDERS_VIEW],
                'showDataOfMaid' => [PermKeys::MAIDS_VIEW],
                'showUserFromContract' => [PermKeys::CLIENTS_VIEW],
                'order_files.download' => [PermKeys::ORDERS_VIEW],
                'return_requests.restore' => [PermKeys::RETURN_REQUEST_DELETE, PermKeys::RETURN_REQUEST_CONTROL],
                'return_requests.restore_previous_status' => [PermKeys::RETURN_REQUEST_EDIT, PermKeys::RETURN_REQUEST_CONTROL],
                'return_requests.export.pdf' => [PermKeys::RETURN_REQUEST_EXPORT],
                'return_requests.export.excel' => [PermKeys::RETURN_REQUEST_EXPORT],
                'professional_application.index' => [PermKeys::PROFESSIONAL_APPLICATION_VIEW],
                'recruitment_requests.index' => [PermKeys::RECRUITMENT_REQUESTS_VIEW],
                'renew-contracts.index' => [PermKeys::RENEW_CONTRACTS_VIEW],
                'known-service-requests.index' => [PermKeys::ORDERS_VIEW],
                'known-service-requests.show' => [PermKeys::ORDERS_VIEW],
                'known-service-requests.update' => [PermKeys::ORDERS_EDIT],
                'known-service-requests.destroy' => [PermKeys::ORDERS_DELETE],
                'contact.index' => [PermKeys::CONTACT_VIEW],
            ]
        );
    }

    private static function recruitmentContractsDefinitions(): array
    {
        return [
            'RecruitmentContract.index' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.create' => [NewPermKeys::REC_CONTRACTS_CREATE, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.store' => [NewPermKeys::REC_CONTRACTS_CREATE, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.show' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.edit' => [NewPermKeys::REC_CONTRACTS_EDIT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.update' => [NewPermKeys::REC_CONTRACTS_EDIT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.destroy' => [NewPermKeys::REC_CONTRACTS_DELETE, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.statistics' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.export.pdf' => [NewPermKeys::REC_CONTRACTS_EXPORT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.export.excel' => [NewPermKeys::REC_CONTRACTS_EXPORT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'contracts.details' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'contract-return-requests.index' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'contract-return-requests.show' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'contract-return-requests.process' => [NewPermKeys::REC_CONTRACTS_EDIT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.externalOfficeDispatch.index' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.externalOfficeDispatch.edit' => [NewPermKeys::REC_CONTRACTS_EDIT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.externalOfficeDispatch.store' => [NewPermKeys::REC_CONTRACTS_EDIT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.externalOfficeDispatch.bulkStore' => [NewPermKeys::REC_CONTRACTS_EDIT, NewPermKeys::REC_CONTRACTS_MANAGE],
            'RecruitmentContract.externalOfficeDispatch.print' => [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_MANAGE],
            'ingaz-delegations.index' => [
                PermKeys::INGAZ_DELEGATIONS_VIEW,
                PermKeys::INGAZ_DELEGATIONS_CONTROL,
                PermKeys::REC_CONTRACTS_VIEW,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'ingaz-delegations.create' => [
                PermKeys::INGAZ_DELEGATIONS_CREATE,
                PermKeys::INGAZ_DELEGATIONS_CONTROL,
                PermKeys::REC_CONTRACTS_CREATE,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'invoices.index' => [
                PermKeys::INVOICES_VIEW,
                PermKeys::INVOICES_CONTROL,
                PermKeys::REC_CONTRACTS_VIEW,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'invoices.show' => [
                PermKeys::INVOICES_VIEW,
                PermKeys::INVOICES_CONTROL,
                PermKeys::REC_CONTRACTS_VIEW,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'invoices.create' => [
                PermKeys::INVOICES_CREATE,
                PermKeys::INVOICES_CONTROL,
                PermKeys::REC_CONTRACTS_CREATE,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'invoices.store' => [
                PermKeys::INVOICES_CREATE,
                PermKeys::INVOICES_CONTROL,
                PermKeys::REC_CONTRACTS_CREATE,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'invoices.export.*' => [
                PermKeys::INVOICES_EXPORT,
                PermKeys::INVOICES_CONTROL,
                PermKeys::REC_CONTRACTS_EXPORT,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'invoice.download' => [
                PermKeys::INVOICES_PRINT,
                PermKeys::INVOICES_VIEW,
                PermKeys::INVOICES_CONTROL,
                PermKeys::REC_CONTRACTS_PRINT,
                PermKeys::REC_CONTRACTS_VIEW,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'invoices.queue-zatca-sale' => [
                PermKeys::INVOICES_CONTROL,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'musaned-transfers.index' => [
                PermKeys::MUSANED_TRANSFERS_VIEW,
                PermKeys::MUSANED_TRANSFERS_CONTROL,
                PermKeys::REC_CONTRACTS_VIEW,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'musaned-transfers.show' => [
                PermKeys::MUSANED_TRANSFERS_VIEW,
                PermKeys::MUSANED_TRANSFERS_CONTROL,
                PermKeys::REC_CONTRACTS_VIEW,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'musaned-transfers.create' => [
                PermKeys::MUSANED_TRANSFERS_CREATE,
                PermKeys::MUSANED_TRANSFERS_CONTROL,
                PermKeys::REC_CONTRACTS_CREATE,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
            'musaned-transfers.store' => [
                PermKeys::MUSANED_TRANSFERS_CREATE,
                PermKeys::MUSANED_TRANSFERS_CONTROL,
                PermKeys::REC_CONTRACTS_CREATE,
                PermKeys::REC_CONTRACTS_MANAGE,
            ],
        ];
    }

    private static function rentContractsDefinitions(): array
    {
        return [
            'RentContract.index' => [PermKeys::RENT_CONTRACT_VIEW, PermKeys::RENT_CONTRACT_CONTROL],
            'RentContract.create' => [PermKeys::RENT_CONTRACT_CREATE, PermKeys::RENT_CONTRACT_CONTROL],
            'rental-orders.index' => [PermKeys::RENTAL_ORDERS_VIEW, PermKeys::ORDERS_VIEW],
            'rent-contract.settings' => [PermKeys::CONTRACT_SETTINGS],
            'rent-contract.settings.update' => [PermKeys::CONTRACT_SETTINGS],
            'rent-contract-terms.index' => [
                PermKeys::RENT_CONTRACT_TERMS_VIEW,
                PermKeys::RENT_CONTRACT_CONTROL,
                PermKeys::CONTRACT_SETTINGS,
            ],
            'rent-contract-terms.create' => [
                PermKeys::RENT_CONTRACT_TERMS_CREATE,
                PermKeys::RENT_CONTRACT_CREATE,
                PermKeys::CONTRACT_SETTINGS,
            ],
            'rent-contract-terms.store' => [
                PermKeys::RENT_CONTRACT_TERMS_CREATE,
                PermKeys::RENT_CONTRACT_CREATE,
                PermKeys::CONTRACT_SETTINGS,
            ],
            'rent-contract-terms.edit' => [
                PermKeys::RENT_CONTRACT_TERMS_EDIT,
                PermKeys::RENT_CONTRACT_EDIT,
                PermKeys::CONTRACT_SETTINGS,
            ],
            'rent-contract-terms.update' => [
                PermKeys::RENT_CONTRACT_TERMS_EDIT,
                PermKeys::RENT_CONTRACT_EDIT,
                PermKeys::CONTRACT_SETTINGS,
            ],
            'rent-contract-terms.destroy' => [
                PermKeys::RENT_CONTRACT_TERMS_DELETE,
                PermKeys::RENT_CONTRACT_DELETE,
                PermKeys::CONTRACT_SETTINGS,
            ],
        ];
    }

    private static function shelterDefinitions(): array
    {
        return [
            'ShelterContract.index' => [PermKeys::SHELTER_CONTRACT_INSIDE_VIEW, PermKeys::SHELTER_CONTRACT_VIEW, PermKeys::SHELTER_CONTRACT_CONTROL],
            'ShelterContract.create' => [PermKeys::SHELTER_CONTRACT_ADDITION_CREATE, PermKeys::SHELTER_CONTRACT_CREATE, PermKeys::SHELTER_CONTRACT_CONTROL],
            'ShelterContract.availableForTransfer' => [PermKeys::SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW, PermKeys::SHELTER_CONTRACT_VIEW, PermKeys::SHELTER_CONTRACT_CONTROL],
            'ShelterContract.deportationStage' => [PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW, PermKeys::SHELTER_CONTRACT_VIEW, PermKeys::SHELTER_CONTRACT_CONTROL],
            'ShelterContract.outside' => [PermKeys::SHELTER_CONTRACT_INSIDE_VIEW, PermKeys::SHELTER_CONTRACT_VIEW, PermKeys::SHELTER_CONTRACT_CONTROL],
            'ShelterContract.deported' => [PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW, PermKeys::SHELTER_CONTRACT_VIEW, PermKeys::SHELTER_CONTRACT_CONTROL],
            'shelter_workers' => [PermKeys::SHELTER_WORKERS_VIEW, PermKeys::SHELTER_WORKERS_CONTROL],
            'shelter-places.index' => [PermKeys::SHELTER_PLACES_VIEW, PermKeys::SHELTER_PLACES_CONTROL],
        ];
    }

    private static function travelDefinitions(): array
    {
        return [
            'DeportationTravel.index' => [PermKeys::TRAVEL_VIEW, PermKeys::TRAVEL_CONTROL],
            'travel' => [PermKeys::TRAVEL_VIEW, PermKeys::TRAVEL_CONTROL],
        ];
    }

    private static function complaintsDefinitions(): array
    {
        return array_merge(
            self::resource('complaints', [
                'index' => [PermKeys::COMPLAINT_VIEW],
                'show' => [PermKeys::COMPLAINT_VIEW],
                'create' => [PermKeys::COMPLAINT_CREATE],
                'edit' => [PermKeys::COMPLAINT_EDIT],
                'destroy' => [PermKeys::COMPLAINT_DELETE],
            ]),
            self::resource('complaint-types', [
                'index' => [PermKeys::COMPLAINT_TYPE_VIEW],
                'show' => [PermKeys::COMPLAINT_TYPE_VIEW],
                'create' => [PermKeys::COMPLAINT_TYPE_CREATE],
                'edit' => [PermKeys::COMPLAINT_TYPE_EDIT],
                'destroy' => [PermKeys::COMPLAINT_TYPE_DELETE],
            ]),
            [
                'complaints.quick-response' => [PermKeys::COMPLAINT_RESPONSE, PermKeys::COMPLAINT_EDIT],
                'complaints.add-comment' => [PermKeys::COMPLAINT_RESPONSE, PermKeys::COMPLAINT_EDIT],
                'complaints.update-status' => [PermKeys::COMPLAINT_EDIT],
                'complaints.assign' => [PermKeys::COMPLAINT_EDIT],
                'complaints.change-priority' => [PermKeys::COMPLAINT_EDIT],
                'complaints.response' => [PermKeys::COMPLAINT_RESPONSE, PermKeys::COMPLAINT_EDIT],
            ]
        );
    }

    private static function transfersDefinitions(): array
    {
        return array_merge(
            self::resource('SponsorshipTransfer', [
                'index' => [PermKeys::SPONSOR_TRANSFER_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'show' => [PermKeys::SPONSOR_TRANSFER_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'create' => [PermKeys::SPONSOR_TRANSFER_CREATE, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'edit' => [PermKeys::SPONSOR_TRANSFER_EDIT, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'destroy' => [PermKeys::SPONSOR_TRANSFER_DELETE, PermKeys::SPONSOR_TRANSFER_CONTROL],
            ]),
            [
                'SponsorshipTransfer.webRequests' => [PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'SponsorshipTransfer.webRequest.show' => [PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'SponsorshipTransfer.webRequest.cancel' => [PermKeys::SPONSOR_TRANSFER_EDIT, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'SponsorshipTransfer.trial' => [PermKeys::SPONSOR_TRANSFER_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'SponsorshipTransfer.transferred' => [PermKeys::SPONSOR_TRANSFER_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'SponsorshipTransfer.failed' => [PermKeys::SPONSOR_TRANSFER_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'SponsorshipTransfer.trial-details' => [PermKeys::SPONSOR_TRANSFER_VIEW, PermKeys::SPONSOR_TRANSFER_CONTROL],
                'notifications.index' => [PermKeys::NOTIFICATIONS_CONTROL],
                'notifications.store' => [PermKeys::NOTIFICATIONS_CONTROL],
                'whatsapp.notifications.index' => [PermKeys::NOTIFICATIONS_CONTROL],
                'whatsapp.notifications.store' => [PermKeys::NOTIFICATIONS_CONTROL],
                'notifications.settings' => [PermKeys::NOTIFICATIONS_CONTROL],
                'notifications.settings.alerts' => [PermKeys::NOTIFICATIONS_CONTROL],
                'notifications.settings.update' => [PermKeys::NOTIFICATIONS_CONTROL],
                'messages.index' => [PermKeys::NOTIFICATIONS_CONTROL],
            ]
        );
    }

    private static function settingsDefinitions(): array
    {
        return [
            'settings.index' => [PermKeys::SETTINGS_VIEW],
            'settings.section' => [PermKeys::SETTINGS_VIEW],
            'settings.store' => [PermKeys::SETTINGS_CONTROL],
            'settings.update' => [PermKeys::SETTINGS_CONTROL],
            'admin.ingaz.settings.index' => [PermKeys::SETTINGS_VIEW],
            'admin.ingaz.settings.update' => [PermKeys::SETTINGS_CONTROL],
        ];
    }

    private static function contentDefinitions(): array
    {
        return array_merge(
            self::resource('sliders', [
                'index' => [PermKeys::SLIDERS_VIEW],
                'show' => [PermKeys::SLIDERS_VIEW],
                'create' => [PermKeys::SLIDERS_CREATE, PermKeys::SLIDERS_CONTROL],
                'edit' => [PermKeys::SLIDERS_EDIT, PermKeys::SLIDERS_CONTROL],
                'destroy' => [PermKeys::SLIDERS_DELETE, PermKeys::SLIDERS_CONTROL],
            ]),
            self::resource('about-us', [
                'index' => [PermKeys::ABOUT_US_VIEW],
                'show' => [PermKeys::ABOUT_US_VIEW],
                'create' => [PermKeys::ABOUT_US_CREATE, PermKeys::ABOUT_US_CONTROL],
                'edit' => [PermKeys::ABOUT_US_EDIT, PermKeys::ABOUT_US_CONTROL],
                'destroy' => [PermKeys::ABOUT_US_DELETE, PermKeys::ABOUT_US_CONTROL],
            ]),
            self::resource('questions', [
                'index' => [PermKeys::FAQ_VIEW],
                'show' => [PermKeys::FAQ_VIEW],
                'create' => [PermKeys::FAQ_CREATE, PermKeys::FAQ_CONTROL],
                'edit' => [PermKeys::FAQ_EDIT, PermKeys::FAQ_CONTROL],
                'destroy' => [PermKeys::FAQ_DELETE, PermKeys::FAQ_CONTROL],
            ]),
            self::resource('services', [
                'index' => [PermKeys::SERVICES_VIEW],
                'show' => [PermKeys::SERVICES_VIEW],
                'create' => [PermKeys::SERVICES_CREATE, PermKeys::SERVICES_CONTROL],
                'edit' => [PermKeys::SERVICES_EDIT, PermKeys::SERVICES_CONTROL],
                'destroy' => [PermKeys::SERVICES_DELETE, PermKeys::SERVICES_CONTROL],
            ]),
            self::resource('statistics', [
                'index' => [PermKeys::STATISTICS_VIEW, PermKeys::STATISTICS_CONTROL],
                'show' => [PermKeys::STATISTICS_EDIT, PermKeys::STATISTICS_CONTROL],
                'create' => [PermKeys::STATISTICS_CREATE, PermKeys::STATISTICS_CONTROL],
                'edit' => [PermKeys::STATISTICS_EDIT, PermKeys::STATISTICS_CONTROL],
                'destroy' => [PermKeys::STATISTICS_DELETE, PermKeys::STATISTICS_CONTROL],
            ]),
            self::resource('steps', [
                'index' => [PermKeys::STEPS_VIEW],
                'show' => [PermKeys::STEPS_VIEW],
                'create' => [PermKeys::STEPS_CREATE, PermKeys::STEPS_CONTROL],
                'edit' => [PermKeys::STEPS_EDIT, PermKeys::STEPS_CONTROL],
                'destroy' => [PermKeys::STEPS_DELETE, PermKeys::STEPS_CONTROL],
            ]),
            self::resource('partenrs', [
                'index' => [PermKeys::PARTENR_VIEW],
                'show' => [PermKeys::PARTENR_VIEW],
                'create' => [PermKeys::PARTENR_CREATE, PermKeys::PARTENR_CONTROL],
                'edit' => [PermKeys::PARTENR_EDIT, PermKeys::PARTENR_CONTROL],
                'destroy' => [PermKeys::PARTENR_DELETE, PermKeys::PARTENR_CONTROL],
            ])
        );
    }

    private static function referenceDataDefinitions(): array
    {
        return array_merge(
            self::resource('nationalities', [
                'index' => [PermKeys::NATIONALITIES_VIEW],
                'show' => [PermKeys::NATIONALITIES_VIEW],
                'create' => [PermKeys::NATIONALITIES_CREATE, PermKeys::NATIONALITIES_CONTROL],
                'edit' => [PermKeys::NATIONALITIES_EDIT, PermKeys::NATIONALITIES_CONTROL],
                'destroy' => [PermKeys::NATIONALITIES_DELETE, PermKeys::NATIONALITIES_CONTROL],
            ]),
            self::resource('job-titles', [
                'index' => [PermKeys::JOB_TITLES_VIEW],
                'show' => [PermKeys::JOB_TITLES_VIEW],
                'create' => [PermKeys::JOB_TITLES_CREATE, PermKeys::JOB_TITLES_CONTROL],
                'edit' => [PermKeys::JOB_TITLES_EDIT, PermKeys::JOB_TITLES_CONTROL],
                'destroy' => [PermKeys::JOB_TITLES_DELETE, PermKeys::JOB_TITLES_CONTROL],
            ]),
            self::resource('religions', [
                'index' => [PermKeys::RELIGIONS_VIEW],
                'show' => [PermKeys::RELIGIONS_VIEW],
                'create' => [PermKeys::RELIGIONS_CREATE, PermKeys::RELIGIONS_CONTROL],
                'edit' => [PermKeys::RELIGIONS_EDIT, PermKeys::RELIGIONS_CONTROL],
                'destroy' => [PermKeys::RELIGIONS_DELETE, PermKeys::RELIGIONS_CONTROL],
            ]),
            self::resource('skills', [
                'index' => [PermKeys::SKILLS_VIEW],
                'show' => [PermKeys::SKILLS_VIEW],
                'create' => [PermKeys::SKILLS_CREATE, PermKeys::SKILLS_CONTROL],
                'edit' => [PermKeys::SKILLS_EDIT, PermKeys::SKILLS_CONTROL],
                'destroy' => [PermKeys::SKILLS_DELETE, PermKeys::SKILLS_CONTROL],
            ]),
            self::resource('social-statuses', [
                'index' => [PermKeys::SOCIAL_STATUS_VIEW],
                'show' => [PermKeys::SOCIAL_STATUS_VIEW],
                'create' => [PermKeys::SOCIAL_STATUS_CREATE, PermKeys::SOCIAL_STATUS_CONTROL],
                'edit' => [PermKeys::SOCIAL_STATUS_EDIT, PermKeys::SOCIAL_STATUS_CONTROL],
                'destroy' => [PermKeys::SOCIAL_STATUS_DELETE, PermKeys::SOCIAL_STATUS_CONTROL],
            ]),
            self::resource('packages', [
                'index' => [PermKeys::PACKAGES_VIEW],
                'show' => [PermKeys::PACKAGES_VIEW],
                'create' => [PermKeys::PACKAGES_CREATE, PermKeys::PACKAGES_CONTROL],
                'edit' => [PermKeys::PACKAGES_EDIT, PermKeys::PACKAGES_CONTROL],
                'destroy' => [PermKeys::PACKAGES_DELETE, PermKeys::PACKAGES_CONTROL],
            ]),
            self::resource('offices', [
                'index' => [PermKeys::OFFICE_VIEW, PermKeys::OFFICE_CONTROL],
                'show' => [PermKeys::OFFICE_VIEW, PermKeys::OFFICE_CONTROL],
                'create' => [PermKeys::OFFICE_CREATE, PermKeys::OFFICE_CONTROL],
                'edit' => [PermKeys::OFFICE_EDIT, PermKeys::OFFICE_CONTROL],
                'destroy' => [PermKeys::OFFICE_DELETE, PermKeys::OFFICE_CONTROL],
            ]),
            [
                'offices.login-as-agent' => [PermKeys::OFFICE_CONTROL],
            ],
            [
                'countries.index' => [PermKeys::COUNTRIES_VIEW, PermKeys::COUNTRIES_CONTROL],
                'countries.show' => [PermKeys::COUNTRIES_VIEW, PermKeys::COUNTRIES_CONTROL],
                'countries.create' => [PermKeys::COUNTRIES_CREATE, PermKeys::COUNTRIES_CONTROL],
                'countries.store' => [PermKeys::COUNTRIES_CREATE, PermKeys::COUNTRIES_CONTROL],
                'countries.edit' => [PermKeys::COUNTRIES_EDIT, PermKeys::COUNTRIES_CONTROL],
                'countries.update' => [PermKeys::COUNTRIES_EDIT, PermKeys::COUNTRIES_CONTROL],
                'countries.destroy' => [PermKeys::COUNTRIES_DELETE, PermKeys::COUNTRIES_CONTROL],
                'countries.toggle-status' => [PermKeys::COUNTRIES_EDIT, PermKeys::COUNTRIES_CONTROL],
                'countries.update-order' => [PermKeys::COUNTRIES_EDIT, PermKeys::COUNTRIES_CONTROL],
                'cities.index' => [PermKeys::COUNTRIES_VIEW, PermKeys::COUNTRIES_CONTROL],
                'cities.create' => [PermKeys::COUNTRIES_CREATE, PermKeys::COUNTRIES_CONTROL],
                'cities.store' => [PermKeys::COUNTRIES_CREATE, PermKeys::COUNTRIES_CONTROL],
                'cities.edit' => [PermKeys::COUNTRIES_EDIT, PermKeys::COUNTRIES_CONTROL],
                'cities.update' => [PermKeys::COUNTRIES_EDIT, PermKeys::COUNTRIES_CONTROL],
                'cities.destroy' => [PermKeys::COUNTRIES_DELETE, PermKeys::COUNTRIES_CONTROL],
            ]
        );
    }

    private static function resource(string $resource, array $permissions): array
    {
        return array_filter([
            $resource . '.index' => $permissions['index'] ?? null,
            $resource . '.show' => $permissions['show'] ?? $permissions['index'] ?? null,
            $resource . '.create' => $permissions['create'] ?? null,
            $resource . '.store' => $permissions['store'] ?? $permissions['create'] ?? null,
            $resource . '.edit' => $permissions['edit'] ?? null,
            $resource . '.update' => $permissions['update'] ?? $permissions['edit'] ?? null,
            $resource . '.destroy' => $permissions['destroy'] ?? null,
        ], static fn ($value) => $value !== null);
    }
}

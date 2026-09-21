<?php

namespace Tests\Feature\Admin;

use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminRoutePermissionMatrixTest extends TestCase
{
    public function test_every_primary_sidebar_route_has_a_permission_mapping(): void
    {
        foreach (AdminRoutePermissionMatrix::primaryRouteNames() as $routeName) {
            $this->assertNotEmpty(
                AdminRoutePermissionMatrix::resolve($routeName, Request::create('/')),
                "Route [{$routeName}] is missing from the admin permission matrix."
            );
        }
    }

    public function test_roles_store_uses_create_permission_for_new_role_requests(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'dash.roles.store',
            Request::create('/dash/roles/store', 'POST', ['name' => 'Support'])
        );

        $this->assertSame([PermKeys::ROLES_CREATE], $permissions);
    }

    public function test_dashboard_route_requires_dashboard_view_permission_only(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'dashboard.index',
            Request::create('/dashboard')
        );

        $this->assertSame([PermKeys::DASHBOARD_VIEW], $permissions);
    }

    public function test_invoice_creation_is_not_granted_by_recruitment_contract_permissions(): void
    {
        foreach (['invoices.create', 'invoices.store', 'invoices.searchClients', 'SponsorshipTransfer.invoice.create'] as $routeName) {
            $permissions = AdminRoutePermissionMatrix::resolve($routeName, Request::create('/'));

            $this->assertSame(
                [PermKeys::INVOICES_CREATE],
                $permissions,
                "Route [{$routeName}] must use invoice permissions only."
            );
            $this->assertNotContains(PermKeys::REC_CONTRACTS_CREATE, $permissions);
            $this->assertNotContains(PermKeys::REC_CONTRACTS_MANAGE, $permissions);
        }
    }

    public function test_every_invoice_operation_route_has_its_dedicated_permission_gate(): void
    {
        $expectations = [
            'invoices.index' => [PermKeys::INVOICES_VIEW, PermKeys::INVOICES_CONTROL],
            'invoices.show' => [PermKeys::INVOICES_VIEW, PermKeys::INVOICES_CONTROL],
            'invoices.edit' => [PermKeys::INVOICES_CONTROL],
            'invoices.update' => [PermKeys::INVOICES_CONTROL],
            'invoices.destroy' => [PermKeys::INVOICES_CONTROL],
            'invoices.export.excel' => [PermKeys::INVOICES_EXPORT, PermKeys::INVOICES_CONTROL],
            'invoices.export.pdf' => [PermKeys::INVOICES_EXPORT, PermKeys::INVOICES_CONTROL],
            'invoice.download' => [PermKeys::INVOICES_PRINT, PermKeys::INVOICES_CONTROL],
            'invoices.legacy-source-file' => [PermKeys::INVOICES_PRINT, PermKeys::INVOICES_CONTROL],
            'rental.invoice.download' => [PermKeys::INVOICES_PRINT, PermKeys::INVOICES_CONTROL],
            'rental.extension-invoice.download' => [PermKeys::INVOICES_PRINT, PermKeys::INVOICES_CONTROL],
            'invoices.queue-zatca-sale' => [PermKeys::INVOICES_CONTROL],
            'rental-invoices.queue-zatca-sale' => [PermKeys::INVOICES_CONTROL],
            'rental-adjustments.queue-zatca-sale' => [PermKeys::INVOICES_CONTROL],
            'invoices.credit-notes.create' => [PermKeys::INVOICE_CREDIT_NOTES_CREATE, PermKeys::INVOICE_CREDIT_NOTES_CONTROL, PermKeys::INVOICES_CONTROL],
            'rental-invoices.credit-notes.store' => [PermKeys::INVOICE_CREDIT_NOTES_CREATE, PermKeys::INVOICE_CREDIT_NOTES_CONTROL, PermKeys::INVOICES_CONTROL],
            'recruitment-credit-notes.download' => [PermKeys::INVOICE_CREDIT_NOTES_PRINT, PermKeys::INVOICE_CREDIT_NOTES_CONTROL, PermKeys::INVOICES_CONTROL],
            'rental.credit-note.download' => [PermKeys::INVOICE_CREDIT_NOTES_PRINT, PermKeys::INVOICE_CREDIT_NOTES_CONTROL, PermKeys::INVOICES_CONTROL],
        ];

        foreach ($expectations as $routeName => $permissions) {
            $this->assertSame(
                $permissions,
                AdminRoutePermissionMatrix::resolve($routeName, Request::create('/')),
                "Route [{$routeName}] has an incorrect invoice permission gate."
            );
        }
    }

    public function test_roles_store_uses_edit_permission_for_edit_requests(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'dash.roles.store',
            Request::create('/dash/roles/store', 'POST', ['edit_id' => 7, 'name' => 'Support'])
        );

        $this->assertSame([PermKeys::ROLES_EDIT], $permissions);
    }

    public function test_offices_route_uses_external_agents_permissions(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'offices.index',
            Request::create('/admin/offices')
        );

        $this->assertSame([PermKeys::OFFICE_VIEW, PermKeys::OFFICE_CONTROL], $permissions);
    }

    public function test_sponsorship_transfer_workflow_routes_require_control_permission(): void
    {
        $routeNames = [
            'SponsorshipTransfer.approve',
            'SponsorshipTransfer.reject',
            'SponsorshipTransfer.complete',
            'SponsorshipTransfer.confirmTransfer',
            'SponsorshipTransfer.returnToShelter',
            'SponsorshipTransfer.cancelTransfer',
            'SponsorshipTransfer.send-contract',
            'SponsorshipTransfer.sign-contract',
            'SponsorshipTransfer.deliver-maid',
            'SponsorshipTransfer.start-trial',
            'sponsorship-transfer.save-signature',
            'sponsorship-transfer.save-company-signature',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertSame(
                [PermKeys::SPONSOR_TRANSFER_CONTROL],
                AdminRoutePermissionMatrix::resolve($routeName, Request::create('/')),
                "Route [{$routeName}] must require sponsorship-transfer control permission."
            );
        }
    }

    public function test_shelter_instant_receipt_route_accepts_the_dedicated_rental_permission(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'ShelterContract.toggleInstantReceipt',
            Request::create('/admin/ShelterContract/10/toggle-instant-receipt', 'POST')
        );

        $this->assertContains(PermKeys::SHELTER_CONTRACT_RENTAL_INSTANT_RECEIPT_EDIT, $permissions);
        $this->assertContains(PermKeys::SHELTER_CONTRACT_INSTANT_RECEIPT_EDIT, $permissions);
    }

    public function test_musaned_transfer_deletion_uses_its_dedicated_permission(): void
    {
        $this->assertSame(
            [PermKeys::MUSANED_TRANSFERS_DELETE, PermKeys::MUSANED_TRANSFERS_CONTROL],
            AdminRoutePermissionMatrix::resolve(
                'musaned-transfers.destroy',
                Request::create('/admin/musaned-transfers/10', 'DELETE')
            )
        );
    }

    public function test_rental_instant_receipt_permission_is_registered_for_the_permissions_screen(): void
    {
        $permission = collect(config('access_control_permission_registry.permissions'))
            ->firstWhere('name', PermKeys::SHELTER_CONTRACT_RENTAL_INSTANT_RECEIPT_EDIT);

        $this->assertNotNull($permission);
        $this->assertSame('إتاحة عاملات الإيجار للاستلام الفوري', $permission['display_name']);
    }
}

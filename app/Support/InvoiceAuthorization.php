<?php

namespace App\Support;

use App\PermKeys;

final class InvoiceAuthorization
{
    public static function viewPermissions(): array
    {
        return [PermKeys::INVOICES_VIEW, PermKeys::INVOICES_CONTROL];
    }

    public static function createPermissions(): array
    {
        return [PermKeys::INVOICES_CREATE, PermKeys::INVOICES_CREATE_DRAFT];
    }

    public static function transferCreatePermissions(): array
    {
        return [PermKeys::SPONSOR_TRANSFER_CREATE_INVOICE];
    }

    public static function exportPermissions(): array
    {
        return [PermKeys::INVOICES_EXPORT, PermKeys::INVOICES_CONTROL];
    }

    public static function printPermissions(): array
    {
        return [PermKeys::INVOICES_PRINT, PermKeys::INVOICES_CONTROL];
    }

    public static function controlPermissions(): array
    {
        return [PermKeys::INVOICES_CONTROL];
    }

    public static function editLineItemsPermissions(): array
    {
        return [PermKeys::INVOICES_EDIT_LINE_ITEMS, PermKeys::INVOICES_CONTROL];
    }

    public static function editMusanedCommissionPermissions(): array
    {
        return [PermKeys::INVOICES_EDIT_MUSANED_COMMISSION, PermKeys::INVOICES_CONTROL];
    }

    public static function creditNoteViewPermissions(): array
    {
        return [PermKeys::INVOICE_CREDIT_NOTES_VIEW, PermKeys::INVOICE_CREDIT_NOTES_CONTROL, PermKeys::INVOICES_CONTROL];
    }

    public static function creditNoteCreatePermissions(): array
    {
        return [PermKeys::INVOICE_CREDIT_NOTES_CREATE, PermKeys::INVOICE_CREDIT_NOTES_CONTROL, PermKeys::INVOICES_CONTROL];
    }

    public static function creditNotePrintPermissions(): array
    {
        return [PermKeys::INVOICE_CREDIT_NOTES_PRINT, PermKeys::INVOICE_CREDIT_NOTES_CONTROL, PermKeys::INVOICES_CONTROL];
    }

    public static function canView(): bool { return self::canAny(self::viewPermissions()); }
    public static function canCreate(): bool { return self::canAny(self::createPermissions()); }
    public static function canIssueDirectly(): bool { return self::canAny([PermKeys::INVOICES_CREATE]); }
    public static function canViewDrafts(): bool { return self::canAny([PermKeys::INVOICES_DRAFTS_VIEW, PermKeys::INVOICES_APPROVE]); }
    public static function canApprove(): bool { return self::canAny([PermKeys::INVOICES_APPROVE]); }
    public static function canViewSupplierTotalCost(): bool { return self::canAny([PermKeys::INVOICES_VIEW_SUPPLIER_TOTAL_COST]); }
    public static function canCreateTransfer(): bool { return self::canAny(self::transferCreatePermissions()); }
    public static function canExport(): bool { return self::canAny(self::exportPermissions()); }
    public static function canPrint(): bool { return self::canAny(self::printPermissions()); }
    public static function canControl(): bool { return self::canAny(self::controlPermissions()); }
    public static function canEditLineItems(): bool { return self::canAny(self::editLineItemsPermissions()); }
    public static function canEditMusanedCommission(): bool { return self::canAny(self::editMusanedCommissionPermissions()); }
    public static function canViewCreditNotes(): bool { return self::canAny(self::creditNoteViewPermissions()); }
    public static function canCreateCreditNotes(): bool { return self::canAny(self::creditNoteCreatePermissions()); }
    public static function canPrintCreditNotes(): bool { return self::canAny(self::creditNotePrintPermissions()); }

    private static function canAny(array $permissions): bool
    {
        return function_exists('AdminGuardHasAnyPerm') && (bool) AdminGuardHasAnyPerm($permissions);
    }
}

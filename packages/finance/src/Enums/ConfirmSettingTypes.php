<?php

namespace Nami\Finance\Enums;

enum ConfirmSettingTypes: string
{
    case SarfBond = "sarf_bond";
    case QabdBond = "qabd_bond";
    case TaxRateExpenditure = "tax_rate_expenditure";
    case TaxRateRevenue = "tax_rate_revenue";
    case CostCenter = "cost_center";
    case AccountTransfer = "account_transfer";
    case BondSarfAccount = "bond_sarf_account";
    case BondQabdAccount = "bond_qabd_account";
    case MusanedAccount = "musaned_account";
    case RevenueTaxAccount = "revenue_tax_account";
    case MusanedIntermediateAccount = "musaned_intermediate_account";
    case MusanedCommissionAccount = "musaned_commission_account";
    case ExpenseTaxAccount = "expense_tax_account";
    case GeneralSarfBond = "general_sarf_bond";
    case BillSales = "bill_sales";
    case GeneralQabdBond = "general_qabd_bond";
    case CostCenterSarfBond = "cost_center_sarf_bond";
    case CostCenterQabdBond = "cost_center_qabd_bond";
    case CostCenterGeneralSarfBond = "cost_center_general_sarf_bond";
    case CostCenterGeneralQabdBond = "cost_center_general_qabd_bond";
    case CostCenterAccountTransfer = "cost_center_account_transfer";

    public function lang()
    {
        return match ($this) {
            ConfirmSettingTypes::SarfBond => __("finance::finance.sarf_bond"),
            ConfirmSettingTypes::QabdBond => __("finance::finance.qabd_bond"),
            ConfirmSettingTypes::BillSales => __("finance::finance.bill-sales"),
            ConfirmSettingTypes::TaxRateExpenditure => __("finance::finance.Tax expense ratio"),
            ConfirmSettingTypes::TaxRateRevenue => __("finance::finance.Tax to revenue ratio"),
            ConfirmSettingTypes::CostCenter => __("finance::finance.fn-cost-centers"),
            ConfirmSettingTypes::AccountTransfer => __("finance::finance.account_transfers"),
            ConfirmSettingTypes::BondSarfAccount => __("finance::finance.bond_sarf_account"),
            ConfirmSettingTypes::BondQabdAccount => __("finance::finance.bond_qabd_account"),
            ConfirmSettingTypes::MusanedAccount => __("finance::finance.musaned_account"),
            ConfirmSettingTypes::RevenueTaxAccount => __("finance::finance.revenue_tax_account"),
            ConfirmSettingTypes::MusanedIntermediateAccount => __("finance::finance.musaned_intermediate_account"),
            ConfirmSettingTypes::MusanedCommissionAccount => __("finance::finance.musaned_commission_account"),
            ConfirmSettingTypes::ExpenseTaxAccount => __("finance::finance.expense_tax_account"),
            ConfirmSettingTypes::GeneralSarfBond => __("finance::finance.general_sarf_bond"),
            ConfirmSettingTypes::GeneralQabdBond => __("finance::finance.general_qabd_bond"),
            ConfirmSettingTypes::CostCenterSarfBond => __("finance::finance.cost_center_sarf_bond"),
            ConfirmSettingTypes::CostCenterQabdBond => __("finance::finance.cost_center_qabd_bond"),
            ConfirmSettingTypes::CostCenterGeneralSarfBond => __("finance::finance.cost_center_general_sarf_bond"),
            ConfirmSettingTypes::CostCenterGeneralQabdBond => __("finance::finance.cost_center_general_qabd_bond"),
            ConfirmSettingTypes::CostCenterAccountTransfer => __("finance::finance.cost_center_account_transfer"),
        };
    }
}

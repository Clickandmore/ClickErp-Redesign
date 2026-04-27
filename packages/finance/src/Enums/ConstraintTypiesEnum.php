<?php
namespace Nami\Finance\Enums;

enum ConstraintTypiesEnum: int{


    case DailyEntry = 1;
    case Sales = 2;
    case SalesReturns = 3;
    case Purchases = 4;
    case BackPurchases = 5;
    case SarfBond = 6;
    case QapdBond = 7;
    case CheckCollection = 8;
    case CheckSarf = 9;
    case FirstBalance = 10;
    case TransferEntry = 11;
    case FreelancerEntry = 12;
    case MarketerEntry = 13;
    case Depreciation = 14; // قيد اهلاك
    case ManualDepreciation = 15; // قيد اهلاك يدوي
    case PrincipleSales = 16; // قيد بيع اصل

    public function lang()
    {
        return match ($this) {
            self::DailyEntry => trans('finance::finance.daily_entry'),
            self::Sales => trans('finance::finance.sales'),
            self::SalesReturns => trans('finance::finance.sales_returns'),
            self::Purchases => trans('finance::finance.purchases'),
            self::BackPurchases => trans('finance::finance.back_purchases'),
            self::SarfBond => trans('finance::finance.sarf_bond'),
            self::QapdBond => trans('finance::finance.qapd_bond'),
            self::CheckCollection => trans('finance::finance.check_collection'),
            self::CheckSarf => trans('finance::finance.check_sarf'),
            self::FirstBalance => trans('finance::finance.first_balance'),
            self::TransferEntry => trans('finance::finance.transfer_entry'),
            self::FreelancerEntry => trans('finance::finance.freelancer_entry'),
            self::MarketerEntry => trans('finance::finance.marketer_entry'),
            self::Depreciation => trans('finance::finance.Depreciation'),
            self::ManualDepreciation => trans('finance::finance.ManualDepreciation'),
            self::PrincipleSales => trans('finance::finance.PrincipleSales'),
        };

    }
    public function route_show()
    {
        return match ($this) {
            self::DailyEntry => 'finance.constraints.show',
            self::Sales => 'finance.constraints.show',
            self::SalesReturns => 'finance.constraints.show',
            self::Purchases => 'finance.constraints.show',
            self::BackPurchases => 'finance.constraints.show',
            self::SarfBond => 'finance.bonds-sarf.show',
            self::QapdBond =>  'finance.bonds-qabd.show',
            self::CheckCollection =>  'finance.constraints.show',
            self::CheckSarf =>  'finance.constraints.show',
            self::FirstBalance =>  'finance.constraints.show',
            self::TransferEntry =>  'finance.account-transfer.show',
            self::FreelancerEntry =>  'finance.constraints.show',
            self::MarketerEntry =>  'finance.constraints.show',
            self::Depreciation =>  'finance.constraints.show',
            self::ManualDepreciation =>  'finance.constraints.show',
            self::PrincipleSales =>  'finance.constraints.show',
        };

    }
}

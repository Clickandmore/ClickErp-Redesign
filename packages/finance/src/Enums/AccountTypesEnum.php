<?php

namespace Nami\Finance\Enums;

enum AccountTypesEnum: int
{
    case NormalAccount = 1;

    case Sales = 2;
    case SalesReturns = 3;
    case TaxSale = 4;
    case DiscountPermitted = 5;
    case Clients = 6;
    case Client = 7;

    case Purchases = 8 ;
    case BackPurchases = 9 ;
    case TaxPurchases = 10 ;
    case EarnedDiscount = 11;
    case Suppliers = 12;
    case Supplier = 13;

    case Banks = 14;
    case Bank = 15;
    case Boxs = 16;
    case Box = 17;
    case CatchPapers = 18;
    case ExchangePapers = 19;

    case Storages = 20;
    case Storage = 21;

    case Employees = 22;
    case Employee = 23;
    case Sulaf = 24; // سلف
    case Rewards = 25; // مكافئات
    case Penalty = 26; //جزاء
    case Allowances = 27; // بدلات
    case Custody = 28; // عهده
    case Insurances = 29; // تأمينات

    case Drivers = 30;
    case Driver = 31;

    case ElectronicWallets = 32;
    case ElectronicWallet = 33;

    case FreeLancers = 34;
    case FreeLancer = 35;
    case FreeLancerExpenses = 36;

    case Marketers = 37;
    case Marketer = 38;
    case MarketerExpenses = 39;


    case ProjectsSales = 40;

    case BoxsAndBanks = 41;

    case CompanyIncome = 42;

    case GeneralIncome = 43;

    case GeneralOutcome = 44;
    case DepreciationProvision = 45; // مخصص اهلاك
    case DepreciationExpenses = 46; // مصروفات اهلاك
    case CapitalLosses = 47; // خسائر رأسمالية
    case CapitalProfits = 48; // ارباح رأسمالية
    case Issues = 100;
    case Issue = 101;
    case Contracts = 102;
    case Contract = 103;
    case Consulting = 104;
    case OpeningBalances = 105;
    case FixedPrinciple  = 106;
    case CurrentPrinciple = 107;
    case ShortLiabilities = 108;
    case LongLiabilities = 109;
    case PropertyRights = 110;
    case BankName = 111;

    public function lang()
    {
        return match ($this) {
            AccountTypesEnum::NormalAccount => __("finance::finance.NormalAccount"),
            AccountTypesEnum::Sales => __("finance::finance.Sales"),
            AccountTypesEnum::SalesReturns => __("finance::finance.SalesReturns"),
            AccountTypesEnum::TaxSale => __("finance::finance.TaxSale"),
            AccountTypesEnum::DiscountPermitted => __("finance::finance.DiscountPermitted"),
            AccountTypesEnum::Clients => __("finance::finance.Clients"),
            AccountTypesEnum::Client => __("finance::finance.Client"),
            AccountTypesEnum::Purchases => __("finance::finance.Purchases"),
            AccountTypesEnum::BackPurchases => __("finance::finance.BackPurchases"),
            AccountTypesEnum::TaxPurchases => __("finance::finance.TaxPurchases"),
            AccountTypesEnum::EarnedDiscount => __("finance::finance.EarnedDiscount"),
            AccountTypesEnum::Suppliers => __("finance::finance.Suppliers"),
            AccountTypesEnum::Supplier => __("finance::finance.Supplier"),
            AccountTypesEnum::Banks => __("finance::finance.Banks"),
            AccountTypesEnum::Bank => __("finance::finance.Bank"),
            AccountTypesEnum::Boxs => __("finance::finance.Boxs"),
            AccountTypesEnum::Box => __("finance::finance.Box"),
            AccountTypesEnum::CatchPapers => __("finance::finance.CatchPapers"),
            AccountTypesEnum::ExchangePapers => __("finance::finance.ExchangePapers"),
            AccountTypesEnum::Storages => __("finance::finance.Storages"),
            AccountTypesEnum::Storage => __("finance::finance.Storage"),
            AccountTypesEnum::Employees => __("finance::finance.Employees"),
            AccountTypesEnum::Employee => __("finance::finance.Employee"),
            AccountTypesEnum::Sulaf => __("finance::finance.Sulaf"),
            AccountTypesEnum::Rewards => __("finance::finance.Rewards"),
            AccountTypesEnum::Penalty => __("finance::finance.Penalty"),
            AccountTypesEnum::Allowances => __("finance::finance.Allowances"),
            AccountTypesEnum::Custody => __("finance::finance.Custody"),
            AccountTypesEnum::Insurances => __("finance::finance.Insurances"),
            AccountTypesEnum::Drivers => __("finance::finance.Drivers"),
            AccountTypesEnum::Driver => __("finance::finance.Driver"),
            AccountTypesEnum::ElectronicWallets => __("finance::finance.ElectronicWallets"),
            AccountTypesEnum::ElectronicWallet => __("finance::finance.ElectronicWallet"),
            AccountTypesEnum::FreeLancers => __("finance::finance.FreeLancers"),
            AccountTypesEnum::FreeLancer => __("finance::finance.FreeLancer"),
            AccountTypesEnum::FreeLancerExpenses => __("finance::finance.FreeLancerExpenses"),
            AccountTypesEnum::Marketers => __("finance::finance.Marketers"),
            AccountTypesEnum::Marketer => __("finance::finance.Marketer"),
            AccountTypesEnum::MarketerExpenses => __("finance::finance.MarketerExpenses"),
            AccountTypesEnum::ProjectsSales => __("finance::finance.ProjectsSales"),
            AccountTypesEnum::BoxsAndBanks => __("finance::finance.BoxsAndBanks"),
            AccountTypesEnum::CompanyIncome => __("finance::finance.CompanyIncome"),
            AccountTypesEnum::GeneralIncome => __("finance::finance.GeneralIncome"),
            AccountTypesEnum::GeneralOutcome => __("finance::finance.GeneralOutcome"),
            AccountTypesEnum::DepreciationProvision => __("finance::finance.DepreciationProvision"),
            AccountTypesEnum::DepreciationExpenses => __("finance::finance.DepreciationExpenses"),
            AccountTypesEnum::CapitalLosses => __("finance::finance.CapitalLosses"),
            AccountTypesEnum::CapitalProfits => __("finance::finance.CapitalProfits"),
            AccountTypesEnum::Issues => __("finance::finance.Issues"),
            AccountTypesEnum::Issue => __("finance::finance.Issue"),
            AccountTypesEnum::Contracts => __("finance::finance.Contracts"),
            AccountTypesEnum::Contract => __("finance::finance.Contract"),
            AccountTypesEnum::Consulting => __("finance::finance.Consulting"),
            AccountTypesEnum::OpeningBalances => __("finance::finance.OpeningBalances"),
            AccountTypesEnum::FixedPrinciple => __("finance::finance.FixedPrinciple"),
            AccountTypesEnum::CurrentPrinciple => __("finance::finance.CurrentPrinciple"),
            AccountTypesEnum::ShortLiabilities => __("finance::finance.ShortLiabilities"),
            AccountTypesEnum::LongLiabilities => __("finance::finance.LongLiabilities"),
            AccountTypesEnum::PropertyRights => __("finance::finance.PropertyRights"),
            AccountTypesEnum::BankName => __("finance::finance.BankName"),
        };

    }
}

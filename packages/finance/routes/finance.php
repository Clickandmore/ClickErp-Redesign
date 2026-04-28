<?php


use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Nami\Finance\Http\Controllers\AccountBalanceDifferedNormalController;
use Nami\Finance\Http\Controllers\AccountBalancesController;
use Nami\Finance\Http\Controllers\AccountController;
use Nami\Finance\Http\Controllers\AccountCostCenterController;
use Nami\Finance\Http\Controllers\AccountGuideManagementController;
use Nami\Finance\Http\Controllers\AccountStatementClientController;
use Nami\Finance\Http\Controllers\AccountStatementClientPrintController;
use Nami\Finance\Http\Controllers\AccountStatementController;
use Nami\Finance\Http\Controllers\AccountStatementPrintController;
use Nami\Finance\Http\Controllers\AccountStatementSuppliePrintController;
use Nami\Finance\Http\Controllers\AccountStatementSupplierController;
use Nami\Finance\Http\Controllers\AccountTransferConfirmController;
use Nami\Finance\Http\Controllers\AccountTransferController;
use Nami\Finance\Http\Controllers\AccountTransferFileController;
use Nami\Finance\Http\Controllers\AccountTransferPrintController;
use Nami\Finance\Http\Controllers\AddConstraintController;
use Nami\Finance\Http\Controllers\AddConversionOrderController;
use Nami\Finance\Http\Controllers\ArithmeticTransactionsController;
use Nami\Finance\Http\Controllers\AuditBudgetController;
use Nami\Finance\Http\Controllers\AuditBudgetMonthlyController;
use Nami\Finance\Http\Controllers\BackPurchaseController;
use Nami\Finance\Http\Controllers\BackPurchasePrintController;
use Nami\Finance\Http\Controllers\BankAccountController;
use Nami\Finance\Http\Controllers\BankAccountStatementController;
use Nami\Finance\Http\Controllers\BankAccountStatementPrintController;
use Nami\Finance\Http\Controllers\BankController;
use Nami\Finance\Http\Controllers\BillPurchaseController;
use Nami\Finance\Http\Controllers\BillPurchasePrintController;
use Nami\Finance\Http\Controllers\BillSaleAttachmentController;
use Nami\Finance\Http\Controllers\BillSalePrintController;
use Nami\Finance\Http\Controllers\BillSaleReportController;
use Nami\Finance\Http\Controllers\BillSalesController;
use Nami\Finance\Http\Controllers\BondConfirmController;
use Nami\Finance\Http\Controllers\BondPaymentController;
use Nami\Finance\Http\Controllers\BondPrintController;
use Nami\Finance\Http\Controllers\BondQabdController;
use Nami\Finance\Http\Controllers\BondSarfController;
use Nami\Finance\Http\Controllers\BoxController;
use Nami\Finance\Http\Controllers\CashMovementController;
use Nami\Finance\Http\Controllers\CatchCheckController;
use Nami\Finance\Http\Controllers\CategoryController;
use Nami\Finance\Http\Controllers\ChangeYearStatusController;
use Nami\Finance\Http\Controllers\ChildCodeController;
use Nami\Finance\Http\Controllers\ClientBalancesController;
use Nami\Finance\Http\Controllers\ClientCategoryController;
use Nami\Finance\Http\Controllers\ClientController;
use Nami\Finance\Http\Controllers\ClientFilesController;
use Nami\Finance\Http\Controllers\ConfirmBillPurchaseController;
use Nami\Finance\Http\Controllers\ConfirmBillSaleController;
use Nami\Finance\Http\Controllers\ConfirmSettingController;
use Nami\Finance\Http\Controllers\ConstraintController;
use Nami\Finance\Http\Controllers\ConstraintFileController;
use Nami\Finance\Http\Controllers\ConversionController;
use Nami\Finance\Http\Controllers\ConvertPurchaseController;
use Nami\Finance\Http\Controllers\CostCenterController;
use Nami\Finance\Http\Controllers\CostCenterManagementController;
use Nami\Finance\Http\Controllers\CostCenterNotificationController;
use Nami\Finance\Http\Controllers\CreditorAccountController;
use Nami\Finance\Http\Controllers\DebtorAccountController;
use Nami\Finance\Http\Controllers\DoubleAccountController;
use Nami\Finance\Http\Controllers\DoubleAccountStatementController;
use Nami\Finance\Http\Controllers\DoubleAccountStatementPrintController;
use Nami\Finance\Http\Controllers\ExchangeCheckController;
use Nami\Finance\Http\Controllers\FilterItemController;
use Nami\Finance\Http\Controllers\FirstStockController;
use Nami\Finance\Http\Controllers\GeneralBondQabdController;
use Nami\Finance\Http\Controllers\GeneralBondSarfController;
use Nami\Finance\Http\Controllers\GetCodeController;
use Nami\Finance\Http\Controllers\GetItemsStockController;
use Nami\Finance\Http\Controllers\GroupDepreciationController;
use Nami\Finance\Http\Controllers\HomeController;
use Nami\Finance\Http\Controllers\IncomeListController;
use Nami\Finance\Http\Controllers\InventoryController;
use Nami\Finance\Http\Controllers\ItemAmountInStockController;
use Nami\Finance\Http\Controllers\ItemCollectController;
use Nami\Finance\Http\Controllers\ItemController;
use Nami\Finance\Http\Controllers\ItemImportController;
use Nami\Finance\Http\Controllers\ItemPurchaseMovementController;
use Nami\Finance\Http\Controllers\ItemSaleMovementController;
use Nami\Finance\Http\Controllers\ItemTotalSaleController;
use Nami\Finance\Http\Controllers\LoadBladeEditViewController;
use Nami\Finance\Http\Controllers\LoadBladeViewController;
use Nami\Finance\Http\Controllers\ManualDepreciationController;
use Nami\Finance\Http\Controllers\MissingItemController;
use Nami\Finance\Http\Controllers\OpeningBalanceController;
use Nami\Finance\Http\Controllers\PrincipleController;
use Nami\Finance\Http\Controllers\PrincipleImageController;
use Nami\Finance\Http\Controllers\PrincipleReportController;
use Nami\Finance\Http\Controllers\PrincipleSaleController;
use Nami\Finance\Http\Controllers\PrincipleWebsiteController;
use Nami\Finance\Http\Controllers\ProductNumSettingController;
use Nami\Finance\Http\Controllers\PurchaseOrderController;
use Nami\Finance\Http\Controllers\PurchaseOrderPrintController;
use Nami\Finance\Http\Controllers\PurchasesAnalysisController;
use Nami\Finance\Http\Controllers\RefusedAcceptedCatchCheckController;
use Nami\Finance\Http\Controllers\RefusedAcceptedExchangeCheckController;
use Nami\Finance\Http\Controllers\SaleOrderPrintController;
use Nami\Finance\Http\Controllers\SalesAnalysisController;
use Nami\Finance\Http\Controllers\SalesOrderController;
use Nami\Finance\Http\Controllers\SarfMovementController;
use Nami\Finance\Http\Controllers\StockController;
use Nami\Finance\Http\Controllers\StockItemController;
use Nami\Finance\Http\Controllers\StockMovementController;
use Nami\Finance\Http\Controllers\StockSettingController;
use Nami\Finance\Http\Controllers\StoreBalanceController;
use Nami\Finance\Http\Controllers\SupplierBalancesController;
use Nami\Finance\Http\Controllers\SupplierController;
use Nami\Finance\Http\Controllers\SupplierFileController;
use Nami\Finance\Http\Controllers\SupplierTypeController;
use Nami\Finance\Http\Controllers\TaxDeclarationController;
use Nami\Finance\Http\Controllers\TaxSettingController;
use Nami\Finance\Http\Controllers\TazherCheckController;
use Nami\Finance\Http\Controllers\TotalItemPurchaseController;
use Nami\Finance\Http\Controllers\TrailBalanceController;
use Nami\Finance\Http\Controllers\TransferPrincipleController;
use Nami\Finance\Http\Controllers\TransferPurchaseOrderController;
use Nami\Finance\Http\Controllers\TransferSalesOrderController;
use Nami\Finance\Http\Controllers\TransferToSarfMovementController;
use Nami\Finance\Http\Controllers\TransferToStockMovementController;
use Nami\Finance\Http\Controllers\TreasuryAndBankBalancesController;
use Nami\Finance\Http\Controllers\UnitsController;
use Nami\Finance\Http\Controllers\YearController;
use Nami\Finance\Models\CostCenter;

//use Nami\Finance\Http\Controllers\YearController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/user', [UserController::class, 'index']);
Route::get('/finance-package', [HomeController::class, 'index']);


Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ["adminLocale", 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
    Route::group(["middleware" => ['web', 'admin', 'package.route.permission:finance'], "prefix" => "finance", "as" => "finance."], function () {
        Route::resource('home', HomeController::class);
        Route::resource('years', YearController::class);
        Route::resource('year-status', ChangeYearStatusController::class);

        Route::get('constraints/export/{format}', [ConstraintController::class, 'export'])
            ->where('format', 'pdf|excel|xlsx')
            ->name('constraints.export');
        Route::get('constraints/pending', [ConstraintController::class, 'pending'])
            ->name('constraints.pending');
        Route::post('constraints/{id}/approve', [ConstraintController::class, 'approve'])
            ->name('constraints.approve');
        Route::resource('constraints', ConstraintController::class);
        Route::resource('account-transfer', AccountTransferController::class);
        Route::resource('account-transfer-print', AccountTransferPrintController::class);
        Route::resource('account-transfer-confirm', AccountTransferConfirmController::class);
        Route::resource('account-transfer-files', AccountTransferFileController::class);
        Route::resource('constraint-files', ConstraintFileController::class);
        Route::resource('bonds-print', BondPrintController::class);
        Route::resource('bonds-sarf', BondSarfController::class);
        Route::resource('bonds-qabd', BondQabdController::class);
        Route::resource('bonds-confirm', BondConfirmController::class);
        Route::resource('bonds-payment', BondPaymentController::class);

        Route::resource('accounts', AccountController::class);
        Route::resource('cost-centers', CostCenterController::class);
        Route::resource('child-code', ChildCodeController::class);
        Route::resource('double-account', DoubleAccountController::class);
        Route::resource('double-account-statement', DoubleAccountStatementController::class);
        Route::resource('double-account-statement-print', DoubleAccountStatementPrintController::class);
        Route::resource('trail-balance', TrailBalanceController::class);
        Route::resource('opening-balance', OpeningBalanceController::class);
        Route::resource('boxes', BoxController::class);
        Route::resource('principles', PrincipleController::class);
        Route::resource('account-statement', AccountStatementController::class);
        Route::resource('account-statement-print', AccountStatementPrintController::class);
        Route::resource('account-statement-clients', AccountStatementClientController::class);
        Route::resource('account-statement-clients-print', AccountStatementClientPrintController::class);
        Route::resource('account-statement-suppliers', AccountStatementSupplierController::class);
        Route::resource('account-statement-supplier-print', AccountStatementSuppliePrintController::class);
        Route::resource('account-cost-centers', AccountCostCenterController::class);
        Route::resource('cash-movements', CashMovementController::class);
        Route::resource('bank-account-statement', BankAccountStatementController::class);
        Route::resource('bank-account-statement-print', BankAccountStatementPrintController::class);
        Route::resource('exchange-checks', ExchangeCheckController::class);
        Route::resource('catch-checks', CatchCheckController::class);
        Route::resource('exchange-checks-data', RefusedAcceptedExchangeCheckController::class);
        Route::resource('catch-checks-data', RefusedAcceptedCatchCheckController::class);
        Route::resource('tazher-check', TazherCheckController::class);


        Route::resource('confirm-settings', ConfirmSettingController::class);
        Route::resource('audit-budget-supplier', AuditBudgetController::class);
        Route::resource('audit-budget-monthly', AuditBudgetMonthlyController::class);

        Route::get('external-agents', [SupplierController::class, 'externalAgentsIndex'])->name('external-agents.index');
        Route::resource('suppliers', SupplierController::class);
        Route::resource('suppliers_types', SupplierTypeController::class);

        Route::resource('supplier-files', SupplierFileController::class);

        //  ---------------- start of balances routes ------------------------------------
        Route::resource('accounts-balances', AccountBalancesController::class);
        Route::resource('clients-balances', ClientBalancesController::class);
        Route::resource('suppliers-balances', SupplierBalancesController::class);
        Route::resource('treasury-and-banks-balances', TreasuryAndBankBalancesController::class);
        //  ---------------- end of balances routes ------------------------------------

        Route::resource('tax-setting', TaxSettingController::class);

        Route::resource('arithmetic-transactions', ArithmeticTransactionsController::class);

        Route::resource('general-bonds-sarf', GeneralBondSarfController::class);
        Route::resource('general-bonds-qabd', GeneralBondQabdController::class);

        Route::get(
            'accounting-guide-management-export/{format?}',
            [AccountGuideManagementController::class, 'export']
        )->name('accounting-guide-management.export');
        Route::resource('accounting-guide-management', AccountGuideManagementController::class);
        Route::resource('cost-center-management', CostCenterManagementController::class);
        Route::resource('cost-center-notification', CostCenterNotificationController::class);
        Route::resource('add-constraint', AddConstraintController::class);

        Route::resource('banks', BankController::class);
        Route::resource('bank_accounts', BankAccountController::class);

        /* ---------- start of stocks routes ------------- */
        Route::resource('units', UnitsController::class);
        Route::resource('categories', CategoryController::class);

        Route::resource('product-num-settings', ProductNumSettingController::class);
        Route::resource('stocks', StockController::class);

        Route::resource('items', ItemController::class);
        Route::resource('items-import', ItemImportController::class);
        Route::resource('missing-items', MissingItemController::class);
        Route::resource('first-stocks', FirstStockController::class);
        Route::resource('filter-items', FilterItemController::class);
        Route::resource('stock_items', StockItemController::class);
        Route::resource('client-categories', ClientCategoryController::class);
        Route::resource('clients', ClientController::class);
        Route::get('clients-show/{id}', [ClientController::class, 'showClient'])->name('clients-show');

        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::resource('purchase-orders-print', PurchaseOrderPrintController::class);
        Route::resource('load-blade-view', LoadBladeViewController::class);
        // Route::resource('load-blade-edit-view', LoadBladeEditViewController::class);
        Route::get('load-blade-edit-view/{type}/{id}', [LoadBladeEditViewController::class, 'show'])->name('load-blade-edit-view.show');

        Route::resource('get-code-by-category', GetCodeController::class);

        /* ---------- end of stocks routes ------------- */


        Route::resource('stock-settings', StockSettingController::class);
        Route::resource('stock-movement', StockMovementController::class);
        Route::resource('sarf-movement', SarfMovementController::class);

        Route::resource('inventories', InventoryController::class);
        Route::resource('conversions', ConversionController::class);
        Route::resource('get-items-by-stock', GetItemsStockController::class);
        Route::resource('sales-order', SalesOrderController::class);
        Route::resource('sales-order-print', SaleOrderPrintController::class);
        Route::get('sales-order-print/{id}/{withTax}', [SaleOrderPrintController::class, 'show'])->name('sales-order-print-withTax');
        Route::resource('transfer-sales-order', TransferSalesOrderController::class);
        Route::resource('transfer-purchase-order', TransferPurchaseOrderController::class);

        Route::resource('store-balances', StoreBalanceController::class);
        Route::resource('item-purchase-movement', ItemPurchaseMovementController::class);
        Route::resource('item-total-sale', ItemTotalSaleController::class);
        Route::resource('item-sale-movement', ItemSaleMovementController::class);
        Route::resource('total-item-purchases', TotalItemPurchaseController::class);


        Route::resource('item-collects', ItemCollectController::class);
        Route::get('item-collects-details', [ItemCollectController::class, 'getItemComponents'])->name('item-collects.details');

        Route::resource('bill-sales', BillSalesController::class);
        Route::resource('bill-sales-report', BillSaleReportController::class);
        Route::resource('confirm-bill-sales', ConfirmBillSaleController::class);
        Route::resource('confirm-bill-purchase', ConfirmBillPurchaseController::class);
        Route::resource('bill-sales-print', BillSalePrintController::class);
        Route::resource('bill-sales-attachments', BillSaleAttachmentController::class);
        Route::resource('bill-purchases', BillPurchaseController::class);
        Route::resource('back-purchases', BackPurchaseController::class);
        Route::resource('convert-purchases', ConvertPurchaseController::class);
        Route::resource('bill-purchases-print', BillPurchasePrintController::class);
        Route::resource('back-purchases-print', BackPurchasePrintController::class);
        Route::resource('get-item-stock-amount', ItemAmountInStockController::class);

        Route::resource('transfer-to-sarf-movement', TransferToSarfMovementController::class);
        Route::resource('add-conversion-orders', AddConversionOrderController::class);
        Route::resource('transfer-to-stock-movement', TransferToStockMovementController::class);

        Route::resource('principle-websites', PrincipleWebsiteController::class);
        Route::resource('transfer-principle', TransferPrincipleController::class);
        Route::resource('principle-files', PrincipleImageController::class);

        Route::resource('manual-depreciations', ManualDepreciationController::class);
        Route::resource('principle-report', PrincipleReportController::class);
        Route::resource('group-depreciation', GroupDepreciationController::class);
        Route::resource('principle-sales', PrincipleSaleController::class);
        Route::resource('debtor-accounts', DebtorAccountController::class);
        Route::resource('creditor-accounts', CreditorAccountController::class);
        Route::resource('account-balance-differed-normal', AccountBalanceDifferedNormalController::class);

        Route::resource('client-files', ClientFilesController::class);
        Route::resource('income-list', IncomeListController::class);

        Route::resource('sales-analysis', SalesAnalysisController::class);
        Route::resource('purchases-analysis', PurchasesAnalysisController::class);
        Route::resource('tax-declaration', TaxDeclarationController::class);


    });

});

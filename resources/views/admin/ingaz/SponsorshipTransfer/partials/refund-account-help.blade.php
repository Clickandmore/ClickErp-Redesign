{{-- Shared body of applyRefundMethod in the transfer failure dialogs. --}}
const isCash = $('#failureRefundMethod').val() === 'cash';
const branchesEnabled = @json(\App\Support\SystemModuleManager::isEnabled('branches'));
const accountSelect = $('#failureRefundAccount');
$('#failureBankRefundFields').toggle(!isCash);
$('#failureRefundAccountLabel').html((isCash
    ? 'الصندوق الذي سيُصرف منه مبلغ الاسترجاع'
    : 'البنك / حساب الصرف') + ' <span class="text-danger">*</span>');
accountSelect.find('option[value!=""]').each(function() {
    const matches = String($(this).data('cash')) === (isCash ? '1' : '0');
    $(this).prop('hidden', !matches).prop('disabled', !matches);
});
if (accountSelect.find('option:selected').prop('disabled')) {
    accountSelect.val('');
}
const availableAccounts = accountSelect.find('option[value!=""]:not(:disabled)');
if (isCash && availableAccounts.length === 1) {
    accountSelect.val(availableAccounts.first().val());
}
accountSelect.find('option[value=""]').text(isCash
    ? (availableAccounts.length ? 'اختر الخزينة التي ستسلّم المبلغ للعميل' : 'لا يوجد صندوق نقدي متاح')
    : 'اختر الحساب الذي سيصدر عليه سند الصرف');
let help = $('#failureRefundAccountHelp');
if (!help.length) {
    help = $('<div>', { id: 'failureRefundAccountHelp', class: 'form-text mb-2', role: 'status' }).insertAfter(accountSelect);
}
help.toggle(isCash).toggleClass('text-danger', isCash && !availableAccounts.length);
if (isCash) {
    const explanation = branchesEnabled
        ? 'الصندوق هو خزينة النقدية. اختر خزينة الفرع التي ستسلّم المبلغ للعميل؛ سيُسجل عليها سند الصرف.'
        : 'الصندوق هو خزينة النقدية. اختر الخزينة التي ستسلّم المبلغ للعميل؛ سيُسجل عليها سند الصرف.';
    const setup = branchesEnabled
        ? 'لا يوجد صندوق نقدي متاح لهذا الفرع. اطلب من مسؤول الحسابات إضافته من الحسابات ← الصناديق، والتحقق من إتاحته للفرع عند تفعيل تخصيص الحسابات.'
        : 'لا يوجد صندوق نقدي متاح. اطلب من مسؤول الحسابات إضافته من الحسابات ← الصناديق.';
    help.text(availableAccounts.length
        ? explanation + (availableAccounts.length === 1 ? ' تم اختيار الصندوق الوحيد المتاح تلقائيًا.' : '')
        : setup);
}

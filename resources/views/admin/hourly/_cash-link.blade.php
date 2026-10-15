@include('admin.hourly._payment-schedule')
@if(\App\Services\Hourly\CashCollectionService::ready() && AdminGuardHasPerm('hourly_orders_collect'))
<div class="alert alert-info"><a href="{{ route('admin.hourly-cash.index') }}"><i class="fa fa-wallet"></i> العهد النقدية وتوريد مبالغ العاملات والسائقين</a><span class="d-block small">تحصيل العميل وتوريد النقد للشركة عمليتان منفصلتان.</span></div>
@endif

@if(AdminGuardHasPerm('hourly_orders_view') && \Illuminate\Support\Facades\Schema::hasTable('hourly_service_incidents'))
@php($pendingIncidents=\Illuminate\Support\Facades\DB::table('hourly_service_incidents')->where('status','pending')->count())
<div class="alert alert-warning"><a href="{{ route('admin.hourly-incidents.index') }}">بلاغات تعذر تنفيذ الخدمة والمتابعة</a> — {{ $pendingIncidents }} بلاغ بانتظار التحقق. راجع أيضًا الزيارات التي تنتظر اختيار العميل أو العودة.</div>
@endif
@if(AdminGuardHasPerm('hourly_orders_view') && \App\Services\Hourly\ServiceTime::ready())
@php($timeReviews=\Illuminate\Support\Facades\DB::table('hourly_service_time_checks')->whereIn('status',['awaiting_reason','awaiting_admin'])->count())
@if($timeReviews)<div class="alert alert-warning" role="status"><a href="{{ route('admin.hourly-service-time.index') }}">تجاوز مدة الخدمة</a> — {{ $timeReviews }} حالة تحتاج تحققًا. لا تُضاف تكلفة تلقائيًا.</div>@endif
@endif

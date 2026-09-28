@extends('admin.layout.indexs.index')
@section('page-title','نداءات النجدة')
@section('style')
@include('admin.hourly_teams._style')
<style>
.sos-live{border-inline-start:4px solid #dc2626;background:#fff7f7}.sos-ack{border-inline-start:4px solid #f59e0b}.sos-pulse{animation:sosPulse 1.5s infinite}@keyframes sosPulse{50%{opacity:.45}}
</style>
@endsection
@section('content')
@php
    $labels=['triggered'=>'جديد وعاجل','acknowledged'=>'تم الاستلام','resolved'=>'تمت المعالجة','cancelled'=>'ألغته العاملة'];
    $tones=['triggered'=>'danger','acknowledged'=>'warning','resolved'=>'success','cancelled'=>'secondary'];
    $openCount=(int)$stats->triggered+(int)$stats->acknowledged;
@endphp
<div class="container-fluid ht-page" dir="rtl">
    <div class="ht-card"><div class="ht-head">
        <div class="ht-head-icon" style="background:#fee2e2;color:#b91c1c"><i class="fa fa-triangle-exclamation"></i></div>
        <div><h5>نداءات النجدة SOS</h5><p>بلاغات الخطر المرسلة من تطبيق العاملة والسائق، مع الموقع وسجل الاستلام والمعالجة.</p></div>
        <div class="ht-actions"><span class="badge bg-danger fs-6">{{ $openCount }} مفتوح</span></div>
    </div></div>

    <div class="hd-stats">
        @foreach(['triggered'=>['بلاغات جديدة','fa-bell','hd-stat-warn'],'acknowledged'=>['قيد المعالجة','fa-headset','hd-stat-info'],'resolved'=>['تمت المعالجة','fa-circle-check','hd-stat-on'],'cancelled'=>['ملغاة','fa-ban','hd-stat-off']] as $key=>[$title,$icon,$tone])
        <a class="hd-stat mh-stat {{ $tone }}" href="{{ route('admin.hourly-sos.index',['status'=>$key]) }}"><div class="hd-stat-icon"><i class="fa {{ $icon }}"></i></div><div><span>{{ $title }}</span><strong>{{ (int)$stats->{$key} }}</strong></div></a>
        @endforeach
        <a class="hd-stat mh-stat hd-stat-info" href="{{ route('admin.hourly-sos.index',['guard'=>'driver']) }}"><div class="hd-stat-icon"><i class="fa fa-truck"></i></div><div><span>بلاغات السائقين</span><strong>{{ (int)($stats->drivers ?? 0) }}</strong></div></a>
    </div>

    <div class="ht-card"><div class="ht-head">
        <div><h5>سجل البلاغات</h5><p>البلاغات الجديدة تظهر أولًا. تُحدّث الصفحة تلقائيًا كل 20 ثانية عند وجود بلاغ مفتوح.</p></div>
        <form method="GET" class="ht-actions d-flex gap-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">كل الحالات</option><option value="open" @selected(($filters['status']??'')==='open')>المفتوحة فقط</option>
                @foreach($labels as $key=>$label)<option value="{{ $key }}" @selected(($filters['status']??'')===$key)>{{ $label }}</option>@endforeach
            </select>
            <select name="guard" class="form-select" onchange="this.form.submit()">
                <option value="">العاملات والسائقون</option>
                <option value="worker" @selected(($filters['guard']??'')==='worker')>العاملات فقط</option>
                <option value="driver" @selected(($filters['guard']??'')==='driver')>السائقون فقط</option>
            </select>
        </form>
    </div><div class="table-responsive"><table class="ht-table">
        <thead><tr><th>البلاغ</th><th>مُرسِل البلاغ</th><th>وقت الإرسال</th><th>الموقع</th><th>التفاصيل</th><th>الحالة</th><th>الإجراء</th></tr></thead>
        <tbody>
        @forelse($alerts as $alert)
            <tr class="{{ $alert->status==='triggered'?'sos-live':($alert->status==='acknowledged'?'sos-ack':'') }}">
                <td class="ht-num"><b>#{{ $alert->id }}</b>@if($alert->repeat_count>1)<br><span class="badge bg-danger sos-pulse">تكرر {{ $alert->repeat_count }} مرات</span>@endif</td>
                @php $owner = \App\Services\Hourly\StaffSos::ownerLabel($alert); @endphp
                <td>
                    <span class="badge bg-{{ $owner['kind']==='driver'?'info':'secondary' }} mb-1">{{ $owner['label'] }}</span><br>
                    @if($owner['kind']==='driver')
                        <a href="{{ route('admin.hourly-drivers.edit',$owner['id']) }}">{{ $owner['name'] }}</a>
                    @else
                        <a href="{{ route('admin.hourly-maids.show',$owner['id']) }}">{{ $owner['name'] }}</a>
                    @endif
                    @if($owner['phone'])<br><small class="text-muted" dir="ltr">{{ $owner['phone'] }}</small>@endif
                </td>
                <td class="ht-num" dir="ltr">{{ $alert->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
                <td>@if($alert->latitude!==null && $alert->longitude!==null)<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($alert->latitude.','.$alert->longitude) }}"><i class="fa fa-location-dot"></i> فتح الخريطة</a>@else<span class="text-muted">غير متاح</span>@endif</td>
                <td>{{ $alert->message ?: 'طلب نجدة دون وصف' }}@if($alert->visit)<br><small><a href="{{ route('admin.hourly-visits.show',$alert->visit) }}">الزيارة #{{ $alert->visit->visit_number }}</a></small>@endif</td>
                <td><span class="badge bg-{{ $tones[$alert->status]??'secondary' }}">{{ $labels[$alert->status]??$alert->status }}</span>@if($alert->acknowledger)<br><small class="text-muted">استلمه: {{ $alert->acknowledger->name }}</small>@endif</td>
                <td style="min-width:210px">
                    @if(in_array($alert->status,['triggered','acknowledged']) && AdminGuardHasPerm('hourly_sos_edit'))
                    <div class="d-flex flex-column gap-2">
                        @if($alert->status==='triggered')<form method="POST" action="{{ route('admin.hourly-sos.update',$alert) }}">@csrf @method('PUT')<input type="hidden" name="status" value="acknowledged"><button class="btn btn-sm btn-warning w-100"><i class="fa fa-headset"></i> تأكيد الاستلام</button></form>@endif
                        <form method="POST" action="{{ route('admin.hourly-sos.update',$alert) }}">@csrf @method('PUT')<input type="hidden" name="status" value="resolved"><input class="form-control form-control-sm mb-1" name="resolution_note" maxlength="500" placeholder="ملاحظة المعالجة"><button class="btn btn-sm btn-success w-100"><i class="fa fa-check"></i> تمت المعالجة</button></form>
                    </div>
                    @elseif($alert->resolution_note)<small>{{ $alert->resolution_note }}</small>@else — @endif
                </td>
            </tr>
        @empty<tr><td colspan="7"><div class="ht-empty"><i class="fa fa-shield-heart"></i>لا توجد نداءات نجدة مطابقة.</div></td></tr>@endforelse
        </tbody>
    </table></div><div class="ht-foot">{{ $alerts->links() }}</div></div>
</div>
@if($openCount)<script>window.setTimeout(function(){window.location.reload()},20000);</script>@endif
@endsection

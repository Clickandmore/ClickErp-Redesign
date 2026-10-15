@extends('hourly_driver.layout')
@section('title', 'بوابة عاملات الساعات')
@section('content')
<h1>مرحبًا {{ $maid->name }}</h1><p>اسم المستخدم: {{ $maid->hourly_username }}</p>
<p><a href="{{ route('hourly-maid.offers') }}">الزيارات المتاحة للاختيار</a></p>
@include('hourly_maid._cash-visits')
<h2>جدول العمل الأسبوعي</h2><table><thead><tr><th>اليوم</th><th>الدوام</th></tr></thead><tbody>
@foreach(\App\Models\Hourly\HourlyDriver::DAYS as $day=>$label)
@php $schedule=$maid->hourly_settings['work_schedule'][$day] ?? ['mode'=>'off']; @endphp
<tr><td>{{ $label }}</td><td>{{ $schedule['mode']==='hours' ? $schedule['start'].' – '.$schedule['end'] : ($schedule['mode']==='all_day' ? 'طوال اليوم' : 'إجازة') }}</td></tr>
@endforeach
</tbody></table><form method="POST" action="{{ route('hourly-maid.logout') }}">@csrf<button>تسجيل الخروج</button></form>
@endsection

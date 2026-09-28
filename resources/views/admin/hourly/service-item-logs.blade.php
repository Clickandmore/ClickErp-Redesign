@extends('admin.layout.indexs.index')
@section('page-title','سجل تعديلات باقات الخدمات')
@section('content')
<div class="container-fluid"><div class="card p-4" dir="rtl"><h4>سجل تعديلات باقات الخدمات</h4><a href="{{ route('admin.hourly-service-items.index') }}">العرض المرئي</a>
<form method="GET" class="my-3 d-flex gap-2"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="ابحث باسم الباقة"><button class="btn btn-primary">بحث</button></form>
<div class="table-responsive"><table class="table"><thead><tr><th>التاريخ</th><th>الباقة</th><th>النوع</th><th>التفاصيل</th><th>المستخدم</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->item_name }}</td><td>{{ ['create'=>'إضافة','edit'=>'تعديل','archive'=>'أرشفة','status'=>'الحالة','price'=>'السعر','bulk_prices'=>'تحديث الأسعار','bulk_shifts'=>'تحديث الشفتات','bulk_breaks'=>'الاستراحة','bulk_buffer'=>'الفاصل'][$log->type] ?? $log->type }}</td><td><details><summary>عرض التغييرات</summary>@foreach($log->changes() as $change)<div class="small mb-2"><strong>{{ $change['label'] }}</strong>: {{ $change['before'] }} ← {{ $change['after'] }}</div>@endforeach</details></td><td>{{ $log->admin_name ?: 'النظام' }}</td></tr>@empty<tr><td colspan="5">لا توجد تعديلات مسجلة.</td></tr>@endforelse
</tbody></table></div>{{ $logs->links() }}</div></div>
@endsection

@extends('admin.layout.indexs.index')
@section('page-title','طلبات معالجة الزيارات غير المنفذة')
@section('content')
<div class="container-fluid p-3" dir="rtl"><h4>طلبات معالجة الزيارات غير المنفذة</h4>
@include('admin.hourly.messages')
@include('admin.hourly._recovery-requests',['recoveryOrders'=>$orders])
@if($orders->isEmpty())<p>لا توجد طلبات معالجة.</p>@endif
{{ $orders->links() }}
</div>
@endsection

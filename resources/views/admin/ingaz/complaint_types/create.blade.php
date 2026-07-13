@extends('admin.layout.indexs.index')
@section('page-title', 'إضافة تصنيف شكوى')
@section('content')
<div class="container mt-4"><div class="card shadow-sm"><div class="card-header"><h4 class="mb-0">إضافة تصنيف شكوى</h4></div><div class="card-body">
    <form action="{{ route('complaint-types.store') }}" method="POST">@csrf
        @include('admin.ingaz.complaint_types.form')
        <button class="btn btn-success">حفظ التصنيف</button><a href="{{ route('complaint-types.index') }}" class="btn btn-light border">إلغاء</a>
    </form>
</div></div></div>
@endsection

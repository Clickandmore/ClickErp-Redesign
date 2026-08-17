@extends('admin.layout.indexs.index')
@section('page-title', 'تعديل تصنيف الشكوى')
@section('content')
<div class="container mt-4"><div class="card shadow-sm"><div class="card-header"><h4 class="mb-0">تعديل تصنيف الشكوى</h4></div><div class="card-body">
    <form action="{{ route('complaint-types.update', $type) }}" method="POST">@csrf @method('PUT')
        @include('admin.ingaz.complaint_types.form')
        <button class="btn btn-primary">حفظ التعديلات</button><a href="{{ route('complaint-types.index') }}" class="btn btn-light border">إلغاء</a>
    </form>
</div></div></div>
@endsection

@extends('admin.layout.indexs.index')

@section('page-title')
    تعديل نوع الشكوى
@endsection

@section('content')
<div class="container mt-4">
    <h4>تعديل نوع الشكوى</h4>
    <form action="{{ route('complaint-types.update', $type->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">اسم النوع</label>
            <input type="text" name="name" id="name" class="form-control" required value="{{ old('name', $type->name) }}">
            @error('name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary">تحديث</button>
        <a href="{{ route('complaint-types.index') }}" class="btn btn-secondary">إلغاء</a>
    </form>
</div>
@endsection

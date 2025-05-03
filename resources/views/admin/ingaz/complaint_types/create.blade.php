@extends('admin.layout.indexs.index')

@section('page-title')
    إضافة نوع شكوى جديد
@endsection

@section('content')
<div class="container mt-4">
    <h4>إضافة نوع شكوى جديد</h4>
    <form action="{{ route('complaint-types.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">اسم النوع</label>
            <input type="text" name="name" id="name" class="form-control" required value="{{ old('name') }}">
            @error('name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-success">حفظ</button>
        <a href="{{ route('complaint-types.index') }}" class="btn btn-secondary">إلغاء</a>
    </form>
</div>
@endsection

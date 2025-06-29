@extends('admin.layout.indexs.index')

@section('page-title')
    إدارة أنواع الشكاوى
@endsection

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>أنواع الشكاوى</h4>
        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_CREATE))
        <a href="{{ route('complaint-types.create') }}" class="btn btn-success">إضافة نوع جديد</a>
        @endif
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم النوع</th>
                <th>العمليات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($types as $type)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $type->name }}</td>
                    <td>
                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_EDIT))
                        <a href="{{ route('complaint-types.edit', $type->id) }}" class="btn btn-primary btn-sm">تعديل</a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_DELETE))
                        <form action="{{ route('complaint-types.destroy', $type->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

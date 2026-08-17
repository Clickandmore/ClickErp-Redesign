@extends('admin.layout.indexs.index')

@section('page-title')
    تعديل تفويض إنجاز
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="mb-1">تعديل تفويض إنجاز</h3>
                    <p class="text-muted mb-0">يمكنك تحديث بيانات التفويض وربط العميل والمرفقات.</p>
                </div>
                <a href="{{ route('ingaz-delegations.show', $delegation) }}" class="btn btn-outline-secondary">رجوع</a>
            </div>

            <form action="{{ route('ingaz-delegations.update', $delegation) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.ingaz_delegations._form', ['delegation' => $delegation])

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    <a href="{{ route('ingaz-delegations.show', $delegation) }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection

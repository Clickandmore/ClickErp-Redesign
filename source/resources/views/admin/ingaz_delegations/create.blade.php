@extends('admin.layout.indexs.index')

@section('page-title')
    إضافة تفويض إنجاز
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="mb-1">إضافة تفويض إنجاز</h3>
                    <p class="text-muted mb-0">أدخل بيانات التفويض والعميل والتأشيرة والمكتب الخارجي.</p>
                </div>
                <a href="{{ route('ingaz-delegations.index') }}" class="btn btn-outline-secondary">رجوع</a>
            </div>

            <form action="{{ route('ingaz-delegations.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('admin.ingaz_delegations._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">حفظ التفويض</button>
                    <a href="{{ route('ingaz-delegations.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection

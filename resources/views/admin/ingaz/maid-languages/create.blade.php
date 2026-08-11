@extends('admin.layout.indexs.index')

@section('page-title', 'إضافة لغة')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="h4 mb-0"><i class="fas fa-language text-primary me-2"></i>إضافة لغة</h3>
                <a href="{{ route('languages.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="{{ route('languages.store') }}">
                        @include('admin.ingaz.maid-languages._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

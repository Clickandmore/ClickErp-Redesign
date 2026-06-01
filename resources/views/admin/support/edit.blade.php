@extends('admin.layout.indexs.index')

@section('page-title', 'تعديل دليل دعم')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        @include('admin.support._alerts')
        @include('admin.support._nav')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-edit me-1"></i> تعديل دليل دعم</h4>
                <form action="{{ route('support.destroy', $article) }}" method="POST" onsubmit="return confirm('حذف هذا الدليل؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </form>
            </div>
            <div class="card-body">
                <form action="{{ route('support.update', $article) }}" method="POST" enctype="multipart/form-data">
                    @include('admin.support._form', ['article' => $article])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

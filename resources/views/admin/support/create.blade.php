@extends('admin.layout.indexs.index')

@section('page-title', 'إضافة دليل دعم')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        @include('admin.support._alerts')
        @include('admin.support._nav')

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-plus me-1"></i> إضافة دليل دعم</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('support.store') }}" method="POST" enctype="multipart/form-data">
                    @include('admin.support._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

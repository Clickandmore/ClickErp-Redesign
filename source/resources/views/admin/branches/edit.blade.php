@extends('admin.layout.indexs.index')

@section('page-title')
    تعديل الفرع
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">تعديل الفرع - {{ $branch->name_ar }}</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('branches.update', $branch) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.branches._form', ['submitLabel' => 'حفظ التعديلات'])
            </form>
        </div>
    </div>
@endsection

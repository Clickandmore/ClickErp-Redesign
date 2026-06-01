@extends('admin.layout.indexs.index')

@section('page-title')
    إضافة فرع جديد
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">إضافة فرع جديد</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('branches.store') }}" method="POST">
                @csrf
                @include('admin.branches._form', ['submitLabel' => 'حفظ الفرع'])
            </form>
        </div>
    </div>
@endsection

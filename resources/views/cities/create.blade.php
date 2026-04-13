@extends('admin.layout.indexs.index')

@section('page-title')
    إضافة مدينة جديدة
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">إضافة مدينة جديدة</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('cities.store') }}" method="POST">
                @csrf
                @include('cities._form', ['submitLabel' => 'حفظ المدينة'])
            </form>
        </div>
    </div>
@endsection

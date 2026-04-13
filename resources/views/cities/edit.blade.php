@extends('admin.layout.indexs.index')

@section('page-title')
    تعديل المدينة
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">تعديل المدينة - {{ $city->name_ar }}</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('cities.update', $city) }}" method="POST">
                @csrf
                @method('PUT')
                @include('cities._form', ['submitLabel' => 'حفظ التعديلات'])
            </form>
        </div>
    </div>
@endsection

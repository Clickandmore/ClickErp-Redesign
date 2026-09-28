@extends('admin.layout.indexs.index')

@section('page-title', $city->exists ? 'تعديل المدينة' : 'إضافة مدينة جديدة')

@section('style')
    @include('admin.hourly_teams._style')
@endsection

@section('content')
    <div class="container-fluid ht-page" dir="rtl">
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3">
                <div class="fw-bold mb-1"><i class="fa fa-circle-xmark ms-1"></i> تعذّر الحفظ، راجع الحقول التالية:</div>
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $city->exists ? route('admin.hourly-cities.update', $city) : route('admin.hourly-cities.store') }}" class="ht-card mx-auto" style="max-width: 900px">
            @csrf
            @if ($city->exists) @method('PUT') @endif
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa {{ $city->exists ? 'fa-pen-to-square' : 'fa-city' }}"></i></div>
                <div>
                    <h5>{{ $city->exists ? 'تعديل المدينة — '.$city->name_ar : 'إضافة مدينة جديدة' }}</h5>
                    <p>الاسم باللغتين، ثم مركز المدينة، ثم ترتيب ظهورها وحالتها</p>
                </div>
                <div class="ht-actions">
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-cities.index') }}"><i class="fa fa-xmark ms-1"></i> إغلاق</a>
                </div>
            </div>
            <div class="ht-body">
                @include('admin.hourly_districts._city-fields')
            </div>
            <div class="ht-foot">
                <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> حفظ المدينة</button>
                <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-cities.index') }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection

@push('js')
    @include('admin.hourly_districts._city-script')
@endpush

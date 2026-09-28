@extends('admin.layout.indexs.index')

@section('page-title', $district->exists ? 'تعديل المنطقة' : 'إضافة منطقة جديدة')

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

        <form method="POST" action="{{ $district->exists ? route('admin.hourly-districts.update', $district) : route('admin.hourly-districts.store') }}" class="ht-card mx-auto" style="max-width: 900px">
            @csrf
            @if ($district->exists) @method('PUT') @endif
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa {{ $district->exists ? 'fa-pen-to-square' : 'fa-map-pin' }}"></i></div>
                <div>
                    <h5>{{ $district->exists ? 'تعديل المنطقة — '.$district->name : 'إضافة منطقة جديدة' }}</h5>
                    <p>المدينة والاسم، ثم الموقع، ثم الحالة والفرق التي تخدمها</p>
                </div>
                <div class="ht-actions">
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-districts.index', ['city_id' => $district->city_id]) }}"><i class="fa fa-xmark ms-1"></i> إغلاق</a>
                </div>
            </div>
            <div class="ht-body">
                @include('admin.hourly_districts._district-fields')
            </div>
            <div class="ht-foot">
                <button class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> حفظ المنطقة</button>
                <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-districts.index', ['city_id' => $district->city_id]) }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection

@push('js')
    @include('admin.hourly_districts._district-script')
@endpush

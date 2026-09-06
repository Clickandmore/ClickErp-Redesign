@extends('admin.layout.indexs.index')
@section('page-title', $qualification->exists ? 'تعديل المؤهل التعليمي' : 'إضافة مؤهل تعليمي')
@section('content')
    <div class="page-body"><div class="container-fluid">
        <h3 class="h4 mb-4">{{ $qualification->exists ? 'تعديل المؤهل التعليمي' : 'إضافة مؤهل تعليمي' }}</h3>
        <div class="card"><div class="card-body">
            <form method="post" action="{{ $qualification->exists ? route('education-qualifications.update', $qualification) : route('education-qualifications.store') }}">
                @csrf
                @if($qualification->exists) @method('PUT') @endif
                <div class="row g-3">
                    @foreach(['ar' => 'المؤهل بالعربية', 'en' => 'المؤهل بالإنجليزية'] as $locale => $label)
                        @php $field = 'title_' . $locale; @endphp
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $qualification->$field) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" maxlength="255" required>
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">حفظ</button>
                        <a class="btn btn-outline-secondary" href="{{ route('education-qualifications.index') }}">رجوع</a>
                    </div>
                </div>
            </form>
        </div></div>
    </div></div>
@endsection

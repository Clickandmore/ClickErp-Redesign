@extends('admin.layout.indexs.index')

@section('title', 'تعديل البند')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">تعديل البند: {{ $term->section_title }}</h3>
                        <div class="card-tools">
                            <a href="{{ route('rent-contract-terms.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-right"></i> العودة للقائمة
                            </a>
                        </div>
                    </div>
                    <form action="{{ route('rent-contract-terms.update', $term->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <datalist id="section_titles">
                            @foreach($sectionTitles ?? [] as $sectionTitle)
                                <option value="{{ $sectionTitle }}">
                            @endforeach
                        </datalist>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="section_title">عنوان القسم <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control @error('section_title') is-invalid @enderror"
                                               id="section_title" name="section_title" list="section_titles"
                                               value="{{ old('section_title', $term->section_title) }}" required>
                                        @error('section_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="term_number">رقم البند</label>
                                        <input type="number" class="form-control @error('term_number') is-invalid @enderror"
                                               id="term_number" name="term_number"
                                               value="{{ old('term_number', $term->term_number) }}" min="1">
                                        @error('term_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="section_order">ترتيب القسم</label>
                                        <input type="number"
                                               class="form-control @error('section_order') is-invalid @enderror"
                                               id="section_order" name="section_order"
                                               value="{{ old('section_order', $term->section_order) }}" min="1">
                                        @error('section_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="term_order">ترتيب البند</label>
                                        <input type="number"
                                               class="form-control @error('term_order') is-invalid @enderror"
                                               id="term_order" name="term_order"
                                               value="{{ old('term_order', $term->term_order) }}" min="1">
                                        @error('term_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="page_number">رقم الصفحة</label>
                                        <input type="number"
                                               class="form-control @error('page_number') is-invalid @enderror"
                                               id="page_number" name="page_number"
                                               value="{{ old('page_number', $term->page_number) }}" min="1" required>
                                        <small class="text-muted">يمكن نقل البند إلى أي صفحة، وستظهر الصفحات النشطة فقط في الطباعة.</small>
                                        @error('page_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="term_content">محتوى البند <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('term_content') is-invalid @enderror"
                                              name="term_content" rows="8" required>{{ old('term_content', $term->term_content) }}</textarea>
                                    @error('term_content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @include('admin.ingaz.RentContractTerms._placeholder-help')
                                </div>


                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                            {{ old('is_active', $term->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            البند نشط
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التعديلات
                                </button>
                                <a href="{{ route('rent-contract-terms.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

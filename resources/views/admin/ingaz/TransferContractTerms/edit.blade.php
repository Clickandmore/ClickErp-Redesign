@extends('admin.layout.indexs.index')

@section('title', 'تعديل البند - عقد نقل الكفالة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('transfer-contract-terms.index') }}">بنود عقد نقل الكفالة</a></li>
                        <li class="breadcrumb-item active">تعديل البند</li>
                    </ol>
                </div>
                <h4 class="page-title">تعديل البند</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-edit"></i> تعديل بند عقد نقل الكفالة
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('transfer-contract-terms.update', $transferContractTerm->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="section_title" class="form-label">عنوان القسم <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('section_title') is-invalid @enderror"
                                           id="section_title" name="section_title"
                                           value="{{ old('section_title', $transferContractTerm->section_title) }}"
                                           placeholder="مثال: أولاً: موضوع العقد">
                                    @error('section_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">عنوان القسم الذي ينتمي إليه البند</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="section_order" class="form-label">ترتيب القسم <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('section_order') is-invalid @enderror"
                                           id="section_order" name="section_order"
                                           value="{{ old('section_order', $transferContractTerm->section_order) }}" min="1">
                                    @error('section_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">ترتيب القسم في العقد</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="term_number" class="form-label">رقم البند</label>
                                    <input type="number" class="form-control @error('term_number') is-invalid @enderror"
                                           id="term_number" name="term_number"
                                           value="{{ old('term_number', $transferContractTerm->term_number) }}" min="1">
                                    @error('term_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">رقم البند داخل القسم (اختياري)</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="term_order" class="form-label">ترتيب البند <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('term_order') is-invalid @enderror"
                                           id="term_order" name="term_order"
                                           value="{{ old('term_order', $transferContractTerm->term_order) }}" min="1">
                                    @error('term_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">ترتيب البند داخل القسم</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="page_number" class="form-label">رقم الصفحة <span class="text-danger">*</span></label>
                                    <select class="form-select @error('page_number') is-invalid @enderror"
                                            id="page_number" name="page_number">
                                        <option value="">اختر الصفحة</option>
                                        <option value="1" {{ old('page_number', $transferContractTerm->page_number) == 1 ? 'selected' : '' }}>الصفحة الأولى</option>
                                        <option value="2" {{ old('page_number', $transferContractTerm->page_number) == 2 ? 'selected' : '' }}>الصفحة الثانية</option>
                                        <option value="3" {{ old('page_number', $transferContractTerm->page_number) == 3 ? 'selected' : '' }}>الصفحة الثالثة</option>
                                    </select>
                                    @error('page_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                               {{ old('is_active', $transferContractTerm->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            البند نشط
                                        </label>
                                    </div>
                                    <div class="form-text">البنود غير النشطة لن تظهر في العقد المطبوع</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="term_content" class="form-label">محتوى البند <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('term_content') is-invalid @enderror"
                                      id="term_content" name="term_content" rows="6"
                                      placeholder="اكتب محتوى البند هنا...">{{ old('term_content', $transferContractTerm->term_content) }}</textarea>
                            @error('term_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text mt-2">
                                <strong>المتغيرات المتاحة:</strong><br>
                                <div class="row">
                                    <div class="col-md-6">
                                        <code>[اسم_العاملة]</code> - اسم العاملة<br>
                                        <code>[جنسية_العاملة]</code> - جنسية العاملة<br>
                                        <code>[رقم_الهوية]</code> - رقم هوية العميل<br>
                                        <code>[رقم_الجواز]</code> - رقم جواز العاملة<br>
                                        <code>[المهنة]</code> - مهنة العاملة<br>
                                        <code>[مدة_العقد]</code> - مدة العقد
                                    </div>
                                    <div class="col-md-6">
                                        <code>[تاريخ_البداية]</code> - تاريخ بداية العقد<br>
                                        <code>[فترة_التجربة]</code> - مدة فترة التجربة<br>
                                        <code>[قيمة_العقد]</code> - قيمة العقد<br>
                                        <code>[طريقة_الدفع]</code> - طريقة الدفع<br>
                                        <code>[تاريخ_التحرير]</code> - تاريخ تحرير العقد
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="text-end">
                                    <a href="{{ route('transfer-contract-terms.index') }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-arrow-left"></i> إلغاء
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> حفظ التعديلات
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
.form-text {
    font-size: 0.875em;
}

.form-text code {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.85em;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}
</style>
@endsection

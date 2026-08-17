@extends('admin.layout.indexs.index')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">
                            <i class="fas fa-file-contract me-2"></i>
                            تعديل بنود عقد التأجير
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-warning btn-sm" onclick="importDefaultTerms()">
                                <i class="fas fa-download me-1"></i>
                                استيراد البنود الافتراضية
                            </button>
                            <a href="{{ route('RentContract.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-right me-1"></i>
                                رجوع
                            </a>
                        </div>
                    </div>

                    <form action="{{ route('rent-contract-terms.update') }}" method="POST" id="termsForm">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <!-- تبويبات للصفحات -->
                            <ul class="nav nav-tabs" id="termsTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="page1-tab" data-bs-toggle="tab" href="#page1" role="tab">
                                        <i class="fas fa-file-alt me-1"></i>
                                        الصفحة الأولى
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="page2-tab" data-bs-toggle="tab" href="#page2" role="tab">
                                        <i class="fas fa-file-alt me-1"></i>
                                        الصفحة الثانية
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="page3-tab" data-bs-toggle="tab" href="#page3" role="tab">
                                        <i class="fas fa-file-alt me-1"></i>
                                        الصفحة الثالثة
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content mt-3" id="termsTabContent">
                                <!-- الصفحة الأولى -->
                                <div class="tab-pane fade show active" id="page1" role="tabpanel">
                                    <div class="terms-container" data-page="1">
                                        <h4 class="mb-3">بنود الصفحة الأولى</h4>

                                        @forelse($termsPage1 ?? [] as $index => $term)
                                        <div class="term-item border rounded p-3 mb-3">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label>عنوان القسم</label>
                                                    <input type="text" name="terms[{{ $index }}][section_title]"
                                                           class="form-control" value="{{ $term->section_title }}"
                                                           placeholder="مثال: أولاً: موضوع العقد">
                                                </div>
                                                <div class="col-md-1">
                                                    <label>رقم البند</label>
                                                    <input type="number" name="terms[{{ $index }}][term_number]"
                                                           class="form-control" value="{{ $term->term_number }}"
                                                           placeholder="1">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>محتوى البند</label>
                                                    <textarea name="terms[{{ $index }}][term_content]"
                                                              class="form-control" rows="3" required>{{ $term->term_content }}</textarea>
                                                </div>
                                                <div class="col-md-1">
                                                    <label>الترتيب</label>
                                                    <input type="number" name="terms[{{ $index }}][term_order]"
                                                           class="form-control" value="{{ $term->term_order }}" min="1">
                                                </div>
                                                <div class="col-md-1">
                                                    <label>تفعيل</label>
                                                    <div class="form-check mt-2">
                                                        <input type="checkbox" name="terms[{{ $index }}][is_active]"
                                                               class="form-check-input" {{ $term->is_active ? 'checked' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="terms[{{ $index }}][page_number]" value="1">
                                            <input type="hidden" name="terms[{{ $index }}][section_order]" value="{{ $term->section_order }}">
                                            <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeTerm(this)">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </div>
                                        @empty
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            لا توجد بنود في هذه الصفحة. اضغط على "إضافة بند" لإضافة بند جديد.
                                        </div>
                                        @endforelse

                                        <button type="button" class="btn btn-success" onclick="addTerm(1)">
                                            <i class="fas fa-plus me-1"></i>
                                            إضافة بند جديد
                                        </button>
                                    </div>
                                </div>

                                <!-- الصفحة الثانية -->
                                <div class="tab-pane fade" id="page2" role="tabpanel">
                                    <div class="terms-container" data-page="2">
                                        <h4 class="mb-3">بنود الصفحة الثانية</h4>

                                        @forelse($termsPage2 ?? [] as $index => $term)
                                        <div class="term-item border rounded p-3 mb-3">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label>عنوان القسم</label>
                                                    <input type="text" name="terms[{{ 1000 + $index }}][section_title]"
                                                           class="form-control" value="{{ $term->section_title }}"
                                                           placeholder="مثال: ثانياً: مدة العقد">
                                                </div>
                                                <div class="col-md-1">
                                                    <label>رقم البند</label>
                                                    <input type="number" name="terms[{{ 1000 + $index }}][term_number]"
                                                           class="form-control" value="{{ $term->term_number }}"
                                                           placeholder="1">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>محتوى البند</label>
                                                    <textarea name="terms[{{ 1000 + $index }}][term_content]"
                                                              class="form-control" rows="3" required>{{ $term->term_content }}</textarea>
                                                </div>
                                                <div class="col-md-1">
                                                    <label>الترتيب</label>
                                                    <input type="number" name="terms[{{ 1000 + $index }}][term_order]"
                                                           class="form-control" value="{{ $term->term_order }}" min="1">
                                                </div>
                                                <div class="col-md-1">
                                                    <label>تفعيل</label>
                                                    <div class="form-check mt-2">
                                                        <input type="checkbox" name="terms[{{ 1000 + $index }}][is_active]"
                                                               class="form-check-input" {{ $term->is_active ? 'checked' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="terms[{{ 1000 + $index }}][page_number]" value="2">
                                            <input type="hidden" name="terms[{{ 1000 + $index }}][section_order]" value="{{ $term->section_order }}">
                                            <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeTerm(this)">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </div>
                                        @empty
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            لا توجد بنود في هذه الصفحة. اضغط على "إضافة بند" لإضافة بند جديد.
                                        </div>
                                        @endforelse

                                        <button type="button" class="btn btn-success" onclick="addTerm(2)">
                                            <i class="fas fa-plus me-1"></i>
                                            إضافة بند جديد
                                        </button>
                                    </div>
                                </div>

                                <!-- الصفحة الثالثة -->
                                <div class="tab-pane fade" id="page3" role="tabpanel">
                                    <div class="terms-container" data-page="3">
                                        <h4 class="mb-3">بنود الصفحة الثالثة</h4>

                                        @forelse($termsPage3 ?? [] as $index => $term)
                                        <div class="term-item border rounded p-3 mb-3">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label>عنوان القسم</label>
                                                    <input type="text" name="terms[{{ 2000 + $index }}][section_title]"
                                                           class="form-control" value="{{ $term->section_title }}"
                                                           placeholder="مثال: سادساً: أحكام عامة">
                                                </div>
                                                <div class="col-md-1">
                                                    <label>رقم البند</label>
                                                    <input type="number" name="terms[{{ 2000 + $index }}][term_number]"
                                                           class="form-control" value="{{ $term->term_number }}"
                                                           placeholder="1">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>محتوى البند</label>
                                                    <textarea name="terms[{{ 2000 + $index }}][term_content]"
                                                              class="form-control" rows="3" required>{{ $term->term_content }}</textarea>
                                                </div>
                                                <div class="col-md-1">
                                                    <label>الترتيب</label>
                                                    <input type="number" name="terms[{{ 2000 + $index }}][term_order]"
                                                           class="form-control" value="{{ $term->term_order }}" min="1">
                                                </div>
                                                <div class="col-md-1">
                                                    <label>تفعيل</label>
                                                    <div class="form-check mt-2">
                                                        <input type="checkbox" name="terms[{{ 2000 + $index }}][is_active]"
                                                               class="form-check-input" {{ $term->is_active ? 'checked' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="terms[{{ 2000 + $index }}][page_number]" value="3">
                                            <input type="hidden" name="terms[{{ 2000 + $index }}][section_order]" value="{{ $term->section_order }}">
                                            <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeTerm(this)">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </div>
                                        @empty
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            لا توجد بنود في هذه الصفحة. اضغط على "إضافة بند" لإضافة بند جديد.
                                        </div>
                                        @endforelse

                                        <button type="button" class="btn btn-success" onclick="addTerm(3)">
                                            <i class="fas fa-plus me-1"></i>
                                            إضافة بند جديد
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ التغييرات
                            </button>
                            <a href="{{ route('RentContract.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
let termIndex = 10000;

function addTerm(pageNumber) {
    const container = document.querySelector(`[data-page="${pageNumber}"] .alert`);
    if (container) {
        container.remove();
    }

    const termsContainer = document.querySelector(`[data-page="${pageNumber}"]`);
    const newTerm = `
        <div class="term-item border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label>عنوان القسم</label>
                    <input type="text" name="terms[${termIndex}][section_title]"
                           class="form-control" placeholder="مثال: أولاً: موضوع العقد">
                </div>
                <div class="col-md-1">
                    <label>رقم البند</label>
                    <input type="number" name="terms[${termIndex}][term_number]"
                           class="form-control" placeholder="1">
                </div>
                <div class="col-md-6">
                    <label>محتوى البند</label>
                    <textarea name="terms[${termIndex}][term_content]"
                              class="form-control" rows="3" required></textarea>
                </div>
                <div class="col-md-1">
                    <label>الترتيب</label>
                    <input type="number" name="terms[${termIndex}][term_order]"
                           class="form-control" value="1" min="1">
                </div>
                <div class="col-md-1">
                    <label>تفعيل</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="terms[${termIndex}][is_active]"
                               class="form-check-input" checked>
                    </div>
                </div>
            </div>
            <input type="hidden" name="terms[${termIndex}][page_number]" value="${pageNumber}">
            <input type="hidden" name="terms[${termIndex}][section_order]" value="1">
            <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeTerm(this)">
                <i class="fas fa-trash"></i> حذف
            </button>
        </div>
    `;

    // إضافة البند قبل زر الإضافة
    const addButton = termsContainer.querySelector('.btn-success');
    addButton.insertAdjacentHTML('beforebegin', newTerm);
    termIndex++;
}

function removeTerm(button) {
    if (confirm('هل أنت متأكد من حذف هذا البند؟')) {
        button.closest('.term-item').remove();
    }
}

function importDefaultTerms() {
    if (confirm('سيتم حذف جميع البنود الحالية واستيراد البنود الافتراضية. هل أنت متأكد؟')) {
        window.location.href = '{{ route("rent-contract-terms.import-default") }}';
    }
}
</script>
@endsection

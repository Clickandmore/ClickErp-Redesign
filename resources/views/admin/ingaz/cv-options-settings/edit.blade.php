@extends('admin.layout.indexs.index')

@section('page-title')
    إعدادات السير الذاتية
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CV_OPTIONS))
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-1">إعدادات السير الذاتية</h4>
                    <div class="text-muted small">تحكم في طريقة ظهور سير العاملات على الموقع الخارجي.</div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('settings.cv-options.update') }}" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <label class="form-label fw-bold" for="cv_site_sort_order">ترتيب السير الذاتية في الموقع الخارجي</label>
                            <select class="form-select" name="cv_site_sort_order" id="cv_site_sort_order" required>
                                @foreach (['random' => 'عشوائي', 'oldest' => 'من الأقدم إلى الأحدث', 'newest' => 'من الأحدث إلى الأقدم'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('cv_site_sort_order', $settings->cv_site_sort_order ?? 'random') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted mt-2 mb-0">يعتمد الترتيب على تاريخ إضافة السيرة، مع ظهور السير المثبّتة أولًا في جميع الخيارات.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <input type="hidden" name="rental_cv_transfer_enabled" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    name="rental_cv_transfer_enabled" id="rental_cv_transfer_enabled" value="1"
                                    {{ old('rental_cv_transfer_enabled', $settings->rental_cv_transfer_enabled ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="rental_cv_transfer_enabled">
                                    إتاحة سيرة عاملة الإيجار لنقل الخدمات
                                </label>
                            </div>

                            <p class="text-muted mb-2">
                                عند التفعيل يظهر حقل <strong>الاستلام الفوري</strong> لعاملات الإيجار في شاشة الإيواء،
                                وتظهر سيرة العاملة المُفعّلة في الموقع الخارجي ضمن قسم <strong>الإيجار</strong> وقسم
                                <strong>نقل الخدمات</strong> معًا.
                            </p>
                            <p class="text-muted mb-0">
                                عند التعطيل يُخفى الحقل لعاملات الإيجار وتبقى سيرتهن في قسم الإيجار فقط. لا يتأثر حقل
                                الاستلام الفوري لعاملات الاستقدام داخل الإيواء.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <input type="hidden" name="maid_transaction_edit_override_enabled" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    name="maid_transaction_edit_override_enabled"
                                    id="maid_transaction_edit_override_enabled" value="1"
                                    {{ old('maid_transaction_edit_override_enabled', $settings->maid_transaction_edit_override_enabled ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="maid_transaction_edit_override_enabled">
                                    السماح بتعديل السيرة المرتبطة بمعاملات
                                </label>
                            </div>

                            <p class="text-muted mb-0">
                                عند التفعيل يمكن تعديل بيانات السيرة حتى لو كانت مرتبطة بطلب أو عقد نشط أو فاتورة أو قيد محاسبي أو إرسالية مكتب خارجي.
                                هذا إعداد عام يطبّق على كل من يملك صلاحية تعديل السير، ويجب تعطيله بعد انتهاء الحاجة إليه.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

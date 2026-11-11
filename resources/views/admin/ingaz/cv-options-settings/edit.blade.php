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
                    <div class="text-muted small">تحكم في حقول نموذج السيرة وطريقة ظهور السير على الموقع الخارجي.</div>
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
                                    <option value="{{ $value }}" {{ old('cv_site_sort_order', $settings->cv_site_sort_order ?? 'random') === $value ? 'selected' : '' }}>{{ $label }}</option>
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

                @php
                    $cvDisplaySource = old('cv_display_source', $settings->cv_display_source ?? \App\Support\CvOptionsSettings::DISPLAY_GENERATED);
                @endphp
                <div class="col-12" id="cv-display-source-settings">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <label class="form-label fw-bold d-block mb-2">السيرة التي يراها العميل في الموقع الخارجي والـ API</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="cv_display_source" id="cv_display_source_generated"
                                    value="{{ \App\Support\CvOptionsSettings::DISPLAY_GENERATED }}"
                                    {{ $cvDisplaySource !== \App\Support\CvOptionsSettings::DISPLAY_UPLOADED ? 'checked' : '' }}>
                                <label class="form-check-label" for="cv_display_source_generated">
                                    <strong>السيرة المولّدة من القالب</strong>
                                    <span class="text-muted d-block small">تُبنى السيرة من بيانات العاملة حسب قالب السيرة الذاتية (الوضع الحالي).</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cv_display_source" id="cv_display_source_uploaded"
                                    value="{{ \App\Support\CvOptionsSettings::DISPLAY_UPLOADED }}"
                                    {{ $cvDisplaySource === \App\Support\CvOptionsSettings::DISPLAY_UPLOADED ? 'checked' : '' }}>
                                <label class="form-check-label" for="cv_display_source_uploaded">
                                    <strong>السيرة المرفوعة من الشركة</strong>
                                    <span class="text-muted d-block small">
                                        يُعرض ملف السيرة الجاهزة (صورة أو PDF) الذي رُفع مع العاملة في حقل
                                        <strong>{{ helperTrans('ingaz.maid_cv_file') }}</strong>.
                                        العاملة التي لم يُرفع لها ملف تظهر بالسيرة المولّدة كالمعتاد.
                                    </span>
                                </label>
                            </div>
                            <p class="text-muted small mt-2 mb-0">
                                يصل الملف للموقع الخارجي عبر الـ API في الحقل <code>cv_preview</code>، ويظهر أيضًا في صفحات السير بالموقع.
                                لاختصار وقت الإضافة يمكن جعل ملف السيرة الجاهزة إلزاميًا وإخفاء الحقول التي لا تحتاجها من القسم التالي.
                            </p>
                        </div>
                    </div>
                </div>

                @php
                    $maidFormFieldStates =old('maid_form_fields', $maidFormFields ?? \App\Support\MaidFormFieldSettings::states());
                    $maidFormFieldCatalog = collect(\App\Support\MaidFormFieldSettings::catalog());
                    $maidFormStateLabels = [
                        \App\Support\MaidFormFieldSettings::DEFAULT => 'كالمعتاد',
                        \App\Support\MaidFormFieldSettings::OPTIONAL => 'ظاهر واختياري',
                        \App\Support\MaidFormFieldSettings::HIDDEN => 'مخفي',
                        \App\Support\MaidFormFieldSettings::REQUIRED => 'إلزامي دائمًا',
                    ];
                @endphp
                <div class="col-12" id="maid-form-fields-settings">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold mb-1">حقول نموذج إضافة السيرة</h5>
                            <p class="text-muted mb-3">
                                اختر الحقول التي تحتاجها شركتك في نموذج إضافة وتعديل السيرة.
                                <strong>كالمعتاد</strong>: يبقى الحقل وإلزامه حسب نوع السيرة كما هو.
                                <strong>ظاهر واختياري</strong>: يظهر ولا يمنع الحفظ إذا تُرك فارغًا.
                                <strong>مخفي</strong>: لا يظهر في النموذج، والبيانات المحفوظة سابقًا لا تُحذف.
                            </p>

                            <div class="alert alert-light border small mb-3">
                                <i class="fas fa-lock me-1"></i>
                                حقول ثابتة لا يمكن إخفاؤها لأن العقود والمالية وبوابة الوكلاء تعتمد عليها:
                                {{ implode('، ', \App\Support\MaidFormFieldSettings::LOCKED) }}.
                            </div>

                            @foreach (\App\Support\MaidFormFieldSettings::GROUPS as $groupKey => $groupLabel)
                                @php $groupFields = $maidFormFieldCatalog->where('group', $groupKey); @endphp
                                @continue($groupFields->isEmpty())
                                <h6 class="fw-bold mt-3 mb-2">{{ $groupLabel }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                            @foreach ($groupFields as $fieldKey => $field)
                                                <tr>
                                                    <td style="width: 55%">
                                                        <label class="fw-semibold mb-0" for="maid_form_field_{{ $fieldKey }}">{{ $field['label'] }}</label>
                                                        @if (!empty($field['hint']))
                                                            <div class="text-muted small">{{ $field['hint'] }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" name="maid_form_fields[{{ $fieldKey }}]" id="maid_form_field_{{ $fieldKey }}">
                                                            @foreach (\App\Support\MaidFormFieldSettings::allowedStates($fieldKey) as $state)
                                                                <option value="{{ $state }}" {{ ($maidFormFieldStates[$fieldKey] ?? 'default') === $state ? 'selected' : '' }}>{{ $maidFormStateLabels[$state] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
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

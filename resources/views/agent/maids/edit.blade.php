@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
    $selectedTypeId = (int) old('type_id', $maid->jobTypes->first()?->id);
    $selectedJobIds = array_map('intval', (array) old('job_id', $maid->jobTitles->pluck('id')->all()));
    $selectedSkills = array_map('intval', (array) old('skills', $maid->skills ?? []));
    $selectedLanguages = array_values((array) old('languages', $maid->languages ?? []));
    $percentages = (array) old('percentage', $maid->percentage ?? []);
    $maritalStatus = old('marital_status:ar', $maid->translate('ar')?->marital_status);
@endphp

@section('page-title', $isAr ? 'تعديل السيرة الذاتية' : 'Edit CV')

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/select2.min.css">
    <style>
        .agent-edit-card { background:#fff; border:1px solid var(--agent-line); border-radius:16px; padding:22px; margin-bottom:18px; }
        .agent-edit-card h4 { color:var(--agent-primary-dark); margin:0 0 18px; padding-bottom:12px; border-bottom:1px solid var(--agent-line); }
        .agent-edit-actions { position:sticky; bottom:12px; display:flex; justify-content:flex-end; gap:10px; padding:14px; background:#fff; border:1px solid var(--agent-line); border-radius:14px; box-shadow:0 10px 30px rgba(15,23,42,.12); }
        .required-mark { color:#dc2626; }
    </style>
@endpush

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('agent.maids.update', $maid) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="alert alert-info">
            <i class="fa fa-circle-info"></i>
            {{ $isAr ? 'بعد الحفظ ستُرسل التعديلات للإدارة للمراجعة، وتبقى السيرة مخفية حتى اعتمادها.' : 'After saving, changes are sent for review and the CV remains hidden until approved.' }}
        </div>

        <section class="agent-edit-card">
            <h4><i class="fa fa-user-pen"></i> {{ $isAr ? 'البيانات الأساسية' : 'Basic Information' }}</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'الاسم' : 'Name' }} <span class="required-mark">*</span></label>
                    <input class="form-control" name="name:en" value="{{ old('name:en', $maid->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'رقم الجواز' : 'Passport Number' }} <span class="required-mark">*</span></label>
                    <input class="form-control" name="passport_num" value="{{ old('passport_num', $maid->passport_num) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'الجنسية' : 'Nationality' }} <span class="required-mark">*</span></label>
                    <select class="form-control" name="nationality_id" required>
                        @foreach ($nationalities as $nationality)
                            <option value="{{ $nationality->id }}" {{ (int) old('nationality_id', $maid->nationality_id) === (int) $nationality->id ? 'selected' : '' }}>
                                {{ $nationality->sub_title ?: $nationality->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'الديانة' : 'Religion' }}</label>
                    <select class="form-control" name="religion_id">
                        @foreach ($religions as $religion)
                            <option value="{{ $religion->id }}" {{ (int) old('religion_id', $maid->religion_id) === (int) $religion->id ? 'selected' : '' }}>{{ $religion->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'النوع' : 'Gender' }}</label>
                    <select class="form-control" name="gender">
                        <option value="female" {{ old('gender', $maid->gender) === 'female' ? 'selected' : '' }}>{{ $isAr ? 'أنثى' : 'Female' }}</option>
                        <option value="male" {{ old('gender', $maid->gender) === 'male' ? 'selected' : '' }}>{{ $isAr ? 'ذكر' : 'Male' }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'الحالة الاجتماعية' : 'Marital Status' }}</label>
                    <select class="form-control" name="marital_status:ar">
                        <option value="">{{ $isAr ? 'اختر' : 'Choose' }}</option>
                        @foreach (['عازبة', 'أعزب', 'متزوجة', 'متزوج'] as $status)
                            <option value="{{ $status }}" {{ $maritalStatus === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="marital_status:en" value="{{ old('marital_status:en', $maid->translate('en')?->marital_status) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'تاريخ الميلاد' : 'Birth Date' }} <span class="required-mark">*</span></label>
                    <input type="date" class="form-control" name="birth_date" value="{{ old('birth_date', $maid->birth_date?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'العمر' : 'Age' }}</label>
                    <input type="number" min="18" max="90" class="form-control" name="age" value="{{ old('age', $maid->age) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'الراتب' : 'Salary' }}</label>
                    <input type="number" step="0.01" class="form-control" name="salary" value="{{ old('salary', $maid->salary) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'الطول (سم)' : 'Height (cm)' }}</label>
                    <input class="form-control" name="height" value="{{ old('height', $maid->height) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'الوزن (كجم)' : 'Weight (kg)' }}</label>
                    <input class="form-control" name="weight" value="{{ old('weight', $maid->weight) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'عدد الأطفال' : 'Children' }}</label>
                    <input type="number" min="0" class="form-control" name="childrens_count" value="{{ old('childrens_count', $maid->childrens_count) }}">
                </div>
            </div>
        </section>

        <section class="agent-edit-card" id="additional_worker_information">
            <h4><i class="fa fa-circle-info"></i> {{ $isAr ? 'بيانات العاملة الإضافية' : 'Additional Worker Information' }}</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'رقم جوال العاملة' : 'Worker Phone' }}</label>
                    <input type="tel" class="form-control" name="phone" maxlength="50"
                        value="{{ old('phone', $maid->phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'اسم أحد أقارب العاملة' : 'Relative Name' }}</label>
                    <input class="form-control" name="relative_name" maxlength="255"
                        value="{{ old('relative_name', $maid->relative_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'رقم جوال قريب العاملة' : 'Relative Phone' }}</label>
                    <input type="tel" class="form-control" name="relative_phone" maxlength="50"
                        value="{{ old('relative_phone', $maid->relative_phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'مدينة سكن العاملة' : 'Residence City' }}</label>
                    <input class="form-control" name="residence_city" maxlength="255"
                        value="{{ old('residence_city', $maid->residence_city) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'مكان إصدار الجواز' : 'Passport Issue Place' }}</label>
                    <input class="form-control" name="passport_issue_place" maxlength="255"
                        value="{{ old('passport_issue_place', $maid->passport_issue_place) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'المؤهل التعليمي' : 'Education' }}</label>
                    <input class="form-control" name="education" maxlength="255"
                        value="{{ old('education', $maid->education) }}">
                </div>
                @php
                    $selectedExperienceStatus = old('experience_status', $maid->experience_status);
                    $selectedCookingExperience = old('cooking_experience', $maid->cooking_experience);
                @endphp
                <div class="col-md-4" id="cooking_experience_field"
                    style="{{ $selectedExperienceStatus === 'has_experience' ? '' : 'display: none;' }}">
                    <label class="form-label">{{ $isAr ? 'خبرة الطبخ' : 'Cooking Experience' }}</label>
                    <select class="form-control" name="cooking_experience" id="cooking_experience"
                        {{ $selectedExperienceStatus === 'has_experience' ? 'required' : '' }}>
                        <option value="">{{ $isAr ? 'اختر خبرة الطبخ' : 'Select cooking experience' }}</option>
                        <option value="arabic_cooking" {{ $selectedCookingExperience === 'arabic_cooking' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة بالطبخ العربي' : 'Experienced in Arabic cooking' }}</option>
                        <option value="regular_cooking" {{ $selectedCookingExperience === 'regular_cooking' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة بالطبخ العادي' : 'Experienced in regular cooking' }}</option>
                        <option value="willing_to_learn" {{ $selectedCookingExperience === 'willing_to_learn' ? 'selected' : '' }}>{{ $isAr ? 'ليس لديها خبرة بالطبخ لكن قابلة للتعلم' : 'No experience, but willing to learn' }}</option>
                        <option value="general_cooking" {{ $selectedCookingExperience === 'general_cooking' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة بالطبخ عامة' : 'Has general cooking experience' }}</option>
                        <option value="no_cooking_experience" {{ $selectedCookingExperience === 'no_cooking_experience' ? 'selected' : '' }}>{{ $isAr ? 'لا يوجد لديها خبرة في الطبخ' : 'Has no cooking experience' }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'هل تحمل رخصة قيادة؟' : 'Has a driving license?' }}</label>
                    <select class="form-control" name="has_driving_license">
                        <option value="">{{ $isAr ? 'اختر' : 'Select' }}</option>
                        <option value="1" {{ (string) old('has_driving_license', $maid->has_driving_license) === '1' ? 'selected' : '' }}>{{ $isAr ? 'نعم' : 'Yes' }}</option>
                        <option value="0" {{ (string) old('has_driving_license', $maid->has_driving_license) === '0' ? 'selected' : '' }}>{{ $isAr ? 'لا' : 'No' }}</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="agent-edit-card">
            <h4><i class="fa fa-briefcase"></i> {{ $isAr ? 'العمل والخبرة' : 'Work and Experience' }}</h4>
            <input type="hidden" name="office_id" value="{{ $maid->office_id }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'نوع السيرة' : 'CV Type' }} <span class="required-mark">*</span></label>
                    <select class="form-control" name="type_id" required>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" {{ $selectedTypeId === (int) $type->id ? 'selected' : '' }}>{{ $type->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'المهن' : 'Jobs' }} <span class="required-mark">*</span></label>
                    <select class="form-control js-select2" name="job_id[]" multiple required>
                        @foreach ($jobs as $job)
                            <option value="{{ $job->id }}" {{ in_array((int) $job->id, $selectedJobIds, true) ? 'selected' : '' }}>{{ $job->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'نوع الطلب' : 'Request Type' }}</label>
                    <select class="form-control" name="request_type">
                        @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('request_type', $maid->request_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'حالة الخبرة' : 'Experience Status' }} <span class="required-mark">*</span></label>
                    <select class="form-control" name="experience_status" id="experience_status" required>
                        <option value="has_experience" {{ old('experience_status', $maid->experience_status) === 'has_experience' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة' : 'Has Experience' }}</option>
                        <option value="no_experience" {{ old('experience_status', $maid->experience_status) === 'no_experience' ? 'selected' : '' }}>{{ $isAr ? 'بدون خبرة' : 'No Experience' }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'المهارات' : 'Skills' }}</label>
                    <select class="form-control js-select2" name="skills[]" multiple>
                        @foreach ($skills as $skill)
                            <option value="{{ $skill->id }}" {{ in_array((int) $skill->id, $selectedSkills, true) ? 'selected' : '' }}>{{ $skill->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $isAr ? 'رابط الفيديو' : 'Video Link' }}</label>
                    <input class="form-control" name="video_link" value="{{ old('video_link', $maid->video_link) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ $isAr ? 'اللغات ونسبة الإتقان' : 'Languages and proficiency' }}</label>
                    <div class="row g-2">
                        @foreach (['arabic' => 'العربية', 'english' => 'الإنجليزية', 'amharic' => 'الأمهرية', 'mother' => 'لغة الأم'] as $key => $label)
                            <div class="col-md-3">
                                <div class="border rounded p-2">
                                    <label><input type="checkbox" name="languages[]" value="{{ $key }}" {{ in_array($key, $selectedLanguages, true) ? 'checked' : '' }}> {{ $label }}</label>
                                    <input type="number" min="0" max="100" class="form-control mt-2" name="percentage[{{ $key }}]" value="{{ $percentages[$key] ?? '' }}" placeholder="%">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ $isAr ? 'ملاحظات إضافية' : 'Additional Notes' }}</label>
                    <textarea class="form-control" name="additional_notes" rows="4">{{ old('additional_notes', $maid->additional_notes) }}</textarea>
                </div>
            </div>
        </section>

        <section class="agent-edit-card">
            <h4><i class="fa fa-paperclip"></i> {{ $isAr ? 'الملفات' : 'Files' }}</h4>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $isAr ? 'استبدال الصورة الشخصية' : 'Replace Personal Photo' }}</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                    @if ($maid->image)<a href="{{ $maid->image_url }}" target="_blank" class="small">{{ $isAr ? 'عرض الصورة الحالية' : 'View current photo' }}</a>@endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $isAr ? 'استبدال ملف السيرة' : 'Replace CV File' }}</label>
                    <input type="file" class="form-control" name="cv_file" accept=".pdf,image/*">
                    @if ($maid->cv_file)<a href="{{ $maid->cv_file_url }}" target="_blank" class="small">{{ $isAr ? 'عرض الملف الحالي' : 'View current file' }}</a>@endif
                </div>
            </div>
        </section>

        <div class="agent-edit-actions">
            <a href="{{ route('agent.maids.index') }}" class="agent-button" style="background:#64748b">{{ $isAr ? 'إلغاء' : 'Cancel' }}</a>
            <button class="agent-button" type="submit"><i class="fa fa-floppy-disk"></i> {{ $isAr ? 'حفظ وإرسال للمراجعة' : 'Save and Submit for Review' }}</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/select2.bundle.js"></script>
    <script>
        $(function () {
            $('.js-select2').select2({ width: '100%' });

            function toggleCookingExperience() {
                var hasExperience = $('#experience_status').val() === 'has_experience';
                var $field = $('#cooking_experience_field');
                var $select = $('#cooking_experience');

                if (hasExperience) {
                    $field.slideDown();
                    $select.attr('required', 'required');
                } else {
                    $select.removeAttr('required').val('');
                    $field.slideUp();
                }
            }

            $('#experience_status').on('change', toggleCookingExperience);
            toggleCookingExperience();
        });
    </script>
@endpush

@php
    $qualifications = \App\Models\ingaz\EducationQualification::where('is_active', true)->orderBy('id')->get();
    $educationAr = old('education', $educationMaid->education ?? '');
    $educationEn = old('education_en', $educationMaid->education_en ?? '');
    $currentQualification = $qualifications->firstWhere('title_ar', $educationAr)
        ?? $qualifications->firstWhere('title_en', $educationEn);
    if ($currentQualification) {
        $educationAr = $currentQualification->title_ar;
        $educationEn = $currentQualification->title_en;
    }
    $legacyEducation = !$currentQualification && ($educationAr !== '' || $educationEn !== '');
@endphp
@foreach(['ar' => 'education', 'en' => 'education_en'] as $educationLocale => $educationField)
    <div class="col-md-4">
        <label class="form-label" for="{{ $educationField }}">{{ app()->getLocale() === 'ar' ? ($educationLocale === 'ar' ? 'المؤهل التعليمي (بالعربية)' : 'المؤهل التعليمي (بالإنجليزية)') : ($educationLocale === 'ar' ? 'Education (Arabic)' : 'Education (English)') }}</label>
        <select class="form-control" name="{{ $educationField }}" id="{{ $educationField }}" data-education-select dir="{{ $educationLocale === 'ar' ? 'rtl' : 'ltr' }}" {{ ($educationRequired ?? false) ? 'required' : '' }}>
            <option value="" data-qualification="">{{ $educationLocale === 'ar' ? 'اختر المؤهل التعليمي' : 'Select education' }}</option>
            @if($legacyEducation)
                <option value="{{ $educationLocale === 'ar' ? $educationAr : $educationEn }}" data-qualification="legacy" selected>{{ ($educationLocale === 'ar' ? $educationAr : $educationEn) ?: ($educationLocale === 'ar' ? $educationEn : $educationAr) }}</option>
            @endif
            @foreach($qualifications as $qualification)
                <option value="{{ $qualification->{'title_' . $educationLocale} }}" data-qualification="{{ $qualification->id }}" {{ $currentQualification && $currentQualification->id === $qualification->id ? 'selected' : '' }}>{{ $qualification->{'title_' . $educationLocale} }}</option>
            @endforeach
        </select>
        @error($educationField)<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
@endforeach
@once
    <script>
        document.addEventListener('change', function (event) {
            if (!event.target.matches('[data-education-select]')) return;
            const selected = event.target.selectedOptions[0];
            event.target.form.querySelectorAll('[data-education-select]').forEach(function (select) {
                if (select === event.target) return;
                const option = Array.from(select.options).find(function (option) {
                    return option.dataset.qualification === selected.dataset.qualification;
                });
                if (option) select.value = option.value;
            });
        });
    </script>
@endonce

@extends('admin.layout.indexs.index')
@section('page-title', 'تأكيد خروج العاملة ونموذج التسليم')
@section('css')
<style>
    .release-page { max-width: 1100px; margin: 0 auto; }
    .release-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2f6690 100%); color: #fff; border-radius: 14px; padding: 22px 26px; margin-bottom: 18px; }
    .release-hero h4 { color: #fff; margin: 0 0 6px; font-weight: 700; }
    .release-hero p { margin: 0; opacity: .85; font-size: 14px; line-height: 1.7; }
    .release-hero .hero-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
    .release-hero .hero-badge { background: rgba(255, 255, 255, .15); border: 1px solid rgba(255, 255, 255, .25); border-radius: 999px; padding: 4px 12px; font-size: 13px; }
    .release-section { background: #fff; border: 1px solid #e6e9ef; border-radius: 12px; margin-bottom: 16px; overflow: hidden; }
    .release-section-head { display: flex; align-items: center; gap: 10px; padding: 12px 18px; background: #f6f8fb; border-bottom: 1px solid #e6e9ef; }
    .release-section-head .num { width: 26px; height: 26px; border-radius: 50%; background: #2f6690; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
    .release-section-head h6 { margin: 0; font-weight: 700; color: #1e3a5f; }
    .release-section-head small { color: #8a94a6; margin-inline-start: auto; font-size: 12px; }
    .release-section-body { padding: 18px; }
    .release-field .form-label { font-weight: 600; font-size: 13.5px; color: #33415c; margin-bottom: 6px; display: flex; justify-content: space-between; gap: 8px; }
    .release-field .form-label .en { font-weight: 400; color: #9aa4b5; font-size: 12px; direction: ltr; }
    .release-field .form-label .req { color: #dc3545; margin-inline-start: 2px; }
    .release-field .form-control { border-radius: 8px; min-height: 40px; }
    .release-field .form-control:focus { border-color: #2f6690; box-shadow: 0 0 0 .2rem rgba(47, 102, 144, .15); }
    .release-choices { display: flex; flex-wrap: wrap; gap: 10px; }
    .release-choice { position: relative; margin: 0; cursor: pointer; }
    .release-choice input { position: absolute; opacity: 0; pointer-events: none; }
    .release-choice span { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1px solid #d5dbe5; border-radius: 999px; background: #fff; font-size: 13.5px; color: #33415c; transition: all .15s ease; }
    .release-choice span::before { content: ''; width: 14px; height: 14px; border: 1.5px solid #9aa4b5; border-radius: 4px; background: #fff; transition: all .15s ease; }
    .release-choice:hover span { border-color: #2f6690; }
    .release-choice input:checked + span { background: #eaf2f9; border-color: #2f6690; color: #1e3a5f; font-weight: 600; }
    .release-choice input:checked + span::before { background: #2f6690 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23fff' stroke-width='2.5' d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E") center/10px no-repeat; border-color: #2f6690; }
    .release-choice input:focus-visible + span { box-shadow: 0 0 0 .2rem rgba(47, 102, 144, .25); }
    .release-actions { position: sticky; bottom: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; background: #fff; border: 1px solid #e6e9ef; border-radius: 12px; padding: 14px 18px; box-shadow: 0 -4px 14px rgba(30, 58, 95, .06); }
    .release-actions .hint { color: #8a94a6; font-size: 12.5px; margin-inline-start: auto; }
    .release-actions .btn { min-width: 140px; border-radius: 8px; }
    #release-errors { white-space: pre-line; border-radius: 10px; }
</style>
@endsection
@section('content')
@php
    $fields = \App\Support\ShelterDocumentFields::fields('release');
    $sections = \App\Support\ShelterDocumentFields::sections('release');
    $requiredFields = ['worker_name', 'date', 'exit_time', 'recipient_name', 'recipient_id', 'recipient_phone', 'recipient_capacity', 'employee_name'];
    $sectionTitles = ['meta' => 'بيانات النموذج | Form Details', 'signatures' => 'التوقيعات | Signatures'];
    $sectionNumber = 0;
@endphp
<div class="release-page">
    <div class="release-hero">
        <h4><i class="fa fa-sign-out-alt"></i> {{ $actionLabel }}</h4>
        <p>استكمل بيانات التسليم ثم أكد الإجراء. تُحدّث بيانات العقد وتتاح الطباعة بالقالب الحالي. يمكنك رفع النموذج الموقّع لاحقًا ضمن مرفقات العقد.</p>
        <div class="hero-badges">
            <span class="hero-badge">عقد #{{ $contract->id }}</span>
            @if ($configuration->template?->name)
                <span class="hero-badge">القالب: {{ $configuration->template->name }}</span>
            @endif
        </div>
    </div>

    <div class="alert alert-danger d-none" id="release-errors" role="alert"></div>

    <form id="shelter-release-form" action="{{ $submitUrl }}" method="post">
        @csrf
        <input type="hidden" name="shelter_release_version" value="{{ $releaseVersion }}">
        @if ($action === 'deportation')<input type="hidden" name="complete_deportation" value="1">@endif

        @foreach ($sections as $sectionKey => [$sectionTitle, $sectionFields])
            @php
                [$titleAr, $titleEn] = \App\Support\ShelterDocumentFields::labelParts($sectionTitle ?: ($sectionTitles[$sectionKey] ?? ''));
                $sectionFields = array_values(array_filter($sectionFields, fn ($key) => isset($fields[$key])));
            @endphp
            @continue(!$sectionFields)
            <div class="release-section">
                <div class="release-section-head">
                    <span class="num">{{ ++$sectionNumber }}</span>
                    <h6>{{ $titleAr }}</h6>
                    @if ($titleEn)<small>{{ $titleEn }}</small>@endif
                </div>
                <div class="release-section-body">
                    <div class="row g-3">
                        @foreach ($sectionFields as $key)
                            @php
                                $field = $fields[$key];
                                [$labelAr, $labelEn] = \App\Support\ShelterDocumentFields::labelParts($field[0]);
                                $isRequired = in_array($key, $requiredFields, true);
                            @endphp
                            <div class="release-field {{ in_array($field[1], ['choices', 'textarea']) ? 'col-12' : ($sectionKey === 'meta' ? 'col-md-4' : 'col-md-6') }}">
                                <label class="form-label" for="release-{{ $key }}">
                                    <span>{{ $labelAr }}@if ($isRequired)<span class="req">*</span>@endif</span>
                                    @if ($labelEn)<span class="en">{{ $labelEn }}</span>@endif
                                </label>
                                @if ($field[1] === 'choices')
                                    <div class="release-choices" id="release-{{ $key }}">
                                        @foreach ($field[2] as $value => $label)
                                            <label class="release-choice">
                                                <input type="checkbox" name="shelter_release[{{ $key }}][]" value="{{ $value }}" {{ in_array($value, $values[$key] ?? [], true) ? 'checked' : '' }}>
                                                <span>{{ \App\Support\ShelterDocumentFields::labelParts($label)[0] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($field[1] === 'textarea')
                                    <textarea id="release-{{ $key }}" class="form-control" name="shelter_release[{{ $key }}]" rows="3" maxlength="5000">{{ $values[$key] ?? '' }}</textarea>
                                @else
                                    <input id="release-{{ $key }}" class="form-control" type="{{ $field[1] }}" name="shelter_release[{{ $key }}]" value="{{ $values[$key] ?? '' }}" maxlength="255" {{ $isRequired ? 'required' : '' }}>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        @if ($action === 'back')
            <div class="release-section">
                <div class="release-section-head">
                    <span class="num">{{ ++$sectionNumber }}</span>
                    <h6>تصفية رصيد الإيواء</h6>
                    <small>عند وجود رصيد، وبعد مراجعة المحاسبة</small>
                </div>
                <div class="release-section-body release-field">
                    <label class="form-label" for="release-write_off_reason"><span>سبب تصفية رصيد الإيواء</span></label>
                    <input id="release-write_off_reason" class="form-control" name="write_off_reason" maxlength="1000">
                </div>
            </div>
        @endif

        <div class="release-actions">
            <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> تأكيد الخروج وحفظ البيانات</button>
            <a class="btn btn-outline-secondary" href="{{ route('ShelterContract.show', $contract->id) }}">إلغاء</a>
            <span class="hint">الحقول المعلّمة بـ <span class="text-danger">*</span> مطلوبة</span>
        </div>
    </form>
</div>
@endsection
@section('js')
<script>
$('#shelter-release-form').on('submit', function (event) {
    event.preventDefault();
    const form = this;
    $(form).find('button[type="submit"]').prop('disabled', true);
    $('#release-errors').addClass('d-none').text('');
    $.ajax({url: form.action, method: 'POST', data: $(form).serialize(),
        success: function (response) {
            if (response.success === false) {
                $('#release-errors').removeClass('d-none').text(response.message || 'تعذر حفظ الإجراء.');
                window.scrollTo({top: 0, behavior: 'smooth'});
                return;
            }
            window.location.href = @json(route('ShelterContract.show', $contract->id));
        },
        error: function (xhr) {
            const response = xhr.responseJSON || {};
            const errors = response.errors ? Object.values(response.errors).flat().join('\n') : response.message;
            $('#release-errors').removeClass('d-none').text(errors || 'تعذر تأكيد الخروج. لم يتم حفظ النموذج؛ راجع البيانات وحاول مرة أخرى.');
            window.scrollTo({top: 0, behavior: 'smooth'});
        },
        complete: function () { $(form).find('button[type="submit"]').prop('disabled', false); }
    });
});
</script>
@endsection

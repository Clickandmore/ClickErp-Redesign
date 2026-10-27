@php $attachmentErrors = $errors->has('attachments') || $errors->has('attachments.*'); @endphp
<div class="mb-3 dispatch-attachments" data-dispatch-attachments data-max-files="10" data-max-mb="10">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <label class="form-label mb-0 fw-bold">
            <i class="fas fa-paperclip text-primary me-1"></i>
            مرفقات الإرسالية / فاتورة المورد <span class="text-muted fw-normal">(اختياري)</span>
        </label>
        <span class="badge rounded-pill bg-light text-secondary border" data-attachments-counter hidden></span>
    </div>

    <div class="dispatch-attachments__zone {{ $attachmentErrors ? 'is-invalid' : '' }}">
        <input type="file" name="attachments[]" class="dispatch-attachments-input" multiple
            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" aria-label="اختيار مرفقات الإرسالية">
        <span class="dispatch-attachments__icon"><i class="fas fa-cloud-upload-alt"></i></span>
        <div class="dispatch-attachments__title">اسحب الملفات وأفلتها هنا أو <span>اختر من جهازك</span></div>
        <div class="dispatch-attachments__types">
            <span><i class="far fa-file-image"></i> صور</span>
            <span><i class="far fa-file-pdf"></i> PDF</span>
            <span><i class="far fa-file-word"></i> Word</span>
            <span><i class="far fa-file-excel"></i> Excel</span>
        </div>
        <div class="dispatch-attachments__limits">حتى 10 ملفات في كل حفظ • بحد أقصى 10 ميجابايت للملف</div>
    </div>
    <div class="form-text"><i class="fas fa-info-circle me-1"></i>تُضاف الملفات الجديدة إلى المرفقات المحفوظة ولا تستبدلها.</div>

    <ul class="dispatch-attachments__list list-unstyled mt-2 mb-0" data-selected-attachments aria-label="الملفات المختارة للرفع" aria-live="polite" hidden></ul>
    <div class="text-danger small mt-1" data-attachments-warning hidden></div>

    @error('attachments')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @foreach($errors->get('attachments.*') as $messages)
        @foreach($messages as $message)<div class="text-danger small">{{ $message }}</div>@endforeach
    @endforeach
</div>

@include('admin.ingaz.RecruitmentContract.partials.dispatch-attachments-styles')

@once
    @push('js')
        <script src="{{ asset('admin/assets/js/dispatch-attachments.js') }}"></script>
    @endpush
@endonce

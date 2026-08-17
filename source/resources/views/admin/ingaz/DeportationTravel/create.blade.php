@extends('admin.layout.indexs.index')

@section('page-title')
    {{ isset($travel) && $travel ? 'تعديل رحلة ترحيل' : 'جدولة رحلة ترحيل' }}
@endsection

@section('css')
<style>
    .travel-form-page {
        max-width: 1180px;
        margin: 0 auto;
    }

    .travel-form-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 22px;
        padding: 22px 24px;
        color: #fff;
        border-radius: 18px;
        background: linear-gradient(135deg, #174a7e 0%, #2475b8 100%);
        box-shadow: 0 12px 28px rgba(23, 74, 126, .18);
    }

    .travel-form-hero h1 {
        margin: 0 0 5px;
        color: inherit;
        font-size: 24px;
    }

    .travel-form-hero p {
        margin: 0;
        color: rgba(255, 255, 255, .82);
    }

    .travel-form-hero__icon {
        display: grid;
        place-items: center;
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .14);
        font-size: 25px;
    }

    .travel-form-card {
        overflow: hidden;
        border: 1px solid #e4eaf1;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 8px 26px rgba(31, 51, 73, .07);
    }

    .travel-form-section {
        padding: 24px;
        border-bottom: 1px solid #edf1f5;
    }

    .travel-form-section:last-of-type {
        border-bottom: 0;
    }

    .travel-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        color: #163b60;
        font-size: 17px;
        font-weight: 700;
    }

    .travel-section-title span {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        color: #2475b8;
        background: #eaf4fc;
    }

    .travel-form-card .form-label {
        margin-bottom: 8px;
        color: #34475a;
        font-weight: 600;
    }

    .travel-form-card .form-control,
    .travel-form-card .select2-selection {
        border-color: #d7e0e9;
        border-radius: 10px;
    }

    .travel-form-card .form-control {
        min-height: 45px;
    }

    .travel-form-card textarea.form-control {
        min-height: 105px;
    }

    #contract_id + .select2-container .select2-selection--single,
    #arrival_airport + .select2-container .select2-selection--single {
        height: 46px;
        padding: 8px 10px;
    }

    #contract_id + .select2-container .select2-selection__rendered,
    #arrival_airport + .select2-container .select2-selection__rendered {
        line-height: 28px;
    }

    #contract_id + .select2-container .select2-selection__arrow,
    #arrival_airport + .select2-container .select2-selection__arrow {
        height: 44px;
    }

    .contract-preview {
        min-height: 78px;
        margin-top: 12px;
        padding: 14px 16px;
        border: 1px dashed #b9cde0;
        border-radius: 12px;
        background: #f8fbfe;
    }

    .contract-preview__title {
        margin-bottom: 5px;
        color: #183d60;
        font-weight: 700;
    }

    .contract-preview__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 18px;
        color: #68798a;
        font-size: 13px;
    }

    .single-trip-notice {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-top: 10px;
        color: #8a6412;
        font-size: 13px;
    }

    .travel-upload-box {
        padding: 18px;
        text-align: center;
        border: 1px dashed #b9cde0;
        border-radius: 12px;
        background: #f8fbfe;
    }

    .travel-upload-box .form-control {
        background: #fff;
    }

    .travel-upload-hint {
        display: block;
        margin-top: 8px;
        color: #718096;
        font-size: 12px;
    }

    .travel-files-list {
        margin-top: 22px;
        overflow: hidden;
        border: 1px solid #e4eaf1;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 8px 26px rgba(31, 51, 73, .06);
    }

    .travel-files-list__header {
        padding: 18px 22px;
        color: #163b60;
        border-bottom: 1px solid #edf1f5;
        font-size: 17px;
        font-weight: 700;
    }

    .travel-files-list .table {
        margin-bottom: 0;
    }

    .travel-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 18px 24px;
        background: #f8fafc;
    }

    .travel-form-actions .btn {
        min-width: 130px;
        padding: 10px 18px;
        border-radius: 10px;
    }

    @media (max-width: 767px) {
        .travel-form-hero,
        .travel-form-section,
        .travel-form-actions {
            padding: 18px;
        }

        .travel-form-hero h1 {
            font-size: 20px;
        }

        .travel-form-actions {
            flex-direction: column;
        }

        .travel-form-actions .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
@php
    $isEditingTrip = isset($travel) && $travel;
    $canManageTrip = $isEditingTrip
        ? @AdminGuardHasPerm(\App\PermKeys::TRAVEL_EDIT)
        : @AdminGuardHasPerm(\App\PermKeys::TRAVEL_CREATE);
    $selectedContractId = (string) old('contract_id', $travel->order_id ?? $contract->id ?? '');
    $rawArrivalAirport = old('arrival_airport', $travel->arrival_airport ?? '');
    $selectedArrivalAirport = filled($rawArrivalAirport)
        ? \App\Support\TravelAirports::label($rawArrivalAirport)
        : '';
    $selectedTravelMethod = old('travel_with', $travel->travel_with ?? 'مطار') ?: 'مطار';
    $selectedAssignedTo = (string) old('assigned_to', $travel->assigned_to ?? '');
@endphp

@if ($canManageTrip)
<div class="container-fluid travel-form-page">
    <div class="travel-form-hero">
        <div>
            <h1>{{ $isEditingTrip ? 'تعديل رحلة الترحيل' : 'جدولة رحلة ترحيل جديدة' }}</h1>
            <p>اختر عقداً واحداً ثم أدخل بيانات المغادرة والتذكرة.</p>
        </div>
        <div class="travel-form-hero__icon"><i class="fa fa-plane-departure"></i></div>
    </div>

    <form id="form-deportation-travel"
        action="{{ $isEditingTrip ? route('DeportationTravel.update', $travel->id) : route('DeportationTravel.store') }}"
        method="POST" enctype="multipart/form-data" class="travel-form-card">
        @csrf

        <section class="travel-form-section">
            <div class="travel-section-title">
                <span><i class="fa fa-file-contract"></i></span>
                اختيار العقد
            </div>

            <label for="contract_id" class="form-label">العقد المراد ترحيله <span class="text-danger">*</span></label>
            @if ($isEditingTrip)
                <input type="hidden" name="contract_id" value="{{ $selectedContractId }}">
            @endif
            <select name="{{ $isEditingTrip ? 'contract_display' : 'contract_id' }}" id="contract_id" class="form-control"
                {{ $isEditingTrip ? 'disabled' : 'required' }}>
                <option value="">ابحث برقم العقد أو اسم العميل أو العاملة</option>
                @foreach ($contracts as $contractOption)
                    <option value="{{ $contractOption->id }}"
                        {{ $selectedContractId === (string) $contractOption->id ? 'selected' : '' }}>
                        عقد #{{ $contractOption->id }}
                        | العميلة: {{ $contractOption->user->name ?? 'غير محدد' }}
                        | العاملة: {{ $contractOption->maid->name ?? 'غير محدد' }}
                        @if ($contractOption->info->msaned_number ?? $contractOption->msaned_number ?? null)
                            | مساند: {{ $contractOption->info->msaned_number ?? $contractOption->msaned_number }}
                        @endif
                        @if ($contractOption->user->national_num ?? null)
                            | الهوية: {{ $contractOption->user->national_num }}
                        @endif
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback d-block" data-error-for="contract_id">@error('contract_id'){{ $message }}@enderror</div>
            @if ($contracts->isEmpty())
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fa fa-exclamation-triangle me-1"></i>
                    لا توجد حالياً عقود في مرحلة الترحيل ومتاحة للجدولة.
                </div>
            @endif

            <div id="selected_contract_preview" class="contract-preview">
                <span class="text-muted">اختر عقداً لعرض بياناته هنا.</span>
            </div>
            <div class="single-trip-notice">
                <i class="fa fa-info-circle"></i>
                رحلة الترحيل فردية وترتبط بعقد واحد فقط، ولا يمكن تجميع عدة عقود في رحلة واحدة.
            </div>
        </section>

        <section class="travel-form-section">
            <div class="travel-section-title">
                <span><i class="fa fa-route"></i></span>
                بيانات الرحلة
            </div>

            <div class="row">
                <div class="col-lg-7 mb-3">
                    <label for="arrival_airport" class="form-label">مطار المغادرة <span class="text-danger">*</span></label>
                    <select name="arrival_airport" id="arrival_airport" class="form-control" required>
                        <option value="">اختر المطار</option>
                        @if ($selectedArrivalAirport && !collect($arrivalAirports)->contains('value', $selectedArrivalAirport))
                            <option value="{{ $selectedArrivalAirport }}" selected>{{ $selectedArrivalAirport }}</option>
                        @endif
                        @foreach ($arrivalAirports as $airport)
                            <option value="{{ $airport['value'] }}"
                                {{ $selectedArrivalAirport === $airport['value'] ? 'selected' : '' }}>
                                {{ $airport['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback d-block" data-error-for="arrival_airport">@error('arrival_airport'){{ $message }}@enderror</div>
                </div>

                <div class="col-lg-5 mb-3">
                    <label for="travel_with" class="form-label">وسيلة السفر <span class="text-danger">*</span></label>
                    <select name="travel_with" id="travel_with" class="form-control" required>
                        <option value="">اختر وسيلة السفر</option>
                        @foreach (['مطار', 'نقل جماعي', 'قطار'] as $travelMethod)
                            <option value="{{ $travelMethod }}"
                                {{ $selectedTravelMethod === $travelMethod ? 'selected' : '' }}>
                                {{ $travelMethod }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback d-block" data-error-for="travel_with">@error('travel_with'){{ $message }}@enderror</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="arrival_date" class="form-label">تاريخ الرحلة <span class="text-danger">*</span></label>
                    <input type="date" name="arrival_date" id="arrival_date" class="form-control"
                        value="{{ old('arrival_date', isset($travel->arrival_date) ? \Carbon\Carbon::parse($travel->arrival_date)->format('Y-m-d') : '') }}"
                        required>
                    <div class="invalid-feedback d-block" data-error-for="arrival_date">@error('arrival_date'){{ $message }}@enderror</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="arrival_time" class="form-label">وقت الرحلة <span class="text-danger">*</span></label>
                    <input type="time" name="arrival_time" id="arrival_time" class="form-control"
                        value="{{ old('arrival_time', $travel->arrival_time ?? '') }}" required>
                    <div class="invalid-feedback d-block" data-error-for="arrival_time">@error('arrival_time'){{ $message }}@enderror</div>
                </div>
            </div>
        </section>

        <section class="travel-form-section">
            <div class="travel-section-title">
                <span><i class="fa fa-ticket-alt"></i></span>
                بيانات التذكرة والملاحظات
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="flight_company" class="form-label">شركة الطيران</label>
                    <input type="text" name="flight_company" id="flight_company" class="form-control"
                        value="{{ old('flight_company', $travel->flight_company ?? '') }}" placeholder="مثال: الخطوط السعودية">
                    <div class="invalid-feedback d-block" data-error-for="flight_company">@error('flight_company'){{ $message }}@enderror</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="flight_number" class="form-label">رقم الرحلة</label>
                    <input type="text" name="flight_number" id="flight_number" class="form-control"
                        value="{{ old('flight_number', $travel->flight_number ?? '') }}" placeholder="مثال: SV123">
                    <div class="invalid-feedback d-block" data-error-for="flight_number">@error('flight_number'){{ $message }}@enderror</div>
                </div>

                <div class="col-12">
                    <label for="notes" class="form-label">ملاحظات</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"
                        placeholder="أي تعليمات أو تفاصيل إضافية عن الرحلة">{{ old('notes', $travel->notes ?? '') }}</textarea>
                    <div class="invalid-feedback d-block" data-error-for="notes">@error('notes'){{ $message }}@enderror</div>
                </div>
            </div>
        </section>

        <section class="travel-form-section">
            <div class="travel-section-title">
                <span><i class="fa fa-user-check"></i></span>
                إسناد الرحلة
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="assigned_to" class="form-label">السائق أو الموظف المسؤول</label>
                    <select name="assigned_to" id="assigned_to" class="form-control">
                        <option value="">بدون إسناد حالياً</option>
                        @foreach ($assignableStaff as $staff)
                            <option value="{{ $staff->id }}"
                                {{ $selectedAssignedTo === (string) $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }} ({{ $staff->admin_type ?: 'موظف' }})
                                @if ($staff->contact_phone)
                                    | {{ $staff->contact_phone }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">سيصل إشعار داخل النظام إلى الشخص الذي تُسند إليه الرحلة.</small>
                    <div class="invalid-feedback d-block" data-error-for="assigned_to">@error('assigned_to'){{ $message }}@enderror</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="assignment_note" class="form-label">ملاحظة التكليف</label>
                    <textarea name="assignment_note" id="assignment_note" class="form-control" rows="3"
                        placeholder="مثال: التواصل مع العاملة قبل موعد الرحلة بساعة">{{ old('assignment_note', $travel->assignment_note ?? '') }}</textarea>
                    <div class="invalid-feedback d-block" data-error-for="assignment_note">@error('assignment_note'){{ $message }}@enderror</div>
                </div>
            </div>
        </section>

        <section class="travel-form-section">
            <div class="travel-section-title">
                <span><i class="fa fa-paperclip"></i></span>
                مرفقات الرحلة
            </div>

            <div class="travel-upload-box">
                <label for="files" class="form-label">اختر ملفاً أو عدة ملفات</label>
                <input type="file" name="files[]" id="files" class="form-control" multiple
                    accept=".pdf,.jpg,.jpeg,.png">
                <span class="travel-upload-hint">
                    الصيغ المسموحة: PDF وJPG وPNG — الحد الأقصى 4 ميجابايت لكل ملف.
                </span>
                <div class="invalid-feedback d-block" data-error-for="files">
                    @error('files'){{ $message }}@enderror
                    @error('files.*'){{ $message }}@enderror
                </div>
            </div>
        </section>

        <div class="travel-form-actions">
            <a href="{{ route('DeportationTravel.index') }}" class="btn btn-light border">
                <i class="fa fa-arrow-right me-1"></i> رجوع
            </a>
            <button type="submit" id="submitTravelBtn" class="btn btn-success">
                <i class="fa fa-save me-1"></i> {{ $isEditingTrip ? 'تحديث الرحلة' : 'جدولة الرحلة' }}
            </button>
        </div>
    </form>

    @if ($isEditingTrip)
        <div class="travel-files-list">
            <div class="travel-files-list__header">
                <i class="fa fa-folder-open me-1"></i> المرفقات الحالية
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>اسم الملف</th>
                            <th>تاريخ الرفع</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="travelFilesTableBody">
                        @forelse ($travel->files as $file)
                            <tr data-file-row="{{ $file->id }}">
                                <td><i class="fa fa-file me-1 text-primary"></i> {{ $file->file }}</td>
                                <td>{{ optional($file->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('travel.file.download', $file->id) }}"
                                        class="btn btn-sm btn-outline-success">
                                        <i class="fa fa-download"></i> تحميل
                                    </a>
                                    @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_DELETE))
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-travel-file"
                                            data-file-id="{{ $file->id }}">
                                            <i class="fa fa-trash"></i> حذف
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="noTravelFilesRow">
                                <td colspan="3" class="text-center text-muted py-4">لا توجد مرفقات مرفوعة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@else
    @include('components.no_permissions')
@endif
@endsection

@push('js')
<script>
$(function () {
    const contracts = @json($contracts->values());
    const deleteFileRouteTemplate = @json(route('travel.file.delete', ['id' => '__ID__']));
    const $form = $('#form-deportation-travel');
    const $submitButton = $('#submitTravelBtn');

    $('#contract_id, #arrival_airport, #assigned_to').select2({
        width: '100%',
        dir: '{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}'
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function updateContractPreview() {
        const selectedId = String($('#contract_id').val() || '');
        const selected = contracts.find(contract => String(contract.id) === selectedId);

        if (!selected) {
            $('#selected_contract_preview').html('<span class="text-muted">اختر عقداً لعرض بياناته هنا.</span>');
            return;
        }

        const client = selected.user && selected.user.name ? selected.user.name : 'غير محدد';
        const maid = selected.maid && selected.maid.name ? selected.maid.name : 'غير محدد';
        const phone = selected.user && selected.user.phone ? selected.user.phone : '-';
        const city = selected.user && selected.user.city ? selected.user.city : 'غير محدد';

        $('#selected_contract_preview').html(`
            <div class="contract-preview__title">عقد #${escapeHtml(selected.id)} — ${escapeHtml(client)}</div>
            <div class="contract-preview__meta">
                <span><i class="fa fa-female me-1"></i> العاملة: ${escapeHtml(maid)}</span>
                <span><i class="fa fa-phone me-1"></i> الجوال: ${escapeHtml(phone)}</span>
                <span><i class="fa fa-map-marker-alt me-1"></i> المدينة: ${escapeHtml(city)}</span>
            </div>
        `);
    }

    $('#contract_id').on('change', updateContractPreview);
    updateContractPreview();

    $('.delete-travel-file').on('click', function () {
        const fileId = $(this).data('file-id');
        const $button = $(this);

        Swal.fire({
            icon: 'warning',
            title: 'حذف المرفق',
            text: 'هل تريد حذف هذا المرفق؟',
            showCancelButton: true,
            confirmButtonText: 'نعم، حذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $button.prop('disabled', true);
            $.ajax({
                url: deleteFileRouteTemplate.replace('__ID__', fileId),
                type: 'DELETE',
                success: function (response) {
                    $('[data-file-row="' + fileId + '"]').remove();
                    if ($('#travelFilesTableBody tr').length === 0) {
                        $('#travelFilesTableBody').html(
                            '<tr id="noTravelFilesRow"><td colspan="3" class="text-center text-muted py-4">لا توجد مرفقات مرفوعة حالياً.</td></tr>'
                        );
                    }
                    Swal.fire('تم', response.message || 'تم حذف المرفق بنجاح', 'success');
                },
                error: function (xhr) {
                    $button.prop('disabled', false);
                    Swal.fire('خطأ', xhr.responseJSON?.message || 'تعذر حذف المرفق.', 'error');
                }
            });
        });
    });

    $form.on('submit', function (event) {
        event.preventDefault();
        const originalButtonHtml = $submitButton.html();

        $('[data-error-for]').empty();
        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            beforeSend: function () {
                $submitButton.prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2"></span> جاري الحفظ...');
            },
            success: function (response) {
                handleSuccessResponce(response);
            },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
                const messages = [];

                Object.keys(errors).forEach(function (field) {
                    const inputName = field.split('.')[0];
                    const message = errors[field][0];
                    messages.push(message);
                    const inputSelector = inputName === 'files'
                        ? '[name="files[]"]'
                        : '[name="' + inputName + '"]';
                    $(inputSelector).addClass('is-invalid');
                    $('[data-error-for="' + inputName + '"]').text(message);
                });

                Swal.fire({
                    icon: 'error',
                    title: 'تعذر حفظ الرحلة',
                    html: messages.length
                        ? messages.map(message => `<div>${escapeHtml(message)}</div>`).join('')
                        : escapeHtml(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'حدث خطأ من الخادم.')
                });
            },
            complete: function () {
                $submitButton.prop('disabled', false).html(originalButtonHtml);
            }
        });
    });
});
</script>
@endpush

@extends('admin.layout.indexs.index')
@section('content')
@php
    $isEditMode = $isEditMode ?? false;
    $formAction = $isEditMode
        ? route('SponsorshipTransfer.update', $transfer->id)
        : route('SponsorshipTransfer.store');
@endphp
<div class="row justify-content-center">
    <div class="col-lg-10 col-md-12">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-bottom-0 pb-0">
                <h3 class="card-title mb-0" style="font-weight:700;font-size:1.5rem;">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    {{ $isEditMode ? 'تعديل طلب نقل الكفالة' : helperTrans("ingaz.add_sponsorship_transfer_request") }}
                </h3>
                @if(!$isEditMode && isset($selectedOrder) && $selectedOrder)
                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-info-circle me-2"></i>طلب من الموقع</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>رقم الطلب:</strong> {{ $selectedOrder->order_code }}<br>
                                <strong>العميل:</strong> {{ $selectedOrder->user ? $selectedOrder->user->name : 'غير محدد' }}<br>
                                <strong>الهاتف:</strong> {{ $selectedOrder->user ? $selectedOrder->user->phone : 'غير محدد' }}
                            </div>
                            <div class="col-md-6">
                                <strong>العاملة المطلوبة:</strong> {{ $selectedOrder->maid ? $selectedOrder->maid->name : 'غير محدد' }}<br>
                                <strong>الجنسية:</strong> {{ $selectedOrder->maid && $selectedOrder->maid->nationality ? $selectedOrder->maid->nationality->sub_title : 'غير محدد' }}<br>
                                <strong>تاريخ الطلب:</strong> {{ $selectedOrder->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-body pt-3">
                @if(session('warning'))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                    </div>
                @endif
                <form id="sponsorshipTransferForm" class="row g-4" enctype="multipart/form-data">
                    @csrf
                    @if($isEditMode)
                        @method('PUT')
                    @endif
                    <!-- اختيار العقد -->
                    <div class="col-12">
                        <label class="form-label fw-bold">اختيار العقد المتاح للنقل <span class="text-danger">*</span></label>
                        <select name="contract_id" id="contract_id" class="form-select select2" required>
                            <option value="">اختر العقد</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}"
                                        data-maid-name="{{ $contract->maid ? $contract->maid->name : 'غير محدد' }}"
                                        data-maid-passport="{{ $contract->maid ? $contract->maid->passport_num : 'غير محدد' }}"
                                        data-maid-nationality="{{ $contract->maid && $contract->maid->nationality ? ($contract->maid->nationality->translations->where('locale', app()->getLocale())->first()->sub_title ?? $contract->maid->nationality->translations->first()->sub_title) : 'غير محدد' }}"
                                        data-sponsor-name="{{ $contract->user ? $contract->user->name : 'غير محدد' }}"
                                        data-sponsor-id="{{ $contract->user ? ($contract->user->documentNumber() ?: 'غير محدد') : 'غير محدد' }}"
                                        data-sponsor-phone="{{ $contract->user ? $contract->user->phone : 'غير محدد' }}"
                                        data-sponsor-city="{{ $contract->user ? $contract->user->city : 'غير محدد' }}"
                                        {{ $selectedContractId == $contract->id ? 'selected' : '' }}>
                                    عقد رقم: {{ $contract->id }} -
                                    {{ $contract->maid ? $contract->maid->name : 'غير محدد' }} -
                                    {{ $contract->user ? $contract->user->name : 'غير محدد' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- معلومات العقد والكفيل الحالي -->
                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="mb-2 text-muted small">معلومات العقد</div>
                            <div class="mb-1"><span class="fw-bold">رقم العقد:</span> <span id="selected_contract_number">-</span></div>
                            <div class="mb-1"><span class="fw-bold">اسم العاملة:</span> <span id="selected_maid_name">-</span></div>
                            <div class="mb-1"><span class="fw-bold">رقم الجواز:</span> <span id="selected_passport_number">-</span></div>
                            <div class="mb-1"><span class="fw-bold">الجنسية:</span> <span id="selected_nationality">-</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-muted small">معلومات الكفيل الحالي</div>
                                <a href="#" id="view_current_sponsor_btn" class="btn btn-sm btn-outline-info" style="display: none;">
                                    <i class="fas fa-eye me-1"></i>عرض العميل
                                </a>
                            </div>
                            <div class="mb-1"><span class="fw-bold">الاسم:</span> <span id="current_sponsor_name">-</span></div>
                            <div class="mb-1"><span class="fw-bold">رقم الهوية:</span> <span id="current_sponsor_id">-</span></div>
                            <div class="mb-1"><span class="fw-bold">الجوال:</span> <span id="current_sponsor_phone">-</span></div>
                            <div class="mb-1"><span class="fw-bold">المدينة:</span> <span id="current_sponsor_city">-</span></div>
                        </div>
                    </div>
                    <!-- الكفيل الجديد -->
                    <div class="col-12">
                        <label class="form-label fw-bold d-block">العميل / الكفيل الجديد <span class="text-danger">*</span></label>
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="new_sponsor_mode" id="new_sponsor_mode_existing" value="existing" checked>
                                    <label class="form-check-label fw-semibold" for="new_sponsor_mode_existing">
                                        اختيار عميل موجود
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="new_sponsor_mode" id="new_sponsor_mode_new" value="new">
                                    <label class="form-check-label fw-semibold" for="new_sponsor_mode_new">
                                        إضافة عميل جديد
                                    </label>
                                </div>
                            </div>

                            <div id="existing_sponsor_wrapper">
                                <label class="form-label fw-bold">اختيار الكفيل الجديد <span class="text-danger">*</span></label>
                                <select name="new_sponsor_id" id="new_sponsor_id" class="form-select select2" required></select>
                                <div class="form-text">ابحث بالاسم أو رقم الجوال أو رقم الهوية.</div>
                            </div>

                            <div id="new_sponsor_fields" class="row g-3 d-none">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">اسم العميل <span class="text-danger">*</span></label>
                                    <input type="text" name="new_sponsor_name" id="new_sponsor_name" class="form-control" maxlength="255" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">رقم الجوال <span class="text-danger">*</span></label>
                                    <input type="text" name="new_sponsor_phone" id="new_sponsor_phone" class="form-control" maxlength="20" inputmode="tel" placeholder="05XXXXXXXX" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">رقم الهوية</label>
                                    <input type="text" name="new_sponsor_national_num" id="new_sponsor_national_num" class="form-control" maxlength="50" inputmode="numeric" placeholder="اختياري" disabled>
                                    <div class="form-text">رقم الهوية اختياري عند الإضافة من هذه الشاشة.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- معلومات العمل التجريبي والتكاليف -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">عدد أيام العمل التجريبي <span class="text-danger">*</span></label>
                        <input type="number" name="trial_days" class="form-control" min="1" max="90" value="{{ old('trial_days', $transfer->trial_days ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">تاريخ بداية العمل التجريبي <span class="text-danger">*</span></label>
                        <input type="date" name="trial_start_date" class="form-control" value="{{ old('trial_start_date', isset($transfer) && $transfer->trial_start_date ? $transfer->trial_start_date->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">قيمة العقد <span class="text-danger">*</span></label>
                        <input type="number" name="contract_value" id="contract_value" class="form-control" min="0" step="0.01" value="{{ old('contract_value', $transfer->contract_value ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">المبلغ المدفوع <span class="text-danger">*</span></label>
                        <input type="number" name="paid_amount" id="paid_amount" class="form-control" min="0" step="0.01" value="{{ old('paid_amount', $transfer->paid_amount ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">المبلغ المتبقي</label>
                        <input type="number" name="remaining_amount" id="remaining_amount" class="form-control bg-white" value="{{ old('remaining_amount', $transfer->remaining_amount ?? '') }}" readonly>
                    </div>
                    <div class="col-12">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="fw-bold">
                                    <i class="fas fa-credit-card text-primary me-2"></i>بيانات الدفع
                                </div>
                                <small class="text-muted">طريقة الدفع وحالة السداد والمرفقات</small>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="payment_type" class="form-label fw-bold">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select name="payment_type" id="payment_type" class="form-select" required>
                                        <option value="">اختر الطريقة</option>
                                        @php($selectedPaymentType = old('payment_type', $transfer->payment_type ?? ''))
                                        <option value="cash" {{ $selectedPaymentType === 'cash' ? 'selected' : '' }}>نقداً</option>
                                        <option value="bank_transfer" {{ $selectedPaymentType === 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                                        <option value="mada" {{ $selectedPaymentType === 'mada' ? 'selected' : '' }}>مدى</option>
                                        <option value="tabby" {{ $selectedPaymentType === 'tabby' ? 'selected' : '' }}>تابي</option>
                                        <option value="tamara" {{ $selectedPaymentType === 'tamara' ? 'selected' : '' }}>تمارا</option>
                                        <option value="pay_later" {{ $selectedPaymentType === 'pay_later' ? 'selected' : '' }}>الدفع لاحقاً</option>
                                        <option value="other" {{ $selectedPaymentType === 'other' ? 'selected' : '' }}>أخرى</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="payment_status" class="form-label fw-bold">حالة الدفع <span class="text-danger">*</span></label>
                                    <select name="payment_status" id="payment_status" class="form-select" required>
                                        <option value="">اختر الحالة</option>
                                        @php($selectedPaymentStatus = old('payment_status', $transfer->payment_status ?? ''))
                                        <option value="paid" {{ $selectedPaymentStatus === 'paid' ? 'selected' : '' }}>تم الدفع</option>
                                        <option value="partial" {{ $selectedPaymentStatus === 'partial' ? 'selected' : '' }}>مدفوع جزئياً</option>
                                        <option value="pending" {{ $selectedPaymentStatus === 'pending' ? 'selected' : '' }}>معلق</option>
                                        <option value="later" {{ $selectedPaymentStatus === 'later' ? 'selected' : '' }}>الدفع لاحقاً</option>
                                        <option value="bank_pending" {{ $selectedPaymentStatus === 'bank_pending' ? 'selected' : '' }}>بانتظار التحويل</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="payment_receipt" class="form-label fw-bold">إيصال الدفع</label>
                                    <input type="file" name="payment_receipt" id="payment_receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="form-text d-none" id="payment_receipt_hint">الإيصال مطلوب عند اختيار تم الدفع أو مدفوع جزئياً.</div>
                                    @if($isEditMode && $transfer->payment_receipt_path)
                                        <div class="form-text text-success">يوجد إيصال محفوظ حالياً، ورفع ملف جديد سيستبدله.</div>
                                    @endif
                                </div>
                                <div class="col-md-6 d-none" id="receipt_account_wrapper">
                                    <label for="receipt_account_id" class="form-label fw-bold">البنك / حساب السداد <span class="text-danger">*</span></label>
                                    <select name="receipt_account_id" id="receipt_account_id" class="form-select">
                                        <option value="">اختر الحساب الذي سيصدر عليه سند القبض</option>
                                        @foreach($receiptAccounts as $account)
                                            <option value="{{ $account->id }}" @selected((string) old('receipt_account_id', $transfer->receipt_account_id ?? '') === (string) $account->id)>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($receiptAccounts->isEmpty())
                                        <div class="form-text text-danger">لا توجد حسابات بنك أو صندوق أو محفظة صالحة في الدليل المحاسبي.</div>
                                    @else
                                        <div class="form-text">سيُصدر سند القبض على هذا الحساب ويرتبط بطلب نقل الكفالة والعميل.</div>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <label for="attachments" class="form-label fw-bold">مرفقات إضافية</label>
                                    <input type="file" name="attachments[]" id="attachments" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" multiple>
                                    <div class="form-text">يمكن رفع أكثر من ملف متعلق بطلب نقل الكفالة.</div>
                                    @if($isEditMode && !empty($transfer->attachments))
                                        <div class="form-text text-success">عدد المرفقات المحفوظة حالياً: {{ count($transfer->attachments) }}. رفع مرفقات جديدة سيضيفها إلى القائمة.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="اكتب أي ملاحظات إضافية هنا...">{{ old('notes', $transfer->notes ?? '') }}</textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-2"></i>رجوع
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>{{ $isEditMode ? 'حفظ التعديلات' : 'حفظ الطلب' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
$(document).ready(function() {
    $('.select2').not('#new_sponsor_id').select2({ placeholder: 'اختر...', allowClear: true });

    $('#new_sponsor_id').select2({
        placeholder: 'ابحث عن الكفيل الجديد...',
        allowClear: true,
        ajax: {
            url: '{{ route('Contract.searchClients') }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { search: params.term };
            },
            processResults: function(data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    function toggleSponsorMode() {
        const mode = $('input[name="new_sponsor_mode"]:checked').val();
        const isExistingMode = mode === 'existing';

        $('#existing_sponsor_wrapper').toggleClass('d-none', !isExistingMode);
        $('#new_sponsor_fields').toggleClass('d-none', isExistingMode);

        $('#new_sponsor_id').prop('disabled', !isExistingMode).prop('required', isExistingMode).trigger('change.select2');
        $('#new_sponsor_name, #new_sponsor_phone, #new_sponsor_national_num').prop('disabled', isExistingMode);
        $('#new_sponsor_name, #new_sponsor_phone').prop('required', !isExistingMode);
    }

    @if(isset($selectedNewSponsorId) && $selectedNewSponsorId && isset($selectedNewSponsor))
        var presetSponsor = new Option(
            '{{ $selectedNewSponsor->name }} - {{ $selectedNewSponsor->phone_code . $selectedNewSponsor->phone }}',
            '{{ $selectedNewSponsorId }}',
            true,
            true
        );
        $('#new_sponsor_id').append(presetSponsor).trigger('change');
    @endif

    $('input[name="new_sponsor_mode"]').on('change', toggleSponsorMode);
    toggleSponsorMode();

    // تعبئة معلومات العقد والكفيل الحالي عند اختيار العقد
    $('#contract_id').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        if ($(this).val()) {
            $('#selected_contract_number').text('عقد رقم: ' + $(this).val());
            $('#selected_maid_name').text(selectedOption.data('maid-name'));
            $('#selected_passport_number').text(selectedOption.data('maid-passport'));
            $('#selected_nationality').text(selectedOption.data('maid-nationality'));
            $('#current_sponsor_name').text(selectedOption.data('sponsor-name'));
            $('#current_sponsor_id').text(selectedOption.data('sponsor-id'));
            $('#current_sponsor_phone').text(selectedOption.data('sponsor-phone'));
            $('#current_sponsor_city').text(selectedOption.data('sponsor-city'));
        } else {
            $('#selected_contract_number, #selected_maid_name, #selected_passport_number, #selected_nationality, #current_sponsor_name, #current_sponsor_id, #current_sponsor_phone, #current_sponsor_city').text('-');
        }
    });
    // تفعيل العقد المحدد تلقائياً إذا كان موجوداً
    @if($selectedContractId)
        $('#contract_id').trigger('change');
    @endif
    // حساب المبلغ المتبقي تلقائياً
    $('#contract_value, #paid_amount').on('input', function() {
        let contractValue = parseFloat($('#contract_value').val()) || 0;
        let paidAmount = parseFloat($('#paid_amount').val()) || 0;
        let remaining = contractValue - paidAmount;
        $('#remaining_amount').val(remaining >= 0 ? remaining.toFixed(2) : 0);
    });

    function syncPaymentReceiptRequirement() {
        const paymentStatus = $('#payment_status').val();
        const hasSavedReceipt = {{ $isEditMode && !empty($transfer->payment_receipt_path) ? 'true' : 'false' }};
        const isPaid = paymentStatus === 'paid' || paymentStatus === 'partial';
        const receiptRequired = isPaid && !hasSavedReceipt;
        $('#payment_receipt').prop('required', receiptRequired);
        $('#payment_receipt_hint').toggleClass('d-none', !receiptRequired);
        $('#receipt_account_wrapper').toggleClass('d-none', !isPaid);
        $('#receipt_account_id').prop('required', isPaid);
    }

    $('#payment_status').on('change', syncPaymentReceiptRequirement);
    syncPaymentReceiptRequirement();

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function collectErrorMessages(response) {
        const messages = [];

        if (response && response.message) {
            messages.push(response.message);
        }

        if (response && response.errors) {
            Object.values(response.errors).forEach(function(fieldMessages) {
                if (Array.isArray(fieldMessages)) {
                    fieldMessages.forEach(function(message) {
                        if (message && !messages.includes(message)) {
                            messages.push(message);
                        }
                    });
                }
            });
        }

        return messages.length ? messages : ['حدث خطأ غير متوقع'];
    }

    function showErrorResponse(response, fallbackMessage) {
        const messages = collectErrorMessages(response || { message: fallbackMessage });
        let html = '<div class="text-start" dir="rtl">';

        if (messages.length === 1) {
            html += '<p class="mb-0">' + escapeHtml(messages[0]) + '</p>';
        } else {
            html += '<ul class="mb-0 pe-3">';
            messages.forEach(function(message) {
                html += '<li>' + escapeHtml(message) + '</li>';
            });
            html += '</ul>';
        }

        if (response && response.debug) {
            html += '<hr class="my-3">';
            html += '<div class="small text-muted" dir="ltr" style="text-align:left; white-space:normal;">';
            html += '<div><strong>Exception:</strong> ' + escapeHtml(response.debug.exception) + '</div>';
            html += '<div><strong>File:</strong> ' + escapeHtml(response.debug.file) + ':' + escapeHtml(response.debug.line) + '</div>';
            html += '</div>';
        }

        html += '</div>';

        Swal.fire({
            icon: 'error',
            title: 'خطأ!',
            html: html,
            width: 650
        });
    }

    // إرسال الطلب
    $('#sponsorshipTransferForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
        $.ajax({
            url: '{{ $formAction }}',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحفظ',
                        text: response.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '{{ route("SponsorshipTransfer.index") }}';
                    });
                } else {
                    showErrorResponse(response, 'حدث خطأ في حفظ طلب نقل الكفالة');
                }
            },
            error: function(xhr) {
                showErrorResponse(xhr.responseJSON, 'حدث خطأ غير متوقع');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>{{ $isEditMode ? 'حفظ التعديلات' : 'حفظ الطلب' }}');
            }
        });
    });
});
</script>
@endsection

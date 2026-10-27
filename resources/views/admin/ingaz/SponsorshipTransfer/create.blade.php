@extends('admin.layout.indexs.index')
@section('content')
@php
    $isEditMode = $isEditMode ?? false;
    $formAction = $isEditMode
        ? route('SponsorshipTransfer.update', $transfer->id)
        : route('SponsorshipTransfer.store');
    $workflowSource = $workflowSource ?? ($transfer->workflow_source ?? 'standard');
    $isRentalWorkflow = $workflowSource === 'rental';

    // حدود الرفع من إعدادات PHP لتنبيه المستخدم قبل الإرسال بدلاً من فشل صامت (0 = بلا حد)
    $iniToBytes = static function ($value): int {
        $value = trim((string) $value);
        if ($value === '' || $value === '-1') {
            return 0;
        }
        $number = (float) $value;
        return (int) match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    };
    $uploadLimitBytes = [
        'total' => $iniToBytes(ini_get('post_max_size')),
        'file' => $iniToBytes(ini_get('upload_max_filesize')),
    ];
    $uploadLimitLabels = [
        'total' => (string) ini_get('post_max_size'),
        'file' => (string) ini_get('upload_max_filesize'),
    ];
@endphp
<div class="row justify-content-center">
    <div class="col-lg-10 col-md-12">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-bottom-0 pb-0">
                <h3 class="card-title mb-0" style="font-weight:700;font-size:1.5rem;">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    {{ $isEditMode ? 'تعديل طلب نقل الخدمات' : helperTrans("ingaz.add_sponsorship_transfer_request") }}
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
                    <input type="hidden" name="workflow_source" value="{{ $workflowSource }}">
                    @if($isEditMode)
                        @method('PUT')
                    @endif
                    @if($isRentalWorkflow)
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                هذا نقل الخدمات لعاملة إيجار. سيستمر تسلسل النقل والتجربة كالمعتاد دون إنشاء قيود أو سندات أو فاتورة مالية.
                            </div>
                        </div>
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
                                    عقد رقم: {{ $contract->id }} — فرع المصدر: {{ $contract->branch?->name ?? 'غير محدد' }} -
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
                    <!-- المسوق المسؤول عن الطلب -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold">المسوق <span class="text-danger">*</span></label>
                        <select name="marketer_id" id="marketer_id" class="form-select @error('marketer_id') is-invalid @enderror" required>
                            <option value="">اختر المسوق</option>
                            @foreach ($marketers ?? collect() as $marketer)
                                <option value="{{ $marketer->id }}" {{ old('marketer_id', $transfer->marketer_id ?? null) == $marketer->id ? 'selected' : '' }}>
                                    {{ $marketer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('marketer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">الموظف/المسوق المسؤول عن طلب نقل الخدمات.</div>
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
                    @unless($isRentalWorkflow)
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
                                        @if (\App\Support\MoyasarSettings::isEnabled() || $selectedPaymentType === 'moyasar')
                                            <option value="moyasar" {{ $selectedPaymentType === 'moyasar' ? 'selected' : '' }}>ميسر: فيزا، مدى، ماستر كارد، أبل باي</option>
                                        @endif
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
                                    @php($selectedReceiptAccountId = old('receipt_account_id', $transfer->receipt_account_id ?? ''))
                                    <select name="receipt_account_id" id="receipt_account_id" class="form-select">
                                        <option value="">اختر الحساب الذي سيصدر عليه سند القبض</option>
                                        @foreach($receiptAccounts as $account)
                                            <option value="{{ $account->id }}" {{ (string) $selectedReceiptAccountId === (string) $account->id ? 'selected' : '' }}>
                                                {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($receiptAccounts->isEmpty())
                                        <div class="form-text text-danger">لا توجد حسابات بنك أو صندوق أو محفظة صالحة في الدليل المحاسبي.</div>
                                    @else
                                        <div class="form-text">سيُصدر سند القبض على هذا الحساب ويرتبط بطلب نقل الخدمات والعميل.</div>
                                    @endif
                                </div>
                                <div class="col-12 d-none" id="moyasar_payment_hint">
                                    @if ($isEditMode && !empty($transfer->moyasar_invoice_id))
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-lock me-1"></i>
                                            أُنشئت فاتورة ميسر لهذا الطلب؛ طريقة الدفع وحالته والمبالغ تُحدّث من ميسر فقط.
                                        </div>
                                    @else
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-credit-card me-1"></i>
                                            بعد الحفظ يُنشأ رابط دفع ميسر بقيمة المتبقي ويُرسل للعميل الجديد برسالة نصية.
                                            يُسجَّل السداد وسند القبض على الحساب المختار تلقائياً عند تأكيد ميسر، لذلك يبقى المدفوع صفراً والحالة "معلق".
                                        </div>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <label for="attachments" class="form-label fw-bold">مرفقات إضافية</label>
                                    <input type="file" name="attachments[]" id="attachments" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" multiple>
                                    <div class="form-text">يمكن رفع أكثر من ملف متعلق بطلب نقل الخدمات.</div>
                                    @if($isEditMode && !empty($transfer->attachments))
                                        <div class="form-text text-success">عدد المرفقات المحفوظة حالياً: {{ count($transfer->attachments) }}. رفع مرفقات جديدة سيضيفها إلى القائمة.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                        <input type="hidden" name="paid_amount" id="paid_amount" value="0">
                        <input type="hidden" name="payment_type" id="payment_type" value="pay_later">
                        <input type="hidden" name="payment_status" id="payment_status" value="later">
                        <div class="col-12">
                            <label for="attachments" class="form-label fw-bold">مرفقات إضافية</label>
                            <input type="file" name="attachments[]" id="attachments" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" multiple>
                            <div class="form-text">يمكن رفع مستندات الطلب دون إنشاء أي حركة مالية.</div>
                        </div>
                    @endunless
                    <div class="col-12">
                        <label class="form-label fw-bold">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="اكتب أي ملاحظات إضافية هنا...">{{ old('notes', $transfer->notes ?? '') }}</textarea>
                    </div>
                    @if(!$isEditMode && !$isRentalWorkflow)
                        <div class="col-12">
                            <div class="alert alert-info d-flex align-items-start gap-2 mb-0">
                                <i class="fas fa-file-invoice-dollar mt-1"></i>
                                <div>
                                    <strong class="d-block">اختر طريقة الحفظ</strong>
                                    يمكنك حفظ طلب نقل الخدمات فقط، أو حفظه والانتقال مباشرة لإعداد وإصدار الفاتورة.
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-2"></i>رجوع
                        </button>
                        @if($isEditMode)
                            <button type="submit" class="btn btn-primary px-4" value="save">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
                            </button>
                        @else
                            <button type="submit" class="btn btn-primary px-4" value="save">
                                <i class="fas fa-plus-circle me-2"></i>إضافة طلب النقل
                            </button>
                            @if(\App\Support\InvoiceAuthorization::canCreateTransfer())
                            <button type="submit" class="btn btn-success px-4 ms-2" value="save_and_invoice">
                                <i class="fas fa-file-invoice-dollar me-2"></i>حفظ وإصدار فاتورة
                            </button>
                            @endif
                        @endif
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
            url: '{{ route('SponsorshipTransfer.searchClients') }}',
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
        const isMoyasar = $('#payment_type').val() === 'moyasar';
        const moyasarLocked = {{ $isEditMode && !empty($transfer->moyasar_invoice_id) ? 'true' : 'false' }};
        // ميسر: السداد يُسجل من البوابة، فالمدفوع صفر والحالة معلق ما لم تُنشأ الفاتورة بعد.
        if (isMoyasar && !moyasarLocked) {
            $('#payment_status').val('pending');
            $('#paid_amount').val(0).trigger('input');
        }
        $('#payment_status, #paid_amount').prop('readonly', isMoyasar)
            .toggleClass('bg-light', isMoyasar);
        $('#payment_status option').prop('disabled', function () {
            return isMoyasar && this.value !== $('#payment_status').val();
        });
        $('#moyasar_payment_hint').toggleClass('d-none', !isMoyasar);
        if (moyasarLocked) {
            $('#payment_type option').prop('disabled', function () { return this.value !== 'moyasar'; });
            $('#contract_value').prop('readonly', true).addClass('bg-light');
        }

        const paymentStatus = $('#payment_status').val();
        const hasSavedReceipt = {{ $isEditMode && !empty($transfer->payment_receipt_path) ? 'true' : 'false' }};
        const isPaid = paymentStatus === 'paid' || paymentStatus === 'partial';
        const receiptRequired = isPaid && !hasSavedReceipt && !isMoyasar;
        $('#payment_receipt').prop('required', receiptRequired);
        $('#payment_receipt_hint').toggleClass('d-none', !receiptRequired);
        $('#receipt_account_wrapper').toggleClass('d-none', !isPaid && !isMoyasar);
        $('#receipt_account_id').prop('required', isPaid || isMoyasar);
    }

    $('#payment_status, #payment_type').on('change', syncPaymentReceiptRequirement);
    syncPaymentReceiptRequirement();

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // حدود الرفع على الخادم (تُقرأ من إعدادات PHP) لفحص حجم الملفات قبل الإرسال
    const uploadLimits = {
        totalBytes: {{ (int) ($uploadLimitBytes['total'] ?? 0) }},
        totalLabel: '{{ $uploadLimitLabels['total'] ?? '' }}',
        fileBytes: {{ (int) ($uploadLimitBytes['file'] ?? 0) }},
        fileLabel: '{{ $uploadLimitLabels['file'] ?? '' }}'
    };

    function formatBytes(bytes) {
        if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' ميجابايت';
        if (bytes >= 1024) return Math.round(bytes / 1024) + ' كيلوبايت';
        return bytes + ' بايت';
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

        return messages;
    }

    // يحوّل حالة HTTP / نوع الفشل إلى شرح واضح بالعربية يفهمه المستخدم
    function describeHttpError(xhr, textStatus) {
        const status = xhr && typeof xhr.status === 'number' ? xhr.status : 0;
        const body = xhr && typeof xhr.responseText === 'string' ? xhr.responseText : '';
        const looksLikeLoginPage = status === 200 && /type="password"|name="password"/i.test(body);

        if (textStatus === 'timeout') {
            return { title: 'انتهت مهلة الاتصال', message: 'استغرق الخادم وقتاً أطول من المتوقع ولم تصل نتيجة الحفظ. تحقق من سرعة الاتصال ثم أعد المحاولة، وراجع قائمة الطلبات للتأكد من عدم تكرار الحفظ.' };
        }
        if (status === 0) {
            return { title: 'تعذر الاتصال بالخادم', message: 'لم يصل الطلب إلى الخادم. تحقق من اتصالك بالإنترنت أو أن النظام يعمل، ثم أعد المحاولة.' };
        }
        if (status === 401 || status === 419 || looksLikeLoginPage) {
            return { title: 'انتهت الجلسة', message: 'انتهت صلاحية جلسة الدخول أو رمز الحماية بسبب بقاء الصفحة مفتوحة لفترة طويلة، ولم يتم حفظ الطلب. حدّث الصفحة وسجّل الدخول من جديد ثم أعد إدخال الطلب.', reload: true };
        }
        if (status === 403) {
            return { title: 'غير مصرح لك', message: 'لا تملك صلاحية إنشاء طلب نقل الخدمات، أو أن العقد أو العميل يتبع فرعاً آخر لا يمكنك التعامل معه. تواصل مع مدير النظام لمنحك الصلاحية.' };
        }
        if (status === 404) {
            return { title: 'مسار الحفظ غير موجود', message: 'لم يعثر الخادم على مسار حفظ الطلب أو على أحد السجلات المطلوبة. حدّث الصفحة وأعد المحاولة، وإن استمرت المشكلة أبلغ الدعم الفني.' };
        }
        if (status === 413) {
            return { title: 'حجم الملفات كبير جداً', message: 'إجمالي حجم إيصال الدفع والمرفقات يتجاوز الحد المسموح على الخادم' + (uploadLimits.totalLabel ? ' (' + uploadLimits.totalLabel + ')' : '') + '. قلّل حجم الملفات أو أرسل مرفقات أقل ثم أعد المحاولة.' };
        }
        if (status === 422) {
            return { title: 'تحقق من البيانات المدخلة', message: 'بعض الحقول غير مكتملة أو غير صحيحة. راجع الملاحظات التالية ثم أعد المحاولة.' };
        }
        if (status === 429) {
            return { title: 'محاولات كثيرة', message: 'تم إرسال طلبات كثيرة خلال وقت قصير. انتظر دقيقة ثم أعد المحاولة.' };
        }
        if (status === 503) {
            return { title: 'النظام تحت الصيانة', message: 'الخادم في وضع الصيانة حالياً ولم يتم الحفظ. أعد المحاولة بعد قليل.' };
        }
        if (status >= 500) {
            return { title: 'خطأ في الخادم', message: 'حدث خطأ داخلي أثناء حفظ الطلب ولم يتم الحفظ. تم تسجيل الخطأ تلقائياً؛ أبلغ الدعم الفني مع ذكر وقت المحاولة (' + new Date().toLocaleTimeString('ar-SA') + ').' };
        }
        if (textStatus === 'parsererror') {
            return { title: 'استجابة غير متوقعة', message: 'أعاد الخادم صفحة غير متوقعة بدلاً من نتيجة الحفظ، وغالباً يعني ذلك انتهاء الجلسة. حدّث الصفحة وأعد المحاولة.', reload: true };
        }
        return { title: 'خطأ غير متوقع', message: 'تعذر حفظ الطلب لسبب غير معروف (رمز الاستجابة ' + status + '). حدّث الصفحة وأعد المحاولة، وإن تكرر الأمر أبلغ الدعم الفني.' };
    }

    function showErrorResponse(response, fallbackMessage, options) {
        options = options || {};
        const serverMessages = collectErrorMessages(response);
        const messages = serverMessages.length ? serverMessages : [fallbackMessage || 'حدث خطأ غير متوقع'];
        let html = '<div class="text-start" dir="rtl">';

        if (options.intro && serverMessages.length) {
            html += '<p class="mb-2">' + escapeHtml(options.intro) + '</p>';
        }

        if (messages.length === 1) {
            html += '<p class="mb-0">' + escapeHtml(messages[0]) + '</p>';
        } else {
            html += '<ul class="mb-0 pe-3">';
            messages.forEach(function(message) {
                html += '<li>' + escapeHtml(message) + '</li>';
            });
            html += '</ul>';
        }

        const details = [];
        if (options.status) {
            details.push('رمز الاستجابة: ' + options.status + (options.statusText ? ' (' + options.statusText + ')' : ''));
        }
        if (options.serverNote) {
            details.push('رسالة الخادم: ' + options.serverNote);
        }
        if (details.length) {
            html += '<div class="small text-muted mt-3">' + details.map(escapeHtml).join('<br>') + '</div>';
        }

        if (response && response.debug) {
            html += '<hr class="my-3">';
            html += '<div class="small text-muted" dir="ltr" style="text-align:left; white-space:normal;">';
            html += '<div><strong>Exception:</strong> ' + escapeHtml(response.debug.exception) + '</div>';
            if (response.debug.message) {
                html += '<div><strong>Message:</strong> ' + escapeHtml(response.debug.message) + '</div>';
            }
            html += '<div><strong>File:</strong> ' + escapeHtml(response.debug.file) + ':' + escapeHtml(response.debug.line) + '</div>';
            html += '</div>';
        }

        html += '</div>';

        const swalOptions = {
            icon: options.icon || 'error',
            title: options.title || 'خطأ!',
            html: html,
            width: 650
        };

        if (options.reload) {
            swalOptions.showCancelButton = true;
            swalOptions.confirmButtonText = '<i class="fas fa-sync-alt"></i> تحديث الصفحة';
            swalOptions.cancelButtonText = 'إغلاق';
        }

        Swal.fire(swalOptions).then(function(result) {
            if (options.reload && result.isConfirmed) {
                window.location.reload();
            }
        });
    }

    // يقرر ما يُعرض عند فشل طلب AJAX: رسالة الخادم إن كانت مفهومة، وإلا شرح مبني على حالة HTTP
    function handleAjaxFailure(xhr, textStatus) {
        const json = xhr && xhr.responseJSON && typeof xhr.responseJSON === 'object' ? xhr.responseJSON : null;
        const info = describeHttpError(xhr, textStatus);
        const status = xhr && xhr.status ? xhr.status : 0;
        const serverExplains = json && (json.errors || (json.message && [400, 404, 409, 422].includes(status)));

        if (serverExplains) {
            showErrorResponse(json, info.message, {
                title: info.title,
                intro: status === 422 ? info.message : null,
                status: status,
                statusText: xhr.statusText
            });
            return;
        }

        showErrorResponse({ message: info.message, debug: json ? json.debug : null }, info.message, {
            title: info.title,
            reload: !!info.reload,
            status: status,
            statusText: xhr ? xhr.statusText : '',
            serverNote: json && json.message ? json.message : null
        });
    }

    // فحص حجم الملفات قبل الإرسال حتى لا يفشل الطلب بصمت على الخادم
    function findUploadSizeProblem(formElement) {
        let total = 0;
        let tooBigFile = null;

        formElement.querySelectorAll('input[type="file"]').forEach(function(input) {
            Array.prototype.forEach.call(input.files || [], function(file) {
                total += file.size;
                if (uploadLimits.fileBytes > 0 && file.size > uploadLimits.fileBytes && !tooBigFile) {
                    tooBigFile = file;
                }
            });
        });

        if (tooBigFile) {
            return 'الملف "' + tooBigFile.name + '" حجمه ' + formatBytes(tooBigFile.size)
                + ' ويتجاوز الحد المسموح للملف الواحد على الخادم (' + uploadLimits.fileLabel + '). اختر ملفاً أصغر ثم أعد المحاولة.';
        }

        if (uploadLimits.totalBytes > 0 && total > uploadLimits.totalBytes) {
            return 'إجمالي حجم الملفات المرفقة ' + formatBytes(total)
                + ' ويتجاوز الحد المسموح على الخادم (' + uploadLimits.totalLabel + '). قلّل عدد المرفقات أو حجمها ثم أعد المحاولة.';
        }

        return null;
    }

    // إرسال الطلب
    $('#sponsorshipTransferForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitter = e.originalEvent && e.originalEvent.submitter
            ? e.originalEvent.submitter
            : form.find('button[type=submit]').first().get(0);
        const submitAction = submitter && submitter.value ? submitter.value : 'save';
        const buttons = form.find('button[type=submit]');
        const formData = new FormData(this);

        formData.set('submit_action', submitAction);

        const uploadProblem = findUploadSizeProblem(this);
        if (uploadProblem) {
            showErrorResponse({ message: uploadProblem }, uploadProblem, { title: 'حجم الملفات كبير جداً', icon: 'warning' });
            return;
        }

        buttons.each(function() {
            $(this).data('original-html', $(this).html());
        }).prop('disabled', true);
        $(submitter).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');

        $.ajax({
            url: '{{ $formAction }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
            timeout: 120000,
            success: function(response) {
                if (!response || typeof response !== 'object') {
                    handleAjaxFailure({ status: 200, statusText: 'OK', responseText: String(response ?? '') }, 'parsererror');
                    return;
                }

                if (response.success) {
                    if (submitAction === 'save_and_invoice' && response.invoice_create_url) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم حفظ الطلب',
                            text: response.message + '، سيتم فتح شاشة إصدار الفاتورة.',
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = response.invoice_create_url;
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحفظ',
                        text: response.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = response.show_url || '{{ route("SponsorshipTransfer.index") }}';
                    });
                } else {
                    showErrorResponse(response, 'لم يتم حفظ طلب نقل الخدمات. راجع البيانات وأعد المحاولة.', {
                        title: 'لم يتم الحفظ',
                        status: 200
                    });
                }
            },
            error: function(xhr, textStatus) {
                handleAjaxFailure(xhr, textStatus);
            },
            complete: function() {
                buttons.each(function() {
                    $(this).prop('disabled', false).html($(this).data('original-html'));
                });
            }
        });
    });
});
</script>
@endsection

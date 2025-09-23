@extends('admin.layout.indexs.index')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-md-12">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-bottom-0 pb-0">
                <h3 class="card-title mb-0" style="font-weight:700;font-size:1.5rem;">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    {{helperTrans("ingaz.add_sponsorship_transfer_request")}}
                </h3>
                @if(isset($selectedOrder) && $selectedOrder)
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
                <form id="sponsorshipTransferForm" class="row g-4">
                    @csrf
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
                                        data-sponsor-id="{{ $contract->user ? $contract->user->id_number : 'غير محدد' }}"
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
                        <label class="form-label fw-bold">اختيار الكفيل الجديد <span class="text-danger">*</span></label>
                        <select name="new_sponsor_id" id="new_sponsor_id" class="form-select select2" required></select>
                    </div>
                    <!-- معلومات العمل التجريبي والتكاليف -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">عدد أيام العمل التجريبي <span class="text-danger">*</span></label>
                        <input type="number" name="trial_days" class="form-control" min="1" max="90" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">تاريخ بداية العمل التجريبي <span class="text-danger">*</span></label>
                        <input type="date" name="trial_start_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">قيمة العقد <span class="text-danger">*</span></label>
                        <input type="number" name="contract_value" id="contract_value" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">المبلغ المدفوع <span class="text-danger">*</span></label>
                        <input type="number" name="paid_amount" id="paid_amount" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">المبلغ المتبقي</label>
                        <input type="number" name="remaining_amount" id="remaining_amount" class="form-control bg-white" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="اكتب أي ملاحظات إضافية هنا..."></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-2"></i>رجوع
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>حفظ الطلب
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
    $('.select2').select2({ placeholder: 'اختر...', allowClear: true });
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
    // إرسال الطلب
    $('#sponsorshipTransferForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');
        $.ajax({
            url: '{{ route('SponsorshipTransfer.store') }}',
            type: 'POST',
            data: form.serialize(),
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
                    Swal.fire('خطأ!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let msg = 'حدث خطأ غير متوقع';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('خطأ!', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>حفظ الطلب');
            }
        });
    });
});
</script>
@endsection

@php
    $returnedContractsCount = $returnedDeductions->pluck('deduction_contract_key')->unique()->count();
    $returnedTotal = $returnedDeductions->sum('suggested_deduction');
@endphp
<div class="card border-0 shadow-sm mb-3 overflow-hidden" id="returned-deductions"
     style="scroll-margin-top:120px; border-top:3px solid #dc3545 !important; border-radius:14px;">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1 fw-bold text-danger"><i class="fa fa-undo me-2"></i>خصومات العقود المرتجعة</h5>
            <div class="small text-muted">تشمل عقود الاستقدام والإيجار وتمديداته حسب نوع العقد المختار، ولا تتأثر بفلتر تاريخ العقود. تسوية التأجير تتبع السياسة المحفوظة للعمولة؛ لا يُطبّق الخصم تلقائيًا عند اعتماد دفعة العمولة.</div>
            @if($returnedDeductions->whereNotNull('deduction_calculation_error')->isNotEmpty())
                <div class="small text-danger mt-1">توجد تسويات ببيانات فترة ناقصة؛ لا تدخل في الإجمالي حتى استكمال بياناتها.</div>
            @endif
        </div>
        <div class="d-flex gap-2">
            <span class="badge rounded-pill bg-light text-danger border border-danger px-3 py-2 returned-pending-count">{{ number_format($returnedContractsCount) }} عقد</span>
            <span class="badge rounded-pill bg-danger px-3 py-2 returned-pending-total">{{ number_format($returnedTotal, 2) }} ر.س</span>
        </div>
    </div>
    @if($returnedDeductions->isEmpty())
        <div class="card-body text-center text-muted py-4">
            <i class="fa fa-check-circle text-success fs-4 d-block mb-2"></i>
            لا توجد خصومات معلقة لعقود مرتجعة.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>#</th><th>العقد</th><th class="text-nowrap">نوع العقد</th><th>العميل</th><th>سبب الخصم</th><th>حالة الضمان الحالية</th><th>تاريخ العقد</th><th>تاريخ الإرجاع</th><th>دفعة العمولة السابقة</th><th class="text-end">العمولة السابقة</th><th>التسوية</th>
                </tr></thead>
                <tbody>
                @foreach($returnedDeductions as $deduction)
                    <tr class="returned-deduction-row" data-contract="{{ $deduction->deduction_contract_key }}" data-amount="{{ $deduction->suggested_deduction ?? 0 }}">
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td><a class="fw-bold" href="{{ $deduction->deduction_contract_url }}" target="_blank">#{{ $deduction->deduction_contract_id }}</a>
                            @if(str_starts_with((string) $deduction->source_type, \App\Services\commissions\CommissionOperationService::SOURCE_RENTAL_EXTENSION_PERIOD_PREFIX))
                                <span class="badge bg-info text-dark ms-1">تمديد</span>
                                <div class="small text-muted">{{ $deduction->operation_reference ?: ('تمديد #' . $deduction->source_id) }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap"><span class="badge bg-light text-primary border">{{ \App\Models\commissions\CommissionSetting::serviceTypeOptions()[$deduction->service_type] ?? ($deduction->service_type ?: 'غير محدد') }}</span></td>
                        <td>{{ $deduction->client_name ?: '—' }}</td>
                        <td style="min-width:240px;max-width:400px;white-space:normal;overflow-wrap:anywhere;">{{ $deduction->deduction_reason ?: 'غير مسجل' }}</td>
                        <td class="text-nowrap">
                            @if($deduction->service_type === 'rental')
                                <span class="text-muted">-</span>
                            @else
                            @if($deduction->return_warranty_status === 'inside')
                                <span class="badge bg-success">داخل الضمان</span>
                            @elseif($deduction->return_warranty_status === 'outside')
                                <span class="badge bg-danger">خارج الضمان</span>
                            @else
                                <span class="badge bg-secondary">غير محدد</span>
                                <div class="small text-muted mt-1" style="white-space:normal;min-width:180px;max-width:260px;">{{ $deduction->return_warranty_unknown_reason }}</div>
                            @endif
                            @if($deduction->return_warranty_start && $deduction->return_warranty_end)
                                <div class="small text-muted mt-1">{{ $deduction->return_warranty_start }} — {{ $deduction->return_warranty_end }}</div>
                            @endif
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $deduction->operation_date?->format('Y-m-d') ?: '—' }}</td>
                        <td class="text-nowrap">{{ $deduction->return_date?->format('Y-m-d') ?: 'غير مسجل' }}</td>
                        <td class="text-nowrap">
                            <span class="badge bg-light text-dark border">#{{ $deduction->batch_id }}</span>
                            <span class="small text-muted ms-1">{{ $deduction->batch?->approved_at?->format('Y-m-d') ?: '—' }}</span>
                        </td>
                        <td class="text-end text-danger fw-bold text-nowrap">
                            {{ number_format($deduction->commission_amount, 2) }} ر.س
                            @if($deduction->deduction_policy === 'remaining_days')
                                <div class="small text-muted">المنفذ: {{ $deduction->deduction_used_days ?? '—' }} / {{ $deduction->deduction_period_days }} يوم</div>
                                @if($deduction->deduction_calculation_error)
                                    <div class="small text-danger" style="white-space:normal;max-width:260px;">{{ $deduction->deduction_calculation_error }}</div>
                                @else
                                    <div class="small text-success">المستحق للموظف: {{ number_format($deduction->retained_commission, 2) }} ر.س</div>
                                    <div class="small">الخصم: {{ number_format($deduction->suggested_deduction, 2) }} ر.س</div>
                                @endif
                            @endif
                        </td>
                        <td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-danger open-returned-settlement"
                            data-url="{{ route('commissions.marketers.returned-commissions.resolve', [$admin->id, $deduction->id]) }}"
                            data-contract="{{ $deduction->deduction_contract_id }}" data-amount="{{ $deduction->commission_amount }}"
                            data-policy="{{ $deduction->deduction_policy }}" data-suggested="{{ $deduction->suggested_deduction }}"
                            data-error="{{ $deduction->deduction_calculation_error }}">اعتماد الخصم</button></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="9">إجمالي الخصومات المحسوبة</th>
                        <th class="text-end text-danger text-nowrap returned-pending-total">{{ number_format($returnedTotal, 2) }} ر.س</th><th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
<div class="modal fade" id="returnedSettlementModal" tabindex="-1" aria-labelledby="returnedSettlementTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow" style="border-radius:16px; overflow:hidden;">
        <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold" id="returnedSettlementTitle"><i class="fa fa-undo text-danger me-2"></i>اعتماد خصم عقد مرتجع</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>
        <div class="modal-body">
            <div class="d-flex justify-content-between align-items-center rounded-3 border p-2 px-3 mb-3 bg-white">
                <div><span class="text-muted small">العقد</span> <strong id="returnedSettlementContract"></strong></div>
                <div><span class="text-muted small">العمولة السابقة</span> <strong class="text-danger"><span id="returnedSettlementOriginal"></span> ر.س</strong></div>
            </div>

            <div class="rounded-3 p-3 mb-3 small" style="background:#fff8e6; border:1px solid #fde3a7;">
                <div class="fw-bold mb-2"><i class="fa fa-info-circle text-warning me-1"></i>اختر طريقة تسوية العمولة:</div>
                <ul class="mb-0 ps-3" style="line-height:1.9">
                    <li><strong class="text-danger">خصم كامل العمولة:</strong> تُخصم العمولة السابقة بالكامل من رصيد المسوق.</li>
                    <li><strong class="text-warning">خصم جزء من العمولة:</strong> يُخصم المبلغ الذي تحدده فقط، ويُقفل الباقي دون خصم.</li>
                    <li><strong class="text-secondary">إقفال بصفر دون خصم:</strong> تُقفل التسوية دون خصم أي مبلغ من المسوق.</li>
                </ul>
            </div>

            <div id="returnedSettlementError" class="alert alert-danger d-none" role="alert"></div>
            <div id="returnedPolicySummary" class="alert alert-info d-none"></div>

            <label class="form-label fw-bold">طريقة الاعتماد <span class="text-danger">*</span></label>
            <div class="d-grid gap-2 mb-3" id="returnedSettlementOptions">
                @foreach([
                    ['policy', 'خصم نصيب المدة غير المنفذة حسب الإعداد', 'fa-calendar', 'primary'],
                    ['deducted', 'خصم كامل العمولة', 'fa-minus-circle', 'danger'],
                    ['partial', 'خصم جزء من العمولة', 'fa-adjust', 'warning'],
                    ['zeroed', 'إقفال بصفر دون خصم', 'fa-ban', 'secondary'],
                ] as [$value, $label, $icon, $color])
                    <input type="radio" class="btn-check" name="returnedSettlementDecision" id="returnedDecision_{{ $value }}" value="{{ $value }}" autocomplete="off">
                    <label class="btn btn-outline-{{ $color }} text-start d-flex align-items-center gap-2 py-2" for="returnedDecision_{{ $value }}">
                        <i class="fa {{ $icon }}"></i> {{ $label }}
                    </label>
                @endforeach
            </div>

            <div id="returnedSettlementAmountGroup" class="d-none mb-3">
                <label for="returnedSettlementAmount" class="form-label fw-bold">مبلغ الخصم الجزئي <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input id="returnedSettlementAmount" type="number" min="0.01" step="0.01" class="form-control">
                    <span class="input-group-text">ر.س</span>
                </div>
            </div>

            <label for="returnedSettlementNotes" class="form-label fw-bold">ملاحظات</label>
            <textarea id="returnedSettlementNotes" class="form-control" maxlength="1000" rows="2" placeholder="اختياري"></textarea>

            <div class="small text-muted mt-3"><i class="fa fa-lock me-1"></i>بعد التأكيد تُقفل التسوية نهائيًا، بما في ذلك الجزء المتبقي عند اختيار خصم جزئي.</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger fw-bold" id="confirmReturnedSettlement" disabled>تأكيد واعتماد الخصم</button>
        </div>
    </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    let settlementButton = null;
    const modalElement = document.getElementById('returnedSettlementModal');
    function showModal(show) {
        if (window.bootstrap && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            show ? modal.show() : modal.hide();
        } else {
            $(modalElement).modal(show ? 'show' : 'hide');
        }
    }
    $(document).on('click', '.open-returned-settlement', function () {
        settlementButton = $(this);
        $('#returnedSettlementContract').text('#' + settlementButton.data('contract'));
        $('#returnedSettlementOriginal').text(Number(settlementButton.data('amount')).toFixed(2));
        $('input[name="returnedSettlementDecision"]').prop('checked', false);
        $('#confirmReturnedSettlement').prop('disabled', true);
        $('#returnedSettlementAmount, #returnedSettlementNotes').val('');
        $('#returnedSettlementAmountGroup, #returnedSettlementError').addClass('d-none');
        const proportional = settlementButton.data('policy') === 'remaining_days';
        const policyError = settlementButton.data('error');
        const suggested = Number(settlementButton.data('suggested'));
        $('#returnedDecision_policy, label[for="returnedDecision_policy"]').toggleClass('d-none', !proportional);
        $('#returnedDecision_deducted, label[for="returnedDecision_deducted"]').toggleClass('d-none', proportional);
        $('#returnedDecision_partial, label[for="returnedDecision_partial"]').toggleClass('d-none', proportional && (policyError || suggested <= 0));
        $('#returnedDecision_policy').prop('disabled', Boolean(policyError));
        $('#returnedPolicySummary').toggleClass('d-none', !proportional).text(policyError || ('الخصم حسب المدة: ' + suggested.toFixed(2) + ' ر.س. يُحتفظ بعمولة المدة المنفذة.'));
        if (proportional && !policyError) {
            $('#returnedDecision_policy').prop('checked', true);
            $('#confirmReturnedSettlement').prop('disabled', false);
        }
        showModal(true);
    });
    $('input[name="returnedSettlementDecision"]').on('change', function () {
        $('#returnedSettlementAmountGroup').toggleClass('d-none', this.value !== 'partial');
        $('#returnedSettlementError').addClass('d-none');
        $('#confirmReturnedSettlement').prop('disabled', false);
    });
    $('#confirmReturnedSettlement').on('click', async function () {
        if (!settlementButton) return;
        const button = $(this);
        const decision = $('input[name="returnedSettlementDecision"]:checked').val();
        const original = Number(settlementButton.data('amount'));
        const amount = Number($('#returnedSettlementAmount').val());
        const errorBox = $('#returnedSettlementError').addClass('d-none');
        if (!decision) {
            errorBox.text('اختر طريقة الاعتماد أولًا.').removeClass('d-none');
            return;
        }
        if (decision === 'partial' && (!Number.isFinite(amount) || amount <= 0 || amount >= original)) {
            errorBox.text('أدخل مبلغًا أكبر من صفر وأقل من العمولة السابقة.').removeClass('d-none');
            return;
        }
        if (decision === 'partial' && settlementButton.data('policy') === 'remaining_days' && amount > Number(settlementButton.data('suggested'))) {
            errorBox.text('لا يجوز أن يتجاوز الخصم نصيب المدة غير المنفذة.').removeClass('d-none');
            return;
        }
        button.prop('disabled', true);
        try {
            const response = await fetch(settlementButton.data('url'), {
                method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                body: JSON.stringify({decision, notes: $('#returnedSettlementNotes').val(), ...(decision === 'partial' ? {amount} : {})})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'تعذر اعتماد الخصم.');
            const settledUrl = settlementButton.data('url');
            $('.open-returned-settlement').filter(function () { return $(this).data('url') === settledUrl; }).each(function () {
                $(this).closest('tr').removeClass('returned-deduction-row').addClass('table-success');
                $(this).replaceWith($('<span class="badge bg-success">').text('أُقفلت · الخصم ' + Number(data.deducted_amount).toFixed(2) + ' ر.س'));
            });
            const pending = document.querySelectorAll('.returned-deduction-row');
            const contracts = new Set(Array.from(pending, row => row.dataset.contract));
            const total = Array.from(pending).reduce((sum, row) => sum + Number(row.dataset.amount), 0);
            $('.returned-pending-count').text(contracts.size + ' عقد');
            $('.returned-pending-total').text(total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ر.س');
            if ($.fn.dataTable && $.fn.dataTable.isDataTable('#operationsTable')) $('#operationsTable').DataTable().ajax.reload(null, false);
            showModal(false);
        } catch (error) {
            errorBox.text(error.message).removeClass('d-none');
        } finally {
            button.prop('disabled', false);
        }
    });
});
</script>

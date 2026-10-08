@php
    $returnedContractsCount = $returnedDeductions->pluck('source_id')->unique()->count();
    $returnedTotal = $returnedDeductions->sum('commission_amount');
@endphp
<div class="card border-0 shadow-sm mb-3 overflow-hidden" id="returned-deductions"
     style="scroll-margin-top:120px; border-top:3px solid #dc3545 !important; border-radius:14px;">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1 fw-bold text-danger"><i class="fa fa-undo me-2"></i>خصومات العقود المرتجعة</h5>
            <div class="small text-muted">اعتمد تسوية كل عقد بصفر أو بكامل العمولة أو بمبلغ جزئي. بعد التأكيد تُقفل التسوية، ولا يُخصم أي مبلغ تلقائيًا عند اعتماد دفعة العمولة.</div>
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
                    <th>#</th><th>العقد</th><th class="text-nowrap">نوع العقد</th><th>العميل</th><th>تاريخ العقد</th><th>دفعة العمولة السابقة</th><th class="text-end">العمولة السابقة</th><th>التسوية</th>
                </tr></thead>
                <tbody>
                @foreach($returnedDeductions as $deduction)
                    <tr class="returned-deduction-row" data-contract="{{ $deduction->source_id }}" data-amount="{{ $deduction->commission_amount }}">
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td><a class="fw-bold" href="{{ url('/admin/contractsDetails/' . $deduction->source_id) }}" target="_blank">#{{ $deduction->source_id }}</a></td>
                        <td class="text-nowrap"><span class="badge bg-light text-primary border">{{ \App\Models\commissions\CommissionSetting::serviceTypeOptions()[$deduction->service_type] ?? ($deduction->service_type ?: 'غير محدد') }}</span></td>
                        <td>{{ $deduction->client_name ?: '—' }}</td>
                        <td class="text-nowrap">{{ $deduction->operation_date?->format('Y-m-d') ?: '—' }}</td>
                        <td class="text-nowrap">
                            <span class="badge bg-light text-dark border">#{{ $deduction->batch_id }}</span>
                            <span class="small text-muted ms-1">{{ $deduction->batch?->approved_at?->format('Y-m-d') ?: '—' }}</span>
                        </td>
                        <td class="text-end text-danger fw-bold text-nowrap">-{{ number_format($deduction->commission_amount, 2) }} ر.س</td>
                        <td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-danger open-returned-settlement"
                            data-url="{{ route('commissions.marketers.returned-commissions.resolve', [$admin->id, $deduction->id]) }}"
                            data-contract="{{ $deduction->source_id }}" data-amount="{{ $deduction->commission_amount }}">اعتماد الخصم</button></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="6">إجمالي الخصومات</th>
                        <th class="text-end text-danger text-nowrap returned-pending-total">{{ number_format($returnedTotal, 2) }} ر.س</th><th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
<div class="modal fade" id="returnedSettlementModal" tabindex="-1" aria-labelledby="returnedSettlementTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="returnedSettlementTitle">اعتماد خصم عقد مرتجع</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
        <div class="modal-body">
            <div id="returnedSettlementError" class="alert alert-danger d-none" role="alert"></div>
            <p>العقد <strong id="returnedSettlementContract"></strong> · العمولة السابقة: <strong id="returnedSettlementOriginal"></strong> ر.س</p>
            <label class="form-label" for="returnedSettlementDecision">طريقة الاعتماد</label>
            <select id="returnedSettlementDecision" class="form-select mb-3">
                <option value="deducted">خصم كامل العمولة</option><option value="partial">خصم جزء من العمولة</option><option value="zeroed">إقفال بصفر دون خصم</option>
            </select>
            <div id="returnedSettlementAmountGroup" class="d-none mb-3">
                <label for="returnedSettlementAmount" class="form-label">مبلغ الخصم الجزئي</label>
                <input id="returnedSettlementAmount" type="number" min="0.01" step="0.01" class="form-control">
            </div>
            <label for="returnedSettlementNotes" class="form-label">ملاحظات</label>
            <textarea id="returnedSettlementNotes" class="form-control" maxlength="1000" rows="2"></textarea>
            <div class="alert alert-info mt-3 mb-0">بعد التأكيد تُقفل التسوية نهائيًا، بما في ذلك الجزء المتبقي عند اختيار خصم جزئي.</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button><button type="button" class="btn btn-danger" id="confirmReturnedSettlement">تأكيد واعتماد الخصم</button></div>
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
        $('#returnedSettlementDecision').val('deducted');
        $('#returnedSettlementAmount, #returnedSettlementNotes').val('');
        $('#returnedSettlementAmountGroup, #returnedSettlementError').addClass('d-none');
        showModal(true);
    });
    $('#returnedSettlementDecision').on('change', function () {
        $('#returnedSettlementAmountGroup').toggleClass('d-none', this.value !== 'partial');
    });
    $('#confirmReturnedSettlement').on('click', async function () {
        if (!settlementButton) return;
        const button = $(this);
        const decision = $('#returnedSettlementDecision').val();
        const original = Number(settlementButton.data('amount'));
        const amount = Number($('#returnedSettlementAmount').val());
        const errorBox = $('#returnedSettlementError').addClass('d-none');
        if (decision === 'partial' && (!Number.isFinite(amount) || amount <= 0 || amount >= original)) {
            errorBox.text('أدخل مبلغًا أكبر من صفر وأقل من العمولة السابقة.').removeClass('d-none');
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
                $(this).replaceWith($('<span class="badge bg-success">').text('أُقفلت · الخصم ' + (decision === 'zeroed' ? 0 : (decision === 'partial' ? amount : original)).toFixed(2) + ' ر.س'));
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

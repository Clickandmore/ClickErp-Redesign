{{-- خطوات اعتماد المستند: كل شرط مع سببه وزر الخطوة الناقصة --}}
@php
    $done = collect($readiness)->where('ok', true)->count();
    $total = count($readiness);
    $contract = $settlement->contract;
    $canEditDraft = AdminGuardHasPerm(\App\PermKeys::POST_ARRIVAL_REFUND_EDIT);
@endphp
<div class="card mb-3 border-{{ $done === $total ? 'success' : 'warning' }}" id="readiness">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-list-check me-1"></i> خطوات الاعتماد</strong>
        <span class="badge bg-{{ $done === $total ? 'success' : 'warning text-dark' }}">{{ $done }} من {{ $total }} مكتملة</span>
    </div>
    <ul class="list-group list-group-flush">
        @foreach($readiness as $i => $step)
            <li class="list-group-item">
                <div class="d-flex gap-2">
                    <span class="fs-5 {{ $step['ok'] ? 'text-success' : 'text-danger' }}"><i class="fas {{ $step['ok'] ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i></span>
                    <div class="flex-grow-1">
                        <div class="fw-bold">{{ $i + 1 }}. {{ $step['title'] }}</div>
                        <div class="small {{ $step['ok'] ? 'text-muted' : '' }}">{{ $step['detail'] }}</div>

                        @if(!$step['ok'])
                            <div class="mt-2 d-flex flex-wrap gap-2 align-items-start">
                                @if($step['key'] === 'returned')
                                    @php($eventStatus = $step['data']['contract_status'] ?? null)
                                    @if(in_array($eventStatus, ['arrival', 'completed', 'under_warranty', 'closed', 'back'], true) && AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_ADDITION_CREATE, \App\PermKeys::SHELTER_CONTRACT_CREATE, \App\PermKeys::SHELTER_CONTRACT_CONTROL]))
                                        <a href="{{ route('ShelterContract.create', ['contract_id' => $contract->id]) }}" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-house-user me-1"></i> إدخال العاملة الإيواء</a>
                                    @endif
                                    @if(in_array($eventStatus, ['arrival', 'completed'], true) && AdminGuardHasAnyPerm([\App\PermKeys::REC_CONTRACTS_ESCAPE_REPORT_CREATE]))
                                        <a href="{{ route('RecruitmentContract.escapeReports.show', $contract->id) }}" class="btn btn-sm btn-outline-danger" target="_blank"><i class="fas fa-person-running me-1"></i> تسجيل بلاغ هروب</a>
                                    @endif
                                    @if(in_array($eventStatus, ['arrival', 'completed'], true) && AdminGuardHasPerm(\App\PermKeys::REC_CONTRACTS_DEPORTATION))
                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="startRefundDeportation(this)"><i class="fas fa-plane-departure me-1"></i> بدء الترحيل من عند العميل</button>
                                    @endif
                                    @if($step['data']['pending_request_id'] ?? null)
                                        <a href="{{ route('contract-return-requests.show', $step['data']['pending_request_id']) }}" class="btn btn-sm btn-outline-primary" target="_blank">طلب إرجاع العميل #{{ $step['data']['pending_request_id'] }}</a>
                                    @endif
                                    <a href="{{ route('contracts.details', $contract->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank">فتح العقد</a>
                                    <div class="small text-muted w-100">بعد تسجيل الحدث في نافذته، حدّث هذه الصفحة.</div>
                                @elseif($step['key'] === 'invoice' && ($step['data']['needs_manual_link'] ?? false) && AdminGuardHasPerm(\App\PermKeys::POST_ARRIVAL_REFUND_POST))
                                    <form method="post" action="{{ route('post-arrival-refunds.link-invoice-source', $settlement) }}" class="d-flex gap-2 align-items-center">@csrf
                                        <label class="small text-nowrap">رقم القيد الأصلي للفاتورة</label>
                                        <input type="number" name="source" min="1" class="form-control form-control-sm" style="max-width:140px" required>
                                        <button class="btn btn-sm btn-primary text-nowrap">تحقق واربط</button>
                                    </form>
                                    <div class="small text-muted w-100">يُقبل الربط فقط إن طابق القيد الفاتورة في المبلغ وحساب العميل والفرع والسطور.</div>
                                @elseif($canEditDraft)
                                    <a href="{{ route('post-arrival-refunds.edit', $settlement) }}" class="btn btn-sm btn-outline-primary">تعديل المسودة</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</div>
<script>
    function startRefundDeportation(button) {
        if (!confirm('سيتم بدء إجراءات ترحيل العاملة للعقد #{{ $contract->id }}. متابعة؟')) return;
        button.disabled = true;
        fetch('{{ route('RecruitmentContract.deportation.start', $contract->id) }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
        }).then(r => r.json().then(body => ({ok: r.ok, body})))
          .then(({ok, body}) => {
              if (ok && body.success) { location.reload(); return; }
              alert(body.message || 'تعذر بدء الترحيل.');
              button.disabled = false;
          })
          .catch(() => { alert('تعذر الاتصال بالخادم.'); button.disabled = false; });
    }
</script>

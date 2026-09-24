@extends('admin.layout.indexs.index')

@section('page-title', 'إنشاء استرداد بعد الوصول')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3"><div><h4>إنشاء مستند استرداد بعد الوصول</h4><div class="text-muted">الحفظ ينشئ مسودة فقط، ولا ينشئ أي قيد.</div></div><a href="{{ route('post-arrival-refunds.index') }}" class="btn btn-outline-secondary">رجوع</a></div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ route('post-arrival-refunds.store') }}" id="refundForm">@csrf
        <div class="card mb-3"><div class="card-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">العقد الواصل</label><select name="contract_id" id="contractId" class="form-select" required><option value="">اختر العقد</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" @selected(old('contract_id',$selected?->id)==$contract->id)>#{{ $contract->id }} — {{ $contract->user?->name }} — {{ $contract->maid?->name }} — وصول {{ $contract->info?->arrival_date }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">تاريخ المستند</label><input type="date" name="settlement_date" id="settlementDate" value="{{ old('settlement_date',now()->toDateString()) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">سبب الإرجاع</label><select name="return_reason_code_id" id="reasonCode" class="form-select" required><option value="">اختر السبب</option>@foreach($reasons as $reason)<option value="{{ $reason->id }}" data-deduction="{{ (int)$reason->suggest_client_deduction }}" data-office="{{ (int)$reason->suggest_office_charge }}" @selected(old('return_reason_code_id')==$reason->id)>{{ $reason->name }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">السبب المكتوب</label><textarea name="reason" class="form-control" rows="2" required>{{ old('reason') }}</textarea></div>
            <div class="col-md-4"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
        </div></div></div>

        @php
            $initialProposal = $proposal ?? [];
            $initialOfficeCreditId = (int) ($initialProposal['office_charge_credit_account_id'] ?? 0);
            $initialOfficeCreditLabel = $initialOfficeCreditId
                ? trim(($initialProposal['office_charge_credit_account_code'] ?? '') . ' — ' . ($initialProposal['office_charge_credit_account_name'] ?? ''))
                : 'غير مربوط؛ اختره يدوياً';
        @endphp
        <div class="alert alert-info" id="facts">
            @if($selected && $proposal)
                الفاتورة {{ number_format((float)($proposal['invoice_total'] ?? 0), 2) }}
                — إشعارات سابقة {{ number_format((float)($proposal['previous_credit'] ?? 0), 2) }}
                — حساب العميل {{ $proposal['client_account_id'] ?: 'غير مضبوط' }}
                — حساب المكتب {{ $proposal['office_account_id'] ?: 'غير مضبوط' }}
                — حساب تكلفة الجنسية {{ $initialOfficeCreditLabel }}
            @else
                اختر العقد لعرض الاقتراحات. الأرقام اقتراحات قابلة للمراجعة وليست قيوداً.
            @endif
        </div>
        <div class="card"><div class="card-header"><strong>بنود المستند</strong></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>اختيار</th><th>البند</th><th>المقترح</th><th>المعتمد</th><th>الحساب الدائن / الطريقة</th><th>سبب التعديل</th></tr></thead><tbody>
            @php
                $lineTypes = [
                    'credit_note' => 'إشعار دائن',
                    'client_deduction' => 'خصم على العميل',
                    'client_payout' => 'طلب صرف للعميل',
                    'office_charge' => 'تحميل المكتب الخارجي',
                    'shelter_cost' => 'تكلفة الإيواء S1',
                    'marketer_commission' => 'استرجاع عمولة المسوق',
                ];
            @endphp
            @foreach($lineTypes as $type=>$label)
            @php
                $lineEnabled = (bool) old("lines.$loop->index.enabled", in_array($type,['credit_note','client_deduction','client_payout']));
                $lineProposed = $type === 'client_payout'
                    ? max(0, (float)($initialProposal['credit_note'] ?? 0) - (float)($initialProposal['client_deduction'] ?? 0))
                    : (float)($initialProposal[$type] ?? 0);
            @endphp
            <tr data-line="{{ $type }}">
                <td><input type="hidden" name="lines[{{ $loop->index }}][enabled]" value="0"><input type="checkbox" class="form-check-input line-enabled" name="lines[{{ $loop->index }}][enabled]" value="1" @checked($lineEnabled)></td>
                <td><strong>{{ $label }}</strong><input type="hidden" name="lines[{{ $loop->index }}][type]" value="{{ $type }}"></td>
                <td><span class="proposal" data-type="{{ $type }}">{{ number_format($lineProposed, 2, '.', '') }}</span></td>
                <td><input type="number" step="0.01" min="0" class="form-control amount line-value" name="lines[{{ $loop->index }}][approved_amount]" value="{{ old("lines.$loop->index.approved_amount", number_format($lineProposed, 2, '.', '')) }}"></td>
                <td>
                    @if($type==='office_charge')
                        <select class="form-select mb-1 settlement-method line-value" name="lines[{{ $loop->index }}][settlement_method]"><option value="claim" @selected(old("lines.$loop->index.settlement_method",'claim')==='claim')>مطالبة على كشف المكتب</option><option value="next_dispatch_deduction" @selected(old("lines.$loop->index.settlement_method")==='next_dispatch_deduction')>خصم من الإرسالية التالية</option></select>
                        @php
                            $officeCreditId = (int) old("lines.$loop->index.credit_account_id", $initialOfficeCreditId);
                            $officeCreditAccount = $accounts->firstWhere('id', $officeCreditId);
                            $officeCreditText = $officeCreditAccount ? $officeCreditAccount->code . ' — ' . $officeCreditAccount->name : '';
                        @endphp
                        <input type="search" class="form-control account-search-input line-value" list="refundLeafAccounts" value="{{ $officeCreditText }}" placeholder="اكتب رقم الحساب أو اسمه ثم اختر النتيجة" autocomplete="off">
                        <input type="hidden" class="line-credit-account" name="lines[{{ $loop->index }}][credit_account_id]" value="{{ $officeCreditId ?: '' }}">
                        <small class="form-text {{ $initialOfficeCreditId ? 'text-success' : 'text-muted' }} office-account-source">@if($initialOfficeCreditId) اختير تلقائياً من {{ ($initialProposal['office_charge_account_source'] ?? null)==='shelter_receipt' ? 'مرجع استلام تكلفة الإيواء #'.($initialProposal['office_charge_receipt_entry_id'] ?? '') : 'حساب مردودات تكلفة الجنسية' }} @else سيُختار حساب تكلفة الجنسية تلقائياً عند وجود مرجع موثق. @endif</small>
                    @elseif($type==='client_deduction')
                        @php
                            $clientCreditId = (int) old("lines.$loop->index.credit_account_id");
                            $clientCreditAccount = $accounts->firstWhere('id', $clientCreditId);
                            $clientCreditText = $clientCreditAccount ? $clientCreditAccount->code . ' — ' . $clientCreditAccount->name : '';
                        @endphp
                        <input type="search" class="form-control account-search-input line-value" list="refundLeafAccounts" value="{{ $clientCreditText }}" placeholder="اكتب رقم الحساب أو اسمه ثم اختر النتيجة" autocomplete="off">
                        <input type="hidden" class="line-credit-account" name="lines[{{ $loop->index }}][credit_account_id]" value="{{ $clientCreditId ?: '' }}">
                    @elseif($type==='client_payout')
                        <select class="form-select line-value" name="lines[{{ $loop->index }}][settlement_method]"><option value="financial_request" @selected(old("lines.$loop->index.settlement_method",'financial_request')==='financial_request')>طلب مالي مربوط</option><option value="balance_only" @selected(old("lines.$loop->index.settlement_method")==='balance_only')>إبقاء رصيد فقط</option></select>
                    @else — @endif
                </td>
                <td><input class="form-control line-value" name="lines[{{ $loop->index }}][reason]" value="{{ old("lines.$loop->index.reason") }}" placeholder="إلزامي عند تغيير المقترح"></td>
            </tr>
            @endforeach
        </tbody></table></div>
        <datalist id="refundLeafAccounts">
            @foreach($accounts as $account)
                <option value="{{ $account->code }} — {{ $account->name }}" data-account-id="{{ $account->id }}"></option>
            @endforeach
        </datalist>
        <div class="card-footer text-end"><button class="btn btn-primary">حفظ المسودة</button></div></div>
    </form>
</div>
@endsection

@section('js')
<script>
(() => {
    const contract = document.getElementById('contractId'), date = document.getElementById('settlementDate'), reason = document.getElementById('reasonCode');
    const money = n => Number(n || 0).toFixed(2);
    async function loadProposal() {
        if (!contract.value || !date.value) return;
        const facts=document.getElementById('facts');
        try {
        facts.className='alert alert-info'; facts.textContent='جاري تحميل الاقتراحات...';
        const url = new URL(@json(route('post-arrival-refunds.proposal')), window.location.origin);
        url.searchParams.set('contract_id', contract.value); url.searchParams.set('date', date.value);
        const response = await fetch(url, {credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const contentType=response.headers.get('content-type')||'';
        if(!contentType.includes('application/json')) throw new Error(response.redirected ? 'انتهت جلسة الدخول؛ أعد تحميل الصفحة.' : 'استجابة المقترح ليست JSON.');
        const json = await response.json();
        if (!response.ok) { facts.className='alert alert-danger'; facts.textContent=json.message || 'تعذر تحميل المقترح'; return; }
        const p=json.data; facts.className='alert alert-info';
        const officeCreditLabel=p.office_charge_credit_account_id ? `${p.office_charge_credit_account_code||''} — ${p.office_charge_credit_account_name||''}` : 'غير مربوط؛ اختره يدوياً';
        document.getElementById('facts').textContent=`الفاتورة ${money(p.invoice_total)} — إشعارات سابقة ${money(p.previous_credit)} — حساب العميل ${p.client_account_id||'غير مضبوط'} — حساب المكتب ${p.office_account_id||'غير مضبوط'} — حساب تكلفة الجنسية ${officeCreditLabel}`;
        document.querySelectorAll('[data-line]').forEach(row => { const t=row.dataset.line; let v=p[t]||0; if(t==='client_payout') v=Math.max(0,(p.credit_note||0)-(p.client_deduction||0)); row.querySelector('.proposal').textContent=money(v); if(!row.querySelector('.amount').value) row.querySelector('.amount').value=money(v); });
        const officeRow=document.querySelector('[data-line="office_charge"]'), officeAccount=officeRow?.querySelector('.line-credit-account'), officeSearch=officeRow?.querySelector('.account-search-input'), officeSource=officeRow?.querySelector('.office-account-source');
        if(officeAccount && officeSearch && p.office_charge_credit_account_id && !officeAccount.value){ officeAccount.value=String(p.office_charge_credit_account_id); officeSearch.value=`${p.office_charge_credit_account_code||''} — ${p.office_charge_credit_account_name||''}`; officeSearch.setCustomValidity(''); }
        if(officeSource){ officeSource.textContent=p.office_charge_credit_account_id ? `اختير تلقائياً من ${p.office_charge_account_source==='shelter_receipt' ? 'مرجع استلام تكلفة الإيواء #'+p.office_charge_receipt_entry_id : 'حساب مردودات تكلفة الجنسية'}: 'تعذر إثبات الحساب من المرجع؛ اختر حساباً طرفياً يدوياً.'; officeSource.className='form-text '+(p.office_charge_credit_account_id?'text-success':'text-warning'); }
        applyReason();
        } catch(error) { facts.className='alert alert-danger'; facts.textContent='تعذر تحديث الاقتراحات: '+error.message; }
    }
    const accountOptions=Array.from(document.querySelectorAll('#refundLeafAccounts option'));
    const accountByText=new Map(accountOptions.map(option=>[option.value.trim(),option.dataset.accountId]));
    function resolveAccountSearch(input){ const hidden=input.closest('[data-line]').querySelector('.line-credit-account'); const id=accountByText.get(input.value.trim())||''; hidden.value=id; input.setCustomValidity(input.required&&!id?'اختر حساباً من نتائج البحث.':''); return Boolean(id); }
    document.querySelectorAll('.account-search-input').forEach(input=>{ input.addEventListener('input',()=>resolveAccountSearch(input)); input.addEventListener('change',()=>resolveAccountSearch(input)); });
    function syncLineState(){ document.querySelectorAll('[data-line]').forEach(row=>{ const enabled=row.querySelector('.line-enabled').checked; row.classList.toggle('opacity-75',!enabled); const account=row.querySelector('.line-credit-account'), search=row.querySelector('.account-search-input'), method=row.querySelector('.settlement-method'); if(search){ search.required=enabled&&(!method||method.value!=='next_dispatch_deduction'); resolveAccountSearch(search); } }); }
    function applyReason(){ const option=reason.options[reason.selectedIndex]; if(!option)return; const d=document.querySelector('[data-line="client_deduction"] .line-enabled'), o=document.querySelector('[data-line="office_charge"] .line-enabled'); d.checked=option.dataset.deduction==='1'; o.checked=option.dataset.office==='1'; syncLineState(); }
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(contract).select2({width:'100%', dir:'rtl', placeholder:'ابحث برقم العقد أو العميل أو العاملة أو مساند', ajax:{url:@json(route('post-arrival-refunds.search-contracts')), dataType:'json', delay:250, data:params=>({q:params.term||''}), processResults:data=>data}})
            .on('change', loadProposal);
    } else contract.addEventListener('change',loadProposal);
    date.addEventListener('change',loadProposal); reason.addEventListener('change',applyReason); document.querySelectorAll('.line-enabled,.settlement-method').forEach(element=>element.addEventListener('change',syncLineState)); syncLineState();
})();
</script>
@endsection

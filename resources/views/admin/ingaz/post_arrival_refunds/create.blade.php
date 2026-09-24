@extends('admin.layout.indexs.index')

@section('page-title', 'إنشاء استرداد بعد الوصول')

@section('style')
<style>
    .account-picker { min-width: 360px; }
    .account-picker-results {
        max-height: 260px;
        overflow-y: auto;
        margin-top: .25rem;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: .375rem;
        box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .08);
    }
    .account-picker-result {
        display: grid;
        grid-template-columns: minmax(90px, auto) 1fr;
        gap: .75rem;
        width: 100%;
        padding: .5rem .65rem;
        color: #212529;
        text-align: right;
        background: #fff;
        border: 0;
        border-bottom: 1px solid #eee;
    }
    .account-picker-result:hover,
    .account-picker-result.is-active { background: #eaf3ff; }
    .account-picker-code { direction: ltr; text-align: left; font-family: monospace; font-weight: 700; }
    .account-picker-empty { padding: .65rem; color: #6c757d; text-align: center; }
</style>
@endsection

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
                        @endphp
                        <div class="account-picker">
                            <input type="search" class="form-control account-picker-search" placeholder="اكتب رقم الحساب أو اسمه" autocomplete="off">
                            <div class="account-picker-results d-none"></div>
                            <select class="form-select account-select line-value mt-1" name="lines[{{ $loop->index }}][credit_account_id]">
                                <option value="">اختر الحساب المالي</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" data-code="{{ $account->code }}" @selected($officeCreditId === (int) $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text account-picker-selected">الحساب المختار: <strong>{{ $officeCreditId ? $accounts->firstWhere('id', $officeCreditId)?->code . ' — ' . $accounts->firstWhere('id', $officeCreditId)?->name : 'لم يتم الاختيار' }}</strong></small>
                        </div>
                        <small class="form-text {{ $initialOfficeCreditId ? 'text-success' : 'text-muted' }} office-account-source">@if($initialOfficeCreditId) اختير تلقائياً من {{ ($initialProposal['office_charge_account_source'] ?? null)==='shelter_receipt' ? 'مرجع استلام تكلفة الإيواء #'.($initialProposal['office_charge_receipt_entry_id'] ?? '') : 'حساب مردودات تكلفة الجنسية' }} @else سيُختار حساب تكلفة الجنسية تلقائياً عند وجود مرجع موثق. @endif</small>
                    @elseif($type==='client_deduction')
                        @php
                            $clientCreditId = (int) old("lines.$loop->index.credit_account_id");
                        @endphp
                        <div class="account-picker">
                            <input type="search" class="form-control account-picker-search" placeholder="اكتب رقم الحساب أو اسمه" autocomplete="off">
                            <div class="account-picker-results d-none"></div>
                            <select class="form-select account-select line-value mt-1" name="lines[{{ $loop->index }}][credit_account_id]">
                                <option value="">اختر الحساب المالي</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" data-code="{{ $account->code }}" @selected($clientCreditId === (int) $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text account-picker-selected">الحساب المختار: <strong>{{ $clientCreditId ? $accounts->firstWhere('id', $clientCreditId)?->code . ' — ' . $accounts->firstWhere('id', $clientCreditId)?->name : 'لم يتم الاختيار' }}</strong></small>
                        </div>
                    @elseif($type==='client_payout')
                        <select class="form-select line-value" name="lines[{{ $loop->index }}][settlement_method]"><option value="financial_request" @selected(old("lines.$loop->index.settlement_method",'financial_request')==='financial_request')>طلب مالي مربوط</option><option value="balance_only" @selected(old("lines.$loop->index.settlement_method")==='balance_only')>إبقاء رصيد فقط</option></select>
                    @else — @endif
                </td>
                <td><input class="form-control line-value" name="lines[{{ $loop->index }}][reason]" value="{{ old("lines.$loop->index.reason") }}" placeholder="إلزامي عند تغيير المقترح"></td>
            </tr>
            @endforeach
        </tbody></table></div>
        <div class="card-footer text-end"><button class="btn btn-primary">حفظ المسودة</button></div></div>
    </form>
</div>
@endsection

@section('js')
<script>
(() => {
    const contract = document.getElementById('contractId'), date = document.getElementById('settlementDate'), reason = document.getElementById('reasonCode');
    const money = n => Number(n || 0).toFixed(2);
    const normalizeAccountTerm = value => String(value || '').toLocaleLowerCase('ar')
        .replace(/[إأآٱ]/g, 'ا')
        .replace(/[ًٌٍَُِّْ]/g, '')
        .replace(/\s+/g, ' ')
        .trim();

    document.querySelectorAll('.account-picker').forEach(picker => {
        const search = picker.querySelector('.account-picker-search');
        const results = picker.querySelector('.account-picker-results');
        const select = picker.querySelector('.account-select');
        const selectedText = picker.querySelector('.account-picker-selected strong');
        const accounts = Array.from(select.options).slice(1).map(option => ({
            id: option.value,
            code: option.dataset.code || '',
            label: option.textContent.trim(),
            searchable: normalizeAccountTerm(option.textContent),
        }));
        let visible = [], activeIndex = -1;

        const updateSelectedText = () => {
            selectedText.textContent = select.value ? select.options[select.selectedIndex].textContent.trim() : 'لم يتم الاختيار';
        };
        const choose = account => {
            select.value = account.id;
            select.dispatchEvent(new Event('change', {bubbles: true}));
            search.value = '';
            results.classList.add('d-none');
            search.focus();
        };
        const setActive = index => {
            if (!visible.length) return;
            activeIndex = (index + visible.length) % visible.length;
            results.querySelectorAll('.account-picker-result').forEach((button, buttonIndex) => {
                button.classList.toggle('is-active', buttonIndex === activeIndex);
                if (buttonIndex === activeIndex) button.scrollIntoView({block: 'nearest'});
            });
        };
        const render = () => {
            const term = normalizeAccountTerm(search.value);
            visible = accounts.filter(account => !term || account.searchable.includes(term)).slice(0, 60);
            activeIndex = -1;
            results.replaceChildren();
            if (!visible.length) {
                const empty = document.createElement('div');
                empty.className = 'account-picker-empty';
                empty.textContent = 'لا توجد حسابات طرفية مطابقة';
                results.appendChild(empty);
            } else {
                visible.forEach(account => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'account-picker-result';
                    const code = document.createElement('span');
                    code.className = 'account-picker-code';
                    code.textContent = account.code;
                    const name = document.createElement('span');
                    name.textContent = account.label.replace(`${account.code} — `, '');
                    button.append(code, name);
                    button.addEventListener('mousedown', event => {
                        event.preventDefault();
                        choose(account);
                    });
                    results.appendChild(button);
                });
            }
            results.classList.remove('d-none');
        };

        search.addEventListener('focus', render);
        search.addEventListener('input', render);
        search.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown') { event.preventDefault(); setActive(activeIndex + 1); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); setActive(activeIndex - 1); }
            else if (event.key === 'Enter' && activeIndex >= 0) { event.preventDefault(); choose(visible[activeIndex]); }
            else if (event.key === 'Escape') results.classList.add('d-none');
        });
        search.addEventListener('blur', () => window.setTimeout(() => results.classList.add('d-none'), 150));
        select.addEventListener('change', updateSelectedText);
        updateSelectedText();
    });
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
        const officeRow=document.querySelector('[data-line="office_charge"]'), officeAccount=officeRow?.querySelector('.account-select'), officeSource=officeRow?.querySelector('.office-account-source');
        if(officeAccount && p.office_charge_credit_account_id && !officeAccount.value){ officeAccount.value=String(p.office_charge_credit_account_id); officeAccount.dispatchEvent(new Event('change', {bubbles:true})); }
        if(officeSource){ officeSource.textContent=p.office_charge_credit_account_id ? `اختير تلقائياً من ${p.office_charge_account_source==='shelter_receipt' ? 'مرجع استلام تكلفة الإيواء #'+p.office_charge_receipt_entry_id : 'حساب مردودات تكلفة الجنسية'}: 'تعذر إثبات الحساب من المرجع؛ اختر حساباً طرفياً يدوياً.'; officeSource.className='form-text '+(p.office_charge_credit_account_id?'text-success':'text-warning'); }
        applyReason();
        } catch(error) { facts.className='alert alert-danger'; facts.textContent='تعذر تحديث الاقتراحات: '+error.message; }
    }
    function syncLineState(){ document.querySelectorAll('[data-line]').forEach(row=>{ const enabled=row.querySelector('.line-enabled').checked; row.classList.toggle('opacity-75',!enabled); const account=row.querySelector('.account-select'), method=row.querySelector('.settlement-method'); if(account) account.required=enabled&&(!method||method.value!=='next_dispatch_deduction'); }); }
    function applyReason(){ const option=reason.options[reason.selectedIndex]; if(!option)return; const d=document.querySelector('[data-line="client_deduction"] .line-enabled'), o=document.querySelector('[data-line="office_charge"] .line-enabled'); d.checked=option.dataset.deduction==='1'; o.checked=option.dataset.office==='1'; syncLineState(); }
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(contract).select2({width:'100%', dir:'rtl', placeholder:'ابحث برقم العقد أو العميل أو العاملة أو مساند', ajax:{url:@json(route('post-arrival-refunds.search-contracts')), dataType:'json', delay:250, data:params=>({q:params.term||''}), processResults:data=>data}})
            .on('change', loadProposal);
    } else contract.addEventListener('change',loadProposal);
    date.addEventListener('change',loadProposal); reason.addEventListener('change',applyReason); document.querySelectorAll('.line-enabled,.settlement-method').forEach(element=>element.addEventListener('change',syncLineState));
    syncLineState();
})();
</script>
@endsection

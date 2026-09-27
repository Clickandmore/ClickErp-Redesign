@extends('admin.layout.indexs.index')

@section('page-title', 'إنشاء استرداد بعد الوصول')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-3">
            <div>
                <h4>إنشاء مستند استرداد بعد الوصول</h4>
                <div class="text-muted">الحفظ ينشئ مسودة فقط، ولا ينشئ أي قيد.</div>
            </div><a href="{{ route('post-arrival-refunds.index') }}" class="btn btn-outline-secondary">رجوع</a>
        </div>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="post" action="{{ route('post-arrival-refunds.store') }}" id="refundForm">@csrf
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">العقد الواصل</label><select name="contract_id"
                                id="contractId" class="form-select" required>
                                <option value="">اختر العقد</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}" @selected(old('contract_id', $selected?->id) == $contract->id)>#{{ $contract->id }} —
                                        {{ $contract->user?->name }} — {{ $contract->maid?->name }} — وصول
                                        {{ $contract->info?->arrival_date }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">تاريخ المستند</label><input type="date"
                                name="settlement_date" id="settlementDate"
                                value="{{ old('settlement_date', now()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="col-md-3"><label class="form-label">سبب الإرجاع</label><select
                                name="return_reason_code_id" id="reasonCode" class="form-select" required>
                                <option value="">اختر السبب</option>
                                @foreach ($reasons as $reason)
                                    <option value="{{ $reason->id }}"
                                        data-deduction="{{ (int) $reason->suggest_client_deduction }}"
                                        data-office="{{ (int) $reason->suggest_office_charge }}"
                                        @selected(old('return_reason_code_id') == $reason->id)>{{ $reason->name }}</option>
                                @endforeach
                            </select></div>
                        <div class="col-md-8"><label class="form-label">السبب المكتوب</label>
                            <textarea name="reason" class="form-control" rows="2" required>{{ old('reason') }}</textarea>
                        </div>
                        <div class="col-md-4"><label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            @if ($selected)
                @php
                    $parseRealDate = static function ($value) {
                        if (!$value) {
                            return null;
                        }
                        try {
                            $date = \Carbon\Carbon::parse($value);
                            return $date->year >= 1900 ? $date : null;
                        } catch (\Throwable $exception) {
                            return null;
                        }
                    };
                    $arrivalDate = $selected->resolveRecruitmentArrivalDateTime();
                    $warrantyStart =
                        $parseRealDate($selected->warranty_start_date) ?: ($arrivalDate ? $arrivalDate->copy() : null);
                    $warrantyEnd =
                        $parseRealDate($selected->warranty_end_date) ?:
                        ($warrantyStart
                            ? $warrantyStart->copy()->addDays(\App\Support\AutomationSettings::warrantyPeriodDays())
                            : null);
                    $returnDate = $parseRealDate($selected->info?->maid_date_back);
                    $warrantyStatus =
                        !$warrantyStart || !$warrantyEnd
                            ? 'unknown'
                            : (now()->lt($warrantyStart)
                                ? 'pending'
                                : (now()->lte($warrantyEnd->copy()->endOfDay())
                                    ? 'active'
                                    : 'expired'));
                    $warrantyLabels = [
                        'active' => 'الضمان ساري',
                        'expired' => 'الضمان منتهي',
                        'pending' => 'الضمان لم يبدأ',
                        'unknown' => 'الضمان غير محدد',
                    ];
                    $warrantyBadge = [
                        'active' => 'success',
                        'expired' => 'danger',
                        'pending' => 'warning',
                        'unknown' => 'secondary',
                    ][$warrantyStatus];
                    $remainingDays = $warrantyStatus === 'active' ? max(0, now()->diffInDays($warrantyEnd, false)) : 0;
                    $returnWithinWarranty =
                        $returnDate && $warrantyStart && $warrantyEnd
                            ? $returnDate->betweenIncluded(
                                $warrantyStart->copy()->startOfDay(),
                                $warrantyEnd->copy()->endOfDay(),
                            )
                            : null;
                    $statusLabels = [
                        'arrival' => 'مرحلة الوصول',
                        'completed' => 'مكتمل',
                        'shelter' => 'داخل الإيواء',
                        'back' => 'مرتجع',
                        'refunded' => 'مسترد',
                        'closed' => 'مغلق',
                        'under_warranty' => 'تحت الضمان',
                    ];
                    $latestReturnRequest = $selected->returnRequests->sortByDesc('id')->first();
                @endphp
                <div class="card mb-3 border-primary">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong><i class="fas fa-file-contract me-1"></i> ملخص العقد والضمان </strong>
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('contracts.details', $selected->id) }}" target="_blank">تفاصيل العقد</a>
                            @if ($selected->status === 'shelter')
                                <a class="btn btn-sm btn-outline-dark"
                                    href="{{ route('ShelterContract.show', $selected->id) }}" target="_blank">تفاصيل
                                    الإيواء</a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <h6 class="text-primary border-bottom pb-2">العقد والعميل</h6>
                                <div class="mb-2"><span class="text-muted">رقم العقد:</span>
                                    <strong>#{{ $selected->id }}</strong></div>
                                <div class="mb-2"><span class="text-muted">حالة العقد:</span> <span
                                        class="badge bg-dark">{{ $statusLabels[$selected->status] ?? $selected->status }}</span>
                                </div>
                                <div class="mb-2"><span class="text-muted">تاريخ العقد:</span>
                                    <strong>{{ $parseRealDate($selected->info?->contract_date)?->format('Y-m-d') ?: $selected->created_at?->format('Y-m-d') ?: 'غير محدد' }}</strong>
                                </div>
                                <div class="mb-2"><span class="text-muted">العميل:</span>
                                    <strong>{{ $selected->user?->name ?: 'غير محدد' }}</strong> <small
                                        class="text-muted">#{{ $selected->user_id }}</small></div>
                                <div class="mb-2"><span class="text-muted">الجوال:</span> <strong
                                        dir="ltr">{{ $selected->user?->phone ?: 'غير محدد' }}</strong></div>
                                <div class="mb-2"><span class="text-muted">الفرع:</span>
                                    {{ $selected->branch?->name ?: 'غير محدد' }}</div>
                                <div><span class="text-muted">المسوق:</span> {{ $selected->marketer?->name ?: 'غير محدد' }}
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <h6 class="text-primary border-bottom pb-2">العاملة والاستقدام</h6>
                                <div class="mb-2"><span class="text-muted">العاملة:</span>
                                    <strong>{{ $selected->maid?->name ?: $selected->info?->manual_maid_name ?: 'غير محددة' }}</strong>
                                </div>
                                <div class="mb-2"><span class="text-muted">الجواز:</span> <strong
                                        dir="ltr">{{ $selected->maid?->passport_num ?: $selected->info?->manual_maid_passport_num ?: 'غير محدد' }}</strong>
                                </div>
                                <div class="mb-2"><span class="text-muted">الجنسية:</span>
                                    {{ $selected->maid?->nationality?->title ?: $selected->info?->manualMaidNationality?->title ?: 'غير محددة' }}
                                </div>
                                <div class="mb-2"><span class="text-muted">المكتب الخارجي:</span>
                                    {{ $selected->maid?->office?->title ?: $selected->info?->manualMaidOffice?->title ?: 'غير محدد' }}
                                </div>
                                <div class="mb-2"><span class="text-muted">رقم مساند:</span> <strong
                                        dir="ltr">{{ $selected->info?->msaned_number ?: 'غير محدد' }}</strong></div>
                                <div class="mb-2"><span class="text-muted">رقم التأشيرة:</span> <span
                                        dir="ltr">{{ $selected->info?->visa_number ?: 'غير محدد' }}</span></div>
                                <div><span class="text-muted">الفاتورة:</span>
                                    <strong>{{ number_format((float) ($selected->invoice?->total_due ?? 0), 2) }}
                                        ريال</strong> — {{ $selected->invoice?->is_paid ? 'مدفوعة' : 'غير مدفوعة' }}</div>
                            </div>
                            <div class="col-lg-4">
                                <div class="h-100 border rounded p-3 border-{{ $warrantyBadge }} bg-light">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                        <h6 class="mb-0">حالة الضمان</h6>
                                        <span
                                            class="badge bg-{{ $warrantyBadge }}">{{ $warrantyLabels[$warrantyStatus] }}</span>
                                    </div>
                                    <div class="mb-2"><span class="text-muted">الوصول الفعلي:</span>
                                        <strong>{{ $arrivalDate?->format('Y-m-d H:i') ?: 'غير محدد' }}</strong></div>
                                    <div class="mb-2"><span class="text-muted">بداية الضمان:</span>
                                        {{ $warrantyStart?->format('Y-m-d H:i') ?: 'غير محددة' }}</div>
                                    <div class="mb-2"><span class="text-muted">نهاية الضمان:</span>
                                        <strong>{{ $warrantyEnd?->format('Y-m-d H:i') ?: 'غير محددة' }}</strong></div>
                                    <div class="mb-2"><span class="text-muted">المدة:</span>
                                        {{ $warrantyStart && $warrantyEnd ? $warrantyStart->diffInDays($warrantyEnd) . ' يوماً' : 'غير محددة' }}
                                    </div>
                                    @if ($warrantyStatus === 'active')
                                        <div class="alert alert-success py-2 mb-2"><strong>{{ $remainingDays }} يوماً
                                                متبقياً</strong> حتى نهاية الضمان.</div>
                                    @elseif($warrantyStatus === 'expired' && $warrantyEnd)
                                        <div class="alert alert-danger py-2 mb-2">انتهى الضمان منذ
                                            <strong>{{ $warrantyEnd->diffInDays(now()) }} يوماً</strong>.</div>
                                    @endif
                                    <div class="mb-2"><span class="text-muted">تاريخ رجوع العاملة:</span>
                                        <strong>{{ $returnDate?->format('Y-m-d') ?: 'غير مسجل' }}</strong></div>
                                    @if ($returnWithinWarranty !== null)
                                        <div
                                            class="alert alert-{{ $returnWithinWarranty ? 'success' : 'danger' }} py-2 mb-2">
                                            الرجوع
                                            {{ $returnWithinWarranty ? 'تم داخل فترة الضمان' : 'تم بعد انتهاء الضمان' }}.
                                        </div>
                                    @endif
                                    <div><span class="text-muted">طلب الإرجاع:</span>
                                        {{ $latestReturnRequest ? '#' . $latestReturnRequest->id . ' — ' . $latestReturnRequest->status : 'لا يوجد طلب إرجاع مسجل' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @php
                $initialProposal = $proposal ?? [];
                $initialOfficeCreditId = (int) ($initialProposal['office_charge_credit_account_id'] ?? 0);
                $initialOfficeCreditLabel = $initialOfficeCreditId
                    ? trim(
                        ($initialProposal['office_charge_credit_account_code'] ?? '') .
                            ' — ' .
                            ($initialProposal['office_charge_credit_account_name'] ?? ''),
                    )
                    : 'غير مربوط؛ اختره يدوياً';
            @endphp
            <div class="alert alert-info" id="facts">
                @if ($selected && $proposal)
                    الفاتورة {{ number_format((float) ($proposal['invoice_total'] ?? 0), 2) }}
                    — إشعارات سابقة {{ number_format((float) ($proposal['previous_credit'] ?? 0), 2) }}
                    — حساب العميل {{ $proposal['client_account_id'] ?: 'غير مضبوط' }}
                    — حساب المكتب {{ $proposal['office_account_id'] ?: 'غير مضبوط' }}
                    — حساب تكلفة الجنسية {{ $initialOfficeCreditLabel }}
                @else
                    اختر العقد لعرض الاقتراحات. الأرقام اقتراحات قابلة للمراجعة وليست قيوداً.
                @endif
            </div>
            <div class="card">
                <div class="card-header"><strong>بنود المستند</strong></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>اختيار</th>
                                <th>البند</th>
                                <th>المقترح</th>
                                <th>المعتمد</th>
                                <th>الحساب الدائن / الطريقة</th>
                                <th>سبب التعديل</th>
                            </tr>
                        </thead>
                        <tbody>
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
                            @foreach ($lineTypes as $type => $label)
                                @php
                                    $lineEnabled = (bool) old(
                                        "lines.$loop->index.enabled",
                                        in_array($type, ['credit_note', 'client_deduction', 'client_payout']),
                                    );
                                    $lineProposed =
                                        $type === 'client_payout'
                                            ? max(
                                                0,
                                                (float) ($initialProposal['credit_note'] ?? 0) -
                                                    (float) ($initialProposal['client_deduction'] ?? 0),
                                            )
                                            : (float) ($initialProposal[$type] ?? 0);
                                @endphp
                                <tr data-line="{{ $type }}">
                                    <td><input type="hidden" name="lines[{{ $loop->index }}][enabled]"
                                            value="0"><input type="checkbox" class="form-check-input line-enabled"
                                            name="lines[{{ $loop->index }}][enabled]" value="1"
                                            @checked($lineEnabled)></td>
                                    <td><strong>{{ $label }}</strong><input type="hidden"
                                            name="lines[{{ $loop->index }}][type]" value="{{ $type }}"></td>
                                    <td><span class="proposal"
                                            data-type="{{ $type }}">{{ number_format($lineProposed, 2, '.', '') }}</span>
                                    </td>
                                    <td><input type="number" step="0.01" min="0"
                                            class="form-control amount line-value"
                                            name="lines[{{ $loop->index }}][approved_amount]"
                                            value="{{ old("lines.$loop->index.approved_amount", number_format($lineProposed, 2, '.', '')) }}">
                                    </td>
                                    <td>
                                        @if ($type === 'office_charge')
                                            <select class="form-select mb-1 settlement-method line-value"
                                                name="lines[{{ $loop->index }}][settlement_method]">
                                                <option value="claim" @selected(old("lines.$loop->index.settlement_method", 'claim') === 'claim')>مطالبة على كشف المكتب
                                                </option>
                                                <option value="next_dispatch_deduction" @selected(old("lines.$loop->index.settlement_method") === 'next_dispatch_deduction')>خصم من
                                                    الإرسالية التالية</option>
                                            </select>
                                            @php
                                                $officeCreditId = (int) old(
                                                    "lines.$loop->index.credit_account_id",
                                                    $initialOfficeCreditId,
                                                );
                                                $officeCreditAccount = $accounts->firstWhere('id', $officeCreditId);
                                                $officeCreditQuery = old(
                                                    "lines.$loop->index.credit_account_query",
                                                    $officeCreditAccount
                                                        ? $officeCreditAccount->code .
                                                            ' — ' .
                                                            $officeCreditAccount->name .
                                                            ' [#' .
                                                            $officeCreditAccount->id .
                                                            ']'
                                                        : '',
                                                );
                                            @endphp
                                            <input type="search" class="form-control account-query line-value"
                                                name="lines[{{ $loop->index }}][credit_account_query]"
                                                list="refundLeafAccounts" value="{{ $officeCreditQuery }}"
                                                placeholder="ابحث برقم الحساب أو الاسم ثم اختر النتيجة" autocomplete="off"
                                                spellcheck="false" onfocus="this.select()">
                                            <small class="form-text text-muted">اختيار النتيجة يثبت رقم الحساب تلقائياً عند
                                                الحفظ.</small>
                                            <small
                                                class="form-text {{ $initialOfficeCreditId ? 'text-success' : 'text-muted' }} office-account-source">
                                                @if ($initialOfficeCreditId)
                                                    اختير تلقائياً من
                                                    {{ ($initialProposal['office_charge_account_source'] ?? null) === 'shelter_receipt' ? 'مرجع استلام تكلفة الإيواء #' . ($initialProposal['office_charge_receipt_entry_id'] ?? '') : 'حساب مردودات تكلفة الجنسية' }}
                                                @else
                                                    سيُختار حساب تكلفة الجنسية تلقائياً عند وجود مرجع موثق.
                                                @endif
                                            </small>
                                        @elseif($type === 'client_deduction')
                                            @php
                                                $clientCreditId = (int) old("lines.$loop->index.credit_account_id");
                                                $clientCreditAccount = $accounts->firstWhere('id', $clientCreditId);
                                                $clientCreditQuery = old(
                                                    "lines.$loop->index.credit_account_query",
                                                    $clientCreditAccount
                                                        ? $clientCreditAccount->code .
                                                            ' — ' .
                                                            $clientCreditAccount->name .
                                                            ' [#' .
                                                            $clientCreditAccount->id .
                                                            ']'
                                                        : '',
                                                );
                                            @endphp
                                            <input type="search" class="form-control account-query line-value"
                                                name="lines[{{ $loop->index }}][credit_account_query]"
                                                list="refundLeafAccounts" value="{{ $clientCreditQuery }}"
                                                placeholder="ابحث برقم الحساب أو الاسم ثم اختر النتيجة" autocomplete="off"
                                                spellcheck="false" onfocus="this.select()">
                                            <small class="form-text text-muted">اختيار النتيجة يثبت رقم الحساب تلقائياً عند
                                                الحفظ.</small>
                                        @elseif($type === 'client_payout')
                                            <select class="form-select line-value"
                                                name="lines[{{ $loop->index }}][settlement_method]">
                                                <option value="financial_request" @selected(old("lines.$loop->index.settlement_method", 'financial_request') === 'financial_request')>طلب مالي
                                                    مربوط</option>
                                                <option value="balance_only" @selected(old("lines.$loop->index.settlement_method") === 'balance_only')>إبقاء رصيد فقط
                                                </option>
                                            </select>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><input class="form-control line-value" name="lines[{{ $loop->index }}][reason]"
                                            value="{{ old("lines.$loop->index.reason") }}"
                                            placeholder="إلزامي عند تغيير المقترح"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <datalist id="refundLeafAccounts">
                    @foreach ($accounts as $account)
                        <option value="{{ $account->code }} — {{ $account->name }} [#{{ $account->id }}]"></option>
                    @endforeach
                </datalist>
                <div class="card-footer text-end"><button class="btn btn-primary">حفظ المسودة</button></div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        (() => {
                const contract = document.getElementById('contractId'),
                    date = document.getElementById('settlementDate'),
                    reason = document.getElementById('reasonCode');
                const selectedContractId = @json($selected ? (string) $selected->id : '');
                const money = n => Number(n || 0).toFixed(2);

                function handleContractChange() {
                    if (!contract.value) return;
                    if (String(contract.value) !== selectedContractId) {
                        const url = new URL(@json(route('post-arrival-refunds.create')), window.location.origin);
                        url.searchParams.set('contract_id', contract.value);
                        window.location.assign(url.toString());
                        return;
                    }
                    loadProposal();
                }
                async function loadProposal() {
                        if (!contract.value || !date.value) return;
                        const facts = document.getElementById('facts');
                        try {
                            facts.className = 'alert alert-info';
                            facts.textContent = 'جاري تحميل الاقتراحات...';
                            const url = new URL(@json(route('post-arrival-refunds.proposal')), window.location.origin);
                            url.searchParams.set('contract_id', contract.value);
                            url.searchParams.set('date', date.value);
                            const response = await fetch(url, {
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const contentType = response.headers.get('content-type') || '';
                            if (!contentType.includes('application/json')) throw new Error(response.redirected ?
                                'انتهت جلسة الدخول؛ أعد تحميل الصفحة.' : 'استجابة المقترح ليست JSON.');
                            const json = await response.json();
                            if (!response.ok) {
                                facts.className = 'alert alert-danger';
                                facts.textContent = json.message || 'تعذر تحميل المقترح';
                                return;
                            }
                            const p = json.data;
                            facts.className = 'alert alert-info';
                            const officeCreditLabel = p.office_charge_credit_account_id ?
                                `${p.office_charge_credit_account_code||''} — ${p.office_charge_credit_account_name||''}` :
                                'غير مربوط؛ اختره يدوياً';
                            document.getElementById('facts').textContent =
                                `الفاتورة ${money(p.invoice_total)} — إشعارات سابقة ${money(p.previous_credit)} — حساب العميل ${p.client_account_id||'غير مضبوط'} — حساب المكتب ${p.office_account_id||'غير مضبوط'} — حساب تكلفة الجنسية ${officeCreditLabel}`;
                            document.querySelectorAll('[data-line]').forEach(row => {
                                const t = row.dataset.line;
                                let v = p[t] || 0;
                                if (t === 'client_payout') v = Math.max(0, (p.credit_note || 0) - (p
                                    .client_deduction || 0));
                                row.querySelector('.proposal').textContent = money(v);
                                if (!row.querySelector('.amount').value) row.querySelector('.amount').value = money(
                                    v);
                            });
                            const officeRow = document.querySelector('[data-line="office_charge"]'),
                                officeAccount = officeRow?.querySelector('.account-query'),
                                officeSource = officeRow?.querySelector('.office-account-source');
                            if (officeAccount && p.office_charge_credit_account_id && !officeAccount.value) {
                                officeAccount.value =
                                    `${p.office_charge_credit_account_code||''} — ${p.office_charge_credit_account_name||''} [#${p.office_charge_credit_account_id}]`;
                            }
                            if (officeSource) {
                                officeSource.textContent = p.office_charge_credit_account_id ? `اختير تلقائياً من ${p.office_charge_account_source==='shelter_receipt' ? 'مرجع استلام تكلفة الإيواء #'+p.office_charge_receipt_entry_id : 'حساب مردودات تكلفة الجنسية'}: 'تعذر إثبات الحساب من المرجع؛ اختر حساباً طرفياً يدوياً.'; officeSource.className='form-text '+(p.office_charge_credit_account_id?'text-success':'text-warning'); }
            applyReason();
            } catch(error) { facts.className='alert alert-danger'; facts.textContent='تعذر تحديث الاقتراحات: '+error.message; }
        }
        function syncLineState(){ document.querySelectorAll('[data-line]').forEach(row=>{ const enabled=row.querySelector('.line-enabled').checked; row.classList.toggle('opacity-75',!enabled); const account=row.querySelector('.account-query'), method=row.querySelector('.settlement-method'); if(account) account.required=enabled&&(!method||method.value!=='next_dispatch_deduction'); }); }
        function applyReason(){ const option=reason.options[reason.selectedIndex]; if(!option)return; const d=document.querySelector('[data-line="client_deduction"] .line-enabled'), o=document.querySelector('[data-line="office_charge"] .line-enabled'); d.checked=option.dataset.deduction==='1'; o.checked=option.dataset.office==='1'; syncLineState(); }
        if (window.jQuery && jQuery.fn.select2) {
            jQuery(contract).select2({width:'100%', dir:'rtl', placeholder:'ابحث برقم العقد أو العميل أو العاملة أو مساند', ajax:{url:@json(route('post-arrival-refunds.search-contracts')), dataType:'json', delay:250, data:params=>({q:params.term||''}), processResults:data=>data}})
                .on('change', handleContractChange);
        } else contract.addEventListener('change',handleContractChange);
        date.addEventListener('change',loadProposal); reason.addEventListener('change',applyReason); document.querySelectorAll('.line-enabled,.settlement-method').forEach(element=>element.addEventListener('change',syncLineState));
        syncLineState();
    })();
    </script>
@endsection

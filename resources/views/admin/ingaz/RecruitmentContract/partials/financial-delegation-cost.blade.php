<div class="editor-panel mb-3" id="financialDelegationCost">
    <h2 class="panel-title mb-3">تكلفة التفويض</h2>
    @if ($financialContract->invoice?->ingaz_delegation_cost_constraint_id)
        <div class="alert alert-success mb-0">
            تكلفة التفويض مسجلة بالفعل بالقيد #{{ $financialContract->invoice->ingaz_delegation_cost_constraint_id }}.
            @if ($delegationCostJournal) المبلغ: {{ number_format($delegationCostJournal->value, 2) }} ريال. @endif
        </div>
    @elseif (!$financialContract->invoice)
        <p class="text-muted mb-0">أصدر فاتورة العقد أولاً لتسجيل تكلفة التفويض.</p>
    @elseif (!$automaticEntriesEnabled)
        <div class="alert alert-warning mb-0">يلزم تفعيل القيود المحاسبية التلقائية قبل تسجيل تكلفة التفويض.</div>
    @else
        <p class="text-muted">سجّل المبلغ المدفوع فعليًا بالريال. يُنشأ قيد مدين على حساب تكلفة التفويض ودائن على حساب الدفع المختار.</p>
        <form method="post" action="{{ route('RecruitmentContract.lateFinance.delegationCost', $financialContract->id) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">تاريخ التكلفة</label><input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required></div>
                <div class="col-md-4"><label class="form-label">مرجع التحويل أو الصرف</label><input name="reference" class="form-control" value="{{ old('reference') }}" maxlength="100" required></div>
                <div class="col-md-4"><label class="form-label">المبلغ بالريال</label><input name="amount" type="number" class="form-control" value="{{ old('amount', $delegationCostDefault) }}" min="0.01" max="999999999.99" step="0.01" required></div>
                <div class="col-md-6"><label class="form-label">مصدر الدفع</label><select id="delegationPaymentSource" name="payment_source_type" class="form-select">
                    @foreach (['bank' => 'تحويل بنكي', 'custody' => 'عهدة موظف', 'ingaz' => 'حساب إنجاز'] as $source => $label)<option value="{{ $source }}" @selected(old('payment_source_type', 'bank') === $source)>{{ $label }}</option>@endforeach
                </select></div>
                <div class="col-md-6"><label class="form-label">حساب الدفع</label><select id="delegationPaymentAccount" name="payment_account_id" class="form-select" required>
                    <option value="">اختر الحساب</option>
                    @foreach ($delegationPaymentAccounts as $source => $accounts)
                        @foreach ($accounts as $account)<option data-source="{{ $source }}" value="{{ $account->id }}" @selected(old('payment_account_id') == $account->id && old('payment_source_type', 'bank') === $source)>{{ $account->code }} — {{ $account->name }}</option>@endforeach
                    @endforeach
                </select></div>
                <div class="col-12"><label class="form-check"><input type="checkbox" name="confirmed" value="1" class="form-check-input" required> أؤكد صحة المبلغ وبيانات الدفع لتسجيل تكلفة التفويض.</label></div>
            </div>
            <button class="btn btn-primary mt-3">تأكيد وحفظ تكلفة التفويض</button>
        </form>
    @endif
</div>

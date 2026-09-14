<section class="operations-panel" id="agent-cost-recovery">
    <h3 class="section-title">استرداد تكلفة الوكيل</h3>
    @if(!$agentCostSummary)
        <div class="alert alert-info" role="status">
            لعرض وتنفيذ استرداد تكلفة الوكيل، يلزم منح المستخدم صلاحية «استرداد تكلفة الوكيل من الإيواء» من إدارة الصلاحيات.
        </div>
    @else
    <p>مطالبة المكتب بتكلفته الأصلية فقط، مع بقاء العاملة في الإيواء. تسجيل التحصيل من المكتب عملية منفصلة.</p>
    @if($dispatch = $contract->externalOfficeDispatch)
        <div class="card mb-3"><div class="card-body">
            <h4>تفاصيل تكلفة الإرسالية</h4>
            <p>المكتب الخارجي: <strong>{{ $dispatch->office_name ?: 'غير مسجل' }}</strong></p>
            <p>تاريخ الإرسالية: {{ optional($dispatch->dispatch_date)->format('Y-m-d') ?: 'غير مسجل' }}
                — تكلفة المكتب: <strong>{{ number_format((float) $dispatch->office_contract_cost_usd, 2) }} دولار</strong></p>
            <p>قيد تكلفة المورد:
                @if($supplierJournal = $contract->invoice?->externalOfficeCostConstraint)
                    <a href="{{ route('finance.constraints.show', $supplierJournal->id) }}">#{{ $supplierJournal->code ?: $supplierJournal->id }}</a>
                    — {{ substr((string) $supplierJournal->date, 0, 10) }}
                    — <strong>{{ number_format((float) $supplierJournal->constraintDetails->sum('creditor_value'), 2) }} ريال</strong>
                @else
                    غير مثبت
                @endif
            </p>
            <a href="{{ route('RecruitmentContract.externalOfficeDispatch.edit', $contract->id) }}" class="btn btn-outline-primary">عرض الإرسالية</a>
            @foreach($supplierStatementAccounts as $officeAccount)
                <a href="{{ route('finance.account-statement-suppliers.index', ['account_id' => $officeAccount->id, 'type' => 'both', 'other_account' => 'yes']) }}" class="btn btn-outline-primary">كشف حساب المورد — {{ $officeAccount->name }}</a>
            @endforeach
            @if(!$agentCostSummary['settled'] && $agentCostSummary['posted'] <= 0)
                <p class="alert alert-warning mt-3 mb-0">الإرسالية موجودة، لكن لم تُسجل تكلفة الوكيل في دفتر الإيواء. يلزم مراجعة قيد نقل التكلفة إلى الإيواء قبل إتاحتها للاسترداد.</p>
            @endif
        </div></div>
    @endif
    @if(session('agent_recovery_success'))
        <div class="alert alert-success">{{ session('agent_recovery_success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <div class="row mb-3">
        <div class="col-md-3">التكلفة المسجلة تاريخياً: <strong>{{ number_format($agentCostSummary['posted'], 2) }}</strong></div>
        <div class="col-md-3">صافي الاسترداد المسجل: <strong>{{ number_format($agentCostSummary['recovered'], 2) }}</strong></div>
        <div class="col-md-3">تكلفة الوكيل المتبقية: <strong>{{ number_format($agentCostSummary['available'], 2) }}</strong></div>
        <div class="col-md-3">الإعاشة المتبقية: <strong>{{ number_format($agentCostSummary['living'], 2) }}</strong></div>
    </div>
    @foreach($agentCostSummary['issues'] as $issue)<div class="alert alert-warning">{{ $issue }}</div>@endforeach
    @include('admin.ingaz.ShelterContract.partials.cost-statement')
    @if($agentCostSummary['settled'])
        <p class="alert alert-info">تمت تسوية تكلفة الوكيل بعكس قيد المورد؛ لا تُسترد مرة أخرى.</p>
    @elseif($agentCostSummary['available'] <= 0)
        <p class="text-muted">لا تكلفة وكيل مثبتة ومتاحة للاسترداد. الإعاشة وحدها لا تتيح استرداد تكلفة الوكيل.</p>
    @endif
    @foreach($agentCostSummary['lots'] as $receiptId => $lot)
        @if($lot['remaining'] > 0)
            <div class="card mb-3"><div class="card-body">
                <p>مرجع استلام التكلفة #{{ $receiptId }} — العقد #{{ $lot['entry']->contract_id }} — المبلغ: <strong>{{ number_format($lot['remaining'], 2) }} ريال</strong></p>
                <p>حساب المكتب الأصلي: {{ \Nami\Finance\Models\Account::find($lot['office_account_id'])?->name ?: 'غير مثبت' }} — قيد تكلفة المورد: {{ $lot['supplier_constraint_id'] ?: 'غير مثبت' }}</p>
                @if(!$lot['valid'])
                    <p class="alert alert-warning">مرجع التكلفة أو حساب المكتب غير مكتمل، أو سبق عكس قيد المورد. يلزم مراجعة المحاسب قبل الاسترداد.</p>
                @endif
                @if($canRecoverAgentCost && $lot['valid'] && !$agentCostSummary['settled'] && !$agentCostSummary['issues'] && (int)$lot['entry']->contract_id === (int)$contract->id && $contract->status === 'shelter' && !$contract->info?->is_outside_shelter)
                    <form method="POST" action="{{ route('ShelterContract.agentRecovery.store', $contract->id) }}">
                        @csrf
                        <input type="hidden" name="receipt_entry_id" value="{{ $receiptId }}">
                        <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                        <div class="mb-2"><label>تاريخ الاسترداد</label><input class="form-control" type="date" name="date" required max="{{ now()->toDateString() }}" value="{{ old('date', now()->toDateString()) }}"></div>
                        <div class="mb-2"><label>سبب الاسترداد ومرجع مطالبة المكتب</label><textarea class="form-control" name="reason" required maxlength="1000">{{ old('reason') }}</textarea></div>
                        <button type="submit" class="btn btn-primary">إثبات استرداد {{ number_format($lot['remaining'], 2) }} ريال من المكتب</button>
                    </form>
                @else
                    <button type="button" class="btn btn-secondary" disabled>الاسترداد غير متاح لهذه التكلفة</button>
                @endif
            </div></div>
        @endif
    @endforeach
    @foreach($agentRecoveries as $recovery)
        <div class="card mb-2"><div class="card-body">
            <strong>استرداد #{{ $recovery->id }} — {{ number_format($recovery->amount, 2) }} ريال</strong>
            <p>{{ $recovery->officeAccount?->name }} — {{ $recovery->date->format('Y-m-d') }} — رقم القيد: {{ $recovery->constraint?->code }}</p>
            <p>{{ $recovery->reason }}</p>
            @if($recovery->reversal_constraint_id)
                <p>تم العكس — رقم القيد: {{ $recovery->reversalConstraint?->code }} — {{ $recovery->reversal_reason }}</p>
            @elseif($canReverseAgentCost && $contract->status === 'shelter')
                <form method="POST" action="{{ route('ShelterContract.agentRecovery.reverse', [$contract->id, $recovery->id]) }}">
                    @csrf
                    <label>سبب العكس</label><input class="form-control mb-2" name="reason" required maxlength="1000">
                    <button class="btn btn-outline-danger" type="submit">عكس الاسترداد</button>
                    <small>يُرفض العكس بعد أي حركة خروج لاحقة، بما فيها التجربة.</small>
                </form>
            @endif
        </div></div>
    @endforeach
    @endif
</section>

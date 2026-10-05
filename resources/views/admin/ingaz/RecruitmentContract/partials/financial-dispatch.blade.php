<div class="editor-panel mb-3" id="financialDispatch">
    <h2 class="panel-title mb-3">إرسالية المكتب الخارجي</h2>
    @if ($financialContract->externalOfficeDispatch)
        @php($dispatch = $financialContract->externalOfficeDispatch)
        <div class="alert alert-success mb-0">
            الإرسالية مسجلة بتاريخ {{ $dispatch->dispatch_date?->format('Y-m-d') }}.
            المكتب: {{ $dispatch->office_name }} — محطة الوصول: {{ $dispatch->arrival_station }}.
            التكلفة: {{ number_format($dispatch->office_contract_cost_usd, 2) }} دولار.
        </div>
    @elseif (!$financialContract->invoice)
        <p class="text-muted mb-0">أصدر فاتورة العقد أولاً لتسجيل الإرسالية وإثبات تكلفة المكتب من هنا.</p>
    @elseif ($financialContract->invoice->external_office_cost_constraint_id)
        <div class="alert alert-warning mb-0">توجد تكلفة مكتب مثبتة لهذا العقد دون إرسالية مرتبطة. يلزم مراجعة القيد الموجود قبل تسجيل إرسالية جديدة.</div>
    @elseif ($dispatchSourceErrors)
        <div class="alert alert-warning mb-0">لا يمكن تسجيل الإرسالية قبل استكمال بيانات العقد والسيرة:
            <ul class="mt-2 mb-0">@foreach ($dispatchSourceErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @else
        <p>المكتب الخارجي: <strong>{{ $dispatchOffice?->title }}</strong></p>
        <p class="text-muted">يُؤخذ المكتب من السيرة المرتبطة بالعقد. تُحسب التكلفة بالريال بسعر النظام: 3.75 ريال لكل دولار.</p>
        <form method="post" action="{{ route('RecruitmentContract.lateFinance.dispatch', $financialContract->id) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">تاريخ الإرسالية وإثبات التكلفة</label><input type="date" name="dispatch_date" class="form-control" value="{{ old('dispatch_date', now()->toDateString()) }}" required></div>
                <div class="col-md-4"><label class="form-label">محطة الوصول</label><input name="arrival_station" class="form-control" maxlength="255" value="{{ old('arrival_station', $financialContract->resolveExternalOfficeDispatchArrivalStation()) }}" required></div>
                <div class="col-md-4"><label class="form-label">تكلفة المكتب بالدولار</label><input name="office_contract_cost_usd" type="number" class="form-control" min="0.01" max="99999999.99" step="0.01" value="{{ old('office_contract_cost_usd', $dispatchOffice?->cost_of_dollar) }}" required></div>
            </div>
            <button class="btn btn-primary mt-3">تسجيل الإرسالية</button>
        </form>
    @endif
</div>

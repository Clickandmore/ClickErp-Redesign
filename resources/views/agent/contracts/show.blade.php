@extends('agent.layout')

@section('page-title', 'تفاصيل العقد #' . $contract->id)

@section('page-action')
    <a href="{{ route('agent.contracts.index') }}" class="agent-button">
        <i class="fa fa-arrow-left"></i>
        <span>رجوع</span>
    </a>
@endsection

@section('content')
    <section class="agent-section">
        <div class="agent-section-header">
            <h3>بيانات العقد</h3>
            <span class="agent-badge warning">{{ getContractStatus($contract->status) }}</span>
        </div>

        <div class="agent-detail-grid">
            <div class="agent-detail-item">
                <span>رقم العقد</span>
                <strong>{{ $contract->id }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>تاريخ الإنشاء</span>
                <strong>{{ optional($contract->created_at)->format('Y-m-d') ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>رقم التأشيرة</span>
                <strong>{{ $contract->info?->visa_number ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>قيمة العقد</span>
                <strong>{{ method_exists($contract, 'calculateContractValue') ? formatMoney($contract->calculateContractValue()) : '-' }}</strong>
            </div>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>بيانات العميل</h3>
        </div>

        <div class="agent-detail-grid">
            <div class="agent-detail-item">
                <span>الاسم</span>
                <strong>{{ $contract->user?->name ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>رقم الهوية</span>
                <strong>{{ $contract->user?->national_num ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>الهاتف</span>
                <strong>{{ $contract->user?->phone ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>البريد الإلكتروني</span>
                <strong>{{ $contract->user?->email ?: '-' }}</strong>
            </div>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>بيانات العاملة</h3>
        </div>

        <div class="agent-detail-grid">
            <div class="agent-detail-item">
                <span>الاسم</span>
                <strong>{{ $contract->maid?->name ?: ($contract->info?->manual_maid_name ?: '-') }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>جواز السفر</span>
                <strong>{{ $contract->maid?->passport_num ?: ($contract->info?->manual_maid_passport_num ?: '-') }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>الجنسية</span>
                <strong>{{ $contract->maid?->nationality?->sub_title ?: ($contract->maid?->nationality?->title ?: '-') }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>المكتب الخارجي</span>
                <strong>{{ $contract->maid?->office?->title ?: ($contract->info?->manualMaidOffice?->title ?: '-') }}</strong>
            </div>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>إرسالية المكتب الخارجي</h3>
        </div>

        @if ($contract->externalOfficeDispatch)
            <div class="agent-detail-grid">
                <div class="agent-detail-item">
                    <span>تاريخ الإرسالية</span>
                    <strong>{{ optional($contract->externalOfficeDispatch->dispatch_date)->format('Y-m-d') ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>محطة الوصول</span>
                    <strong>{{ $contract->externalOfficeDispatch->arrival_station ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>تكلفة المكتب بالدولار</span>
                    <strong>{{ $contract->externalOfficeDispatch->office_contract_cost_usd ? formatMoney($contract->externalOfficeDispatch->office_contract_cost_usd) : '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>آخر تحديث</span>
                    <strong>{{ optional($contract->externalOfficeDispatch->updated_at)->format('Y-m-d') ?: '-' }}</strong>
                </div>
            </div>
        @else
            <p class="agent-empty">لا توجد إرسالية مسجلة لهذا العقد.</p>
        @endif
    </section>
@endsection

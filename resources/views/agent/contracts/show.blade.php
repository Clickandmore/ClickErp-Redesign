@extends('agent.layout')

@section('page-title', 'Contract Details #' . $contract->id)

@section('page-action')
    <a href="{{ route('agent.contracts.index') }}" class="agent-button">
        <i class="fa fa-arrow-left"></i>
        <span>Back</span>
    </a>
@endsection

@section('content')
    <section class="agent-section">
        <div class="agent-section-header">
            <h3>Contract Information</h3>
            <span class="agent-badge warning">{{ getContractStatus($contract->status) }}</span>
        </div>

        <div class="agent-detail-grid">
            <div class="agent-detail-item">
                <span>Contract Number</span>
                <strong>{{ $contract->id }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>Created At</span>
                <strong>{{ optional($contract->created_at)->format('Y-m-d') ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>Visa Number</span>
                <strong>{{ $contract->info?->visa_number ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>Contract Value</span>
                <strong>{{ method_exists($contract, 'calculateContractValue') ? formatMoney($contract->calculateContractValue()) : '-' }}</strong>
            </div>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>Client Information</h3>
        </div>

        <div class="agent-detail-grid">
            <div class="agent-detail-item">
                <span>Name</span>
                <strong>{{ $contract->user?->name ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>National ID</span>
                <strong>{{ $contract->user?->national_num ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>Phone</span>
                <strong>{{ $contract->user?->phone ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>Email</span>
                <strong>{{ $contract->user?->email ?: '-' }}</strong>
            </div>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>Worker Information</h3>
        </div>

        <div class="agent-detail-grid">
            <div class="agent-detail-item">
                <span>Name</span>
                <strong>{{ $contract->maid?->name ?: ($contract->info?->manual_maid_name ?: '-') }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>Passport</span>
                <strong>{{ $contract->maid?->passport_num ?: ($contract->info?->manual_maid_passport_num ?: '-') }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>Nationality</span>
                <strong>{{ $contract->maid?->nationality?->sub_title ?: ($contract->maid?->nationality?->title ?: '-') }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>External Office</span>
                <strong>{{ $contract->maid?->office?->title ?: ($contract->info?->manualMaidOffice?->title ?: '-') }}</strong>
            </div>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ app()->getLocale() === 'ar' ? 'إرسالية المكتب الخارجي' : 'External Office Dispatch' }}</h3>
        </div>

        @if ($contract->externalOfficeDispatch)
            <div class="agent-detail-grid">
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'تاريخ الإرسالية' : 'Dispatch Date' }}</span>
                    <strong>{{ optional($contract->externalOfficeDispatch->dispatch_date)->format('Y-m-d') ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'محطة الوصول' : 'Arrival Station' }}</span>
                    <strong>{{ $contract->externalOfficeDispatch->arrival_station ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'تكلفة العقد بالدولار' : 'Office Contract Cost USD' }}</span>
                    <strong>{{ $contract->externalOfficeDispatch->office_contract_cost_usd ? formatMoney($contract->externalOfficeDispatch->office_contract_cost_usd) : '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'آخر تحديث' : 'Last Update' }}</span>
                    <strong>{{ optional($contract->externalOfficeDispatch->updated_at)->format('Y-m-d') ?: '-' }}</strong>
                </div>
            </div>
        @else
            <p class="agent-empty">{{ app()->getLocale() === 'ar' ? 'لم يتم إضافة سجل إرسالية لهذا العقد.' : 'No dispatch record has been added for this contract.' }}</p>
        @endif
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ app()->getLocale() === 'ar' ? 'معلومات الفاتورة' : 'Invoice Information' }}</h3>
            @if ($contract->invoice)
                <span class="agent-badge {{ $contract->invoice->is_paid ? 'success' : 'warning' }}">
                    {{ $contract->invoice->is_paid ? (app()->getLocale() === 'ar' ? 'مدفوعة' : 'Paid') : (app()->getLocale() === 'ar' ? 'غير مدفوعة' : 'Unpaid') }}
                </span>
            @endif
        </div>

        @if ($contract->invoice)
            <div class="agent-detail-grid">
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'رقم الفاتورة' : 'Invoice Number' }}</span>
                    <strong>#{{ $contract->invoice->id }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'تاريخ الدفع' : 'Paid At' }}</span>
                    <strong>{{ $contract->invoice->paid_at ? $contract->invoice->paid_at->format('Y-m-d') : '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'المجموع الفرعي' : 'Subtotal' }}</span>
                    <strong>{{ formatMoney($contract->invoice->subtotal) }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'الضريبة' : 'VAT' }}</span>
                    <strong>{{ formatMoney($contract->invoice->vat_amount) }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total Due' }}</span>
                    <strong>{{ formatMoney($contract->invoice->total_due) }}</strong>
                </div>
                @if ($contract->invoice->external_recruitment_cost > 0)
                    <div class="agent-detail-item">
                        <span>{{ app()->getLocale() === 'ar' ? 'تكلفة الاستقدام الخارجي' : 'External Recruitment Cost' }}</span>
                        <strong>{{ formatMoney($contract->invoice->external_recruitment_cost) }}</strong>
                    </div>
                @endif
            </div>
        @else
            <p class="agent-empty">{{ app()->getLocale() === 'ar' ? 'لا توجد فاتورة مرتبطة بهذا العقد.' : 'No invoice record has been found for this contract.' }}</p>
        @endif
    </section>
@endsection

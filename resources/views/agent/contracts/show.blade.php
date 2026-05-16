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
            <h3>External Office Dispatch</h3>
        </div>

        @if ($contract->externalOfficeDispatch)
            <div class="agent-detail-grid">
                <div class="agent-detail-item">
                    <span>Dispatch Date</span>
                    <strong>{{ optional($contract->externalOfficeDispatch->dispatch_date)->format('Y-m-d') ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>Arrival Station</span>
                    <strong>{{ $contract->externalOfficeDispatch->arrival_station ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>Office Contract Cost USD</span>
                    <strong>{{ $contract->externalOfficeDispatch->office_contract_cost_usd ? formatMoney($contract->externalOfficeDispatch->office_contract_cost_usd) : '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>Last Update</span>
                    <strong>{{ optional($contract->externalOfficeDispatch->updated_at)->format('Y-m-d') ?: '-' }}</strong>
                </div>
            </div>
        @else
            <p class="agent-empty">No dispatch record has been added for this contract.</p>
        @endif
    </section>
@endsection

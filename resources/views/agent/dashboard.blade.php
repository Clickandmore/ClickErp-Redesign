@extends('agent.layout')

@section('page-title', __('agent.dashboard'))

@section('content')
    <section class="agent-grid">
        <div class="agent-card">
            <span>{{ __('agent.total_cvs') }}</span>
            <strong>{{ $stats['maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ __('agent.available_cvs') }}</span>
            <strong>{{ $stats['available_maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ __('agent.total_contracts') }}</span>
            <strong>{{ $stats['contracts_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ __('agent.active_contracts') }}</span>
            <strong>{{ $stats['active_contracts_count'] }}</strong>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ __('agent.office_information') }}</h3>
        </div>
        <div class="agent-detail-grid">
            @forelse ($offices as $office)
                <div class="agent-detail-item">
                    <span>{{ __('agent.office_name') }}</span>
                    <strong>{{ $office->title ?? '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ __('agent.nationality') }}</span>
                    <strong>{{ $office->nationality?->sub_title ?: ($office->nationality?->title ?: '-') }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ __('agent.phone') }}</span>
                    <strong>{{ $office->phone_office ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ __('agent.office_manager') }}</span>
                    <strong>{{ $office->manager_name ?: '-' }}</strong>
                </div>
            @empty
                <p class="agent-empty">
                    {{ __('agent.no_offices_linked') }}</p>
            @endforelse
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ __('agent.latest_cvs') }}</h3>
            <a href="{{ route('agent.maids.index') }}" class="agent-button">
                <i class="fa fa-list"></i>
                <span>{{ __('agent.view_all') }}</span>
            </a>
        </div>
        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('agent.name') }}</th>
                        <th>{{ __('agent.nationality') }}</th>
                        <th>{{ __('agent.passport') }}</th>
                        <th>{{ __('agent.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestMaids as $maid)
                        <tr>
                            <td>{{ $maid->id }}</td>
                            <td>{{ $maid->name ?: '-' }}</td>
                            <td>{{ $maid->nationality?->sub_title ?: ($maid->nationality?->title ?: '-') }}</td>
                            <td>{{ $maid->passport_num ?: '-' }}</td>
                            <td><span class="agent-badge">{{ $maid->status ?: '-' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ __('agent.no_cvs_added') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ __('agent.latest_contracts') }}</h3>
            <a href="{{ route('agent.contracts.index') }}" class="agent-button">
                <i class="fa fa-list"></i>
                <span>{{ __('agent.view_all') }}</span>
            </a>
        </div>
        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('agent.client') }}</th>
                        <th>{{ __('agent.worker') }}</th>
                        <th>{{ __('agent.status') }}</th>
                        <th>{{ __('agent.created_at') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestContracts as $contract)
                        <tr>
                            <td>{{ $contract->id }}</td>
                            <td>{{ $contract->user?->name ?: '-' }}</td>
                            <td>{{ $contract->maid?->name ?: ($contract->info?->manual_maid_name ?: '-') }}</td>
                            <td><span class="agent-badge warning">{{ getContractStatus($contract->status) }}</span></td>
                            <td>{{ optional($contract->created_at)->format('Y-m-d') ?: '-' }}</td>
                            <td>
                                <a href="{{ route('agent.contracts.show', $contract) }}" class="agent-button">
                                    <i class="fa fa-eye"></i>
                                    <span>{{ __('agent.view') }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ __('agent.no_contracts_added') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

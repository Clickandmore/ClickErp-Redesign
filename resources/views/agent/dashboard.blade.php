@extends('agent.layout')

@section('page-title', __('agent.dashboard'))

@push('style')
    <style>
        .agent-dashboard-welcome {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 18px;
            padding: 26px 28px;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            box-shadow: 0 14px 32px rgba(15, 118, 110, .16);
        }

        .agent-dashboard-welcome::after {
            content: "";
            position: absolute;
            width: 190px;
            height: 190px;
            inset-inline-end: -60px;
            top: -95px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }

        .agent-dashboard-welcome h3 {
            position: relative;
            z-index: 1;
            margin: 0 0 6px;
            color: #fff;
            font-size: 24px;
        }

        .agent-dashboard-welcome p {
            position: relative;
            z-index: 1;
            margin: 0;
            color: rgba(255, 255, 255, .82);
            font-size: 14px;
        }

        .agent-dashboard-welcome .agent-button {
            position: relative;
            z-index: 1;
            flex: 0 0 auto;
            min-height: 42px;
            padding-inline: 16px;
            color: #115e59;
            background: #fff;
        }

        .agent-dashboard-welcome .agent-button:hover {
            color: #0f766e;
            background: #f0fdfa;
        }

        .agent-dashboard-stats .agent-card {
            position: relative;
            min-height: 118px;
            padding: 18px;
            border-color: #e7edf4;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .045);
        }

        .agent-stat-icon {
            position: absolute;
            inset-inline-end: 16px;
            top: 16px;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            color: #0f766e;
            background: #e8f7f5;
            font-size: 17px;
        }

        .agent-dashboard-stats .agent-card:nth-child(2) .agent-stat-icon {
            color: #2563eb;
            background: #eff6ff;
        }

        .agent-dashboard-stats .agent-card:nth-child(3) .agent-stat-icon {
            color: #7c3aed;
            background: #f5f3ff;
        }

        .agent-dashboard-stats .agent-card:nth-child(4) .agent-stat-icon {
            color: #b45309;
            background: #fffbeb;
        }

        .agent-dashboard-stats .agent-card strong {
            margin-top: 14px;
            line-height: 1;
        }

        .agent-dashboard-overview {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(280px, .6fr);
            gap: 18px;
            margin-bottom: 18px;
        }

        .agent-dashboard-overview .agent-section {
            margin-bottom: 0;
            border-color: #e7edf4;
            border-radius: 12px;
        }

        .agent-quick-links {
            display: grid;
            gap: 10px;
        }

        .agent-quick-link {
            min-height: 54px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid #e7edf4;
            border-radius: 10px;
            color: var(--agent-ink);
            background: #f8fafc;
            text-decoration: none;
        }

        .agent-quick-link:hover {
            color: var(--agent-primary-dark);
            border-color: rgba(15, 118, 110, .35);
            background: #f0fdfa;
            text-decoration: none;
        }

        .agent-quick-link i {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            color: #fff;
            background: var(--agent-primary);
        }

        .agent-dashboard-table {
            border-color: #e7edf4;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .035);
        }

        .agent-dashboard-table .agent-table tr:last-child td {
            border-bottom: 0;
        }

        @media (max-width: 900px) {
            .agent-dashboard-overview {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .agent-dashboard-welcome {
                align-items: flex-start;
                flex-direction: column;
                padding: 22px;
            }

            .agent-dashboard-welcome h3 {
                font-size: 21px;
            }
        }
    </style>
@endpush

@section('content')
    <section class="agent-dashboard-welcome">
        <div>
            <h3>{{ __('agent.welcome_back', ['name' => auth('agent')->user()?->name ?: __('agent.external_office')]) }}</h3>
            <p>{{ __('agent.dashboard_intro') }}</p>
        </div>
        <a href="{{ route('agent.maids.create') }}" class="agent-button">
            <i class="fa fa-plus"></i>
            <span>{{ __('agent.add_new_cv') }}</span>
        </a>
    </section>

    <section class="agent-grid agent-dashboard-stats">
        <div class="agent-card">
            <i class="fa fa-address-card agent-stat-icon"></i>
            <span>{{ __('agent.total_cvs') }}</span>
            <strong>{{ $stats['maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <i class="fa fa-circle-check agent-stat-icon"></i>
            <span>{{ __('agent.available_cvs') }}</span>
            <strong>{{ $stats['available_maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <i class="fa fa-file-signature agent-stat-icon"></i>
            <span>{{ __('agent.total_contracts') }}</span>
            <strong>{{ $stats['contracts_count'] }}</strong>
        </div>
        <div class="agent-card">
            <i class="fa fa-chart-line agent-stat-icon"></i>
            <span>{{ __('agent.active_contracts') }}</span>
            <strong>{{ $stats['active_contracts_count'] }}</strong>
        </div>
    </section>

    <div class="agent-dashboard-overview">
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
                    <p class="agent-empty">{{ __('agent.no_offices_linked') }}</p>
                @endforelse
            </div>
        </section>

        <section class="agent-section">
            <div class="agent-section-header">
                <h3>{{ __('agent.quick_actions') }}</h3>
            </div>
            <div class="agent-quick-links">
                <a href="{{ route('agent.maids.index') }}" class="agent-quick-link">
                    <i class="fa fa-address-card"></i>
                    <strong>{{ __('agent.manage_cvs') }}</strong>
                </a>
                <a href="{{ route('agent.contracts.index') }}" class="agent-quick-link">
                    <i class="fa fa-file-signature"></i>
                    <strong>{{ __('agent.review_contracts') }}</strong>
                </a>
                <a href="{{ route('agent.philippines-tracking.index') }}" class="agent-quick-link">
                    <i class="fa fa-clipboard-list"></i>
                    <strong>{{ __('agent.track_procedures') }}</strong>
                </a>
            </div>
        </section>
    </div>

    <section class="agent-section agent-dashboard-table">
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

    <section class="agent-section agent-dashboard-table">
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

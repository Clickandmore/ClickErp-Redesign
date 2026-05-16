@extends('agent.layout')

@php $isAr = app()->getLocale() === 'ar'; @endphp

@section('page-title', $isAr ? 'لوحة التحكم' : 'Dashboard')

@section('content')
    <section class="agent-grid">
        <div class="agent-card">
            <span>{{ $isAr ? 'إجمالي السير الذاتية' : 'Total CVs' }}</span>
            <strong>{{ $stats['maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ $isAr ? 'السير الذاتية المتاحة' : 'Available CVs' }}</span>
            <strong>{{ $stats['available_maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ $isAr ? 'إجمالي العقود' : 'Total Contracts' }}</span>
            <strong>{{ $stats['contracts_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>{{ $isAr ? 'العقود النشطة' : 'Active Contracts' }}</span>
            <strong>{{ $stats['active_contracts_count'] }}</strong>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'معلومات المكتب' : 'Office Information' }}</h3>
        </div>
        <div class="agent-detail-grid">
            @forelse ($offices as $office)
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'اسم المكتب' : 'Office Name' }}</span>
                    <strong>{{ $office->title ?? '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'الجنسية' : 'Nationality' }}</span>
                    <strong>{{ $office->nationality?->sub_title ?: ($office->nationality?->title ?: '-') }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'الهاتف' : 'Phone' }}</span>
                    <strong>{{ $office->phone_office ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'مدير المكتب' : 'Office Manager' }}</span>
                    <strong>{{ $office->manager_name ?: '-' }}</strong>
                </div>
            @empty
                <p class="agent-empty">{{ $isAr ? 'لا توجد مكاتب مرتبطة بهذا الحساب.' : 'No offices are linked to this account.' }}</p>
            @endforelse
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'أحدث السير الذاتية' : 'Latest CVs' }}</h3>
            <a href="{{ route('agent.maids.index') }}" class="agent-button">
                <i class="fa fa-list"></i>
                <span>{{ $isAr ? 'عرض الكل' : 'View All' }}</span>
            </a>
        </div>
        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ $isAr ? 'الاسم' : 'Name' }}</th>
                        <th>{{ $isAr ? 'الجنسية' : 'Nationality' }}</th>
                        <th>{{ $isAr ? 'جواز السفر' : 'Passport' }}</th>
                        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
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
                            <td colspan="5">{{ $isAr ? 'لا توجد سير ذاتية حتى الآن.' : 'No CVs have been added yet.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'أحدث العقود' : 'Latest Contracts' }}</h3>
            <a href="{{ route('agent.contracts.index') }}" class="agent-button">
                <i class="fa fa-list"></i>
                <span>{{ $isAr ? 'عرض الكل' : 'View All' }}</span>
            </a>
        </div>
        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ $isAr ? 'العميل' : 'Client' }}</th>
                        <th>{{ $isAr ? 'العاملة' : 'Worker' }}</th>
                        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                        <th>{{ $isAr ? 'تاريخ الإنشاء' : 'Created At' }}</th>
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
                                    <span>{{ $isAr ? 'عرض' : 'View' }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ $isAr ? 'لا توجد عقود حتى الآن.' : 'No contracts have been added yet.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

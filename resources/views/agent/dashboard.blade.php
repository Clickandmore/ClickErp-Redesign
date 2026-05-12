@extends('agent.layout')

@section('page-title', 'الرئيسية')

@section('content')
    <section class="agent-grid">
        <div class="agent-card">
            <span>إجمالي السير</span>
            <strong>{{ $stats['maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>السير المتاحة</span>
            <strong>{{ $stats['available_maids_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>إجمالي العقود</span>
            <strong>{{ $stats['contracts_count'] }}</strong>
        </div>
        <div class="agent-card">
            <span>العقود النشطة</span>
            <strong>{{ $stats['active_contracts_count'] }}</strong>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>بيانات المكتب</h3>
        </div>
        <div class="agent-detail-grid">
            @forelse ($offices as $office)
                <div class="agent-detail-item">
                    <span>اسم المكتب</span>
                    <strong>{{ $office->title ?? '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>الجنسية</span>
                    <strong>{{ $office->nationality?->sub_title ?: ($office->nationality?->title ?: '-') }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>الهاتف</span>
                    <strong>{{ $office->phone_office ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>مدير المكتب</span>
                    <strong>{{ $office->manager_name ?: '-' }}</strong>
                </div>
            @empty
                <p class="agent-empty">لا توجد مكاتب مرتبطة بهذا الحساب.</p>
            @endforelse
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>أحدث السير الذاتية</h3>
            <a href="{{ route('agent.maids.index') }}" class="agent-button">
                <i class="fa fa-list"></i>
                <span>عرض الكل</span>
            </a>
        </div>
        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>الجنسية</th>
                        <th>جواز السفر</th>
                        <th>الحالة</th>
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
                            <td colspan="5">لا توجد سير ذاتية حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>أحدث العقود</h3>
            <a href="{{ route('agent.contracts.index') }}" class="agent-button">
                <i class="fa fa-list"></i>
                <span>عرض الكل</span>
            </a>
        </div>
        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>العاملة</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
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
                                    <span>عرض</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">لا توجد عقود حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

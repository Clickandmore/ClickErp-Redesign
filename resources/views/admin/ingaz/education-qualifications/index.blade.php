@extends('admin.layout.indexs.index')

@section('page-title', 'المؤهلات التعليمية')

@section('style')
<style>
    .eq-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 999px;
        border: 1px solid #e5ebf4;
        background: #f9fbff;
        font-size: 0.85rem;
        color: #334155;
    }
    .eq-stat-pill i { color: #2563eb; }
    .eq-table th {
        font-weight: 600;
        font-size: 0.85rem;
        color: #475569;
        white-space: nowrap;
    }
    .eq-table td { vertical-align: middle; }
    .eq-title-ar { font-weight: 600; color: #0f172a; }
    .eq-title-en { color: #64748b; font-size: 0.9rem; }
    .eq-row-hidden td:not(.eq-actions) { opacity: 0.6; }
    .eq-usage {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        font-size: 0.8rem;
    }
    .eq-empty { padding: 3rem 1rem; }
    .eq-empty i { font-size: 2.5rem; color: #cbd5e1; }
</style>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h3 class="h4 mb-2"><i class="fas fa-graduation-cap text-primary me-2"></i>المؤهلات التعليمية</h3>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="eq-stat-pill"><i class="fas fa-list"></i>الإجمالي: {{ $stats['total'] }}</span>
                        <span class="eq-stat-pill"><i class="fas fa-eye text-success"></i>الظاهرة: {{ $stats['active'] }}</span>
                        <span class="eq-stat-pill"><i class="fas fa-eye-slash text-danger"></i>المخفية: {{ $stats['hidden'] }}</span>
                    </div>
                </div>
                <a href="{{ route('education-qualifications.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>إضافة مؤهل تعليمي
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                المؤهلات الظاهرة فقط تُعرض في قوائم اختيار المؤهل عند إضافة أو تعديل السيرة الذاتية.
                إخفاء أو حذف مؤهل لا يغيّر قيمته المحفوظة في السير الموجودة.
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 eq-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px">#</th>
                                    <th>المؤهل</th>
                                    <th>الاستخدام في السير</th>
                                    <th>الحالة</th>
                                    <th class="text-end">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($qualifications as $qualification)
                                    @php $usage = $usageCounts[$qualification->title_ar] ?? 0; @endphp
                                    <tr class="{{ $qualification->is_active ? '' : 'eq-row-hidden' }}">
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="eq-title-ar">{{ $qualification->title_ar }}</div>
                                            <div class="eq-title-en" dir="ltr">{{ $qualification->title_en }}</div>
                                        </td>
                                        <td>
                                            <span class="eq-usage" title="عدد السير التي تستخدم هذا المؤهل">
                                                <i class="fas fa-users"></i>{{ $usage }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $qualification->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                <i class="fas {{ $qualification->is_active ? 'fa-eye' : 'fa-eye-slash' }} me-1"></i>
                                                {{ $qualification->is_active ? 'ظاهر' : 'مخفي' }}
                                            </span>
                                        </td>
                                        <td class="eq-actions">
                                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                                <form method="post" action="{{ route('education-qualifications.toggle-visibility', $qualification) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $qualification->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                        <i class="fas {{ $qualification->is_active ? 'fa-eye-slash' : 'fa-eye' }} me-1"></i>
                                                        {{ $qualification->is_active ? 'إخفاء' : 'إظهار' }}
                                                    </button>
                                                </form>
                                                <a href="{{ route('education-qualifications.edit', $qualification) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit me-1"></i>تعديل
                                                </a>
                                                <form method="post" action="{{ route('education-qualifications.destroy', $qualification) }}"
                                                    onsubmit="return confirm('هل تريد حذف هذا المؤهل؟ ستبقى قيمته محفوظة في السير الموجودة.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash me-1"></i>حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted eq-empty">
                                            <div class="mb-2"><i class="fas fa-graduation-cap"></i></div>
                                            <div class="mb-3">لا توجد مؤهلات تعليمية بعد.</div>
                                            <a href="{{ route('education-qualifications.create') }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus me-1"></i>إضافة أول مؤهل
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

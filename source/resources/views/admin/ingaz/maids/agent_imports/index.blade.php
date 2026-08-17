@extends('admin.layouts.app')

@section('title', 'ملفات العمالة من الوكلاء')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">ملفات العمالة المرفوعة من الوكلاء</h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">-- كل الحالات --</option>
                <option value="pending_review" {{ request('status') === 'pending_review' ? 'selected' : '' }}>بانتظار المراجعة</option>
                <option value="completed"      {{ request('status') === 'completed'      ? 'selected' : '' }}>مكتمل</option>
                <option value="rejected"       {{ request('status') === 'rejected'       ? 'selected' : '' }}>مرفوض</option>
                <option value="failed"         {{ request('status') === 'failed'         ? 'selected' : '' }}>فشل</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">بحث</button>
            <a href="{{ route('admin.agent-imports.index') }}" class="btn btn-secondary">مسح</a>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>الملف</th>
                            <th>النوع</th>
                            <th>المكتب</th>
                            <th>السجلات</th>
                            <th>صحيحة</th>
                            <th>أخطاء</th>
                            <th>مستوردة</th>
                            <th>الحالة</th>
                            <th>تاريخ الرفع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($imports as $import)
                        @php
                            $badgeClass = match($import->status) {
                                'completed'      => 'success',
                                'rejected','failed' => 'danger',
                                'pending_review' => 'warning',
                                default          => 'secondary'
                            };
                        @endphp
                        <tr>
                            <td>{{ $import->id }}</td>
                            <td class="text-start" style="max-width:180px;">
                                <i class="fa {{ $import->file_type_icon }}"></i>
                                <span title="{{ $import->original_filename }}" style="display:inline-block;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle;">
                                    {{ $import->original_filename }}
                                </span>
                            </td>
                            <td><span class="badge bg-secondary">{{ strtoupper($import->file_type ?? '-') }}</span></td>
                            <td>{{ $import->office?->title ?? '-' }}</td>
                            <td>{{ $import->total_rows }}</td>
                            <td><span class="text-success fw-bold">{{ $import->valid_rows }}</span></td>
                            <td><span class="text-danger fw-bold">{{ $import->invalid_rows }}</span></td>
                            <td><span class="text-primary fw-bold">{{ $import->imported_rows }}</span></td>
                            <td><span class="badge bg-{{ $badgeClass }}">{{ $import->status_label }}</span></td>
                            <td>{{ $import->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.agent-imports.show', $import) }}" class="btn btn-sm btn-info text-white">
                                    <i class="fa fa-eye"></i> عرض
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="py-4 text-muted">لا توجد بيانات.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($imports->hasPages())
        <div class="card-footer">
            {{ $imports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

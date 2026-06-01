@extends('admin.layout.indexs.index')

@section('page-title', 'تذاكر الدعم')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        @include('admin.support._alerts')
        @include('admin.support._nav')

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-ticket-alt text-primary me-2"></i>
                تذاكر الدعم
            </h3>
            <a href="{{ route('support.tickets.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> فتح تذكرة
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">البحث</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="الموضوع أو الوصف أو التصنيف">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">كل الحالات</option>
                            <option value="open" @selected(request('status') === 'open')>مفتوحة</option>
                            <option value="in_progress" @selected(request('status') === 'in_progress')>قيد المعالجة</option>
                            <option value="closed" @selected(request('status') === 'closed')>مغلقة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select">
                            <option value="">كل الأولويات</option>
                            <option value="low" @selected(request('priority') === 'low')>منخفضة</option>
                            <option value="normal" @selected(request('priority') === 'normal')>عادية</option>
                            <option value="high" @selected(request('priority') === 'high')>مرتفعة</option>
                            <option value="urgent" @selected(request('priority') === 'urgent')>عاجلة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="color:#fff">#</th>
                                <th style="color:#fff">الموضوع</th>
                                <th style="color:#fff">التصنيف</th>
                                <th style="color:#fff">الأولوية</th>
                                <th style="color:#fff">الحالة</th>
                                <th style="color:#fff">الموظف</th>
                                <th style="color:#fff">تاريخ الإنشاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->id }}</td>
                                    <td>
                                        <strong>{{ $ticket->subject }}</strong>
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($ticket->message, 90) }}</div>
                                    </td>
                                    <td>{{ $ticket->category ?: '-' }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $ticket->priority }}</span></td>
                                    <td><span class="badge bg-secondary">{{ $ticket->status }}</span></td>
                                    <td>{{ optional($ticket->creator)->name ?: '-' }}</td>
                                    <td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">لا توجد تذاكر دعم</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($tickets->hasPages())
                    <div class="mt-4">
                        {{ $tickets->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

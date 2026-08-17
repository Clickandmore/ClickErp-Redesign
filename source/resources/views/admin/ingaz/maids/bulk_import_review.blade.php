@extends('admin.layout.indexs.index')

@section('page-title')
    مراجعة الاستيراد الجماعي
@endsection

@section('style')
    <style>
        .review-stat {
            border-radius: 16px;
            border: 1px solid #edf2f7;
            box-shadow: 0 8px 24px rgba(15, 34, 58, 0.05);
        }

        .review-stat .icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .errors-list {
            margin: 0;
            padding-right: 1rem;
        }
    </style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h3 class="mb-1">مراجعة الاستيراد رقم #{{ $import->id }}</h3>
                    <div class="text-muted">
                        الملف: {{ $import->original_filename ?: '-' }} |
                        الحالة: <span class="fw-semibold">{{ $import->status_label }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('maids.bulk-import.create') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i>
                        استيراد جديد
                    </a>
                    <a href="{{ route('maids.bulk-import.template') }}" class="btn btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>
                        تحميل التمبلت
                    </a>
                    @if ($import->can_approve)
                        <form action="{{ route('maids.bulk-import.approve', $import) }}" method="POST" id="maid-import-approve-form">
                            @csrf
                            <button type="submit" class="btn btn-primary"
                                onclick="return confirm('سيتم إنشاء العاملات من الصفوف السليمة فقط. هل تريد المتابعة؟')">
                                <i class="fas fa-check-circle me-1"></i>
                                {{ $import->imported_rows > 0 ? 'استكمال الاعتماد' : 'اعتماد الصفوف السليمة' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card review-stat h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">إجمالي الصفوف</div>
                                <div class="fs-3 fw-bold">{{ $import->total_rows }}</div>
                            </div>
                            <span class="icon bg-primary-subtle text-primary"><i class="fas fa-table"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card review-stat h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">صفوف سليمة</div>
                                <div class="fs-3 fw-bold text-success">{{ $import->valid_rows }}</div>
                            </div>
                            <span class="icon bg-success-subtle text-success"><i class="fas fa-check"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card review-stat h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">صفوف بها أخطاء</div>
                                <div class="fs-3 fw-bold text-danger">{{ $import->invalid_rows + $import->failed_rows }}</div>
                            </div>
                            <span class="icon bg-danger-subtle text-danger"><i class="fas fa-triangle-exclamation"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card review-stat h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">تم إنشاؤها فعلياً</div>
                                <div class="fs-3 fw-bold text-info">{{ $import->imported_rows }}</div>
                            </div>
                            <span class="icon bg-info-subtle text-info"><i class="fas fa-user-check"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            @if (request()->boolean('auto_continue') && $import->can_approve)
                <div class="alert alert-info d-flex align-items-center gap-2" id="auto-import-progress">
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    جارٍ استكمال الاستيراد تلقائيًا على دفعات آمنة. تم {{ $import->imported_rows }} من {{ $import->total_rows }}.
                    يمكنك إغلاق الصفحة والاستكمال لاحقًا دون فقد البيانات.
                </div>
                <script>
                    window.setTimeout(function () {
                        document.getElementById('maid-import-approve-form')?.submit();
                    }, 700);
                </script>
            @endif

            @if (($import->invalid_rows + $import->failed_rows) > 0)
                <div class="alert alert-warning">
                    أي صف غير سليم لن يتم إنشاؤه. عدّل ملف Excel ثم ارفع ملفاً جديداً إذا احتجت تصحيح هذه الصفوف.
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">نتيجة تحليل الصفوف</h5>
                    <span class="badge bg-light text-dark">{{ $rows->total() }} صف</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>الصف</th>
                                <th>الاسم</th>
                                <th>رقم عامل أربيا</th>
                                <th>الجواز</th>
                                <th>الجنسية</th>
                                <th>المكتب</th>
                                <th>نوع السيرة</th>
                                <th>المهنة</th>
                                <th>الحالة</th>
                                <th>الملاحظات</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $raw = $row->raw_data ?? [];
                                @endphp
                                <tr>
                                    <td>{{ $row->row_number }}</td>
                                    <td>{{ $raw['name:en'] ?? '-' }}</td>
                                    <td>{{ $raw['arabia_worker_number'] ?? '-' }}</td>
                                    <td>{{ $raw['passport_num'] ?? '-' }}</td>
                                    <td>{{ $raw['nationality'] ?? '-' }}</td>
                                    <td>{{ $raw['office'] ?? '-' }}</td>
                                    <td>{{ $raw['type_id'] ?? '-' }}</td>
                                    <td>{{ $raw['job_titles'] ?? '-' }}</td>
                                    <td>
                                        @if ($row->status === 'imported')
                                            <span class="badge bg-success-subtle text-success">تم الاستيراد</span>
                                        @elseif ($row->status === 'pending')
                                            <span class="badge bg-primary-subtle text-primary">جاهز للاعتماد</span>
                                        @elseif ($row->status === 'invalid')
                                            <span class="badge bg-danger-subtle text-danger">غير صالح</span>
                                        @elseif ($row->status === 'failed')
                                            <span class="badge bg-warning-subtle text-warning">فشل أثناء الاعتماد</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $row->status }}</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 280px;">
                                        @if (!empty($row->error_messages))
                                            <ul class="errors-list text-danger small">
                                                @foreach ($row->error_messages as $message)
                                                    <li>{{ $message }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-success small">لا توجد ملاحظات</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($row->maid_id)
                                            <a href="{{ route('showMaidDetails', $row->maid_id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                عرض العاملة
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">لا توجد صفوف داخل عملية الاستيراد هذه.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

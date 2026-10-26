@extends('admin.layout.indexs.index')

@section('page-title')
    استيراد جماعي للسير
@endsection

@section('style')
    <style>
        .import-hero {
            border: 1px solid #e9eef5;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8fbff 0%, #ffffff 65%);
            box-shadow: 0 10px 30px rgba(31, 78, 120, 0.08);
        }

        .import-step {
            border-radius: 16px;
            border: 1px solid #edf2f7;
            height: 100%;
        }

        .import-columns-table td,
        .import-columns-table th {
            white-space: nowrap;
        }

        .example-note {
            border-radius: 16px;
            border: 1px solid #ffe8a3;
            background: linear-gradient(135deg, #fffdf5 0%, #fff8de 100%);
        }
    </style>
@endsection

@section('content')
    @if (AdminGuardHasAnyPerm([\App\PermKeys::MAIDS_CREATE, \App\PermKeys::DATA_IMPORT_UPLOAD]))
        <div class="container-fluid">
            <div class="import-hero p-4 p-lg-5 mb-4">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h3 class="mb-2">استيراد جماعي للسير الذاتية</h3>
                        <p class="text-muted mb-3">
                            ارفع ملف Excel واحد، ثم راجع الصفوف المطابقة والأخطاء، وبعدها اعتمد الصفوف السليمة فقط لإضافتها إلى قائمة العاملات.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('maids.bulk-import.template') }}" class="btn btn-success">
                                <i class="fas fa-file-excel me-1"></i>
                                تحميل التمبلت
                            </a>
                            <a href="{{ route('maids.create') }}" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-1"></i>
                                إضافة مفردة
                            </a>
                            <a href="{{ route('maids.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-1"></i>
                                العودة لقائمة العاملات
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">آلية العمل</div>
                                <div class="text-muted small mb-2">1. تحميل التمبلت من النظام</div>
                                <div class="text-muted small mb-2">2. تعبئة البيانات والاختيارات</div>
                                <div class="text-muted small mb-2">3. رفع الملف ومراجعة النتائج</div>
                                <div class="text-muted small">4. اعتماد الصفوف السليمة فقط</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card import-step">
                        <div class="card-header">
                            <h5 class="mb-0">رفع ملف الاستيراد</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('maids.bulk-import.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">ملف Excel</label>
                                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                                        accept=".xlsx,.xls" required>
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="alert alert-info mb-3">
                                    يدعم هذا المسار استيراد البيانات النصية والاختيارات المرتبطة بالنظام. الصور وملفات السيرة تُضاف لاحقاً إذا لزم.
                                </div>
                                <div class="example-note p-3 small mb-3">
                                    التمبلت الذي يتم تنزيله يحتوي الصف الثاني كمثال عملي جاهز للتعديل أو النسخ.
                                    إذا بقيت قيمتا `EDIT_ME_NAME` و `TEMPLATE-EXAMPLE` كما هما، فسيتجاهل النظام هذا الصف تلقائياً عند الرفع.
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-upload me-1"></i>
                                    رفع الملف وبدء المراجعة
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card import-step">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">الحقول داخل التمبلت</h5>
                            <span class="badge bg-light text-dark">{{ count($columns) }} عمود</span>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle import-columns-table">
                                <thead>
                                    <tr>
                                        <th>الحقل</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($columns as $column)
                                        <tr>
                                            <td>{{ $column['label'] }}</td>
                                            <td>
                                                @if ($column['required'])
                                                    <span class="badge bg-danger-subtle text-danger">مطلوب</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">اختياري</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">مثال جاهز للتعبئة</h5>
                    <span class="badge bg-warning-subtle text-warning">موجود أيضاً داخل صف المثال في التمبلت</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        هذا المثال مرجعي لتوضيح طريقة التعبئة، خصوصاً في الحقول التي تقبل أكثر من قيمة مثل `المهنة` و`المهارات` و`اللغات`.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle import-columns-table mb-0">
                            <thead>
                                <tr>
                                    <th>الحقل</th>
                                    <th>مثال</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($columns as $column)
                                    <tr>
                                        <td>{{ $column['label'] }}</td>
                                        <td dir="ltr">{{ $exampleRow[$column['key']] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">آخر عمليات الاستيراد</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الملف</th>
                                <th>الحالة</th>
                                <th>الصفوف</th>
                                <th>المرفوع بواسطة</th>
                                <th>التاريخ</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentImports as $import)
                                <tr>
                                    <td>{{ $import->id }}</td>
                                    <td>{{ $import->original_filename ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $import->status === 'completed' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                            {{ $import->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $import->total_rows }}</td>
                                    <td>{{ $import->uploadedBy->name ?? '-' }}</td>
                                    <td>{{ optional($import->created_at)->format('Y-m-d h:i A') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('maids.bulk-import.show', $import) }}" class="btn btn-sm btn-outline-primary">
                                            مراجعة
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">لا توجد عمليات استيراد بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

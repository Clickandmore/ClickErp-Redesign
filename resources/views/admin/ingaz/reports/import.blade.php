@extends('admin.layout.indexs.index')

@section('page-title')
    استيراد البيانات
@endsection

@section('style')
    <style>
        .import-panel {
            border: 1px solid #e8edf5;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }

        .template-item {
            border: 1px solid #edf2f7;
            border-radius: 8px;
            padding: 16px;
            height: 100%;
            background: #fff;
        }

        .template-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef4ff;
            color: #0d6efd;
        }

        .template-description {
            color: #64748b;
            min-height: 48px;
            line-height: 1.65;
        }

        .upload-zone {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
        }

        .columns-preview {
            max-height: 150px;
            overflow: auto;
        }

        .valid-values-preview {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: 10px 12px;
        }

        .import-progress-overlay {
            position: fixed;
            inset: 0;
            z-index: 11000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(2px);
        }

        .import-progress-overlay.is-visible {
            display: flex;
        }

        .import-progress-dialog {
            width: min(520px, 100%);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.3);
        }

        .import-progress-dialog .progress {
            height: 18px;
        }
    </style>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-file-import text-primary me-2"></i>
                        استيراد البيانات
                    </h3>
                    <p class="text-muted mb-0">تحميل قوالب Excel للأقسام المطلوبة، ثم رفع ملفات الاستيراد إلى النظام.</p>
                </div>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary mt-3 mt-sm-0">
                    <i class="fas fa-chart-pie me-1"></i>
                    إدارة التقارير
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('import_errors'))
                <div class="alert alert-warning">
                    <div class="fw-bold mb-2">ملاحظات على الصفوف التي لم يتم استيرادها:</div>
                    @foreach (session('import_errors') as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                    @if (count(session('import_errors')) >= 10)
                        <div class="mt-2 text-muted">تم عرض أول 10 ملاحظات فقط.</div>
                    @endif
                </div>
            @endif

            @if (session('bond_generation_errors'))
                <div class="alert alert-warning">
                    <div class="fw-bold mb-2">ملاحظات توليد سندات القبض والصرف:</div>
                    @foreach (session('bond_generation_errors') as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="import-panel p-3 p-lg-4 mb-4">
                <h5>ملفات المراجعة والاعتماد الأخيرة</h5>
                <div class="table-responsive"><table class="table table-striped">
                    <thead><tr><th>الملف</th><th>النوع</th><th>الحالة</th><th>الرفع</th><th></th></tr></thead>
                    <tbody>@forelse($reviewBatches as $batch)
                        <tr><td>{{ $batch->original_name }}</td><td>{{ $templates[$batch->type]['label'] ?? $batch->type }}</td>
                            <td>{{ $batch->status === 'approved' ? 'معتمد' : 'بانتظار المراجعة' }}</td><td>{{ $batch->created_at }}</td>
                            <td><a href="{{ route('reports.import.show', $batch) }}">المراجعة والنتائج</a></td></tr>
                    @empty<tr><td colspan="5">لا توجد ملفات مراجعة.</td></tr>@endforelse</tbody>
                </table></div>
            </div>

            @if(AdminGuardHasAnyPerm(['data_import_upload']))
            <div class="import-panel p-3 p-lg-4 mb-4">
                <div class="row g-4 align-items-end">
                    <div class="col-lg-4">
                        <h5 class="mb-2">رفع ملف استيراد</h5>
                        <p class="text-muted mb-0">
                            اختر نوع البيانات ثم ارفع الملف بعد تعبئة القالب. جميع الأنواع تنتقل إلى المراجعة قبل الاعتماد والحفظ.
                        </p>
                    </div>
                    <div class="col-lg-8">
                        <form action="{{ route('reports.import.store') }}" method="POST" enctype="multipart/form-data" id="data-import-form">
                            @csrf
                            <div class="row g-3 upload-zone p-3">
                                <div class="col-md-4">
                                    <label for="import-type" class="form-label">نوع البيانات</label>
                                    <select name="type" id="import-type" class="form-select" required>
                                        <option value="">اختر النوع</option>
                                        @foreach ($templates as $key => $template)
                                            <option value="{{ $key }}" {{ old('type', request('type')) === $key ? 'selected' : '' }}>{{ $template['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="import-file" class="form-label">الملف</label>
                                    <input type="file" name="file" id="import-file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                </div>
                                <div class="col-md-3 d-grid align-self-end">
                                    <button type="submit" class="btn btn-primary" id="data-import-submit">
                                        <i class="fas fa-upload me-1"></i>
                                        رفع الملف
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="hidden" name="finance_constraints_missing_only" value="0">
                                        <input class="form-check-input" type="checkbox" name="finance_constraints_missing_only"
                                               value="1" id="finance-constraints-missing-only"
                                               {{ old('finance_constraints_missing_only', '1') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="finance-constraints-missing-only">
                                            للقيود المالية: إدخال أرقام القيود المفقودة فقط وعدم تعديل أي قيد موجود (موصى به)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @endif
            @if(AdminGuardHasAnyPerm(['data_import_generate_bonds']))
            <div class="import-panel p-3 p-lg-4 mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8">
                        <h5 class="mb-2">توليد سندات القبض والصرف من القيود المحاسبية</h5>
                        <p class="text-muted mb-2">
                            يتم توليد السندات من القيود اليومية غير المرتبطة بسند، بشرط أن يحتوي القيد على طرف مدين واحد وطرف دائن واحد وأن يكون أحد الطرفين حساب صندوق أو بنك.
                        </p>
                        @if (!empty($bondGenerationStats['ready']))
                            <div class="d-flex flex-wrap gap-2 small">
                                <span class="badge bg-light text-dark border">
                                    قيود مرشحة: {{ number_format($bondGenerationStats['candidate_constraints'] ?? 0) }}
                                </span>
                                <span class="badge bg-light text-dark border">
                                    سندات مرتبطة بقيود: {{ number_format($bondGenerationStats['existing_bonds'] ?? 0) }}
                                </span>
                                <span class="badge bg-success-subtle text-success border">
                                    سندات القبض المستوردة: {{ number_format($bondGenerationStats['imported_receipt_bonds'] ?? 0) }}
                                </span>
                                <span class="badge bg-success-subtle text-success border">
                                    إجمالي حركة القبض: {{ number_format($bondGenerationStats['imported_receipt_movement'] ?? 0, 2) }} ريال
                                </span>
                            </div>
                        @else
                            <div class="alert alert-warning py-2 mb-0">
                                جداول المالية المطلوبة غير جاهزة لتوليد السندات.
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-4">
                        <form action="{{ route('reports.import.generate-bonds') }}" method="POST"
                              onsubmit="return confirm('سيتم إنشاء سندات قبض وصرف من القيود اليومية المؤهلة وغير المرتبطة بسند. هل تريد المتابعة؟')">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100" {{ empty($bondGenerationStats['ready']) ? 'disabled' : '' }}>
                                <i class="fas fa-receipt me-1"></i>
                                توليد سندات القبض والصرف
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @endif
            @if(AdminGuardHasAnyPerm(['data_import_upload']))
            <div class="row g-3 mb-4">
                @foreach ($templates as $key => $template)
                    <div class="col-xl-4 col-md-6">
                        <div class="template-item">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <span class="template-icon">
                                    <i class="{{ $template['icon'] }}"></i>
                                </span>
                                <div>
                                    <h5 class="mb-1">{{ $template['label'] }}</h5>
                                    <div class="text-muted small" dir="ltr">{{ $template['route_hint'] }}</div>
                                </div>
                            </div>

                            <p class="template-description mb-3">{{ $template['description'] }}</p>

                            @if (!empty($template['columns']))
                                <div class="columns-preview mb-3">
                                    @foreach ($template['columns'] as $column)
                                        <span class="badge bg-light text-dark border mb-1">
                                            {{ $column['label'] }}
                                            @if ($column['required'])
                                                <span class="text-danger">*</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>

                                @php
                                    $columnsWithValues = collect($template['columns'])
                                        ->filter(fn ($column) => !empty($column['valid_values']));
                                @endphp

                                @if ($columnsWithValues->isNotEmpty())
                                    <div class="valid-values-preview mb-3 small">
                                        @foreach ($columnsWithValues as $column)
                                            <div class="fw-semibold mb-1">{{ $column['label'] }}:</div>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                @foreach ($column['valid_values'] as $value)
                                                    <span class="badge bg-white text-dark border">{{ $value }}</span>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-info py-2 small mb-3">
                                    يستخدم تمبلت السير الذاتية التفصيلي الموجود في صفحة الاستيراد الجماعي.
                                </div>
                            @endif

                            <div class="d-flex flex-wrap gap-2">
                                @if (($template['downloadable'] ?? true) !== false)
                                    <a href="{{ route('reports.import.template', $key) }}" class="btn btn-success btn-sm">
                                        <i class="fas fa-file-excel me-1"></i>
                                        تحميل التمبلت
                                    </a>
                                @else
                                    <span class="badge bg-info text-dark align-self-center">
                                        ارفع تقرير النظام القديم مباشرة
                                    </span>
                                @endif
                                @if (in_array($key, ['maids', 'arabia_cvs'], true))
                                    <a href="{{ route('maids.bulk-import.create') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-check-double me-1"></i>
                                        مراجعة استيراد السير
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @endif
            @if(AdminGuardHasAnyPerm(['data_import_review', 'data_import_approve']))
            <div class="import-panel">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">آخر الملفات المرفوعة من صفحة التقارير</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>نوع البيانات</th>
                                <th>اسم الملف</th>
                                <th>الحجم</th>
                                <th>تاريخ الرفع</th>
                            </tr>
                        </thead>
                        <tbody>
            @forelse ($recentUploads as $upload)
                                <tr>
                                    <td>{{ $templates[$upload['type']]['label'] ?? $upload['type'] }}</td>
                                    <td dir="ltr">{{ $upload['name'] }}</td>
                                    <td>{{ number_format($upload['size'] / 1024, 1) }} KB</td>
                                    <td>{{ \Carbon\Carbon::createFromTimestamp($upload['date'])->format('Y-m-d h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">لا توجد ملفات مرفوعة من هذه الصفحة بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="import-progress-overlay" id="import-progress-overlay" role="dialog" aria-modal="true"
         aria-labelledby="import-progress-title" aria-describedby="import-progress-status">
        <div class="import-progress-dialog p-4 p-lg-5 text-center">
            <div class="mb-3 text-primary"><i class="fas fa-cloud-upload-alt fa-3x"></i></div>
            <h4 id="import-progress-title" class="mb-2">جارٍ رفع ملف الاستيراد</h4>
            <p id="import-progress-status" class="text-muted mb-3" aria-live="polite">يتم تجهيز الملف للرفع...</p>
            <div class="progress mb-2" dir="ltr">
                <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">0%</div>
            </div>
            <div class="small text-muted">يرجى عدم إغلاق الصفحة أو الانتقال إلى شاشة أخرى حتى اكتمال العملية.</div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('data-import-form');
            const submitButton = document.getElementById('data-import-submit');
            const overlay = document.getElementById('import-progress-overlay');
            const progressBar = document.getElementById('import-progress-bar');
            const progressTitle = document.getElementById('import-progress-title');
            const progressStatus = document.getElementById('import-progress-status');
            let importInProgress = false;

            if (!form || !window.XMLHttpRequest || !(new XMLHttpRequest()).upload) {
                return;
            }

            const preventLeaving = function (event) {
                if (!importInProgress) return;
                event.preventDefault();
                event.returnValue = '';
            };

            const setProgress = function (percent) {
                const value = Math.max(0, Math.min(100, Math.round(percent)));
                progressBar.style.width = value + '%';
                progressBar.setAttribute('aria-valuenow', String(value));
                progressBar.textContent = value + '%';
            };

            const unlockPage = function () {
                importInProgress = false;
                submitButton.disabled = false;
                overlay.classList.remove('is-visible');
                document.body.style.overflow = '';
            };

            const showError = function (message) {
                unlockPage();
                const oldAlert = document.getElementById('import-upload-error');
                if (oldAlert) oldAlert.remove();

                const alert = document.createElement('div');
                alert.id = 'import-upload-error';
                alert.className = 'alert alert-danger';
                alert.setAttribute('role', 'alert');
                alert.textContent = message || 'تعذر رفع الملف. يرجى المحاولة مرة أخرى.';
                form.closest('.import-panel').before(alert);
                alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            window.addEventListener('beforeunload', preventLeaving);

            form.addEventListener('submit', function (event) {
                if (!form.checkValidity() || importInProgress) return;

                event.preventDefault();
                importInProgress = true;
                submitButton.disabled = true;
                overlay.classList.add('is-visible');
                document.body.style.overflow = 'hidden';
                progressTitle.textContent = 'جارٍ رفع ملف الاستيراد';
                progressStatus.textContent = 'بدأ رفع الملف...';
                setProgress(0);

                const request = new XMLHttpRequest();
                request.open('POST', form.action, true);
                request.setRequestHeader('Accept', 'application/json');
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                request.upload.addEventListener('progress', function (uploadEvent) {
                    if (!uploadEvent.lengthComputable) {
                        progressStatus.textContent = 'جارٍ رفع الملف...';
                        return;
                    }

                    const percent = (uploadEvent.loaded / uploadEvent.total) * 100;
                    setProgress(percent);
                    progressStatus.textContent = 'تم رفع ' + Math.round(percent) + '% من الملف.';
                });

                request.upload.addEventListener('load', function () {
                    setProgress(100);
                    progressTitle.textContent = 'جارٍ معالجة البيانات';
                    progressStatus.textContent = 'اكتمل نقل الملف، ويتم الآن فحص البيانات دون حفظها...';
                });

                request.addEventListener('load', function () {
                    let response = {};
                    try { response = JSON.parse(request.responseText || '{}'); } catch (error) {}

                    if (request.status >= 200 && request.status < 300 && response.redirect) {
                        progressTitle.textContent = 'اكتملت العملية بنجاح';
                        progressStatus.textContent = 'جارٍ فتح نتيجة الاستيراد...';
                        importInProgress = false;
                        window.location.assign(response.redirect);
                        return;
                    }

                    const validationMessage = response.errors
                        ? Object.values(response.errors).flat().join(' ')
                        : response.message;
                    const statusSuffix = request.status ? ' (HTTP ' + request.status + ')' : '';
                    showError(validationMessage || ('انقطعت معالجة الملف على الخادم' + statusSuffix + '. راجع ملفات المراجعة الأخيرة قبل إعادة الرفع.'));
                });

                request.addEventListener('error', function () {
                    showError('انقطع الاتصال بالخادم أثناء الفحص. راجع ملفات المراجعة الأخيرة قبل إعادة الرفع.');
                });

                request.addEventListener('abort', function () {
                    showError('تم إلغاء رفع الملف قبل اكتماله.');
                });

                request.send(new FormData(form));
            });
        });
    </script>
@endsection

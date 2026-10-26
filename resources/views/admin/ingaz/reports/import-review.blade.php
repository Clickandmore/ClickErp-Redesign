@extends('admin.layout.indexs.index')

@section('page-title', 'مراجعة واعتماد الاستيراد')

@section('content')
@php
    $columnDefinitions = collect($template['columns'] ?? [])->keyBy('key');
    $batchStatusLabels = ['pending' => 'بانتظار المراجعة', 'approved' => 'معتمد', 'partial' => 'معتمد جزئيًا'];
@endphp
<div class="container-fluid" dir="rtl">
    <div class="card">
        <div class="card-header"><h4>مراجعة {{ $template['label'] }} — {{ $batch->original_name }}</h4></div>
        <div class="card-body">
            <a href="{{ route('reports.import.index') }}" class="btn btn-outline-secondary mb-3">العودة ورفع ملف مصحح</a>
            @foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $class)
                @if(session($key))<div class="alert alert-{{ $class }}">{{ session($key) }}</div>@endif
            @endforeach
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <div class="card border mb-3">
                <div class="card-header"><strong>تفاصيل دفعة الاستيراد #{{ $batch->id }}</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-md-2">نوع البيانات</dt><dd class="col-md-4">{{ $template['label'] }}</dd>
                        <dt class="col-md-2">حالة الدفعة</dt><dd class="col-md-4">{{ $batchStatusLabels[$batch->status] ?? $batch->status }}</dd>
                        <dt class="col-md-2">اسم الملف</dt><dd class="col-md-4 text-break">{{ $batch->original_name }}</dd>
                        <dt class="col-md-2">تاريخ الرفع</dt><dd class="col-md-4">{{ $batch->created_at }}</dd>
                        <dt class="col-md-2">معرّف رافع الملف</dt><dd class="col-md-4">{{ $batch->created_by ?? 'غير مسجل' }}</dd>
                        <dt class="col-md-2">معرّف الفرع</dt><dd class="col-md-4">{{ $batch->branch_id ?? 'غير محدد' }}</dd>
                        @if($batch->approved_at)
                            <dt class="col-md-2">تاريخ الاعتماد</dt><dd class="col-md-4">{{ $batch->approved_at }}</dd>
                            <dt class="col-md-2">معرّف المعتمد</dt><dd class="col-md-4">{{ $batch->approved_by ?? 'غير مسجل' }}</dd>
                        @endif
                    </dl>
                    @if(!empty($template['description']))<p class="text-muted mb-0">{{ $template['description'] }}</p>@endif
                    @if(!empty($batch->options['finance_constraints_missing_only']))
                        <p class="mt-2 mb-0">خيار التنفيذ: استيراد القيود المفقودة فقط.</p>
                    @endif
                </div>
            </div>
            <div class="alert alert-info">
                <div class="mb-2"><strong>نسخة احتياطية قبل التنفيذ:</strong> تُحفظ قاعدة البيانات في R2 باسم يتضمن اليوم والوقت قبل اعتماد البيانات. إذا فشل حفظ النسخة يتوقف الاعتماد.</div>
                الرفع يفحص البيانات فقط. لا تُنشأ سجلات أو فواتير أو قيود حتى الاعتماد.
                @if($wholeFile)
                    هذا الملف يحتوي على بيانات مترابطة ويُعتمد كاملًا بعد تصحيح جميع الأخطاء وإعادة رفعه.
                @else
                    تُعتمد الصفوف الصحيحة فقط وتبقى الصفوف الخاطئة دون تنفيذ. صححها ثم ارفعها في ملف جديد.
                @endif
            </div>
            @if(!empty($batch->options['safety_backup']['filename']))
                <div class="alert alert-success">نسخة الأمان قبل آخر تنفيذ: <bdi>{{ $batch->options['safety_backup']['filename'] }}</bdi>. متاحة في لوحة السوبر أدمن ضمن النسخ الاحتياطية.</div>
            @endif
            <div class="alert alert-light border">
                <strong>نطاق الاعتماد:</strong> {{ $counts['valid'] ?? 0 }} صف بانتظار التنفيذ،
                {{ $counts['invalid'] ?? 0 }} صف مرفوض، و{{ $counts['approved'] ?? 0 }} صف سبق اعتماده.
                التصفية وتغيير الصفحة يؤثران على العرض فقط؛ زر الاعتماد يشمل جميع الصفوف الصحيحة في الدفعة.
                <div class="mt-2">راجع القيم والعميل أو العقد أو الحساب المرتبط قبل الاعتماد. نجاح الفحص الأولي لا يؤكد مطابقة البيانات للمصدر، ولا يعني بالضرورة إنشاء سجل جديد؛ قد يُحدَّث سجل قائم حسب نوع الاستيراد.</div>
            </div>
            <details class="mb-3">
                <summary>دليل حقول {{ $template['label'] }} والبيانات المطلوبة</summary>
                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>الحقل</th><th>الإلزام</th><th>القيم المقبولة</th></tr></thead>
                        <tbody>
                            @foreach($columnDefinitions as $column)
                                @if(!str_contains(strtolower($column['key']), 'password') && ($column['key'] !== 'supplier_total_cost_usd' || \App\Support\InvoiceAuthorization::canViewSupplierTotalCost()))
                                    <tr><td>{{ $column['label'] }}</td><td>{{ !empty($column['required']) ? 'مطلوب' : 'اختياري' }}</td><td>{{ implode('، ', $column['valid_values'] ?? []) ?: 'حسب نوع الحقل وضوابط الاستيراد' }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
            <div class="d-flex flex-wrap mb-3" style="gap:12px">
                <a href="{{ route('reports.import.show', $batch) }}" class="btn btn-outline-secondary">الكل {{ $counts->sum() }}</a>
                @foreach(['valid' => 'صحيح', 'invalid' => 'خطأ / مكرر', 'approved' => 'معتمد'] as $state => $label)
                    <a href="{{ route('reports.import.show', [$batch, 'status' => $state]) }}" class="btn btn-outline-{{ $state === 'invalid' ? 'danger' : 'success' }}">{{ $label }} {{ $counts[$state] ?? 0 }}</a>
                @endforeach
            </div>
            @if(!empty($batch->result['errors']))
                <div class="alert alert-danger"><strong>{{ $wholeFile ? 'أخطاء الاعتماد (لم يُحفظ الملف المترابط):' : 'الصفوف التي لم تُعتمد وأسبابها:' }}</strong>
                    <ul>@foreach($batch->result['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if($batch->status === 'approved')
                <div class="alert alert-success">معتمد بتاريخ {{ $batch->approved_at }}. لن يتكرر التنفيذ عند إعادة إرسال الاعتماد.</div>
            @elseif(AdminGuardHasAnyPerm(['data_import_approve']) && ($counts['valid'] ?? 0))
                <form method="POST" action="{{ route('reports.import.approve', $batch) }}" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-success" @disabled(!($counts['valid'] ?? 0) || ($wholeFile && ($counts['invalid'] ?? 0)))>اعتماد {{ $counts['valid'] ?? 0 }} صف صحيح</button>
                </form>
            @endif
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>صف المصدر</th><th>النتيجة</th><th>السبب</th><th>الربط في النظام</th><th>البيانات المرفوعة</th></tr></thead>
                    <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->source_row }}</td>
                            <td><span class="badge bg-{{ $row->status === 'invalid' ? 'danger' : 'success' }}">{{ ['valid' => 'صحيح للفحص الأولي', 'invalid' => 'خطأ / مكرر', 'approved' => 'معتمد'][$row->status] }}</span></td>
                            <td>@forelse($row->issues ?? [] as $issue)<div class="text-danger mb-1">{{ $issue }}</div>@empty<span class="text-muted">لا توجد أخطاء مسجلة{{ $row->status === 'valid' ? ' في الفحص الأولي' : '' }}</span>@endforelse</td>
                            <td>
                                @forelse($row->resolved_references ?? [] as $reference)
                                    <div><strong>{{ $reference['label'] }}</strong>: {{ $reference['name'] ?? '' }}
                                        @if(filled($reference['number'] ?? null)) — {{ $reference['number'] }} @endif
                                        <small class="text-muted">(ID: {{ $reference['id'] }})</small>
                                    </div>
                                @empty
                                    <span class="text-muted">{{ $row->status === 'invalid' ? 'راجع سبب الخطأ' : 'سجل جديد أو نوع لا يتطلب ربطاً' }}</span>
                                @endforelse
                            </td>
                            <td style="min-width:360px"><details open><summary>تفاصيل بيانات الصف {{ $row->source_row }}</summary>
                                <table class="table table-sm table-bordered mb-0 mt-2">
                                    <thead><tr><th>الحقل</th><th>القيمة المرفوعة</th></tr></thead><tbody>
                                @foreach($row->payload ?? [] as $key => $value)
                                    @if(!str_contains(strtolower($key), 'password') && ($key !== 'supplier_total_cost_usd' || \App\Support\InvoiceAuthorization::canViewSupplierTotalCost()))
                                        <tr>
                                            <th style="width:40%">{{ $columnDefinitions->get($key)['label'] ?? $key }}
                                                @if(!empty($columnDefinitions->get($key)['required']))<small class="text-muted d-block">مطلوب</small>@endif
                                            </th>
                                            <td class="text-break" style="white-space:pre-wrap">{{ $value === null || $value === '' ? 'غير مدخل' : (is_bool($value) ? ($value ? 'نعم' : 'لا') : (is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE))) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody></table>
                            </details></td>
                        </tr>
                    @endforeach
                    @if($rows->isEmpty())<tr><td colspan="5" class="text-center text-muted">لا توجد صفوف مطابقة للتصفية المحددة.</td></tr>@endif
                    </tbody>
                </table>
            </div>
            <p class="text-muted">عرض {{ $rows->firstItem() ?? 0 }} إلى {{ $rows->lastItem() ?? 0 }} من {{ $rows->total() }} صف مطابق للتصفية.</p>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection

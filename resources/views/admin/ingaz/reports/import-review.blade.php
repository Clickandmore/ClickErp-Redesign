@extends('admin.layout.indexs.index')

@section('page-title', 'مراجعة واعتماد الاستيراد')

@section('content')
<div class="container-fluid" dir="rtl">
    <div class="card">
        <div class="card-header"><h4>مراجعة {{ $template['label'] }} — {{ $batch->original_name }}</h4></div>
        <div class="card-body">
            <a href="{{ route('reports.import.index') }}" class="btn btn-outline-secondary mb-3">العودة ورفع ملف مصحح</a>
            @foreach (['success' => 'success', 'error' => 'danger'] as $key => $class)
                @if(session($key))<div class="alert alert-{{ $class }}">{{ session($key) }}</div>@endif
            @endforeach
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <div class="alert alert-info">
                الرفع يفحص البيانات فقط. لا تُنشأ سجلات أو فواتير أو قيود حتى الاعتماد.
                @if($wholeFile)
                    هذا الملف يحتوي على بيانات مترابطة ويُعتمد كاملًا بعد تصحيح جميع الأخطاء وإعادة رفعه.
                @else
                    تُعتمد الصفوف الصحيحة فقط وتبقى الصفوف الخاطئة دون تنفيذ. صححها ثم ارفعها في ملف جديد.
                @endif
            </div>
            <div class="d-flex flex-wrap mb-3" style="gap:12px">
                <a href="{{ route('reports.import.show', $batch) }}" class="btn btn-outline-secondary">الكل {{ $counts->sum() }}</a>
                @foreach(['valid' => 'صحيح', 'invalid' => 'خطأ / مكرر', 'approved' => 'معتمد'] as $state => $label)
                    <a href="{{ route('reports.import.show', [$batch, 'status' => $state]) }}" class="btn btn-outline-{{ $state === 'invalid' ? 'danger' : 'success' }}">{{ $label }} {{ $counts[$state] ?? 0 }}</a>
                @endforeach
            </div>
            @if(!empty($batch->result['errors']))
                <div class="alert alert-danger"><strong>أخطاء الاعتماد (تم التراجع عن المحاولة):</strong>
                    <ul>@foreach($batch->result['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if($batch->status === 'approved')
                <div class="alert alert-success">معتمد بتاريخ {{ $batch->approved_at }}. لن يتكرر التنفيذ عند إعادة إرسال الاعتماد.</div>
            @elseif(AdminGuardHasAnyPerm(['data_import_approve']))
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
                            <td>@foreach($row->issues as $issue)<div>{{ $issue }}</div>@endforeach</td>
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
                            <td><details><summary>عرض البيانات</summary><dl>
                                @foreach($row->payload as $key => $value)
                                    @if(!str_contains($key, 'password') && ($key !== 'supplier_total_cost_usd' || \App\Support\InvoiceAuthorization::canViewSupplierTotalCost()))
                                        <dt>{{ collect($template['columns'])->firstWhere('key', $key)['label'] ?? $key }}</dt><dd>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</dd>
                                    @endif
                                @endforeach
                            </dl></details></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection

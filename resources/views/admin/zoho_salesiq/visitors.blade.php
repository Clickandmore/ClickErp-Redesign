@extends('admin.layout.indexs.index')

@section('page-title', 'زوار Zoho SalesIQ')
@section('title', 'زوار Zoho SalesIQ')

@push('style')
    <style>
        .zoho-panel {
            border-radius: 8px;
            border: 1px solid #e7edf3;
            box-shadow: 0 8px 22px rgba(21, 38, 60, 0.06);
        }

        .zoho-kpi {
            border-radius: 8px;
            border: 1px solid #e7edf3;
            background: #fff;
        }

        .zoho-tabs .btn {
            border-radius: 8px;
        }

        .zoho-raw {
            max-height: 260px;
            overflow: auto;
            direction: ltr;
            text-align: left;
            font-size: 12px;
            background: #111827;
            color: #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">زوار Zoho SalesIQ</h4>
                <div class="text-muted">عرض مباشر للزوار القادمين من حساب SalesIQ المرتبط بالموقع.</div>
            </div>
            <div class="zoho-tabs d-flex gap-2">
                <a class="btn btn-primary" href="{{ route('admin.zoho-salesiq.visitors') }}">
                    <i class="fa fa-eye me-1"></i> الزوار
                </a>
                <a class="btn btn-outline-primary" href="{{ route('admin.zoho-salesiq.conversations.index') }}">
                    <i class="fa fa-comments me-1"></i> المحادثات
                </a>
            </div>
        </div>

        @if (!$isConfigured)
            <div class="alert alert-warning zoho-panel">
                <strong>بيانات الربط غير مكتملة.</strong>
                أضف المتغيرات التالية في ملف <code>.env</code> ثم نفذ <code>php artisan config:clear</code>:
                <div class="mt-2">
                    @foreach ($missingConfig as $key)
                        <code class="me-2">{{ $key }}</code>
                    @endforeach
                </div>
            </div>
        @elseif (!$result['ok'])
            @if (str_contains(($result['error'] ?? ''), 'Invalid OAuthScope'))
                <div class="alert alert-danger zoho-panel">
                    <strong>⚠️ مشكلة في صلاحيات OAuth</strong>
                    <div class="mt-2">
                        صلاحيات الوصول الحالية محدودة. يجب:
                        <ol class="mt-2">
                            <li>تفعيل <strong>SalesIQ</strong> في Zoho CRM Account</li>
                            <li>التأكد من أن المستخدم لديه صلاحيات CRM Module</li>
                            <li>إنشاء OAuth Code جديد مع scopes صحيحة:
                                <code>ZohoCRM.modules.all</code>
                            </li>
                        </ol>
                        <a href="https://api-console.zoho.sa/" target="_blank" class="btn btn-sm btn-primary mt-2">
                            الذهاب إلى Zoho API Console
                        </a>
                    </div>
                </div>
            @else
                <div class="alert alert-danger zoho-panel">
                    تعذر جلب الزوار من Zoho SalesIQ.
                    @if (!empty($result['status_code']))
                        <span class="ms-2">HTTP {{ $result['status_code'] }}</span>
                    @endif
                    <div class="mt-1">{{ $result['error'] ?? '' }}</div>
                </div>
            @endif
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="zoho-kpi p-3">
                    <small class="text-muted d-block mb-1">عدد النتائج المعروضة</small>
                    <h3 class="mb-0">{{ number_format($visitors->count()) }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="zoho-kpi p-3">
                    <small class="text-muted d-block mb-1">Portal</small>
                    <h5 class="mb-0">{{ config('services.zoho_salesiq.screen_name') ?: '-' }}</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="zoho-kpi p-3">
                    <small class="text-muted d-block mb-1">آخر تحديث</small>
                    <h5 class="mb-0">{{ now()->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</h5>
                </div>
            </div>
        </div>

        <div class="card zoho-panel mb-4">
            <div class="card-body">
                <form method="get" action="{{ route('admin.zoho-salesiq.visitors') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">من وقت</label>
                        <input type="datetime-local" name="from" class="form-control" value="{{ $filters['from'] }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">عدد النتائج</label>
                        <input type="number" name="limit" min="1" max="100" class="form-control" value="{{ $filters['limit'] }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fa fa-filter me-1"></i> عرض
                        </button>
                        <a href="{{ route('admin.zoho-salesiq.visitors') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-refresh"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card zoho-panel">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الزائر</th>
                                <th>التواصل</th>
                                <th>الموقع</th>
                                <th>الحالة</th>
                                <th>الصفحة / المصدر</th>
                                <th>آخر ظهور</th>
                                <th style="width: 120px;">تفاصيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($visitors as $visitor)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $visitor['name'] }}</div>
                                        <small class="text-muted">{{ $visitor['id'] }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $visitor['email'] }}</div>
                                        <small class="text-muted">{{ $visitor['phone'] }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $visitor['location'] }}</div>
                                        <small class="text-muted">{{ $visitor['ip'] }}</small>
                                    </td>
                                    <td><span class="badge bg-info text-dark">{{ $visitor['status'] }}</span></td>
                                    <td>
                                        <div>{{ $visitor['page'] }}</div>
                                        <small class="text-muted">{{ $visitor['source'] }}</small>
                                    </td>
                                    <td>{{ $visitor['last_seen'] }}</td>
                                    <td>
                                        <details>
                                            <summary class="btn btn-sm btn-outline-secondary">Raw</summary>
                                            <pre class="zoho-raw mt-2">{{ $visitor['raw'] }}</pre>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">لا توجد بيانات زوار مطابقة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (data_get($result, 'meta.next_key'))
            <div class="mt-3">
                <a class="btn btn-outline-primary" href="{{ route('admin.zoho-salesiq.visitors', array_merge(request()->except('next_key'), ['next_key' => data_get($result, 'meta.next_key')])) }}">
                    عرض المزيد
                </a>
            </div>
        @endif
    </div>
@endsection

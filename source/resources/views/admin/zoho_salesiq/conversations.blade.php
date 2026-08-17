@extends('admin.layout.indexs.index')

@section('page-title', 'محادثات Zoho SalesIQ')
@section('title', 'محادثات Zoho SalesIQ')

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
                <h4 class="mb-1">محادثات Zoho SalesIQ</h4>
                <div class="text-muted">قائمة المحادثات التي تم سحبها من SalesIQ حسب الفترة المحددة.</div>
            </div>
            <div class="zoho-tabs d-flex gap-2">
                <a class="btn btn-outline-primary" href="{{ route('admin.zoho-salesiq.visitors', $branchQuery ?? []) }}">
                    <i class="fa fa-eye me-1"></i> الزوار
                </a>
                <a class="btn btn-primary" href="{{ route('admin.zoho-salesiq.conversations.index', $branchQuery ?? []) }}">
                    <i class="fa fa-comments me-1"></i> المحادثات
                </a>
            </div>
        </div>

        @include('admin.zoho_salesiq._branch_selector')

        @if (!$isConfigured)
            <div class="alert alert-warning zoho-panel">
                <strong>بيانات الربط غير مكتملة.</strong>
                أكمل إعدادات Zoho SalesIQ للشركة/الفرع الحالي من شاشة الفروع، أو أضف القيم العامة كاحتياطي:
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
                    تعذر جلب المحادثات من Zoho SalesIQ.
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
                    <small class="text-muted d-block mb-1">عدد المحادثات المعروضة</small>
                    <h3 class="mb-0">{{ number_format($conversations->count()) }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="zoho-kpi p-3">
                    <small class="text-muted d-block mb-1">Portal</small>
                    <h5 class="mb-0">{{ ($zohoConnection['screen_name'] ?? '') ?: '-' }}</h5>
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
                <form method="get" action="{{ route('admin.zoho-salesiq.conversations.index') }}" class="row g-3 align-items-end">
                    @if (!empty($branchQuery['branch_id']))
                        <input type="hidden" name="branch_id" value="{{ $branchQuery['branch_id'] }}">
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">من</label>
                        <input type="datetime-local" name="from" class="form-control" value="{{ $filters['from'] }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">إلى</label>
                        <input type="datetime-local" name="to" class="form-control" value="{{ $filters['to'] }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">عدد النتائج</label>
                        <input type="number" name="limit" min="1" max="200" class="form-control" value="{{ $filters['limit'] }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fa fa-filter me-1"></i> عرض
                        </button>
                        <a href="{{ route('admin.zoho-salesiq.conversations.index', $branchQuery ?? []) }}" class="btn btn-outline-secondary">
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
                                <th>المحادثة</th>
                                <th>الزائر</th>
                                <th>الموظف / القسم</th>
                                <th>آخر رسالة</th>
                                <th>التوقيت</th>
                                <th style="width: 170px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($conversations as $conversation)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $conversation['id'] }}</div>
                                        <span class="badge bg-info text-dark">{{ $conversation['status'] }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $conversation['visitor'] }}</div>
                                        <small class="text-muted d-block">{{ $conversation['email'] }}</small>
                                        <small class="text-muted">{{ $conversation['phone'] }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $conversation['operator'] }}</div>
                                        <small class="text-muted">{{ $conversation['department'] }}</small>
                                    </td>
                                    <td style="min-width: 280px;">{{ $conversation['last_message'] }}</td>
                                    <td>
                                        <div>{{ $conversation['created_at'] }}</div>
                                        <small class="text-muted">إغلاق: {{ $conversation['closed_at'] }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            @if ($conversation['id'] !== '-')
                                                <a class="btn btn-sm btn-outline-primary"
                                                    href="{{ route('admin.zoho-salesiq.conversations.show', array_merge(['conversationId' => $conversation['id']], $branchQuery ?? [])) }}">
                                                    فتح
                                                </a>
                                            @endif
                                            <details>
                                                <summary class="btn btn-sm btn-outline-secondary">Raw</summary>
                                                <pre class="zoho-raw mt-2">{{ $conversation['raw'] }}</pre>
                                            </details>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">لا توجد محادثات مطابقة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

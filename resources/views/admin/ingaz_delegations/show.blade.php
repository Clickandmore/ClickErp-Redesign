@extends('admin.layout.indexs.index')

@section('page-title')
    تفاصيل تفويض الإنجاز
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="mb-1">تفاصيل تفويض الإنجاز #{{ $delegation->id }}</h3>
                    <p class="text-muted mb-0">عرض كامل لجميع البيانات المرتبطة بالتفويض.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('ingaz-delegations.edit', $delegation) }}" class="btn btn-primary">تعديل</a>
                    <a href="{{ route('ingaz-delegations.index') }}" class="btn btn-outline-secondary">رجوع</a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light"><h5 class="mb-0">البيانات الأساسية</h5></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th width="35%">التاريخ الميلادي</th><td>{{ optional($delegation->delegation_date_gregorian)->format('Y-m-d') }}</td></tr>
                                <tr><th>التاريخ الهجري</th><td>{{ $delegation->delegation_date_hijri }}</td></tr>
                                <tr><th>الجنسية</th><td>{{ $delegation->nationality?->sub_title ?? $delegation->nationality?->title ?? '-' }}</td></tr>
                                <tr><th>المهنة</th><td>{{ $delegation->profession?->title ?? '-' }}</td></tr>
                                <tr><th>المسوق</th><td>{{ $delegation->marketer?->name ?? '-' }}</td></tr>
                                <tr><th>رقم إنجاز</th><td>{{ $delegation->ingaz_number }}</td></tr>
                                <tr><th>رقم عقد مساند</th><td>{{ $delegation->msaned_contract_number }}</td></tr>
                                <tr><th>نوع العميل</th><td>{{ $delegation->client_source_type === 'existing_in_program' ? 'موجود في البرنامج' : 'عميل منفصل' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light"><h5 class="mb-0">بيانات العميل</h5></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th width="35%">اسم العميل</th><td>{{ $delegation->user?->name ?? '-' }}</td></tr>
                                <tr><th>رقم الهوية</th><td>{{ $delegation->user?->national_num ?: '-' }}</td></tr>
                                <tr><th>الجوال</th><td>{{ trim(($delegation->user?->phone_code ?: '') . ' ' . ($delegation->user?->phone ?: '-')) }}</td></tr>
                                <tr><th>العنوان</th><td>{{ $delegation->user?->address ?: '-' }}</td></tr>
                                <tr><th>رقم العميل</th><td>{{ $delegation->user?->id ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light"><h5 class="mb-0">بيانات التأشيرة والعاملة</h5></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th width="35%">نوع التأشيرة</th><td>{{ $visaTypeOptions[$delegation->visa_type] ?? $delegation->visa_type }}</td></tr>
                                <tr><th>اسم العاملة</th><td>{{ $delegation->maid?->name ?? $delegation->worker_name }}</td></tr>
                                <tr><th>رقم الجواز</th><td>{{ $delegation->maid?->passport_num ?? $delegation->worker_passport_number }}</td></tr>
                                <tr><th>رقم العاملة</th><td>{{ $delegation->maid?->id ?? '-' }}</td></tr>
                                <tr><th>تاريخ إصدار الجواز</th><td>{{ optional($delegation->worker_passport_issue_date)->format('Y-m-d') ?: '-' }}</td></tr>
                                <tr><th>رقم التأشيرة</th><td>{{ $delegation->visa_number }}</td></tr>
                                <tr><th>تاريخ التأشيرة</th><td>{{ optional($delegation->visa_date)->format('Y-m-d') ?: '-' }}</td></tr>
                                <tr><th>الديانة</th><td>{{ $religionOptions[$delegation->worker_religion] ?? '-' }}</td></tr>
                                <tr><th>الخبرة</th><td>{{ $experienceOptions[$delegation->worker_experience] ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light"><h5 class="mb-0">المكتب الخارجي</h5></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th width="35%">اسم المكتب</th><td>{{ $delegation->external_office_name }}</td></tr>
                                <tr><th>رقم الترخيص</th><td>{{ $delegation->external_office_license_number }}</td></tr>
                                <tr><th>العنوان</th><td>{{ $delegation->external_office_address }}</td></tr>
                                <tr><th>الهاتف</th><td>{{ $delegation->external_office_phone }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light"><h5 class="mb-0">الملاحظات والمرفقات</h5></div>
                        <div class="card-body">
                            <div class="mb-4">
                                <strong class="d-block mb-2">ملاحظة</strong>
                                <div class="p-3 bg-light rounded">{{ $delegation->notes ?: 'لا توجد ملاحظات.' }}</div>
                            </div>

                            <strong class="d-block mb-2">المرفقات</strong>
                            @if (!empty($delegation->attachments))
                                <div class="list-group">
                                    @foreach ($delegation->attachments as $attachment)
                                        @php
                                            $attachmentPath = is_array($attachment) ? ($attachment['path'] ?? '') : $attachment;
                                            $attachmentName = is_array($attachment) ? ($attachment['name'] ?? basename($attachmentPath)) : basename($attachment);
                                        @endphp
                                        <a href="{{ Storage::url($attachmentPath) }}" target="_blank"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <span>{{ $attachmentName }}</span>
                                            <span class="btn btn-sm btn-outline-primary">فتح</span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted">لا توجد مرفقات.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

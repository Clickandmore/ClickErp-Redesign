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
                    @if ($canEditDelegation)
                        <a href="{{ route('ingaz-delegations.edit', $delegation) }}" class="btn btn-primary">تعديل</a>
                    @endif
                    @if ($delegation->invoice)
                        @if ($canViewInvoices)
                            <a href="{{ route('invoices.show', $delegation->invoice->id) }}" class="btn btn-success">عرض الفاتورة</a>
                        @endif
                        @if ($canCreateCreditNotes)
                            <a href="{{ route('invoices.credit-notes.create', $delegation->invoice->id) }}" class="btn btn-danger">إنشاء إشعار دائن</a>
                        @endif
                        @if ($delegation->invoice->musanedTransferItem?->transfer && $canViewMusanedTransfers)
                            <a href="{{ route('musaned-transfers.show', $delegation->invoice->musanedTransferItem->transfer->id) }}" class="btn btn-info">عرض تحويل مساند</a>
                        @endif
                        @if ($delegation->invoice->musanedTransferItem?->transfer && $canDeleteMusanedTransfers)
                            <a href="{{ route('musaned-transfers.show', $delegation->invoice->musanedTransferItem->transfer->id) }}#deleteMusanedTransfer"
                                class="btn btn-outline-danger">حذف تحويل مساند</a>
                        @endif
                        @if (!$delegation->invoice->musanedTransferItem?->transfer && $canCreateMusanedTransfers)
                            <a href="{{ route('musaned-transfers.create', ['ingaz_delegation_ids' => [$delegation->id]]) }}" class="btn btn-info">تسجيل تحويل مساند</a>
                        @endif
                    @elseif ($canCreateInvoices)
                        <a href="{{ route('invoices.create', ['ingaz_delegation_id' => $delegation->id]) }}" class="btn btn-success">إضافة فاتورة</a>
                    @endif
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
                                <tr><th>التاريخ الهجري</th><td>{{ $delegation->delegation_date_hijri ?: '-' }}</td></tr>
                                <tr><th>تاريخ الإضافة</th><td>{{ optional($delegation->created_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
                                <tr><th>آخر تعديل</th><td>{{ optional($delegation->updated_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
                                <tr><th>الدولة</th><td>{{ $delegation->country?->title ?? $delegation->country?->name_ar ?? $delegation->nationality?->sub_title ?? $delegation->nationality?->title ?? '-' }}</td></tr>
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
                                <tr>
                                    <th>الجوال</th>
                                    <td>
                                        @php
                                            $phone = $delegation->user?->phone;
                                        @endphp
                                        {{ $phone && !str_starts_with($phone, 'ingaz-delegation-') ? trim(($delegation->user?->phone_code ?: '') . ' ' . $phone) : '-' }}
                                    </td>
                                </tr>
                                <tr><th>العنوان</th><td>{{ $delegation->user?->address ?: '-' }}</td></tr>
                                <tr><th>رقم العميل</th><td>{{ $delegation->user?->id ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light"><h5 class="mb-0">بيانات التأشيرة والعامل</h5></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr><th width="35%">نوع التأشيرة</th><td>{{ $visaTypeOptions[$delegation->visa_type] ?? $delegation->visa_type }}</td></tr>
                                <tr><th>اسم العامل</th><td>{{ $delegation->maid?->name ?? $delegation->worker_name ?: '-' }}</td></tr>
                                <tr><th>رقم الجواز</th><td>{{ $delegation->maid?->passport_num ?? $delegation->worker_passport_number ?: '-' }}</td></tr>
                                <tr><th>رقم العامل</th><td>{{ $delegation->maid?->id ?? '-' }}</td></tr>
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
                                <tr><th width="35%">اسم المكتب</th><td>{{ $delegation->external_office_name ?: '-' }}</td></tr>
                                <tr><th>رقم الترخيص</th><td>{{ $delegation->external_office_license_number ?: '-' }}</td></tr>
                                <tr><th>العنوان</th><td>{{ $delegation->external_office_address ?: '-' }}</td></tr>
                                <tr><th>الهاتف</th><td>{{ $delegation->external_office_phone ?: '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                @if ($delegation->invoice && ($canViewCreditNotes || $canCreateCreditNotes))
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-file-invoice-dollar text-danger me-1"></i>إشعارات الدائن</h5>
                                @if ($canCreateCreditNotes)
                                    <a href="{{ route('invoices.credit-notes.create', $delegation->invoice->id) }}" class="btn btn-sm btn-outline-danger">
                                        إنشاء إشعار دائن
                                    </a>
                                @endif
                            </div>
                            <div class="card-body">
                                @if ($canViewCreditNotes && $delegation->invoice->recruitmentCreditNotes->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr><th>رقم الإشعار</th><th>التاريخ</th><th>السبب</th><th>الإجمالي</th><th>المستندات</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($delegation->invoice->recruitmentCreditNotes as $creditNote)
                                                    <tr>
                                                        <td>#{{ $creditNote->id }}</td>
                                                        <td>{{ optional($creditNote->issue_date)->format('Y-m-d') }}</td>
                                                        <td>{{ $creditNote->reason }}</td>
                                                        <td>{{ number_format((float) $creditNote->total, 2) }} ريال</td>
                                                        <td>
                                                            <a href="{{ route('recruitment-credit-notes.download', ['id' => $creditNote->id, 'autoprint' => 1]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger">طباعة</a>
                                                            @if ($creditNote->constraint_id)
                                                                <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $creditNote->constraint_id]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">القيد</a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @elseif ($canViewCreditNotes)
                                    <div class="text-muted">لا توجد إشعارات دائنة مرتبطة بهذا التفويض.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

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
                                        <a href="{{ showFile($attachmentPath) }}" target="_blank"
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

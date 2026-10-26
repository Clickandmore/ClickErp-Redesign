@extends('admin.layout.indexs.index')
@section('style')
    <style>
        .order-show-page {
            --order-border: #e8edf5;
            --order-muted: #6b7280;
            --order-ink: #182033;
            color: var(--order-ink);
        }

        .order-info-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid var(--order-border);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-info-header {
            background: #f8fafc;
            padding: 14px 18px;
            border-bottom: 1px solid var(--order-border);
            border-radius: 8px 8px 0 0;
        }

        .order-info-header h5 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }

        .order-info-body {
            padding: 18px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            padding: 11px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--order-muted);
            min-width: 145px;
        }

        .info-value {
            color: var(--order-ink);
            text-align: right;
            word-break: break-word;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-new {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-started {
            background: #cce5ff;
            color: #004085;
        }

        .status-done {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-refused {
            background: #f8d7da;
            color: #721c24;
        }

        .status-under_process {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-contracted {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-contracted_without_contract {
            background: #fff3cd;
            color: #856404;
        }

        .maid-photo {
            width: 118px;
            height: 138px;
            object-fit: cover;
            border-radius: 8px;
            border: 3px solid #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .file-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .progress-section {
            background: linear-gradient(135deg, #172033 0%, #2f4f63 100%);
            color: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        }

        .refuse-reason {
            background-color: #0000003b;
            padding: 10px 5px;
            border-radius: 6px;
            margin: 8px 0 0 0;
        }

        .refuse-reason h5 {
            border-bottom: 1px solid;
        }

        .refuse-reason p {
            color: #d1d1d1;
            font-size: 14px;
        }

        .order-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .order-summary-item {
            background: #fff;
            border: 1px solid var(--order-border);
            border-radius: 8px;
            padding: 14px 16px;
        }

        .order-summary-item span {
            display: block;
            color: var(--order-muted);
            font-size: 12px;
            margin-bottom: 6px;
        }

        .order-summary-item strong {
            color: var(--order-ink);
            font-size: 15px;
        }

        .order-note-item {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 8px;
            padding: 14px;
        }

        .order-note-item + .order-note-item {
            margin-top: 10px;
        }

        .order-note-meta {
            color: var(--order-muted);
            font-size: 12px;
            margin-bottom: 7px;
        }

        .order-note-text {
            white-space: pre-wrap;
            word-break: break-word;
        }

        @media (max-width: 991.98px) {
            .order-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .order-summary-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                display: block;
            }

            .info-value {
                display: block;
                margin-top: 4px;
                text-align: right;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.order_details') }} - #{{ $data->order_code }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
        @php
            $isCompletedOrder = in_array($data->status, [
                \App\Enums\ingaz\OrderEnum::Completed->value,
                \App\Enums\ingaz\OrderEnum::Done->value,
            ], true);
        @endphp

        @php
            $maidRawPath = $data->maid?->image ?: ($data->maid?->cv_preview ?: $data->maid?->cv_file);
            $maidPhoto = $maidRawPath ? showFile($maidRawPath) : asset('admin/assets/img/avatar.png');
        @endphp

        <div class="order-show-page">
        <!-- Back Button -->
        <div class="mb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ helperTrans('ingaz.back_to_orders') }}
            </a>
            <a href="{{ route('return_requests.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-ban"></i> {{ helperTrans('ingaz.cancelled_orders') }}
            </a>
        </div>

        <!-- Order Progress Section -->
        <div class="progress-section">
            <h4><i class="fa fa-info-circle"></i> {{ helperTrans('ingaz.order_status') }}</h4>
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="mb-1">{{ getEnumValue('orders_statuses', $data->status) }}</h5>
                    <small>{{ helperTrans('ingaz.order_created_at') }}:
                        {{ $data->created_at->format('Y-m-d H:i:s') }}</small>
                </div>
                <div class="status-badge status-{{ $data->status }}">
                    {{ getEnumValue('orders_statuses', $data->status) }}
                </div>
            </div>
            @if ($data->refuse_reason)
                <div class="refuse-reason">
                    <h5>{{ helperTrans('ingaz.reason') }} : </h5>
                    <p>
                        {{ $data->refuse_reason }}
                    </p>
                </div>
            @endif
        </div>

        <div class="order-summary-grid">
            <div class="order-summary-item">
                <span>{{ helperTrans('ingaz.order_code') }}</span>
                <strong>#{{ $data->order_code }}</strong>
            </div>
            <div class="order-summary-item">
                <span>{{ helperTrans('ingaz.client') }}</span>
                <strong>{{ $data->user?->name ?? '-' }}</strong>
            </div>
            <div class="order-summary-item">
                <span>{{ helperTrans('ingaz.maid_name') }}</span>
                <strong>{{ $data->maid?->name ?? '-' }}</strong>
            </div>
            <div class="order-summary-item">
                <span>{{ helperTrans('ingaz.responsible_employee') }}</span>
                <strong>{{ $data->resolveResponsibleAdmin()?->name ?? '-' }}</strong>
            </div>
        </div>

        <div class="row">
            <!-- Order Information -->
            <div class="col-md-6">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-shopping-cart"></i> {{ helperTrans('ingaz.order_information') }}</h5>
                    </div>
                    <div class="order-info-body">
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.order_code') }}:</span>
                            <span class="info-value">#{{ $data->order_code }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.job_type') }}:</span>
                            <span class="info-value">{{ $data->jobType?->title ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.payment_method') }}:</span>
                            <span class="info-value">{{ ucfirst($data->payment_method) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.order_date') }}:</span>
                            <span class="info-value">{{ $data->created_at->format('Y-m-d H:i:s') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.last_updated') }}:</span>
                            <span class="info-value">{{ $data->updated_at->format('Y-m-d H:i:s') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Information -->
            <div class="col-md-6">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-user"></i> {{ helperTrans('ingaz.client_information') }}</h5>
                    </div>
                    <div class="order-info-body">
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.client_name') }}:</span>
                            <span class="info-value">{{ $data->user?->name ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.phone') }}:</span>
                            <span class="info-value">{{ $data->user?->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.nationality') }}:</span>
                            <span class="info-value">{{ $data->user?->nationality?->title ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ helperTrans('ingaz.email') }}:</span>
                            <span class="info-value">{{ $data->user?->email ?? 'N/A' }}</span>
                        </div>
                        @if ($data->details && $data->details->address)
                            <div class="info-row">
                                <span class="info-label">{{ helperTrans('ingaz.address') }}:</span>
                                <span class="info-value">{{ $data->details->address->address ?? 'N/A' }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Maid Information -->
        <div class="row">
            <div class="col-12">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-user-tie"></i> {{ helperTrans('ingaz.maid_information') }}</h5>
                    </div>
                    <div class="order-info-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img src="{{ $maidPhoto }}" alt="Maid Photo"
                                    class="maid-photo mb-3">
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.maid_name') }}:</span>
                                            <span class="info-value">{{ $data->maid?->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.nationality') }}:</span>
                                            <span
                                                class="info-value">{{ $data->maid?->nationality?->sub_title ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.religion') }}:</span>
                                            <span class="info-value">{{ $data->maid?->religion?->title ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.passport_num') }}:</span>
                                            <span class="info-value">{{ $data->maid?->passport_num ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.age') }}:</span>
                                            <span class="info-value">{{ $data->maid?->age ?? 'N/A' }}
                                                {{ helperTrans('ingaz.years') }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.salary') }}:</span>
                                            <span class="info-value">{{ $data->maid?->salary ?? 'N/A' }}
                                                {{ helperTrans('ingaz.riyal') }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.recruitment_price') }}:</span>
                                            <span class="info-value">
                                                {{ optional($data->maid)->resolved_price !== null ? number_format($data->maid->resolved_price, 2) : 'N/A' }}
                                                {{ helperTrans('ingaz.riyal') }}
                                            </span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">{{ helperTrans('ingaz.job_titles') }}:</span>
                                            <span
                                                class="info-value">{{ $data->maid?->jobTitles?->pluck('title')?->implode(', ') ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details & Responsible Employee -->
        <div class="row">
            <div class="col-md-6">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-info-circle"></i> {{ helperTrans('ingaz.order_details') }}</h5>
                    </div>
                    <div class="order-info-body">
                        @if ($data->details)
                            <div class="info-row">
                                <span class="info-label">{{ helperTrans('ingaz.responsible_employee') }}:</span>
                                <span class="info-value">{{ $data->resolveResponsibleAdmin()?->name ?? 'N/A' }}</span>
                            </div>
                            @if ($data->details->package)
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.package') }}:</span>
                                    <span class="info-value">{{ $data->details->package->title ?? 'N/A' }}</span>
                                </div>
                            @endif
                            @if ($data->details->address)
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.name') }}:</span>
                                    <span class="info-value">{{ $data->details->address->name ?? 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.delivery_address') }}:</span>
                                    <span class="info-value">{{ $data->details->address->address ?? 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.phone_address') }}:</span>
                                    <span class="info-value">{{ $data->details->address->phone ?? 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.additional_notes') }}:</span>
                                    <span class="info-value">{{ $data->details->address->notes ?? 'N/A' }}</span>
                                </div>
                            @endif
                        @else
                            <p class="text-muted">{{ helperTrans('ingaz.no_details_available') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contract Information -->
            <div class="col-md-6">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-file-contract"></i> {{ helperTrans('ingaz.contract_information') }}</h5>
                    </div>
                    <div class="order-info-body">
                        @if ($data->contracts->isNotEmpty())
                            @foreach ($data->contracts as $contract)
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.contract_number') }}:</span>
                                    <span class="info-value">#{{ $contract->id }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.contract_status') }}:</span>
                                    <span class="info-value">
                                        <span class="status-badge status-{{ $contract->status }}">
                                            {{ getEnumValue('contract_statuses', $contract->status) }}
                                        </span>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ helperTrans('ingaz.contract_date') }}:</span>
                                    <span class="info-value">{{ $contract->created_at->format('Y-m-d') }}</span>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">{{ helperTrans('ingaz.no_contract_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Notes -->
        @if (($clientChanges ?? collect())->isNotEmpty())
            <div class="order-info-card mb-4" id="order-client-history">
                <div class="order-info-header"><h5><i class="fa fa-history"></i> سجل تغيير العميل / الكفيل</h5></div>
                <div class="order-info-body">
                    @foreach ($clientChanges as $change)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                                <span>بواسطة: <strong>{{ $change['actor'] }}</strong></span>
                                <span>{{ $change['date']->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="row">
                                @foreach (['old' => 'العميل / الكفيل السابق', 'new' => 'العميل / الكفيل الجديد'] as $side => $label)
                                    <div class="col-md-6 mb-3">
                                        <h6>{{ $label }}</h6>
                                        <div>الاسم: <strong>{{ $change[$side]['name'] }}</strong> <span class="text-muted">#{{ $change[$side]['id'] }}</span></div>
                                        <div>الجوال: <span dir="ltr">{{ $change[$side]['phone'] }}</span></div>
                                        <div>الهوية: {{ $change[$side]['identity'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div>سبب التغيير: {{ $change['reason'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="row" id="order-notes">
            <div class="col-12">
                <div class="order-info-card">
                    <div class="order-info-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-comment-alt"></i> ملاحظات الطلب</h5>
                        <span class="badge bg-warning text-dark">{{ $data->notes->count() }}</span>
                    </div>
                    <div class="order-info-body">
                        @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT))
                            <form method="POST" action="{{ route('orders.notes.store', $data->id) }}" class="mb-4">
                                @csrf
                                <label for="order_note" class="form-label fw-bold">إضافة ملاحظة جديدة</label>
                                <textarea id="order_note" name="note" rows="3"
                                    class="form-control @error('note') is-invalid @enderror"
                                    maxlength="2000" required placeholder="اكتب الملاحظة المتعلقة بهذا الطلب...">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2 text-end">
                                    <button type="submit" class="btn btn-warning text-dark">
                                        <i class="fa fa-save"></i> حفظ الملاحظة
                                    </button>
                                </div>
                            </form>
                        @endif

                        @forelse ($data->notes as $note)
                            <div class="order-note-item">
                                <div class="order-note-meta">
                                    <i class="fa fa-user me-1"></i>{{ $note->admin?->name ?? 'موظف غير محدد' }}
                                    <span class="mx-2">•</span>
                                    <i class="fa fa-clock me-1"></i>{{ optional($note->created_at)->format('Y-m-d H:i') }}
                                </div>
                                <div class="order-note-text">{{ $note->note }}</div>
                            </div>
                        @empty
                            <div class="text-muted text-center py-3">
                                <i class="far fa-comment-alt me-1"></i> لا توجد ملاحظات على هذا الطلب.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Files -->
        <div class="row">
            <div class="col-12">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-file"></i> {{ helperTrans('ingaz.order_files') }}</h5>
                    </div>
                    <div class="order-info-body">
                        @if ($data->transfers->isNotEmpty())
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ helperTrans('ingaz.file_name') }}</th>
                                        <th>{{ helperTrans('ingaz.notes') }}</th>
                                        <th>{{ helperTrans('ingaz.uploaded_at') }}</th>
                                        <th>{{ helperTrans('ingaz.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data->transfers as $transfer)
                                        <tr>
                                            <td>{{ basename($transfer->file) }}</td>
                                            <td>{{ $transfer->notes ?? 'N/A' }}</td>
                                            <td>{{ $transfer->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>
                                                <a href="{{ route('order_files.download', $transfer->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fa fa-download"></i> {{ helperTrans('ingaz.download') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted">{{ helperTrans('ingaz.no_files_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-history"></i> {{ helperTrans('ingaz.activity_timeline') }}</h5>
                    </div>
                    <div class="order-info-body">
                        @include('admin.partials.activity-feed', ['activities' => $activities ?? collect()])
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="row">
            <div class="col-12">
                <div class="order-info-card">
                    <div class="order-info-header">
                        <h5><i class="fa fa-cogs"></i> {{ helperTrans('ingaz.actions') }}</h5>
                    </div>
                    <div class="order-info-body">
                        <div class="action-buttons">
                            <!-- Edit Status Button -->
                            @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_STATUS) && !$isCompletedOrder)
                                <button class="btn btn-primary edit-status" data-id="{{ $data->id }}">
                                    <i class="fa fa-edit"></i> {{ helperTrans('ingaz.edit_status') }}
                                </button>
                            @endif

                            <!-- Change Maid Button -->
                            @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_MAID))
                                @if ($data->status == 'new')
                                    <button class="btn btn-info" onclick="changeMaid({{ $data->id }})">
                                        <i class="fa fa-user-edit"></i> {{ helperTrans('ingaz.change_maid') }}
                                    </button>
                                @endif
                            @endif

                            <!-- Change Responsible Employee Button -->
                            @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_RESPONSIBLE) && !$isCompletedOrder)
                                <button class="btn btn-warning" onclick="changeResponsible({{ $data->id }})">
                                    <i class="fa fa-user-cog"></i> {{ helperTrans('ingaz.change_responsible') }}
                                </button>
                            @endif

                            <!-- Refuse/Return Order Button -->
                            @if ($data->adminCanReject(auth('admin')->user()) && !$isCompletedOrder)
                                @if ($data->status != 'refused' && $data->status != 'done')
                                    <button class="btn btn-danger" onclick="refuseReturnOrder({{ $data->id }})">
                                        <i class="fa fa-times"></i> {{ helperTrans('ingaz.refuse_return_order') }}
                                    </button>
                                @endif
                            @endif

                            <!-- Add Contract Button -->
                            @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_ADD_CONTRACT))
                                @if (!$data->contracts()->exists() && in_array($data->status, [
                                    \App\Enums\ingaz\OrderEnum::UnderProcess->value,
                                    \App\Enums\ingaz\OrderEnum::Contracted->value,
                                    \App\Enums\ingaz\OrderEnum::ContractedWithoutContract->value,
                                    \App\Enums\ingaz\OrderEnum::Completed->value,
                                    \App\Enums\ingaz\OrderEnum::Done->value,
                                ], true))
                                    <button class="btn btn-success addNewContract" data-id="{{ $data->id }}">
                                        <i class="fa fa-plus"></i> {{ helperTrans('ingaz.add_contract') }}
                                    </button>
                                @endif
                            @endif

                            <!-- Delete Order Button -->
                            @if ($data->adminCanDelete(auth('admin')->user()) && !$isCompletedOrder)
                                <button class="btn btn-danger" onclick="deleteOrder({{ $data->id }})">
                                    <i class="fa fa-trash"></i> {{ helperTrans('ingaz.delete_order') }}
                                </button>
                            @endif

                            <!-- Back to List Button -->
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> {{ helperTrans('ingaz.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

    <!-- Change Maid Modal -->
    <div class="modal fade" id="changeMaidModal" tabindex="-1" aria-labelledby="changeMaidModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeMaidModalLabel">
                        <i class="fa fa-user-edit"></i> {{ helperTrans('ingaz.change_maid') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="orderIdForMaidChange">
                    <div class="mb-3">
                        <label for="newMaidId" class="form-label">{{ helperTrans('ingaz.select_new_maid') }}</label>
                        <select class="form-select" id="newMaidId">
                            <option value="">{{ helperTrans('ingaz.choose_maid') }}</option>
                            @foreach (\App\Models\ingaz\Maid::where('available', 1)->where('status', 'متاحة')->get() as $maid)
                                <option value="{{ $maid->id }}">
                                    {{ $maid->name }} - {{ $maid->nationality?->sub_title ?? 'N/A' }} -
                                    {{ $maid->passport_num }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> {{ helperTrans('ingaz.maid_change_note') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ helperTrans('ingaz.cancel') }}
                    </button>
                    <button type="button" class="btn btn-primary" onclick="confirmChangeMaid()">
                        <i class="fa fa-check"></i> {{ helperTrans('ingaz.confirm_change') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Responsible Employee Modal -->
    <div class="modal fade" id="changeResponsibleModal" tabindex="-1" aria-labelledby="changeResponsibleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeResponsibleModalLabel">
                        <i class="fa fa-user-cog"></i> {{ helperTrans('ingaz.change_responsible') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="orderIdForResponsibleChange">
                    <div class="mb-3">
                        <label for="newAdminId"
                            class="form-label">{{ helperTrans('ingaz.select_new_responsible') }}</label>
                        <select class="form-select" id="newAdminId">
                            <option value="">{{ helperTrans('ingaz.choose_responsible') }}</option>
                            @foreach (\App\Models\auth\Admin::query()->where('admin_type', 'customer_service')->where(function ($query) { $query->whereNull('status')->orWhere('status', 'active'); })->get() as $admin)
                                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> {{ helperTrans('ingaz.responsible_change_note') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ helperTrans('ingaz.cancel') }}
                    </button>
                    <button type="button" class="btn btn-primary" onclick="confirmChangeResponsible()">
                        <i class="fa fa-check"></i> {{ helperTrans('ingaz.confirm_change') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Event delegation for addNewContract button
            $('body').on('click', '.addNewContract', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                if (orderId) {
                    window.location.href = '{{ route('RecruitmentContract.create') }}?orderId=' + orderId;
                } else {
                    console.error('data-id attribute is missing!');
                }
            });
        });

        function changeMaid(orderId) {
            // Open modal to select new maid
            $('#changeMaidModal').modal('show');
            $('#orderIdForMaidChange').val(orderId);
        }

        function changeResponsible(orderId) {
            // Open modal to select new responsible employee
            $('#changeResponsibleModal').modal('show');
            $('#orderIdForResponsibleChange').val(orderId);
        }

        function confirmChangeMaid() {
            var orderId = $('#orderIdForMaidChange').val();
            var maidId = $('#newMaidId').val();

            if (!maidId) {
                alert('{{ helperTrans('ingaz.please_select_maid') }}');
                return;
            }

            $.ajax({
                url: '{{ route('orders.changeMaid') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_id: orderId,
                    maid_id: maidId
                },
                success: function(response) {
                    if (response.success) {
                        $('#changeMaidModal').modal('hide');
                        // Show success message and reload after a short delay
                        alert(response.message || '{{ helperTrans('ingaz.maid_changed_successfully') }}');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert(response.message || '{{ helperTrans('ingaz.error_changing_maid') }}');
                    }
                },
                error: function(xhr) {
                    var errorMessage = '{{ helperTrans('ingaz.error_changing_maid') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    alert(errorMessage);
                }
            });
        }

        function confirmChangeResponsible() {
            var orderId = $('#orderIdForResponsibleChange').val();
            var adminId = $('#newAdminId').val();

            if (!adminId) {
                alert('{{ helperTrans('ingaz.please_select_responsible') }}');
                return;
            }

            $.ajax({
                url: '{{ route('orders.changeResponsible') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_id: orderId,
                    admin_id: adminId
                },
                success: function(response) {
                    if (response.data.success) {
                        $('#changeResponsibleModal').modal('hide');
                        // Show success message and reload after a short delay
                        Swal.fire({
                            title: '{{ helperTrans('ingaz.saved_successfully_title') }}',
                            text: response.message || '{{ helperTrans('ingaz.employee_updated_successfully') }}',
                            icon: 'success',
                            confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                        })
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Swal.fire({
                            title: '{{ helperTrans('ingaz.saved_successfully_title') }}',
                            text: response.message || '{{ helperTrans('ingaz.employee_updated_successfully') }}',
                            icon: 'success',
                            confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                        })
                    }
                },
                error: function(xhr) {
                    var errorMessage = '{{ helperTrans('ingaz.error_changing_responsible') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        title: '{{ helperTrans('ingaz.error_occurred_title') }}',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: '{{ helperTrans('ingaz.ok') }}',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }

        function deleteOrder(orderId) {
            Swal.fire({
                title: '{{ helperTrans('ingaz.confirm_delete_order') }}',
                text: '{{ helperTrans('ingaz.delete_item_confirm_text') }}',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ helperTrans('ingaz.delete') }}',
                cancelButtonText: '{{ helperTrans('ingaz.cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('orders.destroy', '') }}/' + orderId,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.data.success) {
                                Swal.fire({
                                    title: '{{ helperTrans('ingaz.deleted_successfully_title') }}',
                                    text: response.message || '{{ helperTrans('ingaz.employee_deleted_successfully') }}',
                                    icon: 'success',
                                    confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                                })
                                window.location.href = '{{ route('orders.index') }}';
                            } else {
                                toastr.success('{{ helperTrans('ingaz.error_deleting_order') }}');
                            }
                        },
                        error: function() {
                            toastr.error('{{ helperTrans('ingaz.error_deleting_order') }}');
                        }
                    });
                }
            });
        }

        // Edit Status Function
        function editStatus(orderId) {
            $.ajax({
                url: '/admin/orders/' + orderId + '/edit',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Create modal
                        const modal = `
                        <div class="modal fade" id="editStatusModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ helperTrans('ingaz.edit_order_status') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        ${response.html}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                        // Remove existing modal if any
                        $('#editStatusModal').remove();

                        // Add modal to body
                        $('body').append(modal);

                        // Show modal
                        $('#editStatusModal').modal('show');

                        // Handle form submission
                        $('#editStatusModal form').on('submit', function(e) {
                            e.preventDefault();
                            const formData = new FormData(this);

                            $.ajax({
                                url: $(this).attr('action'),
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    if (response.success) {
                                        $('#editStatusModal').modal('hide');
                                        // Show success message and reload
                                        toastr.success(response.message ||
                                            '{{ helperTrans('ingaz.status_update_error') }}'
                                        );
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 1000);
                                    } else {
                                        // alert(response.message ||
                                        //     '{{ helperTrans('ingaz.status_update_error') }}'
                                        // );
                                        toastr.success(response.message ||
                                            '{{ helperTrans('ingaz.status_update_error') }}'
                                        );
                                    }
                                },
                                error: function(xhr) {
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        // alert(xhr.responseJSON.message);
                                        toastr.error(xhr.responseJSON.message);
                                    } else {
                                        toastr.error(xhr.responseJSON.message);
                                    }
                                }
                            });
                        });
                    } else {
                        alert('{{ helperTrans('ingaz.load_form_error') }}');
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        alert(xhr.responseJSON.message);
                    } else {
                        alert('{{ helperTrans('ingaz.load_form_error') }}');
                    }
                }
            });
        }

        // Refuse/Return Order Function
        function refuseReturnOrder(orderId) {
            const modal = `
            <div class="modal fade" id="refuseReturnModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ helperTrans('ingaz.refuse_return_order') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="refuseReturnForm">
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.select_action') }}</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="refused" id="refused">
                                        <label class="form-check-label" for="refused">
                                            {{ helperTrans('ingaz.refuse_order') }}
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="return" id="return">
                                        <label class="form-check-label" for="return">
                                            {{ helperTrans('ingaz.return_order') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.reason') }}</label>
                                    <textarea class="form-control" name="reason" rows="3" placeholder="{{ helperTrans('ingaz.enter_reason') }}"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                            <button type="button" class="btn btn-danger" onclick="confirmRefuseReturn()">{{ helperTrans('ingaz.confirm') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

            $('#refuseReturnModal').remove();
            $('body').append(modal);
            $('#refuseReturnModal').modal('show');
            $('#refuseReturnForm').data('orderId', orderId);
        }

        function confirmRefuseReturn() {
            const orderId = $('#refuseReturnForm').data('orderId');
            const action = $('#refuseReturnForm input[name="action"]:checked').val();
            const reason = $('#refuseReturnForm textarea[name="reason"]').val();

            if (!action) {
                alert('{{ helperTrans('ingaz.please_select_action') }}');
                return;
            }

            $.ajax({
                url: '/admin/orders/' + orderId + '/refuse-return',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    action: action,
                    reason: reason
                },
                success: function(response) {
                    if (response.data.success) {
                        $('#refuseReturnModal').modal('hide');
                        Swal.fire({
                            title: '{{ helperTrans('ingaz.saved_successfully_title') }}',
                            text: response.message || '{{ helperTrans('ingaz.order_updated') }}',
                            icon: 'success',
                            confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                        }).then(() => {
                            window.location.reload();
                        })
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Swal.fire({
                            title: '{{ helperTrans('ingaz.saved_successfully_title') }}',
                            text: response.message || '{{ helperTrans('ingaz.order_updated') }}',
                            icon: 'success',
                            confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                        }).then(() => {
                            window.location.reload();
                        })
                    }
                },
                error: function() {
                    Swal.fire({
                        title: '{{ helperTrans('ingaz.error_occurred_title') }}',
                        text: '{{ helperTrans('ingaz.update_error') }}',
                        icon: 'error',
                        confirmButtonText: '{{ helperTrans('ingaz.ok') }}'
                    })
                }
            });
        }

        // Add event listener for edit status button
        $(document).on('click', '.edit-status', function() {
            const orderId = $(this).data('id');
            editStatus(orderId);
        });
    </script>
@endsection

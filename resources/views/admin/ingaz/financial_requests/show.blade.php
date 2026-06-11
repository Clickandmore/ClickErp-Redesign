@extends('click.layout.indexs.index')

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h3 class="mb-1">طلب مالي {{ $requestItem->request_no }}</h3>
                <div class="text-muted">{{ $types[$requestItem->type] ?? $requestItem->type }}</div>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('financial-requests.index') }}">رجوع</a>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">الحالة</dt><dd class="col-sm-9">{{ $statuses[$requestItem->status] ?? $requestItem->status }}</dd>
                            <dt class="col-sm-3">التصعيد</dt><dd class="col-sm-9">{{ $priorities[$requestItem->priority] ?? $requestItem->priority }}</dd>
                            <dt class="col-sm-3">المبلغ</dt><dd class="col-sm-9">{{ $requestItem->amount ? number_format((float) $requestItem->amount, 2) : '-' }}</dd>
                            <dt class="col-sm-3">العميل</dt><dd class="col-sm-9">{{ $requestItem->client_name ?: '-' }} / {{ $requestItem->client_identity ?: '-' }}</dd>
                            <dt class="col-sm-3">العاملة</dt><dd class="col-sm-9">{{ $requestItem->maid_name ?: '-' }} / {{ $requestItem->maid_passport ?: '-' }}</dd>
                            <dt class="col-sm-3">العقد</dt><dd class="col-sm-9">{{ $requestItem->contract_number ?: '-' }} - {{ $requestItem->contract_type ?: '-' }}</dd>
                            <dt class="col-sm-3">الوصف</dt><dd class="col-sm-9">{{ $requestItem->description ?: '-' }}</dd>
                            <dt class="col-sm-3">السند المالي</dt>
                            <dd class="col-sm-9">
                                @if($requestItem->financeBond)
                                    <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $requestItem->financeBond->id, 'type' => $requestItem->finance_bond_type]) }}" target="_blank">#{{ $requestItem->financeBond->id }}</a>
                                @else
                                    -
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header fw-bold">الحقول الإضافية</div>
                    <div class="card-body">
                        @forelse(($requestItem->payload ?? []) as $key => $value)
                            <div class="mb-2"><strong>{{ $key }}:</strong> {{ $value }}</div>
                        @empty
                            <div class="text-muted">لا توجد حقول إضافية.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header fw-bold">المرفقات</div>
                    <div class="card-body">
                        @forelse($requestItem->attachments as $attachment)
                            <a class="btn btn-sm btn-outline-dark mb-2" href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">
                                <i class="fa fa-paperclip"></i> {{ $attachment->label ?: $attachment->file_name }}
                            </a>
                        @empty
                            <div class="text-muted">لا توجد مرفقات.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                    <div class="card">
                        <div class="card-header fw-bold">تحديث الحالة</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('financial-requests.status', $requestItem) }}">
                                @csrf
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select mb-2" required>
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}" @selected($requestItem->status === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">التصعيد</label>
                                <select name="priority" class="form-select mb-2">
                                    @foreach($priorities as $key => $label)
                                        <option value="{{ $key }}" @selected($requestItem->priority === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">نوع السند عند السداد</label>
                                <select name="bond_type" class="form-select mb-2">
                                    <option value="">بدون</option>
                                    <option value="qabd">سند قبض</option>
                                    <option value="sarf">سند صرف</option>
                                </select>
                                <label class="form-label">الحساب المدين</label>
                                <select name="debit_account_id" class="form-select mb-2">
                                    <option value="">اختر الحساب</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">الحساب الدائن</label>
                                <select name="credit_account_id" class="form-select mb-2">
                                    <option value="">اختر الحساب</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary w-100" type="submit">حفظ الحالة</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

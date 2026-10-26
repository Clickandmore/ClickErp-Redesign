@extends('admin.layout.indexs.index')

@section('page-title', 'الملف المالي للعقد #' . $state['contract_id'])

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="mb-1">الملف المالي للعقد #{{ $state['contract_id'] }}</h4>
                <div class="text-muted">
                    {{ $state['client'] }} · العاملة {{ $state['worker'] }} · {{ $state['nationality'] !== '' ? $state['nationality'] : '-' }}
                    · المكتب {{ $state['office'] }}
                </div>
            </div>
            <a href="{{ route('recruitment-contract-finance.index') }}" class="btn btn-light">القائمة</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        {{-- الحارس يبقي القالب قابلًا للرندر خارج دورة الطلب (الاختبارات والمعاينة). --}}
        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger"><ul class="mb-0">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul></div>
        @endif
        @unless ($automaticEntriesEnabled)
            <div class="alert alert-info">القيود التلقائية غير مفعّلة، فلن يُنشأ قيد لأي إجراء هنا.</div>
        @endunless

        <div class="alert alert-secondary">
            كل إجراء يُسجَّل بتاريخه ووحده. الفاتورة تُصدر الآن، وتحويل مساند يُسجَّل حين تحوّل مساند فعلًا،
            وسداد الوكيل بجدوله على دفعات. لا تسجّل إجراءً قبل وقوعه.
        </div>

        <div class="row g-3">
            {{-- 1. الفاتورة --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>١ · الفاتورة</strong>
                        <span class="badge bg-{{ $state['invoice']['exists'] ? 'success' : 'secondary' }}">
                            {{ $state['invoice']['exists'] ? 'صادرة' : 'لم تصدر' }}
                        </span>
                    </div>
                    <div class="card-body">
                        @if ($state['invoice']['exists'])
                            <div class="mb-1">رقم الفاتورة: <strong>#{{ $state['invoice']['id'] }}</strong></div>
                            <div class="mb-1">الإجمالي: <strong>{{ number_format($state['invoice']['total'], 2) }}</strong></div>
                            <div class="text-muted">بتاريخ {{ $state['invoice']['date'] }}</div>
                        @else
                            <p class="text-muted">لم تصدر فاتورة لهذا العقد بعد.</p>
                        @endif
                    </div>
                    <div class="card-footer">
                        @if ($state['invoice']['exists'])
                            <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.show', $state['invoice']['id']) }}">عرض الفاتورة</a>
                        @else
                            <a class="btn btn-sm btn-primary" href="{{ route('invoices.create', ['contract_id' => $state['contract_id']]) }}">
                                <i class="fas fa-file-invoice me-1"></i>إصدار الفاتورة
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. تحويل مساند --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>٢ · تحصيل مساند</strong>
                        <span class="badge bg-{{ $state['musaned']['settled'] ? 'success' : 'secondary' }}">
                            {{ $state['musaned']['settled'] ? 'محصَّل' : 'لم يحصَّل' }}
                        </span>
                    </div>
                    <div class="card-body">
                        @if ($state['musaned']['settled'])
                            <div class="mb-1">الصافي المحوَّل: <strong>{{ number_format($state['musaned']['transferred'], 2) }}</strong></div>
                            <div class="mb-1">العمولة: {{ number_format($state['musaned']['commission'], 2) }}
                                (منها ضريبة {{ number_format($state['musaned']['expense_tax'], 2) }})</div>
                            <div class="text-muted">تحويل #{{ $state['musaned']['transfer_id'] }} بتاريخ {{ $state['musaned']['date'] }}</div>
                        @elseif (!$state['invoice']['exists'])
                            <p class="text-muted">يلزم إصدار الفاتورة أولًا.</p>
                        @else
                            <form method="POST" action="{{ route('musaned-transfers.store') }}">
                                @csrf
                                <input type="hidden" name="return_to_contract" value="{{ $state['contract_id'] }}">
                                <input type="hidden" name="items[0][contract_id]" value="{{ $state['contract_id'] }}">
                                <div class="mb-2">
                                    <label class="form-label" for="m_date">تاريخ ورود المبلغ</label>
                                    <input type="date" class="form-control form-control-sm" id="m_date" name="transfer_date" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label" for="m_amount">الصافي المحوَّل من مساند</label>
                                    <input type="number" step="0.01" min="0.01" max="{{ $state['invoice']['total'] }}"
                                           class="form-control form-control-sm" id="m_amount" name="items[0][transferred_amount]" required>
                                    <small class="text-muted">الفرق عن {{ number_format($state['invoice']['total'], 2) }} يُسجَّل كتكاليف بنكية لمساند تلقائيًا.</small>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label" for="m_bank">البنك المحصِّل</label>
                                    <select class="form-control form-control-sm" id="m_bank" name="bank_account_id" required>
                                        <option value="">اختر الحساب</option>
                                        @foreach ($bankAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->code }} / {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-sm btn-primary w-100" type="submit">تسجيل التحصيل</button>
                            </form>
                            <p class="text-warning small mt-2 mb-0">
                                هذا تحويل لهذا العقد وحده. مساند تحوّل مبلغًا واحدًا يغطي فواتير كثيرة،
                                فستحتاج المطابقة البنكية تجميع هذه التحويلات يدويًا مقابل سطر البنك الواحد.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3. سداد الوكيل --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>٣ · سداد الوكيل الخارجي</strong>
                        <span class="badge bg-{{ $state['agent']['remaining'] <= 0 && $state['agent']['invoiced'] > 0 ? 'success' : 'secondary' }}">
                            @if ($state['agent']['invoiced'] <= 0) لم تُثبت التكلفة
                            @elseif ($state['agent']['remaining'] <= 0) مسدَّد
                            @else متبقٍ {{ number_format($state['agent']['remaining'], 2) }}
                            @endif
                        </span>
                    </div>
                    <div class="card-body">
                        @if ($state['agent']['invoiced'] <= 0)
                            <p class="text-muted mb-0">
                                لم تُثبت فاتورة الوكيل بعد. تُثبت حسب إعداد «إثبات تكلفة المورد الخارجي»
                                — عند الإرسالية أو عند الوصول.
                            </p>
                        @else
                            <div class="mb-1">فاتورة الوكيل: <strong>{{ number_format($state['agent']['invoiced'], 2) }}</strong></div>
                            <div class="mb-1">المسدَّد: {{ number_format($state['agent']['paid'], 2) }}</div>
                            <div class="mb-2">المتبقي: <strong>{{ number_format($state['agent']['remaining'], 2) }}</strong></div>

                            @if ($state['agent']['remaining'] > 0 && $paymentsEnabled)
                                <form method="POST" action="{{ route('recruitment-supplier-payments.store', $state['contract_id']) }}">
                                    @csrf
                                    <input type="hidden" name="return_to_contract" value="{{ $state['contract_id'] }}">
                                    <div class="mb-2">
                                        <label class="form-label" for="p_date">تاريخ الدفعة</label>
                                        <input type="date" class="form-control form-control-sm" id="p_date" name="date" value="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" for="p_value">المبلغ</label>
                                        <input type="number" step="0.01" min="0.01" max="{{ $state['agent']['remaining'] }}"
                                               class="form-control form-control-sm" id="p_value" name="value"
                                               value="{{ $state['agent']['remaining'] }}" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" for="p_bank">البنك المسدد</label>
                                        <select class="form-control form-control-sm" id="p_bank" name="bank_account_id" required>
                                            <option value="">اختر الحساب</option>
                                            @foreach ($bankAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->code }} / {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-sm btn-primary w-100" type="submit">تسجيل الدفعة</button>
                                </form>
                            @elseif ($state['agent']['remaining'] > 0)
                                <p class="text-muted small mb-0">
                                    تسجيل الدفعات من هذه الشاشة غير مفعّل. فعّل «سداد دفعات الوكيل الخارجي ← شاشة مربوطة بالعقد»
                                    من إعدادات الحسابات، أو استخدم سند الصرف العام.
                                </p>
                            @endif
                        @endif
                    </div>
                    @if ($payments->isNotEmpty())
                        <div class="card-footer p-0">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>سند</th><th>التاريخ</th><th>المبلغ</th></tr></thead>
                                <tbody>
                                @foreach ($payments as $bond)
                                    <tr>
                                        <td>#{{ $bond->code }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($bond->date)->format('Y-m-d') }}</td>
                                        <td>{{ number_format((float) $bond->value, 2) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

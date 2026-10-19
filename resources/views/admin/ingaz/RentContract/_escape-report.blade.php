@php
    $escapeReport = \Illuminate\Support\Facades\Schema::hasTable('rental_escape_reports')
        ? \App\Models\RentalEscapeReport::with('reportedBy')->where('rental_contract_id', $contract->id)->first() : null;
@endphp
@if ($escapeReport)
    @php
        $escapeState = $escapeReport->closed_without_return_at ? 'closed' : ($escapeReport->ready_at ? 'ready' : ($escapeReport->returned_at ? 'returned' : 'open'));
        $escapeStateLabel = [
            'ready' => 'تم اعتماد الجاهزية بعد العودة',
            'returned' => 'عادت — بانتظار مراجعة الجاهزية',
            'open' => 'بلاغ مفتوح — العاملة غير متاحة',
            'closed' => 'مغلق دون عودة — العاملة غير متاحة',
        ][$escapeState];
        $escapeStateIcon = ['closed' => 'fa-lock', 'ready' => 'fa-circle-check', 'returned' => 'fa-hourglass-half', 'open' => 'fa-triangle-exclamation'][$escapeState];
    @endphp
    <style>
        .escape-report { background:#fff; border:1px solid #fecaca; border-radius:22px; box-shadow:0 14px 32px rgba(127,29,29,.08); overflow:hidden; margin-bottom:24px; }
        .escape-report.is-returned { border-color:#bfdbfe; }
        .escape-report.is-ready { border-color:#bbf7d0; }
        .escape-report__head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding:18px 22px; background:linear-gradient(135deg,#b91c1c 0%,#dc2626 60%,#ef4444 100%); color:#fff; }
        .escape-report.is-returned .escape-report__head { background:linear-gradient(135deg,#1e40af 0%,#2563eb 100%); }
        .escape-report.is-ready .escape-report__head { background:linear-gradient(135deg,#047857 0%,#10b981 100%); }
        .escape-report__title { display:flex; align-items:center; gap:12px; margin:0; font-size:18px; font-weight:800; color:#fff; }
        .escape-report__title i { width:40px; height:40px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,255,255,.18); font-size:16px; }
        .escape-report__badge { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:999px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28); font-size:13px; font-weight:700; }
        .escape-report__body { padding:22px; }
        .escape-report__grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:16px; }
        .escape-report__item { background:#f8fafc; border:1px solid #e7eef7; border-radius:16px; padding:14px 16px; }
        .escape-report__item .label { display:flex; align-items:center; gap:6px; color:#64748b; font-size:12px; margin-bottom:6px; }
        .escape-report__item .value { color:#0f172a; font-size:15px; font-weight:700; overflow-wrap:anywhere; }
        .escape-report__item .value small { display:block; color:#475569; font-weight:500; font-size:12px; margin-top:3px; }
        .escape-report__item .value.is-muted { color:#94a3b8; font-weight:600; }
        .escape-report__notes { background:#fff7f7; border:1px solid #fee2e2; border-radius:16px; padding:14px 16px; margin-bottom:16px; }
        .escape-report__notes .label { color:#991b1b; font-size:12px; font-weight:700; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
        .escape-report__notes .text { color:#1f2937; white-space:pre-wrap; overflow-wrap:anywhere; line-height:1.8; margin:0; }
        .escape-report__timeline { display:grid; gap:10px; margin-bottom:16px; }
        .escape-report__event { display:flex; gap:12px; align-items:flex-start; border-radius:16px; padding:12px 16px; }
        .escape-report__event i { margin-top:3px; }
        .escape-report__event.is-return { background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; }
        .escape-report__event.is-ready { background:#ecfdf5; border:1px solid #bbf7d0; color:#065f46; }
        .escape-report__event strong { display:block; font-size:13px; margin-bottom:2px; }
        .escape-report__event p { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; font-size:13px; }
        .escape-report__form { border-top:1px dashed #e2e8f0; padding-top:18px; margin-top:4px; }
        .escape-report__form h6 { display:flex; align-items:center; gap:8px; font-size:15px; font-weight:800; color:#0f172a; margin-bottom:14px; }
        .escape-report__form-grid { display:grid; grid-template-columns:minmax(180px,240px) 1fr; gap:14px; }
        .escape-report__form label { font-size:13px; font-weight:700; color:#334155; margin-bottom:6px; display:block; }
        .escape-report__form .form-control { border-radius:12px; border-color:#dbe4ef; }
        .escape-report__form textarea.form-control { min-height:90px; }
        .escape-report__foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-top:14px; }
        .escape-report__hint { display:flex; align-items:center; gap:6px; color:#64748b; font-size:12px; margin:0; }
        .escape-report__btn { display:inline-flex; align-items:center; gap:8px; border-radius:12px; padding:10px 18px; font-weight:700; }
        @media (max-width: 767px) {
            .escape-report__form-grid { grid-template-columns:1fr; }
            .escape-report__foot { flex-direction:column; align-items:stretch; }
            .escape-report__btn { justify-content:center; }
        }
    </style>

    <section class="escape-report is-{{ $escapeState }}" aria-label="بلاغ هروب العاملة">
        <div class="escape-report__head">
            <h3 class="escape-report__title">
                <i class="fa-solid fa-person-running"></i>
                بلاغ هروب العاملة #{{ $escapeReport->id }}
            </h3>
            <span class="escape-report__badge"><i class="fa-solid {{ $escapeStateIcon }}"></i> {{ $escapeStateLabel }}</span>
        </div>

        <div class="escape-report__body">
            <div class="escape-report__grid">
                <div class="escape-report__item">
                    <span class="label"><i class="fa-regular fa-calendar-xmark"></i> تاريخ الهروب</span>
                    <div class="value">{{ $escapeReport->escape_date->format('Y-m-d') }}</div>
                </div>
                <div class="escape-report__item">
                    <span class="label"><i class="fa-regular fa-clock"></i> تاريخ تسجيل البلاغ</span>
                    <div class="value">
                        {{ $escapeReport->created_at->format('Y-m-d H:i') }}
                        <small><i class="fa-regular fa-user"></i> {{ $escapeReport->reportedBy?->name ?: '—' }}</small>
                    </div>
                </div>
                <div class="escape-report__item">
                    <span class="label"><i class="fa-solid fa-hashtag"></i> مرجع البلاغ الرسمي</span>
                    <div class="value {{ $escapeReport->reference_number ? '' : 'is-muted' }}">{{ $escapeReport->reference_number ?: 'لم يسجل' }}</div>
                </div>
                @if ($escapeReport->attachment)
                    <div class="escape-report__item">
                        <span class="label"><i class="fa-solid fa-paperclip"></i> مرفق البلاغ</span>
                        <a class="btn btn-sm btn-outline-danger escape-report__btn" href="{{ route('RentContract.escapeAttachment', $contract->id) }}">
                            <i class="fa-solid fa-download"></i> تحميل المرفق
                        </a>
                    </div>
                @endif
            </div>

            <div class="escape-report__notes">
                <span class="label"><i class="fa-regular fa-file-lines"></i> تفاصيل الواقعة</span>
                <p class="text">{{ $escapeReport->notes }}</p>
            </div>

            @if ($escapeReport->returned_at || $escapeReport->ready_at)
                <div class="escape-report__timeline">
                    @if ($escapeReport->returned_at)
                        <div class="escape-report__event is-return">
                            <i class="fa-solid fa-house-circle-check"></i>
                            <div>
                                <strong>العودة: {{ $escapeReport->returned_at->format('Y-m-d') }}</strong>
                                <p>{{ $escapeReport->return_notes }}</p>
                            </div>
                        </div>
                    @endif
                    @if ($escapeReport->ready_at)
                        <div class="escape-report__event is-ready">
                            <i class="fa-solid fa-circle-check"></i>
                            <div>
                                <strong>اعتماد الجاهزية: {{ $escapeReport->ready_at->format('Y-m-d H:i') }}</strong>
                                <p>{{ $escapeReport->review_notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if ($escapeReport->closed_without_return_at)
                <div class="escape-report__notes">
                    <strong>إغلاق البلاغ دون عودة — {{ $escapeReport->closed_without_return_date->format('Y-m-d') }}</strong>
                    <p class="text">{{ $escapeReport->no_return_reason }}</p>
                    <p>سجّله {{ $escapeReport->closedWithoutReturnBy?->name ?: '—' }} بتاريخ {{ $escapeReport->closed_without_return_at->format('Y-m-d H:i') }}</p>
                    <a class="btn btn-outline-danger" href="{{ route('RentContract.escapeAttachment', ['id' => $contract->id, 'kind' => 'closure']) }}">تحميل مستند الإغلاق</a>
                    <p class="mt-2 mb-0">العاملة غير متاحة. لم تُسجّل عودة إلى الإيواء، والعقد وتسويته المالية باقيان كما هما.</p>
                </div>
            @endif

            @if (AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL) && !$escapeReport->closed_without_return_at)
                @if (!$escapeReport->returned_at)
                    <form method="POST" action="{{ route('RentContract.escapeReturn', $contract->id) }}" class="escape-report__form">
                        @csrf
                        <h6><i class="fa-solid fa-person-walking-arrow-loop-left text-primary"></i> تسجيل عودة العاملة فعليًا</h6>
                        <div class="escape-report__form-grid">
                            <div>
                                <label for="escapeReturnDate">تاريخ العودة</label>
                                <input id="escapeReturnDate" type="date" name="return_date" min="{{ $escapeReport->escape_date->format('Y-m-d') }}" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div>
                                <label for="escapeReturnNotes">تفاصيل الاستلام</label>
                                <textarea id="escapeReturnNotes" name="return_notes" class="form-control" maxlength="3000" placeholder="مكان الاستلام، الجهة التي سلّمت العاملة، حالتها..." required></textarea>
                            </div>
                        </div>
                        <div class="escape-report__foot">
                            <p class="escape-report__hint"><i class="fa-solid fa-circle-info"></i> تسجيل العودة لا يعيد فتح العقد ولا يجعل العاملة متاحة تلقائيًا.</p>
                            <button class="btn btn-primary escape-report__btn"><i class="fa-solid fa-check"></i> تأكيد استلام العاملة وتسجيل العودة</button>
                        </div>
                    </form>
                    @if (\Illuminate\Support\Facades\Schema::hasColumn('rental_escape_reports', 'closed_without_return_at'))
                        <details class="escape-report__form">
                            <summary class="text-danger fw-bold">إغلاق البلاغ دون عودة</summary>
                            <form method="POST" enctype="multipart/form-data" action="{{ route('RentContract.escapeCloseWithoutReturn', $contract->id) }}" onsubmit="return confirm('تأكيد إغلاق البلاغ دون عودة؟ ستبقى العاملة غير متاحة ولن تتغير تسوية العقد.');">
                                @csrf
                                <p class="mt-3">استخدم هذا الإجراء لإنهاء متابعة البلاغ دون تسجيل عودة العاملة. الإغلاق لا يتيح العاملة للتأجير.</p>
                                <label for="noReturnDate">تاريخ الإغلاق</label>
                                <input id="noReturnDate" type="date" name="no_return_date" min="{{ $escapeReport->escape_date->format('Y-m-d') }}" max="{{ now()->toDateString() }}" value="{{ old('no_return_date', now()->toDateString()) }}" class="form-control" required>
                                <label for="noReturnReason" class="mt-3">سبب الإغلاق دون عودة</label>
                                <textarea id="noReturnReason" name="no_return_reason" class="form-control" maxlength="3000" required>{{ old('no_return_reason') }}</textarea>
                                <label for="noReturnAttachment" class="mt-3">المستند المؤيد للإغلاق (إلزامي)</label>
                                <input id="noReturnAttachment" type="file" name="no_return_attachment" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required>
                                <p class="escape-report__hint mt-2">PDF أو صورة، حتى 10 ميجابايت.</p>
                                <button class="btn btn-danger escape-report__btn mt-3">تأكيد إغلاق البلاغ دون عودة</button>
                            </form>
                        </details>
                    @endif
                @elseif (!$escapeReport->ready_at)
                    <form method="POST" action="{{ route('RentContract.escapeReady', $contract->id) }}" class="escape-report__form">
                        @csrf
                        <h6><i class="fa-solid fa-clipboard-check text-success"></i> مراجعة جاهزية العاملة للتأجير</h6>
                        <label for="escapeReviewNotes">نتيجة مراجعة جاهزية العاملة للتأجير</label>
                        <textarea id="escapeReviewNotes" name="review_notes" class="form-control" maxlength="3000" required></textarea>
                        <div class="escape-report__foot">
                            <p class="escape-report__hint"><i class="fa-solid fa-circle-info"></i> بعد الاعتماد تصبح العاملة متاحة للتأجير من جديد.</p>
                            <button class="btn btn-success escape-report__btn"><i class="fa-solid fa-circle-check"></i> اعتماد الجاهزية وإتاحة العاملة للتأجير</button>
                        </div>
                    </form>
                @endif
            @endif
        </div>
    </section>
@endif

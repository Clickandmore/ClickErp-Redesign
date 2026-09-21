<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'تصحيح الفواتير'])
    <style>
        /* ===================================================================
           تصحيح الفواتير — إضافات فوق نظام تصميم Super Admin الموحّد
           =================================================================== */

        .num {
            direction: ltr;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
        }

        .field-grid.is-compact { grid-template-columns: repeat(auto-fit, minmax(160px, 200px)); }

        .correction-hint { margin-bottom: 20px; padding: 16px 20px; border: 1px solid var(--border); border-radius: var(--r-md); background: var(--info-soft); line-height: 1.9; }
        .correction-hint summary { cursor: pointer; font-weight: 700; }
        .correction-hint summary:focus-visible { outline: 2px solid var(--accent-line); outline-offset: 4px; }
        .correction-hint ul { padding-inline-start: 22px; margin: 12px 0; }
        .correction-hint li + li { margin-top: 6px; }
        .correction-hint p { margin: 12px 0 0; }

        /* شريط بيانات الفاتورة أعلى النموذج */
        .meta-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }

        .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 11px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface-soft);
            color: var(--text-soft);
            font-size: .79rem;
            font-weight: 600;
        }

        .meta-chip span { color: var(--muted); font-weight: 600; }
        .meta-chip b { font-weight: 700; direction: ltr; }

        /* جدول بنود الفاتورة */
        .lines-table td input {
            width: 100%;
            min-width: 120px;
            min-height: 42px;
            border: 1px solid var(--border-strong);
            border-radius: var(--r-sm);
            padding: 9px 12px;
            background: #fff;
            font: inherit;
            font-size: .89rem;
            direction: ltr;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .lines-table td input:focus {
            outline: none;
            border-color: var(--accent-line);
            box-shadow: 0 0 0 4px var(--ring);
        }

        .lines-table th:first-child { width: 40%; }
        .lines-table td:last-child { font-weight: 700; }
        .lines-table tfoot td { background: var(--surface-soft); font-weight: 700; }
        .lines-table .line-name { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-weight: 600; }

        .tax-tag {
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid var(--info-line, #b2ddff);
            background: var(--info-soft);
            color: var(--info);
            font-size: .7rem;
            font-weight: 700;
        }

        /* ملخّص تقديري أسفل نموذج البنود */
        .estimate {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 26px;
            margin-top: 16px;
            padding: 14px 16px;
            border: 1px dashed var(--border-strong);
            border-radius: var(--r-md);
            background: var(--surface-soft);
        }

        .estimate div { display: grid; gap: 3px; }
        .estimate span { color: var(--muted); font-size: .78rem; font-weight: 600; }
        .estimate b { font-size: 1rem; font-weight: 700; direction: ltr; font-variant-numeric: tabular-nums; }
        .estimate .estimate-note { flex: 1 1 100%; margin: 0; color: var(--muted); font-size: .78rem; line-height: 1.7; }

        /* فروقات المعاينة */
        .stats.is-three { grid-template-columns: repeat(3, minmax(0, 1fr)); }

        .delta { font-weight: 700; direction: ltr; font-variant-numeric: tabular-nums; }
        .delta.is-up { color: var(--danger); }
        .delta.is-down { color: var(--success); }
        .delta.is-same { color: var(--muted); font-weight: 600; }

        .diff-table tbody tr.is-changed td { background: var(--accent-soft); }
        .diff-table tbody tr.is-changed td:first-child { font-weight: 700; }

        /* بطاقات القيود المحاسبية */
        .journal-card {
            margin-top: 16px;
            border: 1px solid var(--border);
            border-radius: var(--r-md);
            background: #fff;
            overflow: hidden;
        }

        .journal-card.is-changed { border-color: var(--accent-line); box-shadow: 0 0 0 3px var(--ring); }

        .journal-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            background: var(--surface-soft);
        }

        .journal-head h3 { margin: 0; font-size: .95rem; font-weight: 700; }
        .journal-head .journal-code { margin-top: 4px; color: var(--muted); font-size: .8rem; font-weight: 600; direction: ltr; }
        .journal-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: .8rem; color: var(--muted); }
        .journal-meta .badge.is-neutral { background: var(--surface-alt); color: var(--muted); border-color: var(--border-strong); }
        .journal-card .table-wrap { margin: 0; border: 0; border-radius: 0; }
        .journal-card .table-wrap table { min-width: 620px; }
        .journal-card tbody tr.correction-change td { background: var(--accent-soft); }

        .arrow { color: var(--muted); padding: 0 4px; }

        .empty-state {
            margin: 0;
            padding: 22px 16px;
            text-align: center;
            color: var(--muted);
            font-size: .88rem;
        }

        @media (max-width: 860px) {
            .stats.is-three { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
</head>
<body>
@php
    $currency = fn ($value) => number_format((float) $value, 2);
    $taxableLines = ['direct_recruitment', 'internal_ticket'];
@endphp

<main class="dev-shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1 class="brand-title">تصحيح الفواتير</h1>
                <p class="brand-text">تعديل بنود فاتورة صادرة وتاريخها مع تحديث القيود المحاسبية المرتبطة بها.</p>
            </div>
        </div>

        <div class="topbar-actions">
            <a class="btn" href="{{ route('dev-superadmin.modules') }}">العودة إلى Superadmin</a>
        </div>
    </header>

    @if (session('success') || $errors->any())
        <div class="alerts">
            @if (session('success'))
                <div class="alert">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
        </div>
    @endif

    <div class="page-heading">
        <div>
            <h2>{{ $invoice ? 'الفاتورة ' . ($invoice->legacy_invoice_number ?: $invoice->id) : 'تصحيح الفواتير' }}</h2>
            <p>ابحث عن الفاتورة، عدّل البنود، ثم عاين أثر التعديل على القيود قبل الحفظ.</p>
        </div>

        @if ($invoice)
            <div class="page-heading-meta">
                <span class="heading-chip">الإجمالي الحالي: <b>{{ $currency($invoice->total_due) }} ر.س</b></span>
                <span class="heading-chip {{ $invoice->is_paid ? 'is-good' : 'is-bad' }}">
                    {{ $invoice->is_paid ? 'مسددة' : 'غير مسددة' }}
                </span>
            </div>
        @endif
    </div>

    {{-- ---------------------------------------------------------------- --}}
    {{-- 1) جلب الفاتورة                                                   --}}
    {{-- ---------------------------------------------------------------- --}}
    <details class="correction-hint">
        <summary>متى يُمنع تعديل الفاتورة؟ اضغط لعرض الشروط</summary>
        <ul>
            <li><strong>الفوترة الإلكترونية:</strong> وجود مستند ZATCA مرتبط، حتى لو كانت محاولة الإرسال مرفوضة، أو تعليم الفاتورة بأنها مشاركة سابقًا.</li>
            <li><strong>الإشعارات الدائنة:</strong> وجود إشعار دائن مرتبط بالفاتورة.</li>
            <li><strong>الفترة المالية:</strong> فترة القيد المتأثر مقفلة، أو تاريخه خارج سنته المالية، أو التاريخ الجديد لا يقع داخل سنة مالية نشطة واحدة.</li>
            <li><strong>القيود المحاسبية:</strong> قيد مفقود أو محذوف أو غير متوازن، أو مستخدم لأكثر من غرض، أو مشترك مع مستند آخر، أو لا يطابق تاريخ الفاتورة ومبالغها. إذا كانت مبالغ قيد الفاتورة تطابق التصحيح المطلوب بالفعل، يُسمح باستكمال تصحيح الفاتورة بعد التحقق من توزيع البنود. ويُمنع تعديل المبالغ إذا تعذرت مطابقة بنودها مع حسابات القيد بشكل واضح.</li>
            <li><strong>تسويات مساند:</strong> وجود تسوية مرتبطة عند تغيير إجمالي الفاتورة؛ التسوية وحدها لا تمنع تعديل التاريخ.</li>
            <li><strong>نسخة المبيعات:</strong> النسخة المرتبطة مفقودة أو مشتركة أو مؤكدة أو مرتبطة بقيد مستقل، أو تاريخها وإجماليها لا يطابقان الفاتورة. وتعديل مبالغها يتطلب توافق بنودها مع الشكل المدعوم.</li>
            <li><strong>نوع الفاتورة:</strong> الصفحة تدعم فواتير الاستقدام والتفويض ذات البنود القياسية؛ فواتير نقل الخدمات والبنود المخصصة غير مدعومة هنا.</li>
            <li><strong>القيم المدخلة:</strong> قيم غير صحيحة أو تتجاوز الحدود المسموحة، أو خصم أكبر من مجموع البنود، أو إجمالي غير موجب، أو عدم اتساق المبالغ الأصلية عند تعديلها.</li>
            <li><strong>المعاينة:</strong> يجب إجراء تغيير ومعاينته قبل الحفظ. إذا تغيرت البيانات بعد المعاينة، يجب إعادة المعاينة.</li>
        </ul>
        <p><strong>السداد وحده لا يمنع التعديل.</strong> كذلك تفعيل «الإرسال إلى ZATCA» وحده لا يمنعه ما دام لا يوجد مستند ZATCA مرتبط. تُحدّث نسخة المبيعات غير المرسلة والمتوافقة مع الفاتورة ضمن العملية نفسها، وتبقى سندات القبض بقيمتها الفعلية.</p>
    </details>

    <section class="panel">
        <div class="panel-head">
            <h2>جلب الفاتورة</h2>
            <p class="muted">ابحث برقم الفاتورة أو برقم العقد أو برقم التفويض. تظهر أحدث 100 نتيجة.</p>
        </div>

        <div class="panel-body">
            <form method="GET" action="{{ route('dev-superadmin.invoice-corrections.index') }}">
                <div class="field-grid is-compact">
                    <div class="field">
                        <label for="search_by">البحث بواسطة</label>
                        <select name="search_by" id="search_by">
                            <option value="invoice" {{ request('search_by') === 'invoice' ? 'selected' : '' }}>رقم الفاتورة</option>
                            <option value="contract" {{ request('search_by') === 'contract' ? 'selected' : '' }}>رقم العقد</option>
                            <option value="delegation" {{ request('search_by') === 'delegation' ? 'selected' : '' }}>رقم التفويض</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="number">الرقم</label>
                        <input id="number" name="number" type="number" min="1" required value="{{ request('number') }}" placeholder="مثال: 10254">
                    </div>

                    <div class="field">
                        <label aria-hidden="true">&nbsp;</label>
                        <button class="btn btn-primary" type="submit">بحث</button>
                    </div>
                </div>
            </form>

            @if (request()->filled('number'))
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الفاتورة</th>
                                <th>العقد</th>
                                <th>التفويض</th>
                                <th>التاريخ</th>
                                <th>الإجمالي (ر.س)</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($results as $result)
                                <tr>
                                    <td>
                                        <strong>{{ $result->legacy_invoice_number ?: $result->id }}</strong>
                                        <small class="text-muted">#{{ $result->id }}</small>
                                    </td>
                                    <td class="num">{{ $result->contract_id ?: '—' }}</td>
                                    <td class="num">{{ $result->ingaz_delegation_id ?: '—' }}</td>
                                    <td class="num">{{ $result->created_at?->format('Y-m-d') }}</td>
                                    <td class="num">{{ $currency($result->total_due) }}</td>
                                    <td>
                                        <span class="badge {{ $result->is_paid ? 'ok' : 'bad' }}">
                                            {{ $result->is_paid ? 'مسددة' : 'غير مسددة' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-small" href="{{ route('dev-superadmin.invoice-corrections.show', $result->id) }}">جلب وتعديل</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"><p class="empty-state">لم يتم العثور على فاتورة بهذا الرقم.</p></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- 2) نموذج تعديل الفاتورة                                           --}}
    {{-- ---------------------------------------------------------------- --}}
    @if ($invoice)
        <section class="panel">
            <div class="panel-head">
                <h2>بنود الفاتورة {{ $invoice->legacy_invoice_number ?: $invoice->id }}</h2>
                <p class="muted">أسعار الوحدات قبل الضريبة. تُحتسب الضريبة والإجماليات في المعاينة باستخدام حسابات النظام.</p>

                <div class="meta-chips">
                    <span class="meta-chip"><span>المعرف الداخلي:</span> <b>{{ $invoice->id }}</b></span>
                    <span class="meta-chip"><span>العقد:</span> <b>{{ $invoice->contract_id ?: '—' }}</b></span>
                    <span class="meta-chip"><span>التفويض:</span> <b>{{ $invoice->ingaz_delegation_id ?: '—' }}</b></span>
                    <span class="meta-chip"><span>الفرع:</span> <b>{{ $invoice->branch_id ?: '—' }}</b></span>
                </div>
            </div>

            <div class="panel-body">
                <form method="POST" action="{{ route('dev-superadmin.invoice-corrections.preview', $invoice->id) }}" id="correction-form">
                    @csrf

                    <div class="field-grid">
                        <div class="field">
                            <label for="invoice_date">تاريخ الفاتورة</label>
                            <input type="date" id="invoice_date" name="invoice_date" required
                                   value="{{ old('invoice_date', $plan['input']['invoice_date'] ?? $invoice->created_at?->format('Y-m-d')) }}">
                        </div>

                        @foreach (['visa_recruited_count' => 'عدد المستقدمين في التأشيرة', 'insured_count' => 'عدد المؤمن عليهم'] as $field => $label)
                            <div class="field">
                                <label for="{{ $field }}">{{ $label }}</label>
                                <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" max="10000" step="1" required
                                       value="{{ old($field, $plan['input'][$field] ?? $invoice->$field ?? 0) }}">
                            </div>
                        @endforeach
                    </div>

                    <div class="section-heading">
                        <h3>البنود</h3>
                        <p>البنود المعلّمة بـ «خاضع للضريبة» هي وحدها التي تدخل في احتساب ضريبة القيمة المضافة.</p>
                    </div>

                    <div class="table-wrap">
                        <table class="lines-table">
                            <thead>
                                <tr>
                                    <th>البند</th>
                                    <th>سعر الوحدة (ر.س)</th>
                                    <th>الكمية</th>
                                    <th>إجمالي البند (ر.س)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Services\SuperAdminInvoiceCorrectionService::LINES as $key => $line)
                                    @php
                                        $costValue = old($line['cost'], $plan['input'][$line['cost']] ?? $invoice->{$line['cost']} ?? 0);
                                        $countValue = old($line['count'], $plan['input'][$line['count']] ?? $invoice->{$line['count']} ?? 0);
                                        $isTaxable = in_array($key, $taxableLines, true);
                                    @endphp
                                    <tr data-line-row @if ($isTaxable) data-taxable="1" @endif>
                                        <td>
                                            <span class="line-name">
                                                {{ $line['label'] }}
                                                @if ($isTaxable)<span class="tax-tag">خاضع للضريبة</span>@endif
                                            </span>
                                        </td>
                                        <td>
                                            <input data-line-cost aria-label="{{ $line['label'] }} - السعر" type="number"
                                                   min="0" max="99999999.99" step="0.01" name="{{ $line['cost'] }}" required
                                                   value="{{ $costValue }}">
                                        </td>
                                        <td>
                                            <input data-line-count aria-label="{{ $line['label'] }} - الكمية" type="number"
                                                   min="0" max="10000" step="1" name="{{ $line['count'] }}" required
                                                   value="{{ $countValue }}">
                                        </td>
                                        <td class="num" data-line-total>{{ $currency((float) $costValue * (int) $countValue) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">مجموع البنود قبل الضريبة</td>
                                    <td class="num" id="lines-subtotal">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="field-grid is-compact" style="margin-top:18px">
                        <div class="field">
                            <label for="discount">الخصم (ر.س)</label>
                            <input type="number" name="discount" id="discount" min="0" max="99999999.99" step="0.01" required
                                   value="{{ old('discount', $plan['input']['discount'] ?? $invoice->discount ?? 0) }}">
                        </div>
                    </div>

                    <div class="estimate">
                        <div><span>مجموع البنود</span><b id="estimate-subtotal">0.00</b></div>
                        <div><span>الخصم</span><b id="estimate-discount">0.00</b></div>
                        <div><span>الضريبة (15%)</span><b id="estimate-vat">0.00</b></div>
                        <div><span>الإجمالي المستحق</span><b id="estimate-total">0.00</b></div>
                        <p class="estimate-note">أرقام تقديرية فورية للمراجعة فقط؛ الأرقام المعتمدة هي ما يظهر في المعاينة أدناه.</p>
                    </div>

                    <div class="save-row">
                        <button class="btn btn-primary" type="submit">معاينة الفاتورة والقيود</button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------------------- --}}
    {{-- 3) معاينة التعديل                                                 --}}
    {{-- ---------------------------------------------------------------- --}}
    @if ($plan)
        @php
            $totalBefore = (float) $plan['before']['total_due'];
            $totalAfter = (float) $plan['after']['total_due'];
            $totalDelta = $totalAfter - $totalBefore;
            $changedJournals = collect($plan['journals'])->where('changed', true)->count();
        @endphp

        <section class="panel">
            <div class="panel-head">
                <h2>معاينة التعديل</h2>
                <p class="muted">
                    راجع الفروقات قبل الحفظ. سيتم تحديث {{ $changedJournals }} من {{ count($plan['journals']) }} قيد مرتبط بالفاتورة.
                </p>
            </div>

            <div class="panel-body">
                <div class="stats is-three">
                    <div class="stat">
                        <div class="stat-label">الإجمالي قبل التعديل</div>
                        <div class="stat-value">{{ $currency($totalBefore) }} <small>ر.س</small></div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">الإجمالي بعد التعديل</div>
                        <div class="stat-value">{{ $currency($totalAfter) }} <small>ر.س</small></div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">الفرق</div>
                        <div class="stat-value">
                            <span class="delta {{ $totalDelta > 0 ? 'is-up' : ($totalDelta < 0 ? 'is-down' : 'is-same') }}">{{ $totalDelta > 0 ? '+' : '' }}{{ $currency($totalDelta) }}</span>
                            <small>ر.س</small>
                        </div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="diff-table">
                        <thead>
                            <tr>
                                <th>القيمة</th>
                                <th>قبل</th>
                                <th>بعد</th>
                                <th>الفرق</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $dateBefore = substr($plan['before']['created_at'], 0, 10);
                                $dateAfter = substr($plan['after']['created_at'], 0, 10);
                            @endphp
                            <tr class="{{ $dateBefore !== $dateAfter ? 'is-changed' : '' }}">
                                <td>التاريخ</td>
                                <td class="num">{{ $dateBefore }}</td>
                                <td class="num">{{ $dateAfter }}</td>
                                <td><span class="delta is-same">{{ $dateBefore === $dateAfter ? 'بدون تغيير' : 'تم التغيير' }}</span></td>
                            </tr>

                            @foreach (['subtotal' => 'مجموع البنود', 'discount' => 'الخصم', 'vat_amount' => 'الضريبة', 'total_due' => 'الإجمالي المستحق'] as $field => $label)
                                @php
                                    $valueBefore = (float) $plan['before'][$field];
                                    $valueAfter = (float) $plan['after'][$field];
                                    $delta = $valueAfter - $valueBefore;
                                    $isChanged = abs($delta) >= 0.005;
                                @endphp
                                <tr class="{{ $isChanged ? 'is-changed' : '' }}">
                                    <td>{{ $label }}</td>
                                    <td class="num">{{ $currency($valueBefore) }}</td>
                                    <td class="num">{{ $currency($valueAfter) }}</td>
                                    <td>
                                        <span class="delta {{ $delta > 0 ? 'is-up' : ($delta < 0 ? 'is-down' : 'is-same') }}">{{ $isChanged ? ($delta > 0 ? '+' : '') . $currency($delta) : 'بدون تغيير' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (isset($plan['updates']['is_paid']) && ! $plan['updates']['is_paid'])
                    <div class="alert alert-warning" style="margin-top:16px">ستصبح الفاتورة غير مسددة بالكامل بسبب زيادة الإجمالي.</div>
                @endif

                <div class="section-heading">
                    <h3>القيود المحاسبية المرتبطة</h3>
                    <p>الصفوف المظللة هي التي ستتغير قيمتها أو تاريخها عند الحفظ.</p>
                </div>

                @foreach ($plan['journals'] as $journal)
                    @php
                        $journalDateBefore = $journal['before']['date'];
                        $journalDateAfter = $journal['updates']['date'] ?? $journal['before']['date'];
                    @endphp
                    <div class="journal-card {{ $journal['changed'] ? 'is-changed' : '' }}">
                        <div class="journal-head">
                            <div>
                                <h3>{{ $journal['label'] }}</h3>
                                @if ($journal['already_matches_target'] ?? false)
                                    <p>مبالغ هذا القيد تطابق التصحيح المطلوب بالفعل؛ سيتم تحديث الفاتورة ونسخة المبيعات المرتبطة دون تغيير مبالغ القيد.</p>
                                @endif
                                <div class="journal-code">{{ $journal['before']['code'] }} · #{{ $journal['before']['id'] }}</div>
                            </div>

                            <div class="journal-meta">
                                <span class="badge {{ $journal['changed'] ? 'ok' : 'is-neutral' }}">{{ $journal['changed'] ? 'سيتم تحديثه' : 'لا يتأثر بهذا التعديل' }}</span>
                                <span class="num">
                                    {{ $journalDateBefore }}
                                    @if ($journalDateBefore !== $journalDateAfter)<span class="arrow">←</span>{{ $journalDateAfter }}@endif
                                </span>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>الحساب</th>
                                        <th>مدين قبل</th>
                                        <th>مدين بعد</th>
                                        <th>دائن قبل</th>
                                        <th>دائن بعد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($journal['rows'] as $row)
                                        <tr class="{{ $row['updates'] ? 'correction-change' : '' }}">
                                            <td>{{ $plan['accounts'][$row['before']['account_id']] ?? '#' . $row['before']['account_id'] }}</td>
                                            <td class="num">{{ $currency($row['before']['debtor_value']) }}</td>
                                            <td class="num">{{ $currency($row['updates']['debtor_value'] ?? $row['before']['debtor_value']) }}</td>
                                            <td class="num">{{ $currency($row['before']['creditor_value']) }}</td>
                                            <td class="num">{{ $currency($row['updates']['creditor_value'] ?? $row['before']['creditor_value']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                @if ($plan['sale'])
                    <h3>نسخة المبيعات #{{ $plan['sale']['before']['id'] }}</h3>
                    <p>سيتم تحديث نسخة المبيعات غير المرسلة مع الفاتورة.</p>
                    <p>التاريخ: {{ $plan['sale']['before']['date'] }} ← {{ $plan['sale']['updates']['date'] }} · الإجمالي: {{ number_format($plan['sale']['before']['final_total'], 2) }} ← {{ number_format($plan['sale']['updates']['final_total'] ?? $plan['sale']['before']['final_total'], 2) }}</p>
                @endif
                @if ($plan['receipts']->isNotEmpty())
                    <div class="section-heading">
                        <h3>سندات القبض المرتبطة</h3>
                        <p>تمثل مبالغ مقبوضة فعلًا؛ لا تتغير مبالغها أو تواريخها بمجرد تغيير قيمة الفاتورة. يظهر فرق المبلغ في حساب العميل.</p>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>السند</th>
                                    <th>التاريخ</th>
                                    <th>المبلغ (ر.س)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($plan['receipts'] as $receipt)
                                    <tr>
                                        <td class="num">#{{ $receipt->id }}</td>
                                        <td class="num">{{ $receipt->date }}</td>
                                        <td class="num">{{ $currency($receipt->value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form method="POST" action="{{ route('dev-superadmin.invoice-corrections.update', $invoice->id) }}">
                    @csrf
                    @method('PUT')
                    @foreach ($plan['input'] as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="preview_token" value="{{ $plan['token'] }}">

                    <div class="save-row">
                        <div class="form-actions">
                            <a class="btn" href="{{ route('dev-superadmin.invoice-corrections.show', $invoice->id) }}">إلغاء</a>
                            <button class="btn btn-primary" type="submit">حفظ القيم المعروضة وتحديث القيود</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    @endif
</main>

@if ($invoice)
    <script>
        // حساب فوري لإجماليات البنود والضريبة — للمراجعة فقط؛ الاعتماد على معاينة الخادم.
        (function () {
            var form = document.getElementById('correction-form');
            if (!form) return;

            var VAT_RATE = 0.15;
            var rows = Array.prototype.slice.call(form.querySelectorAll('[data-line-row]'));
            var discountInput = form.querySelector('#discount');
            var out = {
                subtotal: form.querySelector('#lines-subtotal'),
                estimateSubtotal: form.querySelector('#estimate-subtotal'),
                estimateDiscount: form.querySelector('#estimate-discount'),
                estimateVat: form.querySelector('#estimate-vat'),
                estimateTotal: form.querySelector('#estimate-total')
            };

            function money(value) {
                return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function round(value) {
                return Math.round((value + Number.EPSILON) * 100) / 100;
            }

            function recalc() {
                var subtotal = 0;
                var taxable = 0;

                rows.forEach(function (row) {
                    var cost = parseFloat(row.querySelector('[data-line-cost]').value) || 0;
                    var count = parseInt(row.querySelector('[data-line-count]').value, 10) || 0;
                    var total = round(cost * count);

                    subtotal += total;
                    if (row.hasAttribute('data-taxable')) taxable += total;
                    row.querySelector('[data-line-total]').textContent = money(total);
                });

                var discount = parseFloat(discountInput.value) || 0;
                var vat = round(taxable * VAT_RATE);
                var total = Math.max(0, round(round(subtotal) + vat - discount));

                out.subtotal.textContent = money(round(subtotal));
                out.estimateSubtotal.textContent = money(round(subtotal));
                out.estimateDiscount.textContent = money(discount);
                out.estimateVat.textContent = money(vat);
                out.estimateTotal.textContent = money(total);
            }

            form.addEventListener('input', function (event) {
                if (event.target.matches('[data-line-cost], [data-line-count], #discount')) recalc();
            });

            recalc();
        })();
    </script>
@endif
</body>
</html>

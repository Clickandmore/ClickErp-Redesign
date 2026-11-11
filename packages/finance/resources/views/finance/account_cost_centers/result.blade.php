@if ($data->count() > 0)
    @php
        // عند اختيار مركز له فروع (مثل عقدة الدولة) يجمع الكشف حركات كل ما تحته، ويُظهر مركز كل حركة.
        $showCenter = ($linkSummary['descendant_centers'] ?? 0) > 0;
        $colspan = $showCenter ? 7 : 6;
    @endphp
    @if (($linkSummary['linked_constraints'] ?? 0) > 0)
        <div class="alert alert-info py-2 mb-3">
            <i class="fa fa-link"></i>
            هذا الكشف مرتبط مباشرة بـ {{ $linkSummary['linked_constraints'] }} قيد محاسبي
            عبر تفاصيل القيود، بإجمالي {{ $linkSummary['linked_details'] }} حركة.
            @if ($showCenter)
                ويشمل حركات {{ $linkSummary['descendant_centers'] }} مركز تكلفة فرعي تحته.
            @endif
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-striped statement-table" id="simpleTable4" style="width:100%">
            <thead>
                <tr class="header">
                    <th style="width: 4%" scope="col">#</th>
                    @if ($showCenter)
                        <th style="width: 12%" scope="col">{{ __('finance::finance.fn-cost-centers') }}</th>
                    @endif
                    <th style="width: 13%" scope="col">{{ __('finance::finance.account') }}</th>
                    <th style="width: 9%" scope="col">{{ __('finance::finance.Transaction Date') }}</th>
                    <th style="width: 7%" scope="col">{{ __('finance::finance.Transaction Number') }}</th>
                    <th style="width: 9%" scope="col">{{ __('finance::finance.Transaction Type') }}</th>
                    <th style="width: 29%" scope="col">{{ __('finance::finance.statement') }}</th>
                    <th style="width: 7%" scope="col" class="currency-egp">{{ __('finance::finance.debtor') }}</th>
                    <th style="width: 7%" scope="col" class="currency-egp">{{ __('finance::finance.creditor') }}</th>
                    <th style="width: 7%" scope="col" class="currency-egp">{{ __('finance::finance.Balance') }}</th>
                    <th style="width: 8%" scope="col">{{ __('finance::finance.user') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $balance = 0;
                    $sumCreditorValue = $sumDebtorValue = 0;
                @endphp
                @foreach ($data as $key => $row)
                    @php
                        $balance += (float) $row->debtor_value - (float) $row->creditor_value;
                        $sumDebtorValue += $row->debtor_value;
                        $sumCreditorValue += $row->creditor_value;
                        $trClass =
                            $row->debtor_value == 0 && $row->creditor_value == 0 ? 'currency-usd' : 'currency-egp';
                    @endphp
                    <tr class="{{ $trClass }}">
                        <td class="text-center">{{ ++$key }}</td>
                        @if ($showCenter)
                            <td class="account-cell">{{ $row->cost_center_title ?? '' }}</td>
                        @endif
                        <td class="account-cell">{{ @$row->account_name }}</td>
                        <td class="text-center">{{ $row->date }}</td>
                        <td class="text-center">
                            <a href="{{ route('finance.constraints.show', $row->constraint_id) }}" target="_blank"
                                rel="noopener" title="فتح القيد المحاسبي">
                                {{ $row->constraint_id }} <i class="fa fa-external-link"></i>
                            </a>
                        </td>
                        <td class="text-center">{{ getEnumValue('constraint_types', $row->constraints_type) }}</td>
                        <td class="statement-cell">{{ $row->statement }}</td>
                        <td class="currency-egp number-cell">{{ number_format((float) $row->debtor_value, 2) }}</td>
                        <td class="currency-egp number-cell">{{ number_format((float) $row->creditor_value, 2) }}</td>
                        <td class="currency-egp number-cell">{{ number_format((float) $balance, 2) }}</td>
                        <td class="text-center user-cell">{{ $row->name }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $colspan }}" class="text-center">{{ __('finance::finance.Balance') }}</td>
                    <td class="currency-egp number-cell">{{ number_format((float) $sumDebtorValue, 2) }}</td>
                    <td class="currency-egp number-cell">{{ number_format((float) $sumCreditorValue, 2) }}</td>
                    <td class="currency-egp number-cell">{{ number_format((float) $balance, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="report-scroll-hint">مرر الجدول يمينًا ويسارًا عند ضيق الشاشة لعرض جميع الأعمدة.</div>
@else
    @php
        $center = $linkSummary['center'] ?? null;
        $legacyBalanceType = ($center->legacy_balance_type ?? null) === 'creditor' ? 'دائن' : 'مدين';
    @endphp
    <div role="alert" class="alert alert-warning empty-state text-start">
        <div class="fw-bold mb-2">
            <i class="fa fa-unlink"></i>
            لا توجد تفاصيل قيود مرتبطة بمركز التكلفة المحدد ضمن الفترة الحالية.
        </div>

        @if ($center && $center->legacy_balance !== null)
            <div class="mb-2">
                رصيد الظاهر في شجرة المراكز هو رصيد تاريخي مستورد بقيمة
                <strong>{{ number_format((float) $center->legacy_balance, 2) }} {{ $legacyBalanceType }}</strong>،
                وليس قيداً محاسبياً بحد ذاته.
            </div>
        @endif

        @if (($linkSummary['all_constraints'] ?? 0) === 0)
            <div class="mb-2">
                لا توجد قيود مالية مستوردة في النظام حالياً؛ لذلك لا يمكن إظهار القيد الذي نتج عنه هذا الرصيد.
            </div>
            @if (Route::has('reports.import.index'))
                <a href="{{ route('reports.import.index') }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-upload"></i> الانتقال إلى استيراد القيود المالية
                </a>
            @endif
        @elseif (($linkSummary['linked_constraints'] ?? 0) === 0)
            <div>
                الربط الصحيح يتم بحفظ رقم المركز في
                <code>cost_center_id</code> داخل سطر القيد. عند استيراد قيود الرافدين يحاول النظام الربط
                من رقم طلب الاستقدام الموجود في بيان القيد، ولا ينشئ ارتباطاً تخمينياً عند غياب الرقم.
            </div>
        @else
            <div>توجد قيود مرتبطة بالمركز، لكن مرشح التاريخ أو نوع الحركة الحالي استبعدها. جرّب إزالة تاريخ البداية أو
                اختيار كل الحركات.</div>
        @endif
    </div>
@endif

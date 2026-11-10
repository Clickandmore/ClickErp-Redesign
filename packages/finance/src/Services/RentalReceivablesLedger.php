<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;

/**
 * كشف حساب كل عقد تأجير حتى تاريخ معيّن، مبني على الحركة الفعلية لا على أرقام
 * محفوظة في الفاتورة.
 *
 * لماذا؟ حقلا paid_amount / remaining_amount في الفاتورة لقطة لا تتحدّث مع
 * سندات ميسر والقبض الإضافي والاسترداد، ولا تعرف فواتير وإشعارات الإنهاء
 * القديمة. المرجع الفعلي هو حساب العميل في دفتر الأستاذ، فيُبنى كشف العقد هكذا:
 *
 *   1. المستندات: الفاتورة والتمديد وتسوية الإغلاق. المرحَّل منها يؤخذ بقيمته في
 *      الأستاذ، وغير المرحَّل بقيمته في المستند (حتى لا يختفي دين لم يُقيَّد بعد).
 *   2. كل قيد آخر على حساب العميل مربوط بالعقد: سندات القبض والصرف (ومنها ميسر)،
 *      وقيود النظام القديم برقم طلب التأجير (إنهاء مبكر/متأخر، إشعارات).
 *   3. الحركات على حساب العميل غير المربوطة بأي عقد (قيود يدوية، سندات بلا عقد)
 *      تُوزَّع على عقوده — للعملاء الذين ليس لهم استقدام أو نقل كفالة فقط، لأن
 *      حسابهم لا يحمل غير التأجير. بذلك يساوي مجموع عقودهم رصيدهم في الأستاذ.
 *
 * التوزيع: دفعة معروفة المستند تُطبَّق عليه أولاً، ثم بقية الدفعات والإشعارات
 * الدائنة على أقدم رصيد مفتوح في العقد، ثم ما زاد على عقد يغطي عقود العميل
 * الأخرى، والباقي يبقى رصيداً دائناً للعميل.
 */
class RentalReceivablesLedger
{
    /** أنواع فواتير النظام القديم التي تخص التأجير لا الاستقدام. */
    public const LEGACY_RENTAL_TYPES = ['تأجير', 'إنهاء تأجير مبكر', 'انهاء تأجير متأخر'];

    public const TYPE_LABELS = [
        'invoice' => 'فاتورة أساسية',
        'extension' => 'تمديد',
        'adjustment' => 'تسوية إغلاق',
        'legacy' => 'قيد من النظام القديم على العقد',
        'refund' => 'مبلغ مسترد للعميل',
        'unlinked' => 'قيد على حساب العميل نُسب لهذا العقد',
    ];

    /** حالات السداد بالعربي للعرض والتصدير. */
    public const PAYMENT_STATUS_LABELS = [
        'paid' => 'مسدد بالكامل',
        'partial' => 'مسدد جزئياً',
        'unpaid' => 'غير مسدد',
        'later' => 'غير مسدد (الدفع لاحقاً)',
        'pending' => 'غير مسدد (معلق)',
        'bank_pending' => 'بانتظار التحويل البنكي',
        'cash' => 'غير مسدد',
    ];

    public static function paymentStatusLabel(?string $status): string
    {
        return self::PAYMENT_STATUS_LABELS[$status ?? 'unpaid'] ?? ($status ?: 'غير مسدد');
    }

    private const DOC_ORDER = ['invoice' => 0, 'extension' => 1, 'adjustment' => 2, 'legacy' => 3, 'unlinked' => 4, 'refund' => 5];

    /** @var array<string, Collection> */
    private array $memo = [];

    /**
     * كل عقود التأجير مع أرصدتها حتى $to، بلا شرط فرع (التوزيع على مستوى العميل
     * يحتاج كل عقوده). استخدم visibleIds() لتقييد العرض.
     *
     * @return Collection<int, object>
     */
    public function contracts(string $to): Collection
    {
        return $this->memo[$to] ??= $this->build($to);
    }

    /** معرّفات العقود المسموح بعرضها للمستخدم الحالي حسب فرعه. */
    public function visibleIds(): array
    {
        return BranchReportFilter::apply(DB::table('rental_contracts'), 'rental_contracts')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * الأرصدة المفتوحة (ما بقي من كل مطالبة بعد التوزيع) للعقود المرئية.
     *
     * @return Collection<int, object>
     */
    public function openItems(string $to, ?string $from = null): Collection
    {
        $visible = array_flip($this->visibleIds());

        return $this->contracts($to)
            ->filter(fn ($contract) => isset($visible[$contract->id]))
            ->flatMap(fn ($contract) => collect($contract->items)
                ->filter(fn ($item) => $item->remaining_amount > 0.005)
                ->filter(fn ($item) => $from === null || substr((string) $item->date, 0, 10) >= $from)
                ->map(fn ($item) => $this->presentItem($item, $contract)))
            ->values();
    }

    /** المطالبات (فواتير وتمديدات) الصادرة في الفترة مع ما سُدِّد منها. */
    public function billedItems(string $from, string $to): Collection
    {
        $visible = array_flip($this->visibleIds());

        return $this->contracts($to)
            ->filter(fn ($contract) => isset($visible[$contract->id]))
            ->flatMap(fn ($contract) => collect($contract->items)
                ->filter(fn ($item) => in_array($item->document_type, ['invoice', 'extension'], true))
                ->filter(fn ($item) => substr((string) $item->date, 0, 10) >= $from)
                ->map(fn ($item) => $this->presentItem($item, $contract)))
            ->values();
    }

    private function presentItem(object $item, object $contract): object
    {
        $row = clone $item;
        $row->rental_contract_id = $contract->id;
        $row->contract_number = $contract->contract_number;
        $row->client_id = $contract->user_id;
        $row->client_account_id = $contract->account_id;
        $row->client_name = $contract->client_name;
        $row->client_phone = $contract->client_phone;
        $row->client_identity = $contract->client_identity;
        $row->invoiced_at = $item->date;
        $row->created_at = $item->date;

        return $row;
    }

    /* ------------------------------------------------------------------ */

    private function build(string $to): Collection
    {
        $contracts = $this->loadContracts($to);
        if ($contracts->isEmpty()) {
            return $contracts;
        }

        $links = $this->loadLinks();
        $lines = $this->loadClientLines($to);
        $mixedUsers = $this->mixedUsers();
        $otherBusiness = $this->otherBusinessConstraints();
        $legacyTypes = DB::table('invoices')->whereNotNull('legacy_invoice_number')
            ->pluck('legacy_invoice_type', 'legacy_invoice_number')
            ->mapWithKeys(fn ($type, $number) => [(string) $number => (string) $type])->all();
        $accountOwner = [];
        foreach ($contracts as $contract) {
            if ($contract->account_id) {
                $accountOwner[(int) $contract->account_id] = (int) $contract->user_id;
            }
        }

        // قيود حساب العميل: مربوطة بعقد من نفس العميل، أو غير مربوطة.
        $linked = [];   // contract id => cid => line
        $unlinked = []; // user id => list of lines
        $accountBalance = [];
        $accountPending = [];
        foreach ($lines as $line) {
            $account = (int) $line->account_id;
            $userId = $accountOwner[$account] ?? null;
            if ($userId === null) {
                continue;
            }
            $net = round((float) $line->dr - (float) $line->cr, 2);
            if ((int) $line->pending) {
                $accountPending[$userId] = round(($accountPending[$userId] ?? 0) + $net, 2);
            } else {
                $accountBalance[$userId] = round(($accountBalance[$userId] ?? 0) + $net, 2);
            }
            $link = $links[(int) $line->cid] ?? null;
            $contract = $link ? $contracts->get($link['contract']) : null;
            if ($contract && (int) $contract->account_id === $account) {
                $linked[$contract->id][(int) $line->cid] = (object) [
                    'cid' => (int) $line->cid, 'date' => $line->date, 'net' => $net,
                    'statement' => $line->statement, 'link' => $link, 'pending' => (int) $line->pending,
                ];
            } elseif (abs($net) > 0.005) {
                $unlinked[$userId][] = (object) [
                    'cid' => (int) $line->cid, 'date' => $line->date, 'net' => $net, 'statement' => $line->statement,
                    'other' => isset($otherBusiness[(int) $line->cid])
                        || (isset($mixedUsers[$userId]) && $this->isOtherBusinessStatement((string) $line->statement, $legacyTypes)),
                ];
            }
        }

        $invoices = DB::table('rental_contract_invoices as r')
            ->whereDate('r.created_at', '<=', $to)
            ->get(['r.id', 'r.rental_contract_id', 'r.total_due', 'r.paid_amount', 'r.payment_type', 'r.payment_status',
                'r.created_at', 'r.receipt_bond_id', 'r.receipt_constraint_id'])
            ->groupBy('rental_contract_id');
        $extensions = DB::table('rental_contract_extensions')->whereNotNull('extension_value')
            ->whereDate('created_at', '<=', $to)
            ->get(['id', 'rental_contract_id', 'extension_value', 'payment_amount', 'payment_type', 'payment_status',
                'created_at', 'receipt_bond_id', 'receipt_constraint_id'])
            ->groupBy('rental_contract_id');
        $adjustments = DB::table('rental_financial_adjustments')
            ->whereIn('type', ['income', 'expense'])
            ->whereDate(DB::raw('COALESCE(issue_date, created_at)'), '<=', $to)
            ->get(['id', 'rental_contract_id', 'type', 'amount', 'label', 'context', DB::raw('COALESCE(issue_date, created_at) as doc_date')])
            ->groupBy('rental_contract_id');
        $bondReceipts = DB::table('fn_bonds')->whereNotNull('rental_contract_id')->whereNotNull('constraint_id')
            ->pluck('id', 'constraint_id')->all();

        foreach ($contracts as $contract) {
            $this->buildContract(
                $contract,
                $invoices->get($contract->id, collect()),
                $extensions->get($contract->id, collect()),
                $adjustments->get($contract->id, collect()),
                $linked[$contract->id] ?? [],
                $bondReceipts
            );
        }

        // توزيع غير المربوط على عقود العميل (تأجير فقط)، ثم المقاصة بين عقوده.
        $groups = $contracts->filter(fn ($c) => $c->user_id)->groupBy('user_id')->all();
        foreach ($contracts->filter(fn ($c) => !$c->user_id) as $orphan) {
            $groups['orphan-' . $orphan->id] = collect([$orphan]);
        }
        foreach ($groups as $userId => $own) {
            $own = $own->keyBy('id');
            $userId = (int) $userId;
            $mixed = $userId === 0 || isset($mixedUsers[$userId]);
            $extraCredits = [];
            if ($userId !== 0) {
                foreach ($unlinked[$userId] ?? [] as $line) {
                    if ($line->other) {
                        continue;
                    }
                    if ($line->net > 0) {
                        $target = $this->contractForDate($own, $line->date);
                        $target->items[] = $this->item('unlinked', $line->cid, $line->date, $line->net, null, null, $line->statement);
                        $target->movements[] = $this->movement($line->date, self::TYPE_LABELS['unlinked'], $line->statement, $line->net, 0);
                        $this->settle($target);
                    } else {
                        $extraCredits[] = ['amount' => -$line->net, 'origin' => null, 'kind' => 'offset', 'date' => $line->date, 'statement' => $line->statement, 'cid' => $line->cid];
                    }
                }
            }
            $otherLines = $userId !== 0 ? array_values(array_filter($unlinked[$userId] ?? [], fn ($line) => $line->other)) : [];
            $otherResidual = $this->offsetAcrossContracts($own, $extraCredits, $otherLines);
            if ($mixed && $userId !== 0) {
                $this->capToClientAccount($own, ($accountBalance[$userId] ?? 0) + ($accountPending[$userId] ?? 0) - $otherResidual);
            }

            foreach ($own as $contract) {
                $contract->client_mixed = $mixed;
                $contract->client_ledger_balance = $userId && isset($accountBalance[$userId]) ? $accountBalance[$userId] : ($contract->account_id ? 0.0 : null);
                $contract->client_pending = $userId ? (float) ($accountPending[$userId] ?? 0) : 0.0;
                // ما بقي على حساب العميل لقيود الاستقدام أو نقل الكفالة أو التفويض — خارج التأجير.
                $contract->client_other_business = $otherResidual;
                $this->finish($contract);
            }
        }

        return $contracts;
    }

    private function loadContracts(string $to): Collection
    {
        $extensionEnd = DB::table('rental_contract_extensions')->select('rental_contract_id')
            ->selectRaw('COUNT(*) as extension_count, MAX(end_date) as extension_end')->groupBy('rental_contract_id');

        return DB::table('rental_contracts as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('rental_contract_infos as i', 'i.rental_contract_id', '=', 'c.id')
            ->leftJoinSub($extensionEnd, 'e', 'e.rental_contract_id', '=', 'c.id')
            ->orderBy('c.id')
            ->get([
                'c.id', 'c.user_id', 'c.contract_number', 'c.status', 'c.closed_at', 'c.branch_id', 'c.created_at',
                'u.account_id', 'u.name as client_name', 'u.phone as client_phone', 'u.national_num as client_identity',
                'i.ingaz_from as start_date', 'i.ingaz_to as original_end', 'e.extension_count', 'e.extension_end',
            ])
            ->map(function ($contract) {
                $contract->id = (int) $contract->id;
                $contract->user_id = $contract->user_id !== null ? (int) $contract->user_id : null;
                $contract->extension_count = (int) ($contract->extension_count ?? 0);
                $ends = array_filter([$contract->original_end, $contract->extension_end]);
                $contract->effective_end = $ends ? max($ends) : null;
                $contract->items = [];
                $contract->movements = [];
                $contract->pool = [];
                $contract->billed = 0.0;
                $contract->paid = 0.0;
                $contract->adjustments = 0.0;
                $contract->unposted = 0.0;
                $contract->unbacked = 0.0;
                $contract->pending = 0.0;
                $contract->client_cap = 0.0;

                return $contract;
            })
            ->keyBy('id');
    }

    /**
     * cid => [contract, src, doc]. الأولوية: قيد المستند > السند > قيد قديم برقم الطلب.
     *
     * @return array<int, array{contract: int, src: string, doc: ?int}>
     */
    private function loadLinks(): array
    {
        $links = [];
        $add = function ($rows, string $src) use (&$links) {
            $rank = ['doc_invoice' => 0, 'doc_extension' => 0, 'doc_adjustment' => 0, 'bond' => 1, 'legacy' => 2];
            foreach ($rows as $row) {
                $cid = (int) $row->cid;
                if (!$cid || !$row->rc) {
                    continue;
                }
                if (isset($links[$cid]) && $rank[$links[$cid]['src']] <= $rank[$src]) {
                    continue;
                }
                $links[$cid] = ['contract' => (int) $row->rc, 'src' => $src, 'doc' => isset($row->doc) ? (int) $row->doc : null];
            }
        };

        $add(DB::table('rental_contract_invoices')->whereNotNull('invoice_constraint_id')
            ->get(['invoice_constraint_id as cid', 'rental_contract_id as rc', 'id as doc']), 'doc_invoice');
        $add(DB::table('rental_contract_invoices as r')->join('invoices as i', 'i.id', '=', 'r.legacy_invoice_id')
            ->whereNotNull('i.invoice_constraint_id')
            ->get(['i.invoice_constraint_id as cid', 'r.rental_contract_id as rc', 'r.id as doc']), 'doc_invoice');
        // أرقام النظام القديم نصية في جداول بترتيب حروف مختلف، فتُطابق هنا لا في SQL.
        $mirrorNumbers = DB::table('rental_contract_invoices as r')->join('invoices as i', 'i.id', '=', 'r.legacy_invoice_id')
            ->whereNotNull('i.legacy_invoice_number')
            ->get(['i.legacy_invoice_number as number', 'r.rental_contract_id as rc', 'r.id as doc'])
            ->keyBy(fn ($row) => (string) $row->number);
        if ($mirrorNumbers->isNotEmpty()) {
            $add(DB::table('fn_constraints')->where('legacy_invoice_type', 'rental')->whereNotNull('legacy_invoice_number')
                ->get(['id', 'legacy_invoice_number'])
                ->map(fn ($c) => ($m = $mirrorNumbers->get((string) $c->legacy_invoice_number))
                    ? (object) ['cid' => $c->id, 'rc' => $m->rc, 'doc' => $m->doc] : null)
                ->filter(), 'doc_invoice');
        }
        $add(DB::table('rental_contract_extensions')->whereNotNull('invoice_constraint_id')
            ->get(['invoice_constraint_id as cid', 'rental_contract_id as rc', 'id as doc']), 'doc_extension');
        $add(DB::table('rental_financial_adjustments')->whereNotNull('constraint_id')
            ->get(['constraint_id as cid', 'rental_contract_id as rc', 'id as doc']), 'doc_adjustment');
        $add(DB::table('fn_bonds')->whereNotNull('rental_contract_id')->whereNotNull('constraint_id')
            ->get(['constraint_id as cid', 'rental_contract_id as rc', 'id as doc']), 'bond');
        foreach (['rental_contract_invoices', 'rental_contract_extensions'] as $table) {
            $add(DB::table($table)->whereNotNull('receipt_constraint_id')
                ->get(['receipt_constraint_id as cid', 'rental_contract_id as rc']), 'bond');
        }
        $byNumber = DB::table('rental_contracts')->whereNotNull('contract_number')->pluck('id', 'contract_number')
            ->mapWithKeys(fn ($id, $number) => [trim((string) $number) => (int) $id]);
        $byOrder = fn ($rows, string $column) => $rows->map(fn ($row) => ($rc = $byNumber->get(trim((string) $row->{$column})))
            ? (object) ['cid' => $row->cid, 'rc' => $rc] : null)->filter();
        $add($byOrder(DB::table('invoices')->whereNotNull('invoice_constraint_id')->whereNotNull('legacy_order_number')
            ->whereIn('legacy_invoice_type', self::LEGACY_RENTAL_TYPES)
            ->get(['invoice_constraint_id as cid', 'legacy_order_number']), 'legacy_order_number'), 'legacy');
        $add(DB::table('invoices')->whereNotNull('rental_transfer_contract_id')->whereNotNull('invoice_constraint_id')
            ->get(['invoice_constraint_id as cid', 'rental_transfer_contract_id as rc']), 'legacy');
        $add($byOrder(DB::table('fn_constraints')->whereNotNull('legacy_rental_order_number')->whereNull('deleted_at')
            ->get(['id as cid', 'legacy_rental_order_number']), 'legacy_rental_order_number'), 'legacy');

        return $links;
    }

    /** أسطر حسابات عملاء التأجير حتى $to، مجمّعة لكل قيد، مع علامة «بانتظار الاعتماد». */
    private function loadClientLines(string $to): Collection
    {
        $query = DB::table('fn_constraint_details as d')
            ->join('fn_constraints as c', 'c.id', '=', 'd.constraint_id')
            ->join('users as u', 'u.account_id', '=', 'd.account_id')
            ->whereIn('u.id', DB::table('rental_contracts')->whereNotNull('user_id')->select('user_id'))
            ->whereNull('d.deleted_at')->whereNull('c.deleted_at')
            ->whereDate('d.date', '<=', $to);

        // القيود المعلّقة حركة فعلية على العقد، فتدخل الكشف ويُعرض مجموعها منفصلاً.
        $pending = Constraint::hasApprovalColumns()
            ? "MAX(CASE WHEN c.approval_status = '" . Constraint::APPROVAL_APPROVED . "' THEN 0 ELSE 1 END)"
            : '0';

        return $query->groupBy('d.constraint_id', 'd.account_id')
            ->orderBy(DB::raw('MIN(d.date)'))->orderBy('d.constraint_id')
            ->get([
                'd.constraint_id as cid', 'd.account_id',
                DB::raw('MIN(d.date) as date'), DB::raw('SUM(d.debtor_value) as dr'), DB::raw('SUM(d.creditor_value) as cr'),
                DB::raw('MAX(c.statement) as statement'), DB::raw($pending . ' as pending'),
            ]);
    }

    /**
     * قيود تخص أعمالاً غير التأجير (فواتير الاستقدام والتفاويض ونقل الكفالة وسنداتها)،
     * فلا تُوزَّع على عقود التأجير حتى لو كانت على حساب نفس العميل.
     *
     * @return array<int, true>
     */
    private function otherBusinessConstraints(): array
    {
        $ids = collect();
        $constraintColumns = fn (string $table) => array_values(array_filter(
            \Illuminate\Support\Facades\Schema::getColumnListing($table),
            fn ($column) => str_ends_with($column, 'constraint_id')
        ));
        foreach ($constraintColumns('invoices') as $column) {
            $ids = $ids->merge(DB::table('invoices')->whereNotNull($column)
                ->whereNull('rental_transfer_contract_id')
                ->where(fn ($q) => $q->whereNull('legacy_invoice_type')->orWhereNotIn('legacy_invoice_type', self::LEGACY_RENTAL_TYPES))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('rental_contract_invoices as mirror')->whereColumn('mirror.legacy_invoice_id', 'invoices.id'))
                ->pluck($column));
        }
        foreach (['sponsorship_transfers', 'contracts'] as $table) {
            foreach ($constraintColumns($table) as $column) {
                $ids = $ids->merge(DB::table($table)->whereNotNull($column)->pluck($column));
            }
        }
        $ids = $ids->merge(DB::table('fn_bonds')->whereNull('rental_contract_id')->whereNotNull('constraint_id')
            ->where(fn ($q) => $q->whereNotNull('recruitment_contract_id')->orWhereNotNull('sponsorship_transfer_id'))
            ->pluck('constraint_id'));

        return $ids->mapWithKeys(fn ($id) => [(int) $id => true])->all();
    }

    /** عملاء لهم استقدام أو نقل كفالة: حسابهم يحمل غير التأجير (للتنبيه في العرض). */
    private function mixedUsers(): array
    {
        $ids = DB::table('contracts')->whereNotNull('user_id')->distinct()->pluck('user_id');
        foreach (['new_sponsor_id', 'old_sponsor_id'] as $column) {
            $ids = $ids->merge(DB::table('sponsorship_transfers')->whereNotNull($column)->distinct()->pluck($column));
        }

        return $ids->mapWithKeys(fn ($id) => [(int) $id => true])->all();
    }

    /**
     * @param  array<int, object>  $lines  قيود حساب العميل المربوطة بهذا العقد
     * @param  array<int, int>  $bondReceipts  cid => bond id
     */
    private function buildContract(object $contract, Collection $invoices, Collection $extensions, Collection $adjustments, array $lines, array $bondReceipts): void
    {
        $docLines = [];
        $payments = [];
        foreach ($lines as $line) {
            if ($line->pending) {
                $contract->pending += $line->net;
            }
            $src = $line->link['src'];
            if (str_starts_with($src, 'doc_')) {
                $key = substr($src, 4) . ':' . $line->link['doc'];
                $docLines[$key] = ($docLines[$key] ?? 0) + $line->net;
                continue;
            }
            if ($src === 'bond') {
                if ($line->net < 0) {
                    $payments[] = ['amount' => -$line->net, 'cid' => $line->cid, 'bond' => $bondReceipts[$line->cid] ?? null, 'date' => $line->date, 'statement' => $line->statement];
                    $contract->paid += -$line->net;
                    $contract->movements[] = $this->movement($line->date, 'سند قبض / دفعة', $line->statement, 0, -$line->net);
                } elseif ($line->net > 0) {
                    $contract->items[] = $this->item('refund', $line->cid, $line->date, $line->net, null, null, $line->statement);
                    $contract->paid -= $line->net;
                    $contract->movements[] = $this->movement($line->date, self::TYPE_LABELS['refund'], $line->statement, $line->net, 0);
                }
                continue;
            }
            if ($line->net > 0) {
                $contract->items[] = $this->item('legacy', $line->cid, $line->date, $line->net, null, null, $line->statement);
                $contract->billed += $line->net;
                $contract->movements[] = $this->movement($line->date, self::TYPE_LABELS['legacy'], $line->statement, $line->net, 0);
            } elseif ($line->net < 0) {
                $contract->pool[] = ['amount' => -$line->net, 'kind' => 'adjustment', 'date' => $line->date];
                $contract->adjustments += -$line->net;
                $contract->movements[] = $this->movement($line->date, 'إشعار دائن / تسوية على العقد', $line->statement, 0, -$line->net);
            }
        }

        $documents = [];
        foreach ($invoices as $invoice) {
            $documents[] = ['invoice', $invoice, (float) $invoice->total_due, (float) $invoice->paid_amount, $invoice->created_at];
        }
        foreach ($extensions as $extension) {
            $value = (float) $extension->extension_value;
            $paid = $extension->payment_amount !== null
                ? (float) $extension->payment_amount
                : ($extension->payment_status === 'paid' ? $value : 0.0);
            $documents[] = ['extension', $extension, $value, $paid, $extension->created_at];
        }

        $specific = [];
        foreach ($documents as [$type, $doc, $value, $snapshotPaid, $date]) {
            $key = $type . ':' . $doc->id;
            $posted = array_key_exists($key, $docLines);
            $amount = round($posted ? $docLines[$key] : $value, 2);
            if (!$posted) {
                $contract->unposted += $amount;
            }
            if (abs($amount) < 0.005) {
                continue;
            }
            if ($amount < 0) {
                $contract->pool[] = ['amount' => -$amount, 'kind' => 'adjustment', 'date' => $date];
                $contract->adjustments += -$amount;
                continue;
            }
            $label = self::TYPE_LABELS[$type] . ' #' . $doc->id;
            $item = $this->item($type, (int) $doc->id, $date, $amount, $doc->payment_status, $doc->payment_type, $label);
            $contract->items[] = $item;
            $contract->billed += $amount;
            $contract->movements[] = $this->movement($date, $label, $posted ? 'مرحّل في الأستاذ' : 'غير مرحّل في الأستاذ بعد', $amount, 0);

            $backed = $doc->receipt_bond_id || $doc->receipt_constraint_id;
            if ($backed) {
                foreach ($payments as $index => $payment) {
                    if (($doc->receipt_bond_id && (int) $payment['bond'] === (int) $doc->receipt_bond_id)
                        || ($doc->receipt_constraint_id && (int) $payment['cid'] === (int) $doc->receipt_constraint_id)) {
                        $specific[] = [$item, $payment['amount']];
                        unset($payments[$index]);
                    }
                }
            } elseif ($snapshotPaid > 0.005) {
                $specific[] = [$item, min($snapshotPaid, $amount), true];
            }
        }

        // دفعة مسجّلة في المستند بلا سند مربوط به: إن وُجد سند قبض غير محدد المستند
        // على العقد فهو نفسه (لا تُحتسب مرتين)، وإلا فهي دفعة لم يُصدر لها سند.
        $payments = array_values($payments);
        foreach ($specific as $index => $entry) {
            if (empty($entry[2])) {
                continue;
            }
            $need = $entry[1];
            $fromBonds = 0.0;
            foreach ($payments as $p => $payment) {
                if ($need - $fromBonds <= 0.005) {
                    break;
                }
                $take = min($payment['amount'], $need - $fromBonds);
                $fromBonds += $take;
                $payments[$p]['amount'] -= $take;
            }
            $withoutBond = round($need - $fromBonds, 2);
            if ($withoutBond > 0.005) {
                $contract->paid += $withoutBond;
                $contract->unbacked += $withoutBond;
                $contract->movements[] = $this->movement($entry[0]->date, 'دفعة مسجلة في المستند بلا سند قبض', $entry[0]->label, 0, $withoutBond);
            }
        }

        foreach ($adjustments as $adjustment) {
            $key = 'adjustment:' . $adjustment->id;
            $posted = array_key_exists($key, $docLines);
            // المرحَّلة يُؤخذ اتجاهها من قيدها على حساب العميل لا من نوعها المسجل.
            $signed = round($posted
                ? $docLines[$key]
                : ($adjustment->type === 'income' ? 1 : -1) * (float) $adjustment->amount, 2);
            $amount = abs($signed);
            if ($amount < 0.005) {
                continue;
            }
            $label = ($adjustment->label ?: self::TYPE_LABELS['adjustment']) . ' #' . $adjustment->id;
            if (!$posted) {
                $contract->unposted += $signed;
            }
            if ($signed > 0) {
                $contract->items[] = $this->item('adjustment', (int) $adjustment->id, $adjustment->doc_date, $amount, null, null, $label);
                $contract->billed += $amount;
                $contract->movements[] = $this->movement($adjustment->doc_date, $label, 'تسوية مدينة', $amount, 0);
            } else {
                $contract->pool[] = ['amount' => $amount, 'kind' => 'adjustment', 'date' => $adjustment->doc_date];
                $contract->adjustments += $amount;
                $contract->movements[] = $this->movement($adjustment->doc_date, $label, 'إشعار دائن للعميل', 0, $amount);
            }
        }

        foreach ($specific as $entry) {
            $item = $entry[0];
            $apply = min($entry[1], $item->remaining_amount);
            $item->paid_amount = round($item->paid_amount + $apply, 2);
            $item->remaining_amount = round($item->remaining_amount - $apply, 2);
            if ($entry[1] - $apply > 0.005) {
                $contract->pool[] = ['amount' => $entry[1] - $apply, 'kind' => 'payment', 'date' => $item->date];
            }
        }
        foreach ($payments as $payment) {
            if ($payment['amount'] > 0.005) {
                $contract->pool[] = ['amount' => $payment['amount'], 'kind' => 'payment', 'date' => $payment['date']];
            }
        }

        $this->settle($contract);
    }

    /** يطبّق رصيد العقد الدائن على أقدم مطالباته المفتوحة. */
    private function settle(object $contract): void
    {
        usort($contract->items, fn ($a, $b) => [substr((string) $a->date, 0, 10), self::DOC_ORDER[$a->document_type], $a->id]
            <=> [substr((string) $b->date, 0, 10), self::DOC_ORDER[$b->document_type], $b->id]);
        usort($contract->pool, fn ($a, $b) => [$a['kind'] === 'payment' ? 0 : 1, (string) $a['date']] <=> [$b['kind'] === 'payment' ? 0 : 1, (string) $b['date']]);

        foreach ($contract->items as $item) {
            foreach ($contract->pool as $index => $credit) {
                if ($item->remaining_amount <= 0.005) {
                    break;
                }
                if ($credit['amount'] <= 0.005) {
                    continue;
                }
                $apply = min($credit['amount'], $item->remaining_amount);
                $field = $credit['kind'] === 'payment' ? 'paid_amount' : ($credit['kind'] === 'offset' ? 'offset_amount' : 'adjustment_amount');
                $item->{$field} = round($item->{$field} + $apply, 2);
                $item->remaining_amount = round($item->remaining_amount - $apply, 2);
                $contract->pool[$index]['amount'] = round($credit['amount'] - $apply, 2);
            }
        }
        $contract->pool = array_values(array_filter($contract->pool, fn ($credit) => $credit['amount'] > 0.005));
    }

    /**
     * ما زاد على عقد، والحركات الدائنة غير المربوطة، تُطبَّق على أقدم دين مفتوح
     * للعميل كما يفعل حسابه في الأستاذ. ديون الاستقدام/الكفالة المعروفة على نفس
     * الحساب تدخل الترتيب كمطالبات «ظل» لا تُعرض: فالدفعة غير المعروفة تسدد
     * الأقدم أياً كان نوعه، ولا يُحمَّل التأجير ما يخص غيره.
     *
     * @param  Collection<int, object>  $contracts  عقود العميل
     * @param  array<int, object>  $otherLines  قيود الحساب المعروف أنها ليست تأجيراً
     * @return float  ما بقي من صافي تلك القيود خارج التأجير
     */
    private function offsetAcrossContracts(Collection $contracts, array $credits, array $otherLines = []): float
    {
        foreach ($contracts as $contract) {
            foreach ($contract->pool as $credit) {
                $credits[] = ['amount' => $credit['amount'], 'origin' => $contract->id, 'kind' => $credit['kind'], 'date' => $credit['date']];
            }
            $contract->pool = [];
        }

        $phantoms = [];
        $otherCredits = [];
        foreach ($otherLines as $line) {
            if ($line->net > 0) {
                $phantoms[] = (object) ['date' => $line->date, 'remaining_amount' => $line->net, 'id' => $line->cid];
            } else {
                $otherCredits[] = ['amount' => -$line->net, 'origin' => 'other', 'kind' => 'offset', 'date' => $line->date, 'statement' => $line->statement];
            }
        }
        usort($phantoms, fn ($x, $y) => [substr((string) $x->date, 0, 10), $x->id] <=> [substr((string) $y->date, 0, 10), $y->id]);
        // سداد الاستقدام المعروف يسدد ديون الاستقدام أولاً، وما زاد يدخل الترتيب العام.
        foreach ($otherCredits as $index => $credit) {
            foreach ($phantoms as $phantom) {
                if ($otherCredits[$index]['amount'] <= 0.005) {
                    break;
                }
                $apply = min($otherCredits[$index]['amount'], $phantom->remaining_amount);
                $phantom->remaining_amount = round($phantom->remaining_amount - $apply, 2);
                $otherCredits[$index]['amount'] = round($otherCredits[$index]['amount'] - $apply, 2);
            }
            if ($otherCredits[$index]['amount'] > 0.005) {
                $credits[] = $otherCredits[$index];
            }
        }
        if ($credits === []) {
            return round(array_sum(array_map(fn ($p) => $p->remaining_amount, $phantoms)), 2);
        }
        usort($credits, fn ($x, $y) => (string) $x['date'] <=> (string) $y['date']);

        $open = $contracts->flatMap(fn ($contract) => collect($contract->items)
            ->filter(fn ($item) => $item->remaining_amount > 0.005)
            ->map(fn ($item) => [$contract, $item]))
            ->merge(array_map(fn ($phantom) => [null, $phantom], $phantoms))
            ->sortBy(fn ($pair) => substr((string) $pair[1]->date, 0, 10) . sprintf('%012d', $pair[0]->id ?? 0))
            ->values();

        foreach ($credits as $index => $credit) {
            foreach ($open as [$contract, $item]) {
                if ($credits[$index]['amount'] <= 0.005) {
                    break;
                }
                if ($item->remaining_amount <= 0.005) {
                    continue;
                }
                $apply = min($credits[$index]['amount'], $item->remaining_amount);
                $item->remaining_amount = round($item->remaining_amount - $apply, 2);
                $credits[$index]['amount'] = round($credits[$index]['amount'] - $apply, 2);
                if ($contract === null) {
                    continue;
                }
                $field = $credit['origin'] === $contract->id
                    ? ($credit['kind'] === 'payment' ? 'paid_amount' : 'adjustment_amount')
                    : 'offset_amount';
                $item->{$field} = round($item->{$field} + $apply, 2);
                if ($credit['origin'] !== $contract->id) {
                    $contract->movements[] = $this->movement($credit['date'], match (true) {
                        $credit['origin'] === null => 'حركة دائنة على حساب العميل غير مربوطة بعقد',
                        $credit['origin'] === 'other' => 'رصيد دائن من قيود استقدام/كفالة على حساب العميل',
                        default => 'مقاصة من رصيد عقد آخر للعميل',
                    }, $credit['statement'] ?? null, 0, $apply);
                }
            }
        }

        $otherLeftover = 0.0;
        $latest = $contracts->sortBy(fn ($c) => ($c->start_date ?: substr((string) $c->created_at, 0, 10)) . sprintf('%012d', $c->id))->last();
        foreach ($credits as $credit) {
            if ($credit['amount'] <= 0.005) {
                continue;
            }
            if ($credit['origin'] === 'other') {
                $otherLeftover += $credit['amount'];
                continue;
            }
            $holder = $credit['origin'] !== null ? $contracts->get($credit['origin']) : $latest;
            $holder = $holder ?: $latest;
            $holder->credit_balance = round(($holder->credit_balance ?? 0) + $credit['amount'], 2);
            if ($credit['origin'] === null) {
                $holder->movements[] = $this->movement($credit['date'], 'حركة دائنة على حساب العميل غير مربوطة بعقد', $credit['statement'] ?? null, 0, $credit['amount']);
            }
        }

        return round(array_sum(array_map(fn ($p) => $p->remaining_amount, $phantoms)) - $otherLeftover, 2);
    }

    /**
     * قيد قديم على حساب عميل مشترك يُعرف من بيانه: رقم فاتورة/إشعار نوعها غير
     * تأجير في جدول الفواتير، أو نص صريح عن نقل كفالة أو استقدام أو مساند أو تفويض.
     */
    private function isOtherBusinessStatement(string $statement, array $legacyTypes): bool
    {
        if (preg_match('/(?:فاتورة|[اإ]شعار دائن|[اإ]شعار مدين)\s*(?:رقم)?\s*:\s*(\d+)/u', $statement, $match)) {
            $type = $legacyTypes[$match[1]] ?? null;
            if ($type !== null) {
                return !in_array($type, self::LEGACY_RENTAL_TYPES, true);
            }
        }
        if (preg_match('/تأجير|تاجير|[اإ]يجار/u', $statement)) {
            return false;
        }

        return (bool) preg_match('/نقل\s*(?:ال)?كفال|استقدام|مساند|تفويض|تفاويض/u', $statement);
    }

    /**
     * عميل له استقدام وتأجير: بعض قيود حسابه لا يُعرف مصدرها، فلا يُسمح أن يزيد
     * مستحق التأجير عليه عن رصيد حسابه المدين كاملاً، ولا رصيده الدائن على التأجير
     * عن رصيد حسابه الدائن كاملاً. الفرق يظهر تخفيضاً صريحاً (client_cap).
     *
     * @param  Collection<int, object>  $contracts
     */
    private function capToClientAccount(Collection $contracts, float $accountTotal): void
    {
        $accountTotal += $contracts->sum('unposted') - $contracts->sum('unbacked');
        $open = $contracts->sum(fn ($c) => collect($c->items)->sum('remaining_amount'));
        $held = $contracts->sum(fn ($c) => $c->credit_balance ?? 0);
        $net = $open - $held;

        // رصيد دائن على التأجير أكبر من رصيد الحساب الدائن كله: يُخفَّض كذلك.
        $extraCredit = round(min($held, min($accountTotal, 0) - $net), 2);
        foreach ($contracts as $contract) {
            if ($extraCredit <= 0.005 || ($contract->credit_balance ?? 0) <= 0.005) {
                continue;
            }
            $apply = min($extraCredit, $contract->credit_balance);
            $contract->credit_balance = round($contract->credit_balance - $apply, 2);
            $contract->client_cap -= $apply;
            $contract->movements[] = $this->movement(null, 'تخفيض: الرصيد الدائن على التأجير لا يتجاوز رصيد حساب العميل كاملاً', 'العميل لديه استقدام أو نقل كفالة وقيود غير محددة المصدر', $apply, 0);
            $extraCredit = round($extraCredit - $apply, 2);
        }

        $excess = round(min($open, $net - max($accountTotal, 0)), 2);
        if ($excess <= 0.005) {
            return;
        }

        $pairs = $contracts->flatMap(fn ($c) => collect($c->items)->filter(fn ($i) => $i->remaining_amount > 0.005)->map(fn ($i) => [$c, $i]))
            ->sortBy(fn ($pair) => substr((string) $pair[1]->date, 0, 10) . sprintf('%012d', $pair[0]->id));
        foreach ($pairs as [$contract, $item]) {
            if ($excess <= 0.005) {
                break;
            }
            $apply = min($excess, $item->remaining_amount);
            $item->offset_amount = round($item->offset_amount + $apply, 2);
            $item->remaining_amount = round($item->remaining_amount - $apply, 2);
            $contract->client_cap += $apply;
            $contract->movements[] = $this->movement(null, 'تخفيض: مستحق التأجير لا يتجاوز رصيد حساب العميل كاملاً', 'العميل لديه استقدام أو نقل كفالة وقيود غير محددة المصدر', 0, $apply);
            $excess = round($excess - $apply, 2);
        }
    }

    private function contractForDate(Collection $contracts, $date): object
    {
        $day = substr((string) $date, 0, 10);
        $sorted = $contracts->sortBy(fn ($c) => ($c->start_date ?: substr((string) $c->created_at, 0, 10)) . sprintf('%012d', $c->id))->values();
        $covering = $sorted->first(fn ($c) => $c->start_date && $c->start_date <= $day && (!$c->effective_end || $c->effective_end >= $day));
        if ($covering) {
            return $covering;
        }
        $before = $sorted->filter(fn ($c) => ($c->start_date ?: substr((string) $c->created_at, 0, 10)) <= $day)->last();

        return $before ?: $sorted->first();
    }

    private function finish(object $contract): void
    {
        $open = 0.0;
        foreach ($contract->items as $item) {
            $open += $item->remaining_amount;
        }
        $contract->credit_balance = round($contract->credit_balance ?? 0, 2);
        $contract->billed = round($contract->billed, 2);
        $contract->paid = round($contract->paid, 2);
        $contract->adjustments = round($contract->adjustments, 2);
        $contract->unposted = round($contract->unposted, 2);
        $contract->unbacked = round($contract->unbacked, 2);
        $contract->pending = round($contract->pending, 2);
        $contract->client_cap = round($contract->client_cap, 2);
        $contract->remaining = round($open - $contract->credit_balance, 2);
        // صافي ما دخل العقد من حركات غير مربوطة ومن مقاصة عقود العميل الأخرى.
        $contract->offsets = round($contract->remaining - ($contract->billed - $contract->paid - $contract->adjustments), 2);
        $contract->open_since = collect($contract->items)->filter(fn ($item) => $item->remaining_amount > 0.005)->min('date');
        usort($contract->movements, fn ($a, $b) => (string) $a->date <=> (string) $b->date);
        unset($contract->pool);
    }

    private function item(string $type, int $id, $date, float $amount, ?string $status, ?string $paymentType, ?string $label): object
    {
        return (object) [
            'id' => $id,
            'document_type' => $type,
            'label' => $label ?: self::TYPE_LABELS[$type],
            'date' => $date ? (string) $date : null,
            'total_due' => round($amount, 2),
            'paid_amount' => 0.0,
            'adjustment_amount' => 0.0,
            'offset_amount' => 0.0,
            'remaining_amount' => round($amount, 2),
            'original_status' => $status,
            'payment_type' => $paymentType,
        ];
    }

    private function movement($date, string $label, ?string $statement, float $debit, float $credit): object
    {
        return (object) [
            'date' => $date ? substr((string) $date, 0, 10) : null,
            'label' => $label,
            'statement' => $statement ? mb_substr(trim(preg_replace('/\s+/u', ' ', $statement)), 0, 160) : null,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
        ];
    }

    /** حالة السداد المعروضة لمطالبة بعد التوزيع. */
    public static function statusOf(object $item): string
    {
        if ($item->remaining_amount <= 0.005) {
            return 'paid';
        }

        return $item->paid_amount > 0.005 ? 'partial' : ($item->original_status ?: 'unpaid');
    }

    public static function dateOf($value): ?string
    {
        return $value ? Carbon::parse($value)->toDateString() : null;
    }
}

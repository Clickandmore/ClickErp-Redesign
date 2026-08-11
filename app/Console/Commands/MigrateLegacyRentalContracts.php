<?php

namespace App\Console\Commands;

use App\Models\RentalContract;
use App\Models\ingaz\Package;
use App\Services\ingaz\RentalContractFinanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Client as FinanceClient;
use Nami\Finance\Models\ClientCategory;

/**
 * ينقل عقود الإيجار التي أُنشئت على الخادم القديم بعد أخذ النسخة الاحتياطية،
 * والتي تصادمت أرقامها ومعرّفاتها مع سجلات مختلفة أُنشئت على الخادم الجديد.
 *
 * كل القيم تُقرأ مباشرة من القاعدة القديمة — لا شيء مكتوب يدويًا.
 * المعرّفات وأرقام العقود والأكواد المحاسبية وحدها هي التي تُولَّد من جديد،
 * لأن نسخها كما هي يُنتج تكرارًا في دليل الحسابات وفي القيود.
 *
 * الوضع الافتراضي محاكاة: ينفّذ كل شيء داخل معاملة ثم يتراجع عنها.
 */
class MigrateLegacyRentalContracts extends Command
{
    protected $signature = 'migrate:legacy-rental-contracts
        {--source=aqro_old_backup : اسم قاعدة البيانات القديمة}
        {--contracts=303,304,305 : معرّفات العقود في القاعدة القديمة}
        {--user=657 : معرّف العميل الجديد في القاعدة القديمة (اتركه فارغًا إن لم يكن هناك عميل جديد)}
        {--apply : تنفيذ فعلي وحفظ. بدونه محاكاة فقط}
        {--keep-dates=1 : الإبقاء على created_at/updated_at الأصلية}';

    protected $description = 'نقل عقود الإيجار المفقودة من الخادم القديم مع إعادة توليد المعرّفات والأكواد المحاسبية';

    private string $source;
    private bool $apply;
    private bool $keepDates;
    /** @var array<string,string> */
    private array $notes = [];

    public function handle(RentalContractFinanceService $finance): int
    {
        $this->source    = (string) $this->option('source');
        $this->apply     = (bool) $this->option('apply');
        $this->keepDates = (bool) $this->option('keep-dates');

        $contractIds = collect(explode(',', (string) $this->option('contracts')))
            ->map(fn ($v) => (int) trim($v))->filter()->values()->all();
        $legacyUserId = $this->option('user') !== null && $this->option('user') !== ''
            ? (int) $this->option('user')
            : null;

        $this->line('');
        $this->info($this->apply
            ? '=== تنفيذ فعلي — سيتم الحفظ ==='
            : '=== محاكاة — سيتم التراجع عن كل شيء في النهاية ===');
        $this->line('');

        if (!$this->preflight($contractIds, $legacyUserId)) {
            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $newUserId = $legacyUserId ? $this->createClient($legacyUserId) : null;

            $results = [];
            foreach ($contractIds as $legacyId) {
                $results[] = $this->migrateContract($finance, $legacyId, $newUserId);
            }

            $clean = $this->report($results);

            if (!$this->apply) {
                DB::rollBack();
                $this->line('');
                $this->warn('محاكاة — تم التراجع. لم تُحفظ أي بيانات.');
                $this->line('أعد التشغيل مع --apply للتنفيذ الفعلي.');
                return $clean ? self::SUCCESS : self::FAILURE;
            }

            if (!$clean) {
                DB::rollBack();
                $this->error('تم اكتشاف فروقات — تم التراجع ولم يُحفظ شيء.');
                return self::FAILURE;
            }

            DB::commit();
            $this->line('');
            $this->info('تم التنفيذ والحفظ بنجاح.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->line('');
            $this->error('فشل وتم التراجع عن كل شيء:');
            $this->error($e->getMessage());
            $this->line($e->getFile() . ':' . $e->getLine());
            return self::FAILURE;
        }
    }

    // ----------------------------------------------------------- الفحص المسبق

    private function preflight(array $contractIds, ?int $legacyUserId): bool
    {
        $ok = true;

        try {
            DB::select("SELECT 1 FROM `{$this->source}`.rental_contracts LIMIT 1");
        } catch (\Throwable $e) {
            $this->error("تعذر قراءة القاعدة القديمة [{$this->source}]: " . $e->getMessage());
            return false;
        }

        // العقود موجودة في المصدر؟
        $found = DB::table(DB::raw("`{$this->source}`.rental_contracts"))
            ->whereIn('id', $contractIds)->pluck('id')->all();
        $missing = array_diff($contractIds, $found);
        if ($missing) {
            $this->error('عقود غير موجودة في المصدر: ' . implode(', ', $missing));
            $ok = false;
        }

        // هل سبق نقلها؟
        foreach ($contractIds as $id) {
            $number = DB::table(DB::raw("`{$this->source}`.rental_contracts"))
                ->where('id', $id)->value('contract_number');
            if (RentalContract::query()->where('legacy_source', $this->legacyTag($number))->exists()) {
                $this->error("العقد {$number} منقول مسبقًا (legacy_source موجود).");
                $ok = false;
            }
        }

        // العاملات متاحات؟
        $maidIds = DB::table(DB::raw("`{$this->source}`.rental_contracts"))
            ->whereIn('id', $contractIds)->pluck('maid_id')->filter()->all();
        foreach ($maidIds as $maidId) {
            $maid = DB::table('maids')->where('id', $maidId)->first(['id', 'name', 'status', 'available']);
            if (!$maid) {
                $this->error("العاملة {$maidId} غير موجودة في القاعدة الجديدة.");
                $ok = false;
            } elseif (!$maid->available) {
                $this->warn("تنبيه: العاملة {$maidId} ({$maid->name}) غير متاحة حاليًا (status={$maid->status}).");
            }
        }

        // أسعار الباقات متطابقة؟
        $packageIds = DB::table(DB::raw("`{$this->source}`.rental_contract_infos"))
            ->whereIn('rental_contract_id', $contractIds)->pluck('package_id')->filter()->unique()->all();
        foreach ($packageIds as $pid) {
            $old = DB::table(DB::raw("`{$this->source}`.packages"))->where('id', $pid)->first();
            $new = DB::table('packages')->where('id', $pid)->first();
            if (!$new) {
                $this->error("الباقة {$pid} غير موجودة في القاعدة الجديدة.");
                $ok = false;
            } elseif ($old && (string) $old->price_with_tax !== (string) $new->price_with_tax) {
                $this->error("سعر الباقة {$pid} مختلف: قديم {$old->price_with_tax} / جديد {$new->price_with_tax}");
                $ok = false;
            }
        }

        // العميل الجديد غير موجود مسبقًا؟
        if ($legacyUserId) {
            $oldUser = DB::table(DB::raw("`{$this->source}`.users"))->where('id', $legacyUserId)->first();
            if (!$oldUser) {
                $this->error("العميل {$legacyUserId} غير موجود في المصدر.");
                $ok = false;
            } else {
                $clash = DB::table('users')
                    ->where(function ($q) use ($oldUser) {
                        $q->where('phone', $oldUser->phone);
                        if (filled($oldUser->national_num)) {
                            $q->orWhere('national_num', $oldUser->national_num);
                        }
                    })->first(['id', 'name']);
                if ($clash) {
                    $this->error("العميل موجود مسبقًا في القاعدة الجديدة: #{$clash->id} {$clash->name}");
                    $ok = false;
                }
            }
        }

        $this->line($ok ? '✔ الفحص المسبق سليم' : '✘ الفحص المسبق فشل');
        $this->line('');

        return $ok;
    }

    // -------------------------------------------------------------- العميل

    private function createClient(int $legacyUserId): int
    {
        $old = (array) DB::table(DB::raw("`{$this->source}`.users"))->where('id', $legacyUserId)->first();

        $parent = Account::query()->where('type', AccountTypesEnum::Clients->value)->lockForUpdate()->first();
        if (!$parent) {
            throw new \RuntimeException('حساب العملاء الأب غير موجود في دليل الحسابات.');
        }

        $lastCode = Account::query()->where('parent_id', $parent->id)->max('code');
        if (!$lastCode) {
            $lastCode = (int) ($parent->code . '00000');
        }

        $oldAccount = $old['account_id']
            ? (array) DB::table(DB::raw("`{$this->source}`.fn_accounts"))->where('id', $old['account_id'])->first()
            : [];

        $account = Account::query()->create([
            'name'           => $old['name'],
            'operation_type' => $parent->operation_type,
            'status'         => $parent->status,
            'level'          => $parent->level + 1,
            'type'           => AccountTypesEnum::Client->value,
            'parent_id'      => $parent->id,
            'code'           => $lastCode + 1,
            'created_by_id'  => $oldAccount['created_by_id'] ?? null,
        ]);

        $this->notes[] = sprintf(
            'حساب العميل: كود قديم %s → كود جديد %s',
            $oldAccount['code'] ?? '-', $account->code
        );

        FinanceClient::query()->create([
            'is_company'         => ($old['client_type'] ?? 'person') === 'company' ? 1 : 0,
            'client_category_id' => $this->financeClientCategoryId(),
            'name'               => $old['name'],
            'phone'              => $old['phone'],           // كما هو في المصدر، بلا تطبيع
            'email'              => $old['email'] ?? '',
            'address'            => $old['address'] ?? '',
            'city'               => $old['city'] ?? null,
            'tax_number'         => ($old['client_type'] ?? 'person') === 'company'
                                        ? ($old['tax_number'] ?: null)
                                        : ($old['national_num'] ?: null),
            'code'               => $account->code,
            'days_credit_period' => 30,
            'credit_limit'       => 30,
            'created_by_id'      => $oldAccount['created_by_id'] ?? null,
            'account_id'         => $account->id,
        ]);

        // صف المستخدم: نسخة طبق الأصل عدا المعرّف وربط الحساب
        $newUserId = $this->insertCopy('users', $old, [
            'account_id' => $account->id,
        ]);

        $this->line("العميل: {$old['name']}  (قديم #{$legacyUserId} → جديد #{$newUserId})");

        return $newUserId;
    }

    private function financeClientCategoryId(): int
    {
        $existing = ClientCategory::query()->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $data = ['name' => __('ingaz.general')];
        if (Schema::hasColumn('fn_client_categories', 'created_by_id')) {
            $data['created_by_id'] = null;
        }

        return (int) ClientCategory::query()->create($data)->id;
    }

    // -------------------------------------------------------------- العقود

    private function migrateContract(RentalContractFinanceService $finance, int $legacyId, ?int $newUserId): array
    {
        $oldContract = (array) DB::table(DB::raw("`{$this->source}`.rental_contracts"))->where('id', $legacyId)->first();
        $oldInfo     = (array) DB::table(DB::raw("`{$this->source}`.rental_contract_infos"))->where('rental_contract_id', $legacyId)->first();
        $oldInvoice  = (array) DB::table(DB::raw("`{$this->source}`.rental_contract_invoices"))->where('rental_contract_id', $legacyId)->first();

        if (!$oldInfo) {
            throw new \RuntimeException("العقد {$legacyId} بلا بيانات مالية في المصدر.");
        }

        // العميل: يُعاد ربطه فقط إن كان العميل نفسه قد نُقل الآن
        $userId = ($newUserId && (int) $oldContract['user_id'] === (int) $this->option('user'))
            ? $newUserId
            : (int) $oldContract['user_id'];

        $newContractId = $this->insertCopy('rental_contracts', $oldContract, [
            'user_id'         => $userId,
            'contract_number' => $this->nextContractNumber(),
            'legacy_source'   => $this->legacyTag($oldContract['contract_number']),
            'legacy_order_id' => null,
        ]);

        $this->insertCopy('rental_contract_infos', $oldInfo, [
            'rental_contract_id' => $newContractId,
        ]);

        /** @var RentalContract $contract */
        $contract = RentalContract::query()->findOrFail($newContractId);
        $contract->loadMissing(['user', 'maid.nationality', 'info']);

        $package = $oldInfo['package_id'] ? Package::query()->find($oldInfo['package_id']) : null;
        $receiptAccountId = $oldInvoice['receipt_account_id'] ?? null;

        // الفاتورة + قيدها + مركز التكلفة، بأكواد يولّدها التطبيق
        $finance->createForContract($contract, $package, $receiptAccountId ? (int) $receiptAccountId : null);

        // سند القبض إن كان هناك مبلغ محصّل فعلًا
        $paid = (float) ($oldInvoice['paid_amount'] ?? 0);
        if ($paid > 0) {
            $contract->refresh()->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);
            $finance->recordPaymentForContract(
                $contract,
                $paid,
                (string) ($oldInvoice['payment_type'] ?? 'cash'),
                $oldInvoice['paid_at'] ?? $oldInvoice['updated_at'] ?? null,
                $receiptAccountId ? (int) $receiptAccountId : null
            );
        }

        $contract->refresh()->loadMissing('financeInvoice');

        return [
            'legacy_id'     => $legacyId,
            'legacy_number' => $oldContract['contract_number'],
            'new_id'        => $newContractId,
            'new_number'    => $contract->contract_number,
            'old_invoice'   => $oldInvoice,
            'new_invoice'   => $contract->financeInvoice ? $contract->financeInvoice->toArray() : null,
        ];
    }

    // ------------------------------------------------------------ المقارنة

    private function report(array $results): bool
    {
        $compare = [
            'subtotal', 'discount', 'vat_amount', 'total_due',
            'paid_amount', 'remaining_amount', 'payment_status', 'is_paid',
        ];

        $clean = true;
        $rows  = [];

        foreach ($results as $r) {
            $old = $r['old_invoice'];
            $new = $r['new_invoice'];

            if (!$new) {
                $clean = false;
                $rows[] = [$r['legacy_number'], $r['new_number'], 'الفاتورة', '—', '—', '✘ لم تُنشأ'];
                continue;
            }

            foreach ($compare as $field) {
                $o = $old[$field] ?? null;
                $n = $new[$field] ?? null;

                $same = is_numeric($o) && is_numeric($n)
                    ? abs((float) $o - (float) $n) < 0.005
                    : (string) $o === (string) $n;

                if (!$same) {
                    $clean = false;
                }

                $rows[] = [
                    $r['legacy_number'] . ' → ' . $r['new_number'],
                    $field,
                    (string) $o,
                    (string) $n,
                    $same ? '✔' : '✘ فرق',
                ];
            }
        }

        $this->line('');
        $this->table(['العقد', 'الحقل', 'القديم', 'الجديد', ''], $rows);

        $this->line('');
        foreach ($results as $r) {
            $inv = $r['new_invoice'];
            $this->line(sprintf(
                '%s → %s (id %d)   قيد الفاتورة #%s   سند القبض #%s   قيد القبض #%s',
                $r['legacy_number'], $r['new_number'], $r['new_id'],
                $inv['invoice_constraint_id'] ?? '-',
                $inv['receipt_bond_id'] ?? '-',
                $inv['receipt_constraint_id'] ?? '-'
            ));
        }

        foreach ($this->notes as $note) {
            $this->line('• ' . $note);
        }

        $this->line('');
        $this->line($clean ? '✔ كل القيم مطابقة للمصدر' : '✘ توجد فروقات — راجع الجدول أعلاه');

        return $clean;
    }

    // -------------------------------------------------------------- أدوات

    /**
     * ينسخ صفًا من القاعدة القديمة إلى الجديدة عمودًا بعمود، مع تجاهل الأعمدة
     * غير الموجودة في الوجهة، ويعيد المعرّف الجديد.
     */
    private function insertCopy(string $table, array $old, array $overrides): int
    {
        $targetColumns = Schema::getColumnListing($table);

        $payload = [];
        foreach ($old as $column => $value) {
            if ($column === 'id' || !in_array($column, $targetColumns, true)) {
                continue;
            }
            $payload[$column] = $value;
        }

        foreach ($overrides as $column => $value) {
            if (in_array($column, $targetColumns, true)) {
                $payload[$column] = $value;
            }
        }

        if (!$this->keepDates) {
            unset($payload['created_at'], $payload['updated_at']);
            $payload['created_at'] = now();
            $payload['updated_at'] = now();
        }

        $skipped = array_diff(array_keys($old), $targetColumns);
        if ($skipped) {
            $this->notes[] = "أعمدة غير موجودة في {$table} بالقاعدة الجديدة وتم تجاهلها: " . implode(', ', $skipped);
        }

        return (int) DB::table($table)->insertGetId($payload);
    }

    private function nextContractNumber(): string
    {
        $year = date('Y');
        $last = RentalContract::query()->whereYear('created_at', $year)->orderByDesc('id')->first();
        $n    = $last ? ((int) substr($last->contract_number, -4)) + 1 : 1;

        return 'RC-' . $year . '-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    private function legacyTag(?string $legacyNumber): string
    {
        return $this->source . ':' . $legacyNumber;
    }
}

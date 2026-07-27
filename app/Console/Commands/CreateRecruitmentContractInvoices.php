<?php

namespace App\Console\Commands;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\Contract;
use App\Services\ingaz\InvoiceCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use RuntimeException;
use Throwable;

class CreateRecruitmentContractInvoices extends Command
{
    private const CONFIRMATION = 'CREATE-INVOICES-WITH-ACCOUNTING';

    protected $signature = 'recruitment-contracts:create-missing-invoices
        {--from= : بداية نطاق تاريخ إضافة العقد بصيغة Y-m-d؛ الافتراضي 1 مارس من السنة الحالية}
        {--to= : نهاية نطاق تاريخ إضافة العقد بصيغة Y-m-d؛ الافتراضي اليوم}
        {--contract=* : أرقام عقود محددة داخل النطاق للاختبار أو التنفيذ المحدود}
        {--send-to-zatca : إنشاء فواتير المبيعات المعلقة لهيئة الزكاة أيضاً}
        {--apply : تنفيذ الإنشاء؛ بدونه يعمل الأمر في وضع المعاينة فقط}
        {--confirm= : عبارة التأكيد المطلوبة مع --apply}
        {--expected-database= : اسم قاعدة البيانات المسموح بالتنفيذ عليها، ويجب أن يطابق الاتصال الحالي تماماً}
        {--expected-database-fingerprint= : بصمة اتصال قاعدة البيانات كما تظهر في المعاينة}';

    protected $description = 'إنشاء الفواتير الناقصة وقيودها وسندات قبضها لعقود الاستقدام ضمن نطاق زمني، بصورة ذرية وآمنة';

    public function handle(
        InvoiceController $invoiceController,
        InvoiceCalculationService $calculator,
        ObjService $constraintService,
        YearService $yearService,
        BondService $bondService
    ): int {
        try {
            [$from, $to] = $this->resolveDates();
            $contractIds = $this->resolveContractIds();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $currentDatabase = (string) DB::connection()->getDatabaseName();
        $currentDatabaseFingerprint = $this->databaseFingerprint();
        if ($this->option('apply')) {
            $expectedDatabase = trim((string) $this->option('expected-database'));
            $expectedFingerprint = trim((string) $this->option('expected-database-fingerprint'));
            if ($expectedDatabase === '') {
                $this->error('رفض التنفيذ: يجب تحديد اسم قاعدة البيانات المسموح بها عبر --expected-database.');

                return self::INVALID;
            }
            if ($expectedFingerprint === '') {
                $this->error('رفض التنفيذ: يجب تمرير بصمة اتصال قاعدة البيانات من المعاينة.');

                return self::INVALID;
            }
            if (!hash_equals($expectedDatabase, $currentDatabase)) {
                $this->error('رفض التنفيذ: قاعدة البيانات الحالية لا تطابق القاعدة المتوقعة.');
                $this->line('الحالية: ' . $currentDatabase);
                $this->line('المتوقعة: ' . $expectedDatabase);

                return self::FAILURE;
            }
            if (!hash_equals($expectedFingerprint, $currentDatabaseFingerprint)) {
                $this->error('رفض التنفيذ: بصمة اتصال قاعدة البيانات لا تطابق الاتصال الحالي.');
                $this->line('الحالية: ' . $currentDatabaseFingerprint);
                $this->line('المتوقعة: ' . $expectedFingerprint);

                return self::FAILURE;
            }
        }

        $excludedShelterCount = $this->excludedShelterCount($from, $to, $contractIds);
        $contracts = $this->contracts($from, $to, $contractIds);
        $preflight = $this->preflight(
            $contracts,
            $invoiceController,
            $calculator,
            $yearService
        );
        $this->displayPreflight(
            $from,
            $to,
            $contracts,
            $preflight,
            $excludedShelterCount,
            $currentDatabase,
            $currentDatabaseFingerprint
        );

        if (!$this->option('apply')) {
            $this->newLine();
            $this->warn('وضع المعاينة: لم يتم إنشاء فواتير أو قيود أو سندات، ولم تُعدّل قاعدة البيانات.');
            if ($preflight['global_errors'] !== [] || $preflight['blockers']->isNotEmpty()) {
                $this->error('المعاينة تحتوي موانع. أصلحها ثم أعد تشغيل المعاينة قبل التنفيذ.');
            } elseif ($preflight['ready']->isNotEmpty()) {
                $this->line('للتنفيذ بعد أخذ نسخة احتياطية:');
                $this->line($this->applyCommand(
                    $from,
                    $to,
                    $contractIds,
                    $currentDatabase,
                    $currentDatabaseFingerprint
                ));
            }

            return self::SUCCESS;
        }

        if ((string) $this->option('confirm') !== self::CONFIRMATION) {
            $this->error('رفض التنفيذ: عبارة التأكيد غير صحيحة.');
            $this->line('--confirm=' . self::CONFIRMATION);

            return self::INVALID;
        }

        if ($preflight['global_errors'] !== [] || $preflight['blockers']->isNotEmpty()) {
            $this->error('رفض التنفيذ: يجب أن تكون المعاينة خالية من جميع الموانع لضمان عدم إنشاء دفعة جزئية.');

            return self::FAILURE;
        }

        if ($preflight['ready']->isEmpty()) {
            $this->info('لا توجد فواتير ناقصة جاهزة للإنشاء ضمن النطاق.');

            return self::SUCCESS;
        }

        $lockDirectory = storage_path('framework');
        if (!is_dir($lockDirectory) && !mkdir($lockDirectory, 0775, true) && !is_dir($lockDirectory)) {
            $this->error('تعذر إنشاء مجلد قفل التنفيذ.');

            return self::FAILURE;
        }

        $lockHandle = fopen($lockDirectory . '/recruitment-invoice-creation.lock', 'c');
        if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
            if (is_resource($lockHandle)) {
                fclose($lockHandle);
            }
            $this->error('يوجد تنفيذ آخر للأمر قيد العمل. لم يتم إجراء أي تعديل.');

            return self::FAILURE;
        }

        $created = [];

        try {
            DB::transaction(function () use (
                $preflight,
                $invoiceController,
                $constraintService,
                $yearService,
                $bondService,
                &$created
            ): void {
                foreach ($preflight['ready'] as $row) {
                    $result = $invoiceController->createRecruitmentContractInvoice(
                        $row['contract_id'],
                        $row['payload'],
                        Carbon::parse($row['invoice_date'], config('app.timezone')),
                        (bool) $this->option('send-to-zatca'),
                        $constraintService,
                        $yearService,
                        $bondService,
                        true
                    );

                    $invoice = $result['invoice'];
                    $constraint = $result['invoice_constraint'];
                    $receiptBond = $result['receipt_bond'];

                    $created[] = [
                        'contract_id' => $row['contract_id'],
                        'invoice_id' => (int) $invoice->id,
                        'invoice_date' => $invoice->created_at?->format('Y-m-d H:i:s'),
                        'invoice_total' => (float) $invoice->total_due,
                        'invoice_constraint_id' => (int) $constraint->id,
                        'receipt_bond_id' => (int) $receiptBond->id,
                        'receipt_constraint_id' => (int) $receiptBond->constraint_id,
                        'finance_sale_id' => $invoice->finance_sale_id ? (int) $invoice->finance_sale_id : null,
                    ];
                }

                if (count($created) !== $preflight['ready']->count()) {
                    throw new RuntimeException('فشل التحقق من اكتمال دفعة الفواتير.');
                }
            }, 1);
        } catch (Throwable $exception) {
            $this->error('فشل التنفيذ وتم التراجع عن الدفعة كاملة: ' . $exception->getMessage());

            return self::FAILURE;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }

        $reportPath = $this->writeAuditReport($from, $to, $currentDatabase, $created);

        $this->newLine();
        $this->info('تم إنشاء الدفعة كاملة بنجاح.');
        $this->table(
            ['العقود', 'الفواتير', 'قيود الفواتير', 'سندات القبض', 'قيود سندات القبض'],
            [[count($created), count($created), count($created), count($created), count($created)]]
        );
        $this->line('إجمالي الفواتير: ' . number_format((float) collect($created)->sum('invoice_total'), 2));
        $this->line('تقرير التنفيذ: ' . $reportPath);

        return self::SUCCESS;
    }

    private function resolveDates(): array
    {
        $fromValue = trim((string) ($this->option('from') ?: now()->startOfYear()->addMonths(2)->toDateString()));
        $toValue = trim((string) ($this->option('to') ?: now()->toDateString()));

        try {
            $from = Carbon::createFromFormat('!Y-m-d', $fromValue, config('app.timezone'))->startOfDay();
            $to = Carbon::createFromFormat('!Y-m-d', $toValue, config('app.timezone'))->endOfDay();
        } catch (Throwable $exception) {
            throw new RuntimeException('صيغة التاريخ غير صحيحة. استخدم Y-m-d.');
        }

        if ($from->gt($to)) {
            throw new RuntimeException('تاريخ البداية يجب ألا يكون بعد تاريخ النهاية.');
        }
        if ($to->copy()->startOfDay()->gt(now()->startOfDay())) {
            throw new RuntimeException('تاريخ نهاية نطاق العقود لا يمكن أن يكون في المستقبل.');
        }
        return [$from, $to];
    }

    private function resolveContractIds(): Collection
    {
        $rawIds = collect($this->option('contract'));
        $invalid = $rawIds->reject(fn ($id) => ctype_digit((string) $id) && (int) $id > 0);
        if ($invalid->isNotEmpty()) {
            throw new RuntimeException('أرقام العقود في --contract يجب أن تكون أرقاماً صحيحة موجبة.');
        }

        return $rawIds->map(fn ($id) => (int) $id)->unique()->values();
    }

    private function contracts(Carbon $from, Carbon $to, Collection $contractIds): Collection
    {
        return Contract::query()
            ->whereIn('type', ['recruitment', 'maid'])
            ->whereBetween('created_at', [$from, $to])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', ContractStatusEnum::Shelter->value);
            })
            ->when($contractIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $contractIds))
            ->with([
                'invoice',
                'user',
                'info',
                'maid.nationality',
                'requiredMaidNationality',
            ])
            ->orderBy('id')
            ->get();
    }

    private function excludedShelterCount(Carbon $from, Carbon $to, Collection $contractIds): int
    {
        return Contract::query()
            ->whereIn('type', ['recruitment', 'maid'])
            ->whereBetween('created_at', [$from, $to])
            ->where('status', ContractStatusEnum::Shelter->value)
            ->when($contractIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $contractIds))
            ->count();
    }

    private function preflight(
        Collection $contracts,
        InvoiceController $invoiceController,
        InvoiceCalculationService $calculator,
        YearService $yearService
    ): array {
        $globalErrors = [];
        $invoiceDates = $contracts
            ->reject(fn (Contract $contract) => (bool) $contract->invoice)
            ->pluck('created_at')
            ->filter()
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();
        foreach ($invoiceDates as $invoiceDate) {
            try {
                $invoiceController->assertRecruitmentInvoiceAccountingConfiguration(
                    Carbon::parse($invoiceDate, config('app.timezone')),
                    $yearService
                );
            } catch (Throwable $exception) {
                $globalErrors[] = $invoiceDate . ': ' . $exception->getMessage();
            }
        }
        $globalErrors = array_values(array_unique($globalErrors));

        $existing = $contracts->filter(fn (Contract $contract) => (bool) $contract->invoice)->values();
        $ready = collect();
        $blockers = collect();

        foreach ($contracts->reject(fn (Contract $contract) => (bool) $contract->invoice) as $contract) {
            $errors = [];
            $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;

            if (!$contract->user_id || !$contract->user) {
                $errors[] = 'العقد غير مرتبط بعميل صالح';
            }
            if (!$nationality) {
                $errors[] = 'الجنسية غير محددة';
            }

            $payload = $this->invoicePayload($nationality, $calculator);
            if ((float) $payload['total_due'] <= 0) {
                $errors[] = 'أسعار الجنسية غير مكتملة أو إجمالي الفاتورة صفر';
            }

            if ($nationality && !$invoiceController->resolveContractNationalityLiabilityAccountId($nationality)) {
                $errors[] = 'حساب التزامات عقود الجنسية غير مربوط أو غير صالح';
            }

            $contractValue = (float) ($contract->info?->contract_value ?? 0);
            if ($contractValue > 0 && abs($contractValue - (float) $payload['total_due']) >= 0.01) {
                $errors[] = 'قيمة العقد لا تطابق الفاتورة المحسوبة من أسعار الجنسية';
            }

            $row = [
                'contract_id' => (int) $contract->id,
                'status' => (string) $contract->status,
                'nationality' => (string) ($nationality?->sub_title ?: ($nationality?->title ?: '-')),
                'contract_value' => $contractValue,
                'invoice_total' => (float) $payload['total_due'],
                'invoice_date' => $contract->created_at?->format('Y-m-d H:i:s'),
                'payload' => $payload,
                'errors' => $errors,
            ];

            $errors === [] ? $ready->push($row) : $blockers->push($row);
        }

        return [
            'global_errors' => $globalErrors,
            'existing' => $existing,
            'ready' => $ready,
            'blockers' => $blockers,
        ];
    }

    private function invoicePayload($nationality, InvoiceCalculationService $calculator): array
    {
        $payload = [
            'insurance_company' => null,
            'general_description' => null,
            'direct_recruitment_cost' => $calculator->normalizeMoney($nationality?->direct_recruitment_cost ?? 0),
            'external_recruitment_cost' => $calculator->normalizeMoney($nationality?->external_recruitment_cost ?? 0),
            'government_cost' => $calculator->normalizeMoney($nationality?->government_cost ?? 0),
            'internal_ticket_cost' => 0.0,
            'visa_recruited_count' => 1,
            'direct_recruitment_count' => 1,
            'external_recruitment_count' => 1,
            'insured_count' => 1,
            'government_cost_count' => 1,
            'internal_ticket_count' => 1,
            'discount' => 0.0,
        ];

        return array_merge($payload, $calculator->calculateTotals($payload));
    }

    private function displayPreflight(
        Carbon $from,
        Carbon $to,
        Collection $contracts,
        array $preflight,
        int $excludedShelterCount,
        string $currentDatabase,
        string $currentDatabaseFingerprint
    ): void {
        $this->table(
            ['البيان', 'القيمة'],
            [
                ['بداية إضافة العقود', $from->toDateString()],
                ['نهاية إضافة العقود', $to->toDateString()],
                ['تاريخ الفواتير والقيود', 'تاريخ إنشاء كل عقد'],
                ['قاعدة البيانات الحالية', $currentDatabase],
                ['بصمة اتصال قاعدة البيانات', $currentDatabaseFingerprint],
                ['عقود الإيواء المستثناة', $excludedShelterCount],
                ['العقود المطابقة', $contracts->count()],
                ['لديها فاتورة مسبقاً', $preflight['existing']->count()],
                ['جاهزة للإنشاء', $preflight['ready']->count()],
                ['عقود بها موانع', $preflight['blockers']->count()],
                ['إجمالي الدفعة المتوقعة', number_format((float) $preflight['ready']->sum('invoice_total'), 2)],
                ['إرسال إلى الزكاة', $this->option('send-to-zatca') ? 'نعم' : 'لا'],
            ]
        );

        foreach ($preflight['global_errors'] as $error) {
            $this->error('إعداد محاسبي: ' . $error);
        }

        if ($preflight['blockers']->isNotEmpty()) {
            $this->newLine();
            $this->warn('موانع العقود (أول 50 عقداً):');
            $this->table(
                ['العقد', 'الحالة', 'الجنسية', 'الإجمالي', 'المانع'],
                $preflight['blockers']->take(50)->map(fn (array $row) => [
                    $row['contract_id'],
                    $row['status'],
                    $row['nationality'],
                    number_format($row['invoice_total'], 2),
                    implode('؛ ', $row['errors']),
                ])->all()
            );

            $reasonSummary = $preflight['blockers']
                ->flatMap(fn (array $row) => $row['errors'])
                ->countBy()
                ->map(fn (int $count, string $reason) => [$reason, $count])
                ->values()
                ->all();
            $this->table(['سبب المنع', 'عدد العقود'], $reasonSummary);

            $nationalitySummary = $preflight['blockers']
                ->groupBy('nationality')
                ->map(function (Collection $rows, string $nationality) {
                    $payload = $rows->first()['payload'];

                    return [
                        $nationality,
                        $rows->count(),
                        number_format((float) $payload['direct_recruitment_cost'], 2),
                        number_format((float) $payload['external_recruitment_cost'], 2),
                        number_format((float) $payload['government_cost'], 2),
                        implode('؛ ', $rows->flatMap(fn (array $row) => $row['errors'])->unique()->values()->all()),
                    ];
                })
                ->values()
                ->all();
            $this->table(
                ['الجنسية', 'العقود', 'المباشر', 'الخارجي', 'الحكومي', 'المطلوب إصلاحه'],
                $nationalitySummary
            );
        }
    }

    private function applyCommand(
        Carbon $from,
        Carbon $to,
        Collection $contractIds,
        string $currentDatabase,
        string $currentDatabaseFingerprint
    ): string {
        $parts = [
            'php artisan recruitment-contracts:create-missing-invoices',
            '--from=' . $from->toDateString(),
            '--to=' . $to->toDateString(),
            '--apply',
            '--confirm=' . self::CONFIRMATION,
            '--expected-database=' . $currentDatabase,
            '--expected-database-fingerprint=' . $currentDatabaseFingerprint,
            '--no-interaction',
        ];

        foreach ($contractIds as $contractId) {
            $parts[] = '--contract=' . $contractId;
        }
        if ($this->option('send-to-zatca')) {
            $parts[] = '--send-to-zatca';
        }

        return implode(' ', $parts);
    }

    private function databaseFingerprint(): string
    {
        $connection = DB::connection();
        $config = $connection->getConfig();

        return hash('sha256', implode('|', [
            (string) ($config['driver'] ?? ''),
            (string) ($config['host'] ?? ''),
            (string) ($config['port'] ?? ''),
            (string) $connection->getDatabaseName(),
        ]));
    }

    private function writeAuditReport(
        Carbon $from,
        Carbon $to,
        string $database,
        array $created
    ): string
    {
        $directory = storage_path('app/exports');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return 'تعذر إنشاء ملف التقرير؛ الفواتير والقيود أُنشئت بنجاح.';
        }

        $path = $directory . '/recruitment-invoices-created-' . now()->format('Y-m-d_His') . '.json';
        $written = file_put_contents($path, json_encode([
            'executed_at' => now()->toIso8601String(),
            'database' => $database,
            'contracts_created_from' => $from->toDateString(),
            'contracts_created_to' => $to->toDateString(),
            'invoice_date_source' => 'contract.created_at',
            'send_to_zatca' => (bool) $this->option('send-to-zatca'),
            'count' => count($created),
            'total' => round((float) collect($created)->sum('invoice_total'), 2),
            'rows' => $created,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $written === false ? 'تعذر كتابة ملف التقرير؛ الفواتير والقيود أُنشئت بنجاح.' : $path;
    }
}

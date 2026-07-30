<?php

namespace App\Console\Commands;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Services\ingaz\InvoiceCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class ReconcileRecruitmentContractInvoicesFromExcel extends Command
{
    private const CONFIRMATION = 'RECONCILE-RECRUITMENT-INVOICES-FROM-EXCEL';

    protected $signature = 'recruitment-contracts:reconcile-invoices-from-excel
        {file : مسار ملف Excel}
        {--admin-id= : معرف مدير داخلي نشط لتسجيل الأثر المحاسبي}
        {--apply : تنفيذ التصحيح؛ بدونه معاينة فقط}
        {--allow-skips : السماح بتنفيذ السجلات الجاهزة وترك السجلات ذات الموانع}
        {--confirm= : عبارة التأكيد المطلوبة}
        {--expected-database= : اسم قاعدة البيانات المسموح بها}
        {--expected-database-fingerprint= : بصمة اتصال قاعدة البيانات من المعاينة}
        {--expected-file-sha256= : بصمة ملف Excel من المعاينة}';

    protected $description = 'مطابقة وتصحيح فواتير وبيانات عقود الاستقدام من ملف Excel اعتماداً على بنود الفاتورة';

    public function handle(
        InvoiceController $invoiceController,
        InvoiceCalculationService $calculator,
        ObjService $constraintService,
        YearService $yearService,
        BondService $bondService
    ): int {
        try {
            $path = $this->resolveFile();
            $rows = $this->readRows($path, $calculator);
            $admin = $this->resolveAdmin();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $database = (string) DB::connection()->getDatabaseName();
        $databaseFingerprint = $this->databaseFingerprint();
        $fileHash = hash_file('sha256', $path);
        $matched = $this->matchRows($rows);
        $preflight = $this->preflight($matched, $invoiceController, $yearService);

        $this->display($path, $database, $databaseFingerprint, $fileHash, $admin, $rows, $preflight);

        if (!$this->option('apply')) {
            $this->warn('وضع المعاينة: لم تُعدّل العقود أو الفواتير أو القيود أو السندات.');
            $this->line('قاعدة الاحتساب: المباشر + الخارجي + الحكومي + 15% من المباشر.');

            return self::SUCCESS;
        }

        try {
            $this->assertApplyGuards($database, $databaseFingerprint, $fileHash, $admin, $preflight);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        Auth::guard('admin')->setUser($admin);
        $created = [];

        try {
            DB::transaction(function () use (
                $preflight,
                $invoiceController,
                $constraintService,
                $yearService,
                $bondService,
                $path,
                &$created
            ): void {
                foreach ($preflight['ready'] as $row) {
                    try {
                        $contract = Contract::query()
                            ->with('info')
                            ->lockForUpdate()
                            ->findOrFail($row['contract_id']);
                        $contract->update([
                            'contract_date' => $row['invoice_date']->toDateString(),
                            'visa_number' => $row['visa'],
                        ]);
                        $contract->info()->updateOrCreate(
                            ['contract_id' => $contract->id],
                            [
                                'contract_date' => $row['invoice_date']->toDateString(),
                                'visa_number' => $row['visa'],
                                'contract_value' => $row['payload']['total_due'],
                            ]
                        );

                        $result = $invoiceController->reconcileRecruitmentContractInvoice(
                            $contract->id,
                            $row['payload'],
                            $row['invoice_date'],
                            basename($path),
                            $row['source_row'],
                            $constraintService,
                            $yearService,
                            $bondService
                        );
                        $created[] = [
                            'source_row' => $row['source_row'],
                            'source_contract_number' => $row['source_contract_number'],
                            'contract_id' => $contract->id,
                            'invoice_id' => $result['invoice']->id,
                            'action' => $result['action'],
                            'total' => (float) $result['invoice']->total_due,
                            'invoice_constraint_id' => $result['invoice_constraint']->id,
                            'receipt_bond_id' => $result['receipt_bond']->id,
                            'receipt_constraint_id' => $result['receipt_bond']->constraint_id,
                        ];
                    } catch (Throwable $exception) {
                        throw new RuntimeException(
                            'صف Excel ' . $row['source_row'] . ' / العقد #' . $row['contract_id']
                            . ': ' . $exception->getMessage(),
                            (int) $exception->getCode(),
                            $exception
                        );
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->error('فشل التنفيذ وتم التراجع عن الدفعة كاملة: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $report = $this->writeReport($path, $database, $fileHash, $admin, $created, $preflight['blockers']);
        $this->info('تم تصحيح جميع السجلات الجاهزة بنجاح: ' . count($created));
        $this->line('إجمالي الفواتير بعد التصحيح: ' . number_format((float) collect($created)->sum('total'), 2));
        $this->line('تقرير التنفيذ: ' . $report);

        return self::SUCCESS;
    }

    private function resolveFile(): string
    {
        $path = (string) $this->argument('file');
        $path = str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
        $real = realpath($path);
        if (!$real || !is_file($real) || !is_readable($real)) {
            throw new RuntimeException('ملف Excel غير موجود أو غير قابل للقراءة.');
        }

        return $real;
    }

    private function readRows(string $path, InvoiceCalculationService $calculator): Collection
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $headers = array_map(
            fn (string $column) => trim((string) $sheet->getCell($column . '1')->getValue()),
            range('A', 'N')
        );
        foreach (['رقم العقد', 'هوية صاحب العمل', 'رقم الصادر', 'حالة العقد'] as $required) {
            if (!in_array($required, $headers, true)) {
                throw new RuntimeException('تنسيق الملف غير صحيح؛ العمود المطلوب غير موجود: ' . $required);
            }
        }

        return collect(range(2, $sheet->getHighestDataRow()))
            ->map(function (int $row) use ($sheet, $calculator): array {
                $dateValue = $sheet->getCell('I' . $row)->getValue();
                $date = is_numeric($dateValue)
                    ? Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        ExcelDate::excelToDateTimeObject((float) $dateValue)->format('Y-m-d H:i:s'),
                        config('app.timezone')
                    )
                    : Carbon::parse((string) $dateValue, config('app.timezone'));
                $payload = [
                    'insurance_company' => null,
                    'general_description' => null,
                    'direct_recruitment_cost' => $calculator->normalizeMoney($sheet->getCell('K' . $row)->getValue()),
                    'external_recruitment_cost' => $calculator->normalizeMoney($sheet->getCell('L' . $row)->getValue()),
                    'government_cost' => $calculator->normalizeMoney($sheet->getCell('N' . $row)->getValue()),
                    'internal_ticket_cost' => 0.0,
                    'visa_recruited_count' => 1,
                    'direct_recruitment_count' => 1,
                    'external_recruitment_count' => 1,
                    'insured_count' => 1,
                    'government_cost_count' => 1,
                    'internal_ticket_count' => 1,
                    'discount' => 0.0,
                ];

                return [
                    'source_row' => $row,
                    'source_contract_number' => trim((string) $sheet->getCell('A' . $row)->getValue()),
                    'identity' => trim((string) $sheet->getCell('B' . $row)->getValue()),
                    'visa' => trim((string) $sheet->getCell('C' . $row)->getValue()),
                    'nationality' => trim((string) $sheet->getCell('E' . $row)->getValue()),
                    'source_status' => trim((string) $sheet->getCell('H' . $row)->getValue()),
                    'invoice_date' => $date,
                    'payload' => array_merge($payload, $calculator->calculateTotals($payload)),
                ];
            })
            ->filter(fn (array $row) => $row['source_status'] !== 'ملغى')
            ->values();
    }

    private function matchRows(Collection $rows): Collection
    {
        $contracts = Contract::query()
            ->whereIn('type', ['recruitment', 'maid'])
            ->with(['user', 'info', 'invoice', 'maid.nationality', 'requiredMaidNationality'])
            ->where(function ($query) use ($rows) {
                $query->whereHas('user', fn ($user) => $user->whereIn('national_num', $rows->pluck('identity')))
                    ->orWhereIn('visa_number', $rows->pluck('visa'))
                    ->orWhereHas('info', fn ($info) => $info->whereIn('visa_number', $rows->pluck('visa')));
            })
            ->get();

        $assigned = collect();
        $result = collect();
        $pending = $rows->map(function (array $row) use ($contracts): array {
            $row['candidates'] = $contracts->filter(fn (Contract $contract) =>
                trim((string) $contract->user?->national_num) === $row['identity']
                || in_array($row['visa'], [
                    trim((string) $contract->visa_number),
                    trim((string) $contract->info?->visa_number),
                ], true)
            )->values();
            $row['strong_candidates'] = $row['candidates']->filter(fn (Contract $contract) =>
                trim((string) $contract->user?->national_num) === $row['identity']
                && in_array($row['visa'], [
                    trim((string) $contract->visa_number),
                    trim((string) $contract->info?->visa_number),
                ], true)
            )->values();

            return $row;
        });

        foreach ($pending as $key => $row) {
            $strong = $row['strong_candidates'];
            $selected = $strong->count() === 1
                ? $strong->first()
                : ($strong->whereNotNull('invoice')->count() === 1 ? $strong->whereNotNull('invoice')->first() : null);
            if ($selected && !$assigned->contains($selected->id)) {
                $assigned->push($selected->id);
                $result->put($key, $this->matchedRow($row, $selected, 'identity+visa'));
            }
        }

        foreach ($pending as $key => $row) {
            if ($result->has($key)) {
                continue;
            }
            $date = $row['invoice_date']->toDateString();
            $candidates = $row['candidates']
                ->reject(fn (Contract $contract) => $assigned->contains($contract->id))
                ->filter(fn (Contract $contract) => in_array($date, [
                    substr((string) $contract->contract_date, 0, 10),
                    substr((string) $contract->info?->contract_date, 0, 10),
                ], true))
                ->values();
            if ($candidates->count() === 1) {
                $selected = $candidates->first();
                $assigned->push($selected->id);
                $result->put($key, $this->matchedRow($row, $selected, 'date+identity-or-visa'));
            }
        }

        foreach ($pending as $key => $row) {
            if ($result->has($key)) {
                continue;
            }
            $candidates = $row['candidates']
                ->reject(fn (Contract $contract) => $assigned->contains($contract->id))
                ->values();
            if ($candidates->count() === 1) {
                $selected = $candidates->first();
                $assigned->push($selected->id);
                $result->put($key, $this->matchedRow($row, $selected, 'unique-remaining'));
            } else {
                $row['match_error'] = $candidates->isEmpty()
                    ? 'لم يوجد عقد غير مستخدم يطابق الهوية أو رقم الصادر'
                    : 'أكثر من عقد محتمل: ' . $candidates->pluck('id')->implode(', ');
                unset($row['candidates'], $row['strong_candidates']);
                $result->put($key, $row);
            }
        }

        return $result->sortKeys()->values();
    }

    private function matchedRow(array $row, Contract $contract, string $method): array
    {
        unset($row['candidates'], $row['strong_candidates']);
        $row['contract'] = $contract;
        $row['contract_id'] = (int) $contract->id;
        $row['match_method'] = $method;

        return $row;
    }

    private function preflight(
        Collection $rows,
        InvoiceController $invoiceController,
        YearService $yearService
    ): array {
        $ready = collect();
        $blockers = collect();

        foreach ($rows as $row) {
            $errors = [];
            $contract = $row['contract'] ?? null;
            if (!$contract) {
                $errors[] = $row['match_error'];
            } else {
                $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
                if (!$contract->user) {
                    $errors[] = 'العقد غير مرتبط بعميل';
                }
                if (!$nationality) {
                    $errors[] = 'الجنسية غير محددة';
                } elseif (!$invoiceController->resolveContractNationalityLiabilityAccountId($nationality)) {
                    $errors[] = 'حساب التزامات عقود الجنسية غير مربوط';
                }
                if ((float) $row['payload']['total_due'] <= 0) {
                    $errors[] = 'إجمالي البنود المحسوب صفر';
                }
                try {
                    $invoiceController->assertRecruitmentInvoiceAccountingConfiguration(
                        $row['invoice_date'],
                        $yearService
                    );
                } catch (Throwable $exception) {
                    $errors[] = $exception->getMessage();
                }
                $invoice = $contract->invoice;
                $amountChanged = $invoice ? $this->invoiceAmountChanged($invoice, $row['payload']) : false;
                if ($invoice?->financeSale?->zatca_invoice_id) {
                    $errors[] = 'الفاتورة مرسلة فعلياً للزكاة';
                }
                if ($amountChanged && $invoice?->musanedTransferItem) {
                    $errors[] = 'الفاتورة مرتبطة بتحويل مساند';
                }
                if ($amountChanged && $invoice && (
                    $invoice->arrival_revenue_constraint_id
                    || $invoice->external_office_cost_constraint_id
                    || $invoice->external_office_purchase_id
                )) {
                    $errors[] = 'الفاتورة مرتبطة بقيود وصول أو تكلفة مكتب خارجي';
                }
                if ($invoice?->financeSale && $invoice->financeSale->items()->count() !== 1) {
                    $errors[] = 'فاتورة المبيعات المحاسبية لا تحتوي بنداً واحداً';
                }
                $bonds = $contract->financeBonds;
                if ($bonds->count() > 1) {
                    $errors[] = 'يوجد أكثر من سند قبض مرتبط بالعقد';
                } elseif ($bonds->first()?->check || $bonds->first()?->bondTax) {
                    $errors[] = 'سند القبض يحتوي شيكاً أو ضريبة';
                }
            }
            $row['errors'] = array_values(array_unique(array_filter($errors)));
            unset($row['contract']);
            $row['errors'] === [] ? $ready->push($row) : $blockers->push($row);
        }

        return compact('ready', 'blockers');
    }

    private function invoiceAmountChanged($invoice, array $payload): bool
    {
        return collect([
            [(float) $invoice->direct_recruitment_cost, (float) $payload['direct_recruitment_cost']],
            [(float) $invoice->external_recruitment_cost, (float) $payload['external_recruitment_cost']],
            [(float) $invoice->government_cost, (float) $payload['government_cost']],
            [(float) $invoice->vat_amount, (float) $payload['vat_amount']],
            [(float) $invoice->total_due, (float) $payload['total_due']],
        ])->contains(fn (array $values) => abs($values[0] - $values[1]) >= 0.01);
    }

    private function resolveAdmin(): ?Admin
    {
        $value = trim((string) $this->option('admin-id'));
        if ($value === '') {
            return null;
        }
        if (!ctype_digit($value) || (int) $value <= 0) {
            throw new RuntimeException('--admin-id يجب أن يكون رقماً صحيحاً موجباً.');
        }

        return Admin::query()->activeForOperations()->notExternalOfficeAgents()->find((int) $value)
            ?? throw new RuntimeException('المدير المحدد غير موجود أو غير صالح للتنفيذ.');
    }

    private function assertApplyGuards(
        string $database,
        string $databaseFingerprint,
        string $fileHash,
        ?Admin $admin,
        array $preflight
    ): void {
        if (!$admin) {
            throw new RuntimeException('رفض التنفيذ: --admin-id مطلوب.');
        }
        if ((string) $this->option('confirm') !== self::CONFIRMATION) {
            throw new RuntimeException('رفض التنفيذ: عبارة التأكيد غير صحيحة.');
        }
        if (!hash_equals($database, (string) $this->option('expected-database'))) {
            throw new RuntimeException('رفض التنفيذ: اسم قاعدة البيانات لا يطابق المعاينة.');
        }
        if (!hash_equals($databaseFingerprint, (string) $this->option('expected-database-fingerprint'))) {
            throw new RuntimeException('رفض التنفيذ: بصمة اتصال قاعدة البيانات لا تطابق المعاينة.');
        }
        if (!hash_equals($fileHash, (string) $this->option('expected-file-sha256'))) {
            throw new RuntimeException('رفض التنفيذ: ملف Excel تغير بعد المعاينة.');
        }
        if ($preflight['blockers']->isNotEmpty() && !$this->option('allow-skips')) {
            throw new RuntimeException('رفض التنفيذ: توجد موانع؛ استخدم --allow-skips لتنفيذ الجاهز وتركها في التقرير.');
        }
    }

    private function display(
        string $path,
        string $database,
        string $databaseFingerprint,
        string $fileHash,
        ?Admin $admin,
        Collection $rows,
        array $preflight
    ): void {
        $this->table(['البيان', 'القيمة'], [
            ['الملف', $path],
            ['بصمة الملف', $fileHash],
            ['قاعدة البيانات', $database],
            ['بصمة قاعدة البيانات', $databaseFingerprint],
            ['منفذ العملية', $admin ? "#{$admin->id} - {$admin->name}" : 'غير محدد'],
            ['السجلات غير الملغاة', $rows->count()],
            ['جاهزة للتصحيح', $preflight['ready']->count()],
            ['بها موانع', $preflight['blockers']->count()],
            ['الإجمالي المحسوب من البنود', number_format((float) $preflight['ready']->sum('payload.total_due'), 2)],
        ]);
        if ($preflight['blockers']->isNotEmpty()) {
            $this->table(
                ['صف Excel', 'رقم المصدر', 'العقد', 'الجنسية', 'المانع'],
                $preflight['blockers']->map(fn (array $row) => [
                    $row['source_row'],
                    $row['source_contract_number'],
                    $row['contract_id'] ?? '-',
                    $row['nationality'],
                    implode('؛ ', $row['errors']),
                ])->all()
            );
        }
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

    private function writeReport(
        string $path,
        string $database,
        string $fileHash,
        Admin $admin,
        array $created,
        Collection $blockers
    ): string {
        $directory = storage_path('app/exports');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $report = $directory . '/recruitment-invoice-reconciliation-' . now()->format('Y-m-d_His') . '.json';
        file_put_contents($report, json_encode([
            'executed_at' => now()->toIso8601String(),
            'database' => $database,
            'source_file' => basename($path),
            'source_sha256' => $fileHash,
            'admin_id' => $admin->id,
            'calculation_rule' => 'direct + external + government + 15% of direct',
            'corrected' => $created,
            'skipped' => $blockers->map(fn (array $row) => [
                'source_row' => $row['source_row'],
                'source_contract_number' => $row['source_contract_number'],
                'contract_id' => $row['contract_id'] ?? null,
                'errors' => $row['errors'],
            ])->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $report;
    }
}

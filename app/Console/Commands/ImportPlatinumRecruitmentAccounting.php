<?php

namespace App\Console\Commands;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Services\ingaz\InvoiceCalculationService;
use App\Services\ingaz\RecruitmentTripAccountingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class ImportPlatinumRecruitmentAccounting extends Command
{
    private const CONFIRMATION = 'IMPORT-PLATINUM-RECRUITMENT-ACCOUNTING';
    private const USD_TO_SAR_RATE = 3.75;

    protected $signature = 'platinum:import-recruitment-accounting
        {file : ملف نظام فواتير العقود وتحويلات مساند}
        {--admin-id= : المدير الداخلي الذي يسجل الأثر المحاسبي}
        {--bank-account-id= : حساب البنك المستخدم في قيود تحويلات مساند}
        {--apply : تنفيذ الاستيراد؛ بدونه معاينة فقط}
        {--allow-skips : تنفيذ السجلات الجاهزة مع إبقاء غير المطابق في التقرير}
        {--confirm= : عبارة تأكيد التنفيذ}
        {--expected-database= : اسم قاعدة البيانات من المعاينة}
        {--expected-database-fingerprint= : بصمة اتصال قاعدة البيانات من المعاينة}
        {--expected-file-sha256= : بصمة ملف Excel من المعاينة}
        {--musaned=* : قصر المعاينة والتنفيذ على أرقام مساند محددة}';

    protected $description = 'استيراد دورة Platinum: الفواتير وبنودها وتحويلات مساند وإرساليات المكاتب وقيود الوصول';

    public function handle(
        InvoiceController $invoiceController,
        MusanedTransferController $transferController,
        RecruitmentTripAccountingService $tripAccounting,
        InvoiceCalculationService $calculator,
        ObjService $constraintService,
        YearService $yearService,
        BondService $bondService
    ): int {
        try {
            $path = $this->resolveFile();
            $source = $this->readWorkbook($path, $calculator);
            $admin = $this->resolveAdmin();
            $bankAccountId = $this->resolveBankAccountId();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $database = (string) DB::connection()->getDatabaseName();
        $databaseFingerprint = $this->databaseFingerprint();
        $fileHash = (string) hash_file('sha256', $path);
        $plan = $this->buildPlan($source, $invoiceController, $yearService);

        $this->display($path, $database, $databaseFingerprint, $fileHash, $admin, $bankAccountId, $plan);

        if (!$this->option('apply')) {
            $this->warn('وضع المعاينة: لم يتم تعديل قاعدة البيانات.');
            return self::SUCCESS;
        }

        try {
            $this->assertApplyGuards($database, $databaseFingerprint, $fileHash, $admin, $bankAccountId, $plan);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        Auth::guard('admin')->setUser($admin);
        $audit = ['invoices' => [], 'dispatches' => [], 'transfers' => [], 'arrivals' => []];

        try {
            DB::transaction(function () use (
                $plan,
                $path,
                $invoiceController,
                $transferController,
                $tripAccounting,
                $constraintService,
                $yearService,
                $bondService,
                $bankAccountId,
                &$audit
            ): void {
                foreach ($plan['ready'] as $row) {
                    $contract = Contract::query()->with(['info', 'externalOfficeDispatch'])->lockForUpdate()->findOrFail($row['contract_id']);
                    $contract->forceFill(['contract_date' => $row['invoice_date']->toDateString()])->save();
                    $contract->info()->updateOrCreate(
                        ['contract_id' => $contract->id],
                        [
                            'contract_date' => $row['invoice_date']->toDateString(),
                            'contract_value' => $row['payload']['total_due'],
                        ]
                    );

                    $invoiceResult = $invoiceController->reconcileRecruitmentContractInvoice(
                        $contract->id,
                        $row['payload'],
                        $row['invoice_date'],
                        basename($path),
                        $row['source_row'],
                        $constraintService,
                        $yearService,
                        $bondService
                    );
                    $audit['invoices'][] = [
                        'source_row' => $row['source_row'],
                        'musaned_number' => $row['musaned_number'],
                        'contract_id' => $contract->id,
                        'invoice_id' => $invoiceResult['invoice']->id,
                        'action' => $invoiceResult['action'],
                        'total' => (float) $invoiceResult['invoice']->total_due,
                    ];

                    $audit['dispatches'][] = $this->reconcileDispatch($contract->fresh(), $row);
                }

                foreach ($plan['transferGroups'] as $group) {
                    $request = Request::create('/admin/musaned-transfers', 'POST', [
                        'reference_number' => $group['reference_number'],
                        'transfer_date' => $group['transfer_date'],
                        'bank_account_id' => $bankAccountId,
                        'notes' => 'استيراد من ' . basename($path),
                        'items' => collect($group['items'])->map(fn (array $item) => [
                            'contract_id' => $item['contract_id'],
                            'transferred_amount' => $item['transferred_amount'],
                        ])->all(),
                    ]);
                    try {
                        $transferController->store($request, $constraintService, $yearService);
                    } catch (ValidationException $exception) {
                        throw new RuntimeException(
                            'تحويل مساند ' . $group['reference_number'] . ' بتاريخ ' . $group['transfer_date']
                            . ': ' . collect($exception->errors())->flatten()->implode('؛ '),
                            0,
                            $exception
                        );
                    }
                    $transfer = \App\Models\MusanedTransfer::query()
                        ->where('reference_number', $group['reference_number'])
                        ->whereDate('transfer_date', $group['transfer_date'])
                        ->latest('id')
                        ->firstOrFail();
                    $audit['transfers'][] = [
                        'transfer_id' => $transfer->id,
                        'reference_number' => $group['reference_number'],
                        'transfer_date' => $group['transfer_date'],
                        'items' => count($group['items']),
                        'transferred_total' => (float) $transfer->transferred_total,
                        'settlement_constraint_id' => $transfer->settlement_constraint_id,
                        'bank_transfer_constraint_id' => $transfer->bank_transfer_constraint_id,
                    ];
                }

                foreach ($plan['ready'] as $row) {
                    if (!$row['arrival_travel_id']) {
                        continue;
                    }
                    $contract = Contract::query()->with(['invoice', 'externalOfficeDispatch'])->findOrFail($row['contract_id']);
                    $travel = \App\Models\TravelHistory::query()->find($row['arrival_travel_id']);
                    $result = $tripAccounting->recordCompletionEntries($contract, $travel);
                    $audit['arrivals'][] = array_merge([
                        'contract_id' => $contract->id,
                        'travel_id' => $travel?->id,
                    ], $result);
                }
            }, 1);
        } catch (Throwable $exception) {
            $this->error('فشل التنفيذ وتم التراجع عن الدفعة كاملة: ' . $exception->getMessage());
            return self::FAILURE;
        }

        $report = $this->writeReport($path, $database, $fileHash, $admin, $plan, $audit);
        $this->info('اكتملت دورة الاستيراد بنجاح.');
        $this->table(['الفواتير', 'الإرساليات', 'دفعات مساند', 'قيود وصول تمت معالجتها'], [[
            count($audit['invoices']), count($audit['dispatches']), count($audit['transfers']), count($audit['arrivals']),
        ]]);
        $this->line('تقرير التنفيذ: ' . $report);

        return self::SUCCESS;
    }

    private function resolveFile(): string
    {
        $value = (string) $this->argument('file');
        $path = str_starts_with($value, DIRECTORY_SEPARATOR) ? $value : base_path($value);
        $real = realpath($path);
        if (!$real || !is_file($real) || !is_readable($real)) {
            throw new RuntimeException('ملف Excel غير موجود أو غير قابل للقراءة.');
        }
        return $real;
    }

    private function readWorkbook(string $path, InvoiceCalculationService $calculator): array
    {
        $book = IOFactory::load($path);
        $invoiceSheet = $book->getSheetByName('فواتير العقود');
        $transferSheet = $book->getSheetByName('تحويلات مساند');
        if (!$invoiceSheet || !$transferSheet) {
            throw new RuntimeException('يجب أن يحتوي الملف ورقتي «فواتير العقود» و«تحويلات مساند».');
        }
        if (trim((string) $invoiceSheet->getCell('A1')->getValue()) !== 'رقم العقد'
            || trim((string) $transferSheet->getCell('A1')->getValue()) !== 'رقم العقد') {
            throw new RuntimeException('تنسيق أوراق Excel غير مطابق للتنسيق المتوقع.');
        }

        $invoices = collect(range(2, $invoiceSheet->getHighestDataRow()))
            ->filter(fn (int $r) => ctype_digit(trim((string) $invoiceSheet->getCell('A' . $r)->getValue())))
            ->map(function (int $r) use ($invoiceSheet, $calculator): array {
                $payload = [
                    'insurance_company' => null,
                    'general_description' => 'فاتورة ' . trim((string) $invoiceSheet->getCell('B' . $r)->getValue()),
                    'direct_recruitment_cost' => $this->money($invoiceSheet->getCell('F' . $r)->getCalculatedValue()),
                    'external_recruitment_cost' => $this->money($invoiceSheet->getCell('H' . $r)->getCalculatedValue()),
                    'internal_ticket_cost' => $this->money($invoiceSheet->getCell('I' . $r)->getCalculatedValue()),
                    'government_cost' => $this->money($invoiceSheet->getCell('K' . $r)->getCalculatedValue()),
                    'visa_recruited_count' => 1,
                    'direct_recruitment_count' => 1,
                    'external_recruitment_count' => 1,
                    'insured_count' => 1,
                    'government_cost_count' => 1,
                    'internal_ticket_count' => 1,
                    'discount' => 0,
                ];
                $payload = array_merge($payload, $calculator->calculateTotals($payload));
                $storedVat = $this->money($invoiceSheet->getCell('G' . $r)->getCalculatedValue())
                    + $this->money($invoiceSheet->getCell('J' . $r)->getCalculatedValue());
                $storedTotal = $this->money($invoiceSheet->getCell('L' . $r)->getCalculatedValue());

                return [
                    'source_row' => $r,
                    'musaned_number' => trim((string) $invoiceSheet->getCell('A' . $r)->getValue()),
                    'source_invoice_number' => trim((string) $invoiceSheet->getCell('B' . $r)->getValue()),
                    'invoice_date' => $this->parseExcelDate($invoiceSheet->getCell('C' . $r)->getValue()),
                    'client_name' => trim((string) $invoiceSheet->getCell('E' . $r)->getValue()),
                    'supplier_name' => trim((string) $invoiceSheet->getCell('M' . $r)->getValue()),
                    'stored_vat' => $storedVat,
                    'stored_total' => $storedTotal,
                    'payload' => $payload,
                    'source_errors' => array_values(array_filter([
                        abs($storedVat - (float) $payload['vat_amount']) >= 0.02 ? 'ضريبة Excel لا تطابق 15% من البنود الخاضعة' : null,
                        abs($storedTotal - (float) $payload['total_due']) >= 0.02 ? 'إجمالي Excel لا يطابق مجموع البنود والضريبة' : null,
                    ])),
                ];
            })->keyBy('musaned_number');

        $transfers = collect(range(2, $transferSheet->getHighestDataRow()))
            ->filter(fn (int $r) => ctype_digit(trim((string) $transferSheet->getCell('A' . $r)->getValue())))
            ->map(fn (int $r) => [
                'source_row' => $r,
                'musaned_number' => trim((string) $transferSheet->getCell('A' . $r)->getValue()),
                'reference_number' => trim((string) $transferSheet->getCell('C' . $r)->getValue()),
                'transfer_date' => $this->parseExcelDate($transferSheet->getCell('D' . $r)->getValue())->toDateString(),
                'invoice_total' => $this->money($transferSheet->getCell('F' . $r)->getCalculatedValue()),
                'platform_fee' => $this->money($transferSheet->getCell('G' . $r)->getCalculatedValue()),
                'transferred_amount' => $this->money($transferSheet->getCell('H' . $r)->getCalculatedValue()),
            ])->values();

        $requestedNumbers = collect((array) $this->option('musaned'))
            ->flatMap(fn ($value) => preg_split('/[,\s]+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();
        if ($requestedNumbers->isNotEmpty()) {
            $availableNumbers = $invoices->keys();
            $missingNumbers = $requestedNumbers->diff($availableNumbers);
            if ($missingNumbers->isNotEmpty()) {
                throw new RuntimeException('أرقام مساند المطلوبة غير موجودة في ورقة الفواتير: ' . $missingNumbers->implode(', '));
            }
            $invoices = $invoices->only($requestedNumbers->all());
            $transfers = $transfers
                ->whereIn('musaned_number', $requestedNumbers->all())
                ->values();
        }

        return compact('invoices', 'transfers');
    }

    private function parseExcelDate($value): Carbon
    {
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))
                ->setTimezone(config('app.timezone'))
                ->startOfDay();
        }

        return Carbon::parse((string) $value, config('app.timezone'))->startOfDay();
    }

    private function money($value): float
    {
        return round(max(0, (float) str_replace([',', ' '], '', trim((string) $value))), 2);
    }

    private function buildPlan(array $source, InvoiceController $invoiceController, YearService $yearService): array
    {
        $numbers = $source['invoices']->keys();
        $candidates = Contract::query()
            ->whereIn('type', ['recruitment', 'maid'])
            ->whereHas('info', fn ($q) => $q->whereIn('msaned_number', $numbers))
            ->with([
                'info', 'user', 'invoice.financeSale.zatca', 'invoice.musanedTransferItem.transfer',
                'externalOfficeDispatch', 'travelHistory', 'maid.nationality', 'maid.office',
                'maid.recruitmentOffice', 'requiredMaidNationality', 'info.manualMaidOffice',
            ])->get()->groupBy(fn (Contract $c) => trim((string) $c->info?->msaned_number));

        $ready = collect();
        $blockers = collect();
        foreach ($source['invoices'] as $number => $row) {
            $group = $candidates->get($number, collect());
            if ($group->isEmpty()) {
                $row['errors'] = ['رقم مساند غير موجود في عقود النظام'];
                $blockers->push($row);
                continue;
            }
            $contract = $this->selectCanonicalContract($group, $row['invoice_date']);
            $row['contract_id'] = (int) $contract->id;
            $row['candidate_contract_ids'] = $group->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $row['errors'] = $row['source_errors'];
            $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
            if (!$contract->user) {
                $row['errors'][] = 'العقد غير مرتبط بعميل';
            }
            if (!$nationality) {
                $row['errors'][] = 'الجنسية غير محددة';
            } elseif (!$invoiceController->resolveContractNationalityLiabilityAccountId($nationality)) {
                $row['errors'][] = 'حساب التزامات عقود الجنسية غير مربوط';
            }
            if (!$contract->resolveExternalOfficeDispatchOffice()) {
                $row['errors'][] = 'المكتب الخارجي غير محدد على العقد/العاملة';
            }
            try {
                $invoiceController->assertRecruitmentInvoiceAccountingConfiguration($row['invoice_date'], $yearService);
            } catch (Throwable $e) {
                $row['errors'][] = $e->getMessage();
            }
            $invoice = $contract->invoice;
            $changed = $invoice ? $this->invoiceAmountChanged($invoice, $row['payload']) : false;
            if ($invoice?->financeSale?->zatca_invoice_id || $invoice?->financeSale?->zatca) {
                $row['errors'][] = 'الفاتورة مرسلة للزكاة';
            }
            if ($changed && $invoice?->musanedTransferItem?->transfer) {
                $row['errors'][] = 'فاتورة مختلفة مرتبطة بتحويل مساند قائم';
            }
            if ($changed && $invoice && ($invoice->arrival_revenue_constraint_id || $invoice->external_office_cost_constraint_id || $invoice->external_office_purchase_id)) {
                $row['errors'][] = 'فاتورة مختلفة مرتبطة بقيود وصول/مورد قائمة';
            }
            $travel = $contract->travelHistory;
            if (!$travel || blank($travel->arrival_date) || ($travel->status !== 'completed' && !in_array($contract->status, ['arrival', 'completed', 'back'], true))) {
                $travel = null;
            }
            $row['arrival_travel_id'] = $travel?->id;
            $row['sibling_dispatch'] = $group->pluck('externalOfficeDispatch')->filter()->sortByDesc('id')->first();
            $row['errors'] = array_values(array_unique(array_filter($row['errors'])));
            $row['errors'] ? $blockers->push($row) : $ready->push($row);
        }

        $readyByNumber = $ready->keyBy('musaned_number');
        $transferAlreadyCorrect = collect();
        $transferBlockers = collect();
        $transferMissing = collect();
        foreach ($source['transfers'] as $transfer) {
            $invoiceRow = $readyByNumber->get($transfer['musaned_number']);
            if (!$invoiceRow) {
                $transfer['errors'] = ['الفاتورة/العقد غير جاهز للاستيراد'];
                $transferBlockers->push($transfer);
                continue;
            }
            if (abs($transfer['invoice_total'] - (float) $invoiceRow['payload']['total_due']) >= 0.02) {
                $transfer['errors'] = ['إجمالي التحويل لا يطابق إجمالي الفاتورة'];
                $transferBlockers->push($transfer);
                continue;
            }
            if ($transfer['transferred_amount'] - (float) $invoiceRow['payload']['total_due'] >= 0.01) {
                $transfer['errors'] = ['المبلغ المحول من مساند أكبر من إجمالي الفاتورة ويحتاج تسوية يدوية'];
                $transferBlockers->push($transfer);
                continue;
            }
            $contract = Contract::query()->with('invoice.musanedTransferItem.transfer')->find($invoiceRow['contract_id']);
            $existing = $contract?->invoice?->musanedTransferItem;
            if ($existing?->transfer) {
                $matches = abs((float) $existing->transferred_amount - $transfer['transferred_amount']) < 0.02
                    && trim((string) $existing->transfer->reference_number) === $transfer['reference_number']
                    && $existing->transfer->transfer_date?->toDateString() === $transfer['transfer_date'];
                if ($matches) {
                    $transferAlreadyCorrect->push($transfer);
                } else {
                    $transfer['errors'] = ['تحويل مساند قائم مختلف عن Excel'];
                    $transferBlockers->push($transfer);
                }
                continue;
            }
            $transfer['contract_id'] = $invoiceRow['contract_id'];
            $transferMissing->push($transfer);
        }

        $transferGroups = $transferMissing
            ->groupBy(fn (array $r) => $r['reference_number'] . '|' . $r['transfer_date'])
            ->map(fn (Collection $items) => [
                'reference_number' => $items->first()['reference_number'],
                'transfer_date' => $items->first()['transfer_date'],
                'items' => $items->values()->all(),
            ])->values();

        return compact('ready', 'blockers', 'transferGroups', 'transferAlreadyCorrect', 'transferBlockers');
    }

    private function selectCanonicalContract(Collection $contracts, Carbon $invoiceDate): Contract
    {
        return $contracts->sort(function (Contract $a, Contract $b) use ($invoiceDate): int {
            $score = function (Contract $c) use ($invoiceDate): int {
                $dateMatch = in_array($invoiceDate->toDateString(), [
                    substr((string) $c->contract_date, 0, 10),
                    substr((string) $c->info?->contract_date, 0, 10),
                ], true);
                return ($c->invoice ? 1000000 : 0)
                    + ($c->externalOfficeDispatch ? 100000 : 0)
                    + (filled($c->travelHistory?->arrival_date) ? 10000 : 0)
                    + ($dateMatch ? 1000 : 0)
                    + (in_array($c->status, ['arrival', 'completed'], true) ? 100 : 0)
                    - (int) $c->id;
            };
            return $score($b) <=> $score($a);
        })->first();
    }

    private function invoiceAmountChanged($invoice, array $payload): bool
    {
        foreach (['direct_recruitment_cost', 'external_recruitment_cost', 'government_cost', 'internal_ticket_cost', 'vat_amount', 'total_due'] as $field) {
            if (abs((float) $invoice->{$field} - (float) $payload[$field]) >= 0.01) {
                return true;
            }
        }
        return false;
    }

    private function reconcileDispatch(Contract $contract, array $row): array
    {
        $usd = round((float) $row['payload']['external_recruitment_cost'] / self::USD_TO_SAR_RATE, 2);
        $existingCostConstraintId = DB::table('invoices')
            ->where('contract_id', $contract->id)
            ->value('external_office_cost_constraint_id');
        if ($existingCostConstraintId) {
            $postedCostSar = (float) DB::table('fn_constraint_details')
                ->where('constraint_id', $existingCostConstraintId)
                ->sum('debtor_value');
            if ($postedCostSar > 0) {
                $usd = round($postedCostSar / self::USD_TO_SAR_RATE, 2);
            }
        }
        $sibling = $row['sibling_dispatch'];
        $source = $contract->buildExternalOfficeDispatchSourceData(number_format($usd, 2, '.', ''));
        $payload = array_merge($source, [
            'dispatch_date' => $contract->externalOfficeDispatch?->dispatch_date?->toDateString()
                ?: $sibling?->dispatch_date?->toDateString()
                ?: $row['invoice_date']->toDateString(),
            'arrival_station' => $contract->externalOfficeDispatch?->arrival_station
                ?: $sibling?->arrival_station
                ?: $contract->resolveExternalOfficeDispatchArrivalStation(),
            'office_contract_cost_usd' => number_format($usd, 2, '.', ''),
            'source_hash' => $contract->buildExternalOfficeDispatchSourceHash(number_format($usd, 2, '.', '')),
            'updated_by_admin_id' => Auth::guard('admin')->id(),
        ]);
        if (!$contract->externalOfficeDispatch) {
            $payload['created_by_admin_id'] = Auth::guard('admin')->id();
        }
        $dispatch = $contract->externalOfficeDispatch()->updateOrCreate(['contract_id' => $contract->id], $payload);
        return ['contract_id' => $contract->id, 'dispatch_id' => $dispatch->id, 'office_cost_usd' => (float) $dispatch->office_contract_cost_usd];
    }

    private function resolveAdmin(): ?Admin
    {
        $value = trim((string) $this->option('admin-id'));
        if ($value === '') return null;
        if (!ctype_digit($value) || (int) $value <= 0) throw new RuntimeException('--admin-id غير صالح.');
        return Admin::query()->activeForOperations()->notExternalOfficeAgents()->find((int) $value)
            ?? throw new RuntimeException('المدير المحدد غير موجود أو غير صالح.');
    }

    private function resolveBankAccountId(): ?int
    {
        $value = trim((string) $this->option('bank-account-id'));
        if ($value === '') return null;
        if (!ctype_digit($value) || (int) $value <= 0) throw new RuntimeException('--bank-account-id غير صالح.');
        return (int) $value;
    }

    private function assertApplyGuards(string $database, string $fingerprint, string $fileHash, ?Admin $admin, ?int $bankId, array $plan): void
    {
        if (!$admin || !$bankId) throw new RuntimeException('رفض التنفيذ: --admin-id و --bank-account-id مطلوبان.');
        if ((string) $this->option('confirm') !== self::CONFIRMATION) throw new RuntimeException('عبارة التأكيد غير صحيحة.');
        if (!hash_equals($database, (string) $this->option('expected-database'))) throw new RuntimeException('اسم قاعدة البيانات لا يطابق المعاينة.');
        if (!hash_equals($fingerprint, (string) $this->option('expected-database-fingerprint'))) throw new RuntimeException('بصمة قاعدة البيانات لا تطابق المعاينة.');
        if (!hash_equals($fileHash, (string) $this->option('expected-file-sha256'))) throw new RuntimeException('ملف Excel تغير بعد المعاينة.');
        if (($plan['blockers']->isNotEmpty() || $plan['transferBlockers']->isNotEmpty()) && !$this->option('allow-skips')) {
            throw new RuntimeException('توجد سجلات غير مطابقة؛ استخدم --allow-skips لتنفيذ الجاهز فقط وإثبات الباقي بالتقرير.');
        }
    }

    private function display(string $path, string $database, string $fingerprint, string $fileHash, ?Admin $admin, ?int $bankId, array $plan): void
    {
        $this->table(['البيان', 'القيمة'], [
            ['الملف', $path], ['بصمة الملف', $fileHash], ['قاعدة البيانات', $database], ['بصمة قاعدة البيانات', $fingerprint],
            ['المدير', $admin ? "#{$admin->id} - {$admin->name}" : 'غير محدد'], ['حساب البنك', $bankId ?: 'غير محدد'],
            ['فواتير جاهزة', $plan['ready']->count()], ['فواتير/عقود غير جاهزة', $plan['blockers']->count()],
            ['تحويلات موجودة ومطابقة', $plan['transferAlreadyCorrect']->count()], ['دفعات تحويل جديدة', $plan['transferGroups']->count()],
            ['تحويلات غير جاهزة', $plan['transferBlockers']->count()], ['وصولات ستُعالج محاسبياً', $plan['ready']->whereNotNull('arrival_travel_id')->count()],
        ]);
        if ($plan['blockers']->isNotEmpty()) {
            $this->table(['صف', 'مساند', 'العقد', 'المانع'], $plan['blockers']->take(100)->map(fn ($r) => [
                $r['source_row'], $r['musaned_number'], $r['contract_id'] ?? '-', implode('؛ ', $r['errors']),
            ])->all());
        }
        if ($plan['transferBlockers']->isNotEmpty()) {
            $this->table(['صف تحويل', 'مساند', 'المرجع', 'المانع'], $plan['transferBlockers']->take(100)->map(fn ($r) => [
                $r['source_row'], $r['musaned_number'], $r['reference_number'], implode('؛ ', $r['errors']),
            ])->all());
        }
    }

    private function databaseFingerprint(): string
    {
        $connection = DB::connection();
        $config = $connection->getConfig();
        return hash('sha256', implode('|', [(string) ($config['driver'] ?? ''), (string) ($config['host'] ?? ''), (string) ($config['port'] ?? ''), (string) $connection->getDatabaseName()]));
    }

    private function writeReport(string $path, string $database, string $fileHash, Admin $admin, array $plan, array $audit): string
    {
        $directory = storage_path('app/exports');
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        $report = $directory . '/platinum-recruitment-accounting-' . now()->format('Y-m-d_His') . '.json';
        file_put_contents($report, json_encode([
            'executed_at' => now()->toIso8601String(), 'database' => $database, 'source_file' => basename($path),
            'source_sha256' => $fileHash, 'admin_id' => $admin->id, 'audit' => $audit,
            'skipped_invoices' => $plan['blockers']->map(fn ($r) => ['source_row' => $r['source_row'], 'musaned_number' => $r['musaned_number'], 'contract_id' => $r['contract_id'] ?? null, 'candidate_contract_ids' => $r['candidate_contract_ids'] ?? [], 'errors' => $r['errors']])->values(),
            'skipped_transfers' => $plan['transferBlockers']->values(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $report;
    }
}

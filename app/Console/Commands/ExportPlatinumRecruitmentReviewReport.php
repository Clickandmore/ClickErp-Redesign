<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ExportPlatinumRecruitmentReviewReport extends Command
{
    protected $signature = 'platinum:export-recruitment-review
        {audit : مسار تقرير JSON الناتج من الاستيراد}
        {source : مسار ملف Excel الأصلي}
        {--output= : مسار ملف التقرير الناتج}';

    protected $description = 'إنشاء تقرير Excel بالعقود وتحويلات مساند التي لم تُستورد وأسبابها';

    public function handle(): int
    {
        $auditPath = $this->resolveFile((string) $this->argument('audit'));
        $sourcePath = $this->resolveFile((string) $this->argument('source'));
        $audit = json_decode((string) file_get_contents($auditPath), true, 512, JSON_THROW_ON_ERROR);
        $skippedInvoices = collect($audit['skipped_invoices'] ?? []);
        $skippedTransfers = collect($audit['skipped_transfers'] ?? []);

        $sourceBook = IOFactory::load($sourcePath);
        $invoiceSheet = $sourceBook->getSheetByName('فواتير العقود')
            ?? throw new RuntimeException('ورقة فواتير العقود غير موجودة في المصدر.');

        $numbers = $skippedInvoices->pluck('musaned_number')
            ->merge($skippedTransfers->pluck('musaned_number'))
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->unique();
        $contracts = Contract::query()
            ->with([
                'user', 'info', 'maid.nationality', 'requiredMaidNationality',
                'invoice.musanedTransferItem.transfer', 'order',
            ])
            ->whereHas('info', fn ($query) => $query->whereIn('msaned_number', $numbers))
            ->get()
            ->groupBy(fn (Contract $contract) => trim((string) $contract->info?->msaned_number));
        $invoiceBlockersByNumber = $skippedInvoices->keyBy(fn ($row) => trim((string) ($row['musaned_number'] ?? '')));
        $resolvedInvoiceCount = $skippedInvoices->filter(function ($row) use ($contracts) {
            return $contracts->get(trim((string) ($row['musaned_number'] ?? '')))?->contains(
                fn (Contract $contract) => (bool) $contract->invoice
            );
        })->count();
        $resolvedTransferCount = $skippedTransfers->filter(function ($row) use ($contracts) {
            return $contracts->get(trim((string) ($row['musaned_number'] ?? '')))?->contains(
                fn (Contract $contract) => (bool) $contract->invoice?->musanedTransferItem
            );
        })->count();

        $finalSkippedInvoices = $skippedInvoices->reject(function ($row) use ($contracts) {
            $sourceRow = (int) ($row['source_row'] ?? 0);
            $contract = $contracts
                ->get(trim((string) ($row['musaned_number'] ?? '')))?->first(
                    fn (Contract $item) => (int) ($item->invoice?->legacy_source_row ?? 0) === $sourceRow
                );

            return (bool) $contract;
        })->values();
        $finalSkippedTransfers = $skippedTransfers->reject(function ($row) use ($contracts) {
            $contract = $contracts
                ->get(trim((string) ($row['musaned_number'] ?? '')))?->first(
                    fn (Contract $item) => $this->transferMatchesRow($item, $row)
                );

            return (bool) $contract;
        })->values();
        $excludedLiabilityCount = $skippedInvoices
            ->filter(fn ($row) => str_contains(implode(' ', $row['errors'] ?? []), 'حساب التزامات'))
            ->count();

        $sourceInvoiceRows = collect(range(2, $invoiceSheet->getHighestDataRow()))
            ->mapWithKeys(function (int $row) use ($invoiceSheet): array {
                $number = trim((string) $invoiceSheet->getCell('A' . $row)->getValue());
                if (!ctype_digit($number)) {
                    return [];
                }

                return [$number => [
                    'row' => $row,
                    'invoice_number' => trim((string) $invoiceSheet->getCell('B' . $row)->getValue()),
                    'invoice_date' => (string) $invoiceSheet->getCell('C' . $row)->getFormattedValue(),
                    'client_name' => trim((string) $invoiceSheet->getCell('E' . $row)->getValue()),
                    'total' => (float) $invoiceSheet->getCell('L' . $row)->getCalculatedValue(),
                ]];
            });
        $effectiveDateSql = "DATE(COALESCE(
            NULLIF((SELECT ci.contract_date FROM contract_infos ci WHERE ci.contract_id = contracts.id LIMIT 1), '0000-00-00'),
            NULLIF(contracts.contract_date, '0000-00-00')
        ))";
        $withoutInvoiceContracts = Contract::query()
            ->with(['info', 'user', 'maid.nationality'])
            ->whereIn('type', ['recruitment', 'maid'])
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'shelter'))
            ->whereDoesntHave('invoice')
            ->whereRaw($effectiveDateSql . ' BETWEEN ? AND ?', ['2026-01-01', '2026-06-30'])
            ->orderBy('id')
            ->get();
        $withoutInvoiceNumbers = $withoutInvoiceContracts
            ->pluck('info.msaned_number')
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->unique();
        $relatedContractsByNumber = Contract::query()
            ->with(['info', 'invoice', 'user', 'maid'])
            ->whereHas('info', fn ($query) => $query->whereIn('msaned_number', $withoutInvoiceNumbers))
            ->get()
            ->groupBy(fn (Contract $contract) => trim((string) $contract->info?->msaned_number));
        $withoutInvoiceInExcel = $withoutInvoiceContracts->filter(
            fn (Contract $contract) => $sourceInvoiceRows->has(trim((string) $contract->info?->msaned_number))
        );
        $withoutInvoiceNotInExcel = $withoutInvoiceContracts->filter(function (Contract $contract) use ($sourceInvoiceRows) {
            $number = trim((string) $contract->info?->msaned_number);

            return $number !== '' && !$sourceInvoiceRows->has($number);
        });
        $withoutInvoiceMissingNumber = $withoutInvoiceContracts->filter(
            fn (Contract $contract) => trim((string) $contract->info?->msaned_number) === ''
        );

        $book = new Spreadsheet();
        $summary = $book->getActiveSheet();
        $summary->setTitle('الملخص');
        $contractsSheet = $book->createSheet()->setTitle('عقود تحتاج مراجعة');
        $transfersSheet = $book->createSheet()->setTitle('تحويلات تحتاج مراجعة');
        $withoutInvoicesSheet = $book->createSheet()->setTitle('العقود بلا فاتورة 1-6');

        $summary->fromArray([
            ['تقرير Platinum النهائي لمراجعة الفواتير وتحويلات مساند', null],
            ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')],
            ['ملف المصدر', basename($sourcePath)],
            ['تقرير تنفيذ الاستيراد', basename($auditPath)],
            ['موانع الفواتير المتبقية للمراجعة', $finalSkippedInvoices->count()],
            ['موانع التحويل المتبقية للمراجعة', $finalSkippedTransfers->count()],
            ['حالات حساب التزام الجنسية المستبعدة بعد معالجتها', $excludedLiabilityCount],
            ['العقود بلا فاتورة 2026-01-01 إلى 2026-06-30', $withoutInvoiceContracts->count()],
            ['منها رقم مساند موجود في Excel', $withoutInvoiceInExcel->count()],
            ['منها رقم مساند غير موجود في Excel', $withoutInvoiceNotInExcel->count()],
            ['منها بلا رقم مساند', $withoutInvoiceMissingNumber->count()],
            ['أرقام مساند مكررة ولها فاتورة على عقد آخر', $withoutInvoiceInExcel->filter(function (Contract $contract) use ($relatedContractsByNumber) {
                $number = trim((string) $contract->info?->msaned_number);

                return $relatedContractsByNumber->get($number)?->contains(
                    fn (Contract $related) => $related->id !== $contract->id && (bool) $related->invoice
                );
            })->count()],
            ['ملاحظة', 'تم استبعاد الحالات التي زال مانع حساب التزام الجنسية عنها. لا تُنشأ فاتورة ثانية لنفس رقم مساند المكرر قبل اعتماد العقد الصحيح.'],
        ], null, 'A1');

        $contractHeaders = [
            'م', 'صف Excel', 'رقم مساند', 'رقم الفاتورة بالمصدر', 'تاريخ الفاتورة',
            'العميل بالمصدر', 'إجمالي الفاتورة', 'حالة الكيان بالنظام', 'رقم العقد بالنظام',
            'رقم الطلب بالنظام', 'حالة الطلب', 'رقم العميل', 'العميل بالنظام', 'رقم العاملة',
            'الجنسية', 'حالة العقد', 'رقم الفاتورة بالنظام', 'حالة المعالجة الآن',
            'السبب الفني التفصيلي وقت الاستيراد', 'البيانات الناقصة/غير المطابقة',
            'الإجراء المطلوب', 'حالة مراجعة الفريق', 'ملاحظات الفريق',
        ];
        $contractsSheet->fromArray($contractHeaders, null, 'A1');

        foreach ($finalSkippedInvoices as $index => $row) {
            $sourceRow = (int) $row['source_row'];
            $number = trim((string) $row['musaned_number']);
            $contract = $contracts->get($number)?->sortByDesc(fn (Contract $item) => (int) (bool) $item->invoice)->first();
            $reason = implode('؛ ', $row['errors'] ?? []);
            $nationality = $contract?->maid?->nationality ?? $contract?->requiredMaidNationality;
            $sourceClient = trim((string) $invoiceSheet->getCell('E' . $sourceRow)->getValue());
            $context = $this->resolveSystemContext($contract, $sourceClient);
            $invoiceImported = $contract?->invoice
                && (int) $contract->invoice->legacy_source_row === $sourceRow;
            $invoiceCurrentStatus = !$contract?->invoice
                ? 'لا توجد فاتورة حتى الآن'
                : ($invoiceImported ? 'تمت الإضافة من ملف Excel بعد معالجة المانع' : 'فاتورة موجودة مسبقًا وتحتاج مقارنة مع Excel');
            $values = [
                $index + 1,
                $sourceRow,
                (string) $row['musaned_number'],
                (string) $invoiceSheet->getCell('B' . $sourceRow)->getValue(),
                (string) $invoiceSheet->getCell('C' . $sourceRow)->getFormattedValue(),
                $sourceClient,
                (float) $invoiceSheet->getCell('L' . $sourceRow)->getCalculatedValue(),
                $context['entity_state'],
                $contract?->id,
                $context['order_id'],
                $context['order_status'],
                $context['user_id'],
                $context['user_name'],
                $context['maid_id'],
                $nationality?->display_name ?? $nationality?->title,
                $contract?->status,
                $contract?->invoice?->id,
                $invoiceCurrentStatus,
                $reason,
                $this->missingDataDetail($reason, $context),
                $this->requiredAction($reason),
                $invoiceImported ? 'تمت المعالجة - مطلوب تحقق الفريق' : 'قيد المراجعة',
                '',
            ];
            $excelRow = $index + 2;
            $contractsSheet->fromArray($values, null, 'A' . $excelRow);
            $contractsSheet->setCellValueExplicit('C' . $excelRow, (string) $row['musaned_number'], DataType::TYPE_STRING);
        }

        $transferHeaders = [
            'م', 'صف Excel', 'رقم مساند', 'مرجع التحويل', 'تاريخ التحويل', 'إجمالي الفاتورة',
            'رسوم المنصة/البنك', 'المبلغ المحول', 'الفرق', 'علاقة المبلغ بالفاتورة',
            'مانع التحويل المباشر', 'مانع الفاتورة/العقد الذي تبعه التحويل',
            'رقم العقد الآن', 'رقم الفاتورة الآن', 'رقم دفعة مساند الآن', 'حالة المعالجة الآن',
            'الإجراء المطلوب', 'حالة مراجعة الفريق', 'ملاحظات الفريق',
        ];
        $transfersSheet->fromArray($transferHeaders, null, 'A1');
        foreach ($finalSkippedTransfers as $index => $row) {
            $reason = implode('؛ ', $row['errors'] ?? []);
            $number = trim((string) ($row['musaned_number'] ?? ''));
            $invoiceBlocker = $invoiceBlockersByNumber->get($number);
            $invoiceReason = implode('؛ ', $invoiceBlocker['errors'] ?? []);
            $contract = $contracts->get($number)?->sortByDesc(fn (Contract $item) => (int) (bool) $item->invoice)->first();
            $transferItem = $contract?->invoice?->musanedTransferItem;
            $difference = round((float) ($row['transferred_amount'] ?? 0) - (float) ($row['invoice_total'] ?? 0), 2);
            $transferMatches = $transferItem?->transfer
                && abs((float) $transferItem->transferred_amount - (float) ($row['transferred_amount'] ?? 0)) < 0.02
                && trim((string) $transferItem->transfer->reference_number) === trim((string) ($row['reference_number'] ?? ''))
                && $transferItem->transfer->transfer_date?->toDateString() === ($row['transfer_date'] ?? null);
            $values = [
                $index + 1,
                $row['source_row'] ?? null,
                (string) ($row['musaned_number'] ?? ''),
                (string) ($row['reference_number'] ?? ''),
                $row['transfer_date'] ?? null,
                (float) ($row['invoice_total'] ?? 0),
                (float) ($row['platform_fee'] ?? 0),
                (float) ($row['transferred_amount'] ?? 0),
                $difference,
                $difference < -0.01 ? 'أقل من الفاتورة (مسموح؛ الفرق رسوم/رصيد)' : ($difference > 0.01 ? 'أكبر من الفاتورة (يتطلب تسوية)' : 'مساوٍ للفاتورة'),
                $reason,
                $invoiceReason ?: 'لا يوجد مانع فاتورة مسجل',
                $contract?->id,
                $contract?->invoice?->id,
                $transferItem?->musaned_transfer_id,
                !$transferItem ? 'لا يوجد تحويل حتى الآن' : ($transferMatches ? 'تمت إضافة/مطابقة التحويل بعد معالجة المانع' : 'يوجد تحويل مختلف يحتاج مقارنة مع Excel'),
                $this->requiredTransferAction($reason . ' ' . $invoiceReason),
                $transferMatches ? 'تمت المعالجة - مطلوب تحقق الفريق' : 'قيد المراجعة',
                '',
            ];
            $excelRow = $index + 2;
            $transfersSheet->fromArray($values, null, 'A' . $excelRow);
            $transfersSheet->setCellValueExplicit('C' . $excelRow, (string) ($row['musaned_number'] ?? ''), DataType::TYPE_STRING);
            $transfersSheet->setCellValueExplicit('D' . $excelRow, (string) ($row['reference_number'] ?? ''), DataType::TYPE_STRING);
        }

        $withoutInvoiceHeaders = [
            'م', 'رقم العقد', 'تاريخ العقد', 'حالة العقد', 'رقم مساند',
            'رقم العميل', 'العميل', 'رقم العاملة', 'العاملة', 'الجواز', 'الجنسية',
            'نتيجة المطابقة مع Excel', 'صف Excel', 'رقم الفاتورة في Excel', 'تاريخ الفاتورة',
            'العميل في Excel', 'إجمالي Excel', 'العقود الأخرى بنفس رقم مساند',
            'حالات العقود الأخرى', 'الفواتير الموجودة على عقود أخرى', 'نفس العميل؟', 'نفس العاملة؟',
            'سبب عدم وجود فاتورة على هذا العقد', 'الإجراء المطلوب من الفريق', 'حالة المراجعة', 'ملاحظات الفريق',
        ];
        $withoutInvoicesSheet->fromArray($withoutInvoiceHeaders, null, 'A1');
        foreach ($withoutInvoiceContracts->values() as $index => $contract) {
            $number = trim((string) $contract->info?->msaned_number);
            $source = $number !== '' ? $sourceInvoiceRows->get($number) : null;
            $others = $number !== ''
                ? $relatedContractsByNumber->get($number, collect())->where('id', '!=', $contract->id)->values()
                : collect();
            $invoicedOthers = $others->filter(fn (Contract $other) => (bool) $other->invoice)->values();
            $sameUser = $invoicedOthers->contains(fn (Contract $other) => (int) $other->user_id === (int) $contract->user_id);
            $sameMaid = $invoicedOthers->contains(fn (Contract $other) => (int) $other->maid_id === (int) $contract->maid_id);

            if ($number === '') {
                $matchResult = 'لا يمكن المطابقة: رقم مساند فارغ';
                $reason = 'العقد بلا رقم مساند، لذلك لا يمكن ربطه بأي صف في Excel.';
                $action = 'تحديد رقم مساند الصحيح من المستندات أو الطلب وتحديث العقد، ثم إعادة المطابقة.';
            } elseif (!$source) {
                $matchResult = 'رقم مساند غير موجود في Excel';
                $reason = 'رقم مساند موجود بالنظام، لكن لا يوجد له صف في ورقة فواتير العقود.';
                $action = 'توفير فاتورة المصدر أو اعتماد أن العقد خارج نطاق ملف Platinum.';
            } elseif ($invoicedOthers->isNotEmpty()) {
                $matchResult = 'موجود في Excel والفاتورة على عقد آخر';
                $reason = 'رقم مساند مكرر على عدة عقود؛ أُضيفت فاتورة Excel مرة واحدة على العقد الأساسي: '
                    . $invoicedOthers->pluck('id')->implode(', ') . '.';
                $action = (!$sameUser && !$sameMaid)
                    ? 'تصحيح رقم مساند على هذا العقد لأنه يخص عميلًا وعاملة مختلفين؛ لا تنسخ الفاتورة.'
                    : 'مراجعة ازدواجية العقود واعتماد العقد الأساسي أو دمج/إغلاق السجل المكرر؛ لا تنشئ فاتورة ثانية.';
            } else {
                $matchResult = 'موجود في Excel بلا عقد آخر يحمل الفاتورة';
                $reason = 'يوجد صف Excel مطابق، لكن لا توجد فاتورة مرتبطة بأي عقد يحمل الرقم حاليًا.';
                $action = 'فحص جاهزية العقد محاسبيًا ثم إعادة الاستيراد الانتقائي لهذا الرقم.';
            }

            $values = [
                $index + 1,
                $contract->id,
                $contract->info?->contract_date ?: $contract->contract_date,
                $contract->status,
                $number,
                $contract->user_id,
                $contract->user?->name,
                $contract->maid_id,
                $contract->maid?->name,
                $contract->maid?->passport_num,
                $contract->maid?->nationality?->display_name ?? $contract->maid?->nationality?->title,
                $matchResult,
                $source['row'] ?? null,
                $source['invoice_number'] ?? null,
                $source['invoice_date'] ?? null,
                $source['client_name'] ?? null,
                $source['total'] ?? null,
                $others->pluck('id')->implode(', '),
                $others->map(fn (Contract $other) => $other->id . ': ' . $other->status)->implode(' | '),
                $invoicedOthers->map(fn (Contract $other) => $other->id . ': فاتورة ' . $other->invoice->id)->implode(' | '),
                $invoicedOthers->isEmpty() ? '' : ($sameUser ? 'نعم' : 'لا'),
                $invoicedOthers->isEmpty() ? '' : ($sameMaid ? 'نعم' : 'لا'),
                $reason,
                $action,
                'قيد المراجعة',
                '',
            ];
            $excelRow = $index + 2;
            $withoutInvoicesSheet->fromArray($values, null, 'A' . $excelRow);
            $withoutInvoicesSheet->setCellValueExplicit('E' . $excelRow, $number, DataType::TYPE_STRING);
        }

        $this->styleSummary($summary);
        $this->styleTable($contractsSheet, count($contractHeaders), $finalSkippedInvoices->count() + 1);
        $this->styleTable($transfersSheet, count($transferHeaders), $finalSkippedTransfers->count() + 1);
        $contractsSheet->getStyle('G2:G' . ($finalSkippedInvoices->count() + 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $transfersSheet->getStyle('F2:I' . ($finalSkippedTransfers->count() + 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $this->styleTable($withoutInvoicesSheet, count($withoutInvoiceHeaders), $withoutInvoiceContracts->count() + 1);
        $withoutInvoicesSheet->getStyle('Q2:Q' . ($withoutInvoiceContracts->count() + 1))->getNumberFormat()->setFormatCode('#,##0.00');

        $output = trim((string) $this->option('output'));
        if ($output === '') {
            $output = storage_path('app/exports/platinum-recruitment-review-' . now()->format('Y-m-d_His') . '.xlsx');
        } elseif (!str_starts_with($output, DIRECTORY_SEPARATOR)) {
            $output = base_path($output);
        }
        $directory = dirname($output);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('تعذر إنشاء مجلد التقرير.');
        }
        (new Xlsx($book))->save($output);

        $this->info('تم إنشاء تقرير مراجعة الفريق بنجاح.');
        $this->line('موانع العقود: ' . $finalSkippedInvoices->count()
            . ' | موانع التحويل: ' . $finalSkippedTransfers->count()
            . ' | العقود بلا فاتورة في الفترة: ' . $withoutInvoiceContracts->count());
        $this->line('الملف: ' . $output);
        return self::SUCCESS;
    }

    private function resolveFile(string $value): string
    {
        $path = str_starts_with($value, DIRECTORY_SEPARATOR) ? $value : base_path($value);
        $real = realpath($path);
        if (!$real || !is_readable($real)) {
            throw new RuntimeException('الملف غير موجود أو غير قابل للقراءة: ' . $value);
        }
        return $real;
    }

    private function countReason($rows, string $needle): int
    {
        return $rows->filter(fn ($row) => str_contains(implode(' ', $row['errors'] ?? []), $needle))->count();
    }

    private function transferMatchesRow(Contract $contract, array $row): bool
    {
        $item = $contract->invoice?->musanedTransferItem;

        return (bool) ($item?->transfer
            && abs((float) $item->transferred_amount - (float) ($row['transferred_amount'] ?? 0)) < 0.02
            && trim((string) $item->transfer->reference_number) === trim((string) ($row['reference_number'] ?? ''))
            && $item->transfer->transfer_date?->toDateString() === ($row['transfer_date'] ?? null));
    }

    private function resolveSystemContext(?Contract $contract, string $sourceClient): array
    {
        if ($contract) {
            return [
                'entity_state' => 'عقد موجود',
                'order_id' => $contract->order_id,
                'order_status' => $contract->order?->status,
                'user_id' => $contract->user_id,
                'user_name' => $contract->user?->name,
                'maid_id' => $contract->maid_id,
            ];
        }

        $user = User::query()->where('name', $sourceClient)->latest('id')->first();
        $order = $user ? Order::query()
            ->with('contracts.info')
            ->where('user_id', $user->id)
            ->where('job_type_id', 1)
            ->latest('id')
            ->first() : null;
        $entityState = 'لا يوجد عميل مطابق بالاسم حرفيًا';
        if ($order?->contracts->isNotEmpty()) {
            $otherNumbers = $order->contracts->pluck('info.msaned_number')->filter()->implode(', ');
            $entityState = 'طلب مرشح مرتبط بعقد/رقم مساند مختلف' . ($otherNumbers ? ': ' . $otherNumbers : '');
        } elseif ($order) {
            $entityState = 'طلب استقدام موجود بلا عقد';
        } elseif ($user) {
            $entityState = 'عميل موجود بلا طلب استقدام مطابق';
        }

        return [
            'entity_state' => $entityState,
            'order_id' => $order?->id,
            'order_status' => $order?->status,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'maid_id' => $order?->maid_id,
        ];
    }

    private function missingDataDetail(string $reason, array $context): string
    {
        if (str_contains($reason, 'رقم مساند غير موجود') && $context['entity_state'] === 'عقد موجود') {
            return 'وقت الاستيراد الأول لم يوجد عقد مرتبط برقم مساند هذا. العقد الظاهر حاليًا أُنشئ أو رُبط لاحقًا بعد التحقق من الطلب والعميل والعاملة.';
        }
        if (str_contains($reason, 'رقم مساند غير موجود') && str_starts_with($context['entity_state'], 'طلب استقدام موجود بلا عقد')) {
            return 'وقت الاستيراد كان الطلب والعميل والعاملة موجودين، لكن سجل Contract وContractInfo ورقم مساند لم تكن منشأة؛ لذلك تعذر ربط الفاتورة والقيد.';
        }
        if (str_contains($reason, 'رقم مساند غير موجود') && str_contains($context['entity_state'], 'مختلف')) {
            return 'وجد النظام طلبًا لنفس العميل، لكنه مرتبط بعقد ورقم مساند مختلفين؛ لا يجوز ربط فاتورة Excel به تلقائيًا دون مستند يثبت أنها لنفس الطلب.';
        }
        if (str_contains($reason, 'رقم مساند غير موجود') && !$context['user_id']) {
            return 'اسم العميل في Excel باللغة الإنجليزية بينما بيانات النظام غالبًا بالعربية؛ لم توجد مطابقة حرفية آمنة. يلزم من الفريق تزويد الهوية/الجوال أو رقم الطلب لتحديد العقد دون تخمين.';
        }
        if (str_contains($reason, 'حساب التزامات')) {
            return 'العقد موجود، لكن حساب التزام الجنسية لم يكن مربوطًا داخل شجرة التزامات عقود الاستقدام وقت التنفيذ.';
        }
        if (str_contains($reason, 'مرتبطة')) {
            return 'يوجد أثر مالي قائم بقيم تختلف عن Excel؛ أوقف النظام التعديل لمنع تغيير فاتورة مرتبطة بقيد أو تحويل منشور.';
        }

        return $reason;
    }

    private function requiredAction(string $reason): string
    {
        return match (true) {
            str_contains($reason, 'غير موجود') => 'إنشاء/استعادة العقد المفقود من الطلب الصحيح وربط رقم مساند، ثم إعادة الاستيراد.',
            str_contains($reason, 'حساب التزامات') => 'ربط حساب التزامات عقود الجنسية من الإعدادات المحاسبية، ثم إعادة الاستيراد.',
            str_contains($reason, 'مرتبطة') => 'مراجعة الفاتورة والقيود وتحويل مساند القائم يدويًا قبل إنشاء قيد تصحيح.',
            default => 'مراجعة بيانات العقد والفاتورة وتصحيح المانع ثم إعادة الاستيراد.',
        };
    }

    private function requiredTransferAction(string $reason): string
    {
        return match (true) {
            str_contains($reason, 'أكبر من إجمالي') => 'مطابقة كشف البنك/مساند وتحديد العقود التي يغطيها الفرق قبل التسجيل.',
            str_contains($reason, 'قائم مختلف') => 'مقارنة المرجع والتاريخ والمبلغ مع التحويل القائم وإنشاء تسوية معتمدة عند الحاجة.',
            str_contains($reason, 'حساب التزامات') => 'ربط حساب التزام الجنسية ثم إعادة الفاتورة والتحويل؛ انخفاض التحويل عن الإجمالي ليس مانعًا.',
            str_contains($reason, 'غير موجود') => 'إنشاء/استعادة العقد المفقود وربط رقم مساند ثم إعادة الفاتورة والتحويل؛ انخفاض التحويل ليس مانعًا.',
            default => 'معالجة مانع العقد أو الفاتورة أولًا، ثم إعادة إضافة التحويل.',
        };
    }

    private function styleSummary($sheet): void
    {
        $sheet->setRightToLeft(true);
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
        $sheet->getStyle('A1:B13')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('A2:A13')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(36);
        $sheet->getColumnDimension('B')->setWidth(85);
        $sheet->freezePane('A2');
    }

    private function styleTable($sheet, int $columnCount, int $lastRow): void
    {
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);
        $sheet->setRightToLeft(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C65911');
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        for ($column = 1; $column <= $columnCount; $column++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($letter)->setWidth(in_array($column, [6, 8, 19, 20, 21, 22], true) ? 42 : 18);
        }
        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getStyle('A2:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->getColor()->setRGB('D9E1F2');
    }
}

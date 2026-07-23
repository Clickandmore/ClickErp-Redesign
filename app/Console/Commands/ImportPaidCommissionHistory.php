<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\LegacyCommissionMarketerResolver;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ImportPaidCommissionHistory extends Command
{
    protected $signature = 'commissions:import-paid-history
        {file=migration_system/data/alrafedin/comission/all_calculated_commissions_combined.xls}
        {--dry-run : Validate and report without writing data}';

    protected $description = 'Import historically calculated and paid commissions without creating an outstanding balance';

    public function handle(LegacyCommissionMarketerResolver $marketerResolver): int
    {
        $path = base_path($this->argument('file'));
        if (! is_file($path)) {
            $this->error("Commission file not found: {$path}");

            return self::FAILURE;
        }

        try {
            $rows = $this->readRows($path, $marketerResolver);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $existingKeys = CommissionBatchItem::query()
            ->whereIn('source_type', $rows->pluck('source_type')->unique())
            ->whereIn('source_id', $rows->pluck('source_id')->unique())
            ->get(['source_type', 'source_id'])
            ->mapWithKeys(fn (CommissionBatchItem $item) => [
                $item->source_type . ':' . $item->source_id => true,
            ]);

        $newRows = $rows
            ->reject(fn (array $row) => $existingKeys->has($row['source_type'] . ':' . $row['source_id']))
            ->values();

        $this->table(
            ['الحالة', 'العدد', 'قيمة العمولة'],
            [
                ['إجمالي الملف', $rows->count(), number_format($rows->sum('commission_amount'), 2)],
                ['موجودة مسبقًا', $rows->count() - $newRows->count(), '-'],
                ['جاهزة للاستيراد', $newRows->count(), number_format($newRows->sum('commission_amount'), 2)],
            ]
        );

        if ($this->option('dry-run') || $newRows->isEmpty()) {
            $this->info($this->option('dry-run') ? 'Dry run completed.' : 'No new commission rows to import.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($newRows, $path) {
            foreach ($newRows->groupBy('marketer_id') as $marketerId => $marketerRows) {
                $latestDate = $marketerRows->max('operation_date');
                $batch = CommissionBatch::query()->create([
                    'marketer_id' => $marketerId,
                    'total_requests' => $marketerRows->count(),
                    'total_base_amount' => 0,
                    'total_commission' => round($marketerRows->sum('commission_amount'), 2),
                    'status' => CommissionBatch::STATUS_APPROVED,
                    'notes' => 'عمولات تاريخية محتسبة ومسـددة في النظام السابق؛ لا تُضاف إلى الرصيد المستحق.',
                    'approved_at' => $latestDate ? Carbon::parse($latestDate)->endOfDay() : now(),
                ]);

                foreach ($marketerRows as $row) {
                    CommissionBatchItem::query()->create([
                        'batch_id' => $batch->id,
                        'source_type' => $row['source_type'],
                        'source_id' => $row['source_id'],
                        'order_id' => $row['order_id'],
                        'contract_id' => $row['contract_id'],
                        'operation_reference' => $row['operation_reference'],
                        'client_name' => $row['client_name'],
                        'operation_date' => $row['operation_date'],
                        'service_type' => $row['service_type'],
                        'base_amount' => 0,
                        'method' => 'fixed',
                        'rate_or_amount' => $row['commission_amount'],
                        'commission_amount' => $row['commission_amount'],
                        'legacy_source' => 'alrafedin_paid_commissions',
                        'legacy_source_file' => basename($path),
                        'legacy_source_row' => $row['source_row'],
                    ]);
                }
            }
        });

        $this->info("Imported {$newRows->count()} paid historical commission operations.");

        return self::SUCCESS;
    }

    private function readRows(
        string $path,
        LegacyCommissionMarketerResolver $marketerResolver
    ): Collection {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheetRows = $reader->load($path)->getActiveSheet()->toArray(null, true, true, true);
        array_shift($sheetRows);

        $recruitmentOrderNumbers = [];
        $rentalOrderNumbers = [];
        foreach ($sheetRows as $row) {
            $department = trim((string) ($row['C'] ?? ''));
            $orderNumber = (int) ($row['D'] ?? 0);
            if ($department === 'استقدام' && $orderNumber > 0) {
                $recruitmentOrderNumbers[] = $orderNumber;
            } elseif ($department === 'تأجير للنقل' && $orderNumber > 0) {
                $rentalOrderNumbers[] = $orderNumber;
            }
        }

        $recruitmentContracts = Contract::query()
            ->whereIn('arabia_order_number', array_unique($recruitmentOrderNumbers))
            ->get(['id', 'order_id', 'arabia_order_number'])
            ->keyBy('arabia_order_number');
        $rentalContracts = RentalContract::query()
            ->whereIn('legacy_order_id', array_unique($rentalOrderNumbers))
            ->get(['id', 'order_id', 'legacy_order_id'])
            ->keyBy('legacy_order_id');

        $rows = collect();
        $errors = [];
        foreach ($sheetRows as $index => $source) {
            $sourceRow = $index + 2;
            $department = trim((string) ($source['C'] ?? ''));
            $orderNumber = (int) ($source['D'] ?? 0);
            $marketerName = trim((string) ($source['B'] ?? ''));

            if ($department === '' && $orderNumber === 0 && $marketerName === '') {
                continue;
            }

            $marketer = $marketerResolver->resolve($marketerName);
            if (! $marketer) {
                $errors[] = "Row {$sourceRow}: marketer not found ({$marketerName}).";
                continue;
            }

            if ($department === 'استقدام') {
                $contract = $recruitmentContracts->get($orderNumber);
                $sourceType = CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT;
                $serviceType = 'recruitment';
            } elseif ($department === 'تأجير للنقل') {
                $contract = $rentalContracts->get($orderNumber);
                $sourceType = CommissionOperationService::SOURCE_RENTAL_CONTRACT;
                $serviceType = 'rental';
            } else {
                $errors[] = "Row {$sourceRow}: unsupported department ({$department}).";
                continue;
            }

            if (! $contract) {
                $errors[] = "Row {$sourceRow}: contract not found for order {$orderNumber}.";
                continue;
            }

            $rows->push([
                'source_row' => $sourceRow,
                'marketer_id' => $marketer->id,
                'source_type' => $sourceType,
                'source_id' => $contract->id,
                'order_id' => $contract->order_id,
                'contract_id' => $contract->id,
                'operation_reference' => (string) $orderNumber,
                'client_name' => trim((string) ($source['I'] ?? '')) ?: null,
                'operation_date' => $this->date($source['N'] ?? null),
                'service_type' => $serviceType,
                'commission_amount' => $this->amount($source['M'] ?? 0),
            ]);
        }

        if ($errors) {
            throw new RuntimeException(implode(PHP_EOL, array_slice($errors, 0, 20)));
        }

        return $rows;
    }

    private function amount(mixed $value): float
    {
        return round((float) str_replace(',', '', trim((string) $value)), 2);
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}

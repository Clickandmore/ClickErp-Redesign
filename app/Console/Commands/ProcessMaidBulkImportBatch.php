<?php

namespace App\Console\Commands;

use App\Http\Actions\ingaz\MaidAction;
use App\Models\ingaz\MaidBulkImport;
use App\Services\ingaz\MaidBulkImportService;
use Illuminate\Console\Command;

class ProcessMaidBulkImportBatch extends Command
{
    protected $signature = 'maids:process-import-batch {import} {--admin=1}';
    protected $description = 'Process one memory-safe batch of a maid bulk import';

    public function handle(MaidBulkImportService $service, MaidAction $maidAction): int
    {
        $import = MaidBulkImport::findOrFail((int) $this->argument('import'));
        $result = $service->approveImport($import, $maidAction, (int) $this->option('admin'));
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }
}

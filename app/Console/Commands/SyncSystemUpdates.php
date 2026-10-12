<?php

namespace App\Console\Commands;

use App\Services\SystemUpdates\SystemUpdateSyncService;
use App\Support\SystemUpdatesSettings;
use Illuminate\Console\Command;

class SyncSystemUpdates extends Command
{
    protected $signature = 'system-updates:sync {--full : تجاهل آخر مزامنة واسحب كل التحديثات من جديد} {--force : نفّذ حتى لو كانت المزامنة الدورية متوقفة}';

    protected $description = 'سحب تحديثات النظام من مصدر التحديثات المضبوط في لوحة Super Admin';

    public function handle(SystemUpdateSyncService $sync): int
    {
        if (! $this->option('force') && ! SystemUpdatesSettings::syncConfigured()) {
            $this->line('المزامنة الدورية غير مفعّلة أو لم يُضبط المصدر؛ لا شيء للتنفيذ.');

            return self::SUCCESS;
        }

        try {
            $result = $sync->sync((bool) $this->option('full'));
        } catch (\Throwable $exception) {
            $this->error('فشلت المزامنة: ' . $exception->getMessage());

            return self::FAILURE;
        }

        // يعمل كل دقيقة افتراضيًا؛ لا نكتب في السجل إلا إذا وصل شيء
        if ($result['received'] === 0) {
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'تمت المزامنة: وصل %d، حُفظ %d، حُذف %d.',
            $result['received'],
            $result['saved'],
            $result['removed']
        ));

        return self::SUCCESS;
    }
}

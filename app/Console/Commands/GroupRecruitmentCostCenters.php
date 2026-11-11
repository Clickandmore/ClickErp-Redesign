<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Support\RecruitmentCostCenters;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\CostCenter;
use RuntimeException;

/**
 * ينقل مراكز تكلفة عقود الاستقدام الموجودة تحت عقدة دولتها:
 * عقود الاستقدام ـــ> الدولة ـــ> عقد.
 *
 * يغيّر parent_id فقط، فالقيود وأرصدتها تبقى مربوطة بنفس المركز. يعرض ما سيحدث دون
 * تعديل إلا مع --apply، ويحفظ سجلاً بكل نقل يُرجَع به بـ --undo.
 */
class GroupRecruitmentCostCenters extends Command
{
    protected $signature = 'finance:group-recruitment-cost-centers
        {--apply : Move the centres; otherwise preview only}
        {--database= : Required exact database name when applying or undoing}
        {--undo= : Undo a previous run from its log file (path under storage/app)}';

    protected $description = 'Preview or move recruitment contract cost centres under their nationality node';

    private const LOG_DIR = 'cost-center-grouping';

    public function handle(): int
    {
        try {
            if ($this->option('undo')) {
                return $this->undo((string) $this->option('undo'));
            }

            return $this->group((bool) $this->option('apply'));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function group(bool $apply): int
    {
        if ($apply) {
            $this->guardDatabase();
        }

        $root = CostCenter::query()->whereNull('parent_id')->where('title', RecruitmentCostCenters::ROOT_TITLE)->first();
        if (!$root) {
            $this->info('لا يوجد جذر «' . RecruitmentCostCenters::ROOT_TITLE . '». لا شيء لنقله.');

            return self::SUCCESS;
        }

        $centers = CostCenter::query()
            ->whereIn('parent_id', RecruitmentCostCenters::lookupParentIds($root))
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', '>', 0)
            ->where('title', 'like', 'عقد استقدام #%')
            ->orderBy('id')
            ->get();

        $nationalities = $this->nationalitiesFor($centers->pluck('table_related_id')->all());

        $plan = [];
        $skipped = ['no_nationality' => [], 'no_contract' => []];
        $fromTitle = [];
        foreach ($centers as $center) {
            $contractId = (int) $center->table_related_id;
            if (array_key_exists($contractId, $nationalities)) {
                $name = $nationalities[$contractId];
            } else {
                // العقد محذوف: الجنسية مكتوبة في عنوان المركز وقت إنشائه («عقد استقدام #535 - الفلبين»).
                $name = $this->nationalityFromCenterTitle($center->title);
                if ($name === '') {
                    $skipped['no_contract'][] = $contractId;
                    continue;
                }
                $fromTitle[] = $contractId;
            }
            if ($name === '') {
                $skipped['no_nationality'][] = $contractId;
                continue;
            }
            $plan[$name][] = $center;
        }

        $rows = [];
        $toMove = 0;
        foreach ($plan as $name => $list) {
            $node = RecruitmentCostCenters::findNationalityNode($root, $name);
            $moving = collect($list)->filter(fn ($c) => !$node || (int) $c->parent_id !== (int) $node->id)->count();
            $toMove += $moving;
            $rows[] = [
                $name,
                $node ? $node->title . ' (#' . $node->id . ')' : 'جديدة: ' . RecruitmentCostCenters::NATIONALITY_TITLE_PREFIX . $name,
                count($list),
                $moving,
            ];
        }
        usort($rows, fn ($a, $b) => $b[2] <=> $a[2]);

        $this->table(['الجنسية', 'عقدة الدولة', 'مراكز العقود', 'ستُنقل'], $rows);
        $this->line('مراكز ستُنقل: ' . $toMove);
        $this->line('عقود بلا جنسية (تبقى تحت الجذر): ' . count($skipped['no_nationality'])
            . ($skipped['no_nationality'] ? ' — ' . implode(', ', array_slice($skipped['no_nationality'], 0, 20)) : ''));
        $this->line('عقود محذوفة أُخذت جنسيتها من عنوان المركز: ' . count($fromTitle)
            . ($fromTitle ? ' — ' . implode(', ', array_slice($fromTitle, 0, 20)) : ''));
        $this->line('مراكز لعقود محذوفة بلا جنسية في العنوان (تبقى كما هي): ' . count($skipped['no_contract'])
            . ($skipped['no_contract'] ? ' — ' . implode(', ', array_slice($skipped['no_contract'], 0, 20)) : ''));

        if (!$apply) {
            $this->warn('معاينة فقط. لم يتغير شيء. للتنفيذ: --apply --database=' . DB::connection()->getDatabaseName());

            return self::SUCCESS;
        }

        $log = ['database' => DB::connection()->getDatabaseName(), 'ran_at' => now()->toIso8601String(), 'created_nodes' => [], 'moves' => []];

        DB::transaction(function () use ($plan, $root, &$log) {
            foreach ($plan as $name => $list) {
                $node = RecruitmentCostCenters::findNationalityNode($root, $name);
                if (!$node) {
                    $node = RecruitmentCostCenters::nationalityNode($root, $name);
                    $log['created_nodes'][] = $node->id;
                }
                foreach ($list as $center) {
                    if ((int) $center->parent_id === (int) $node->id) {
                        continue;
                    }
                    $log['moves'][] = ['id' => $center->id, 'from' => (int) $center->parent_id, 'to' => (int) $node->id];
                    $center->update(['parent_id' => $node->id]);
                }
            }
        });

        $path = self::LOG_DIR . '/' . now()->format('Ymd_His') . '.json';
        Storage::put($path, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('تم نقل ' . count($log['moves']) . ' مركز، وإنشاء ' . count($log['created_nodes']) . ' عقدة دولة.');
        $this->line('للتراجع: php artisan finance:group-recruitment-cost-centers --undo=' . $path . ' --database=' . $log['database']);

        return self::SUCCESS;
    }

    private function undo(string $path): int
    {
        $this->guardDatabase();

        if (!Storage::exists($path)) {
            throw new RuntimeException('ملف السجل غير موجود: storage/app/' . $path);
        }
        $log = json_decode((string) Storage::get($path), true);
        if (!is_array($log) || !isset($log['moves'])) {
            throw new RuntimeException('ملف السجل غير صالح.');
        }
        if (($log['database'] ?? null) !== DB::connection()->getDatabaseName()) {
            throw new RuntimeException('هذا السجل من قاعدة بيانات أخرى: ' . ($log['database'] ?? '?'));
        }

        $restored = 0;
        $removed = 0;
        DB::transaction(function () use ($log, &$restored, &$removed) {
            foreach (array_reverse($log['moves']) as $move) {
                $restored += CostCenter::query()
                    ->where('id', $move['id'])
                    ->where('parent_id', $move['to'])
                    ->update(['parent_id' => $move['from']]);
            }
            foreach ($log['created_nodes'] as $nodeId) {
                $empty = !CostCenter::query()->where('parent_id', $nodeId)->exists()
                    && !DB::table('fn_constraint_details')->where('cost_center_id', $nodeId)->exists();
                if ($empty) {
                    $removed += CostCenter::query()->where('id', $nodeId)->delete();
                }
            }
        });

        $this->info('تمت إعادة ' . $restored . ' مركز، وحذف ' . $removed . ' عقدة دولة فارغة.');

        return self::SUCCESS;
    }

    /** contract_id => اسم الجنسية ('' إن لم توجد). نفس قاعدة باقي النظام: جنسية العاملة ثم الجنسية المطلوبة. */
    private function nationalitiesFor(array $contractIds): array
    {
        $names = [];
        Contract::query()
            ->whereIn('id', $contractIds)
            ->with(['maid.nationality', 'requiredMaidNationality'])
            ->get()
            ->each(function (Contract $contract) use (&$names) {
                $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
                $names[(int) $contract->id] = trim((string) ($nationality?->sub_title ?: $nationality?->title ?: ''));
            });

        return $names;
    }

    private function nationalityFromCenterTitle(string $title): string
    {
        return preg_match('/^عقد استقدام #\d+\s+-\s+(.+)$/u', trim($title), $m) ? trim($m[1]) : '';
    }

    private function guardDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        if ($this->option('database') !== $database) {
            throw new RuntimeException('للتنفيذ اكتب اسم قاعدة البيانات بالضبط: --database=' . $database);
        }
    }
}

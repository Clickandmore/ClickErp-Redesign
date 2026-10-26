<?php

namespace App\Services\SystemUpdates;

use App\Models\SystemUpdate;
use App\Models\SystemUpdateRead;
use App\Support\SystemUpdatesSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * التحديثات من زاوية الموظف: ما يظهر له في الشريط، وما أكّد اطلاعه عليه.
 */
class SystemUpdateInbox
{
    public const BANNER_LIMIT = 5;

    private static ?bool $ready = null;

    /** الهيدر يُرسم في كل صفحة؛ قبل تشغيل الـ migration يجب ألا يكسر أي شاشة. */
    public static function ready(): bool
    {
        return self::$ready ??= Schema::hasTable('system_updates') && Schema::hasTable('system_update_reads');
    }

    public function visibleQuery(): Builder
    {
        return SystemUpdate::query()->visible();
    }

    /**
     * @return array{count: int, items: Collection<int, SystemUpdate>, has_important: bool}
     */
    public function banner($admin): array
    {
        $empty = ['count' => 0, 'items' => collect(), 'has_important' => false];

        if (! $admin || ! self::ready() || ! SystemUpdatesSettings::bannerEnabled()) {
            return $empty;
        }

        $query = $this->pendingQuery($admin)
            ->where(fn (Builder $q) => $q->whereNull('banner_until')->orWhere('banner_until', '>', now()));

        $count = (clone $query)->count();
        if ($count === 0) {
            return $empty;
        }

        return [
            'count' => $count,
            'items' => (clone $query)
                ->orderByRaw("CASE WHEN category = 'important' THEN 0 ELSE 1 END")
                ->orderByDesc('published_at')
                ->limit(self::BANNER_LIMIT)
                ->get(['id', 'title', 'category', 'published_at']),
            'has_important' => (clone $query)->where('category', 'important')->exists(),
        ];
    }

    /** كل ما لم يؤكده الموظف بعد، بغض النظر عن انتهاء مدة ظهوره في الشريط. */
    public function pendingQuery($admin): Builder
    {
        return $this->visibleQuery()->whereDoesntHave('reads', function (Builder $q) use ($admin) {
            $q->where('admin_id', $admin->getKey())->whereNotNull('acknowledged_at');
        });
    }

    /** @return Collection<int, SystemUpdateRead> مفهرسة بـ system_update_id */
    public function readsFor($admin, iterable $updateIds): Collection
    {
        return SystemUpdateRead::query()
            ->where('admin_id', $admin->getKey())
            ->whereIn('system_update_id', collect($updateIds)->all())
            ->get()
            ->keyBy('system_update_id');
    }

    public function markSeen($admin, iterable $updateIds): void
    {
        $ids = collect($updateIds)->map(fn ($id) => (int) $id)->filter()->unique();
        if ($ids->isEmpty()) {
            return;
        }

        $existing = SystemUpdateRead::query()
            ->where('admin_id', $admin->getKey())
            ->whereIn('system_update_id', $ids)
            ->pluck('system_update_id');

        $now = now();
        $rows = $ids->diff($existing)->map(fn (int $id) => [
            'system_update_id' => $id,
            'admin_id' => $admin->getKey(),
            'seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        if ($rows !== []) {
            SystemUpdateRead::query()->insertOrIgnore($rows);
        }
    }

    public function acknowledge($admin, SystemUpdate $update): SystemUpdateRead
    {
        $read = SystemUpdateRead::query()->firstOrNew([
            'system_update_id' => $update->getKey(),
            'admin_id' => $admin->getKey(),
        ]);

        $read->seen_at = $read->seen_at ?: now();
        $read->acknowledged_at = $read->acknowledged_at ?: now();
        $read->save();

        return $read;
    }

    public function acknowledgeAll($admin): int
    {
        $count = 0;
        $this->pendingQuery($admin)->select('system_updates.id')->each(function (SystemUpdate $update) use ($admin, &$count) {
            $this->acknowledge($admin, $update);
            $count++;
        });

        return $count;
    }
}

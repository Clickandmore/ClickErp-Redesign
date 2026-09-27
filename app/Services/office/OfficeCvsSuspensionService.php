<?php

namespace App\Services\office;

use App\Models\ingaz\Maid;
use App\Models\office\Office;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تعليق / فك تعليق السير الذاتية الخاصة بمكتب خارجي.
 *
 * التعليق = إخفاء كل سير المكتب عن الموقع والتطبيق (is_show = 0) مع حفظ لقطة
 * بأرقام السير التي كانت ظاهرة، حتى لا يعيد فك التعليق إظهار سيرة أخفاها موظف عمداً.
 *
 * العملية على مستوى المكتب لا الفرع، لذلك تتجاوز عزل الفروع عمداً:
 * راية التعليق واحدة على سجل المكتب، وتعليق جزئي يجعلها كاذبة.
 */
class OfficeCvsSuspensionService
{
    /** شكل الجدول لا يتغير أثناء الطلب، فيُفحص مرة واحدة لا مرة لكل سيرة تُنشأ. */
    private static ?bool $supported = null;

    /**
     * هل عمود التعليق موجود؟ (قواعد بيانات لم تُرحّل بعد)
     */
    public function isSupported(): bool
    {
        if (self::$supported === null) {
            self::$supported = Schema::hasTable('recruitment_offices')
                && Schema::hasColumn('recruitment_offices', 'cvs_suspended_at');
        }

        return self::$supported;
    }

    /**
     * يعكس حالة التعليق ويعيد ['suspended' => bool, 'affected' => int].
     */
    public function toggle(Office $office): array
    {
        return $office->cvsAreSuspended()
            ? $this->resume($office)
            : $this->suspend($office);
    }

    /**
     * تعليق كل سير المكتب.
     */
    public function suspend(Office $office): array
    {
        return DB::transaction(function () use ($office) {
            $visibleIds = $this->officeMaidsQuery($office)
                ->where('is_show', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $affected = $this->officeMaidsQuery($office)
                ->where(function ($query) {
                    $query->where('is_show', true)->orWhereNull('is_show');
                })
                ->update([
                    'is_show' => false,
                    'visibility_batch_id' => null,
                    'updated_at' => now(),
                ]);

            $office->forceFill([
                'cvs_suspended_at' => now(),
                'cvs_suspended_by' => Auth::guard('admin')->id(),
                'cvs_suspension_snapshot' => $visibleIds,
            ])->save();

            return ['suspended' => true, 'affected' => (int) $affected];
        });
    }

    /**
     * فك التعليق: تعود السير المسجّلة في اللقطة فقط — أي التي كانت ظاهرة وقت
     * التعليق، ومعها السير التي أُضيفت أثناء التعليق فأخفاها التعليق نفسه.
     * سيرة أخفاها موظف عمداً قبل التعليق تبقى مخفية.
     */
    public function resume(Office $office): array
    {
        return DB::transaction(function () use ($office) {
            $snapshot = collect($office->cvs_suspension_snapshot ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $affected = 0;

            if ($snapshot->isNotEmpty()) {
                $affected = $this->officeMaidsQuery($office)
                    ->whereIn('id', $snapshot->all())
                    ->where(function ($query) {
                        $query->where('is_show', false)->orWhereNull('is_show');
                    })
                    ->update([
                        'is_show' => true,
                        'visibility_batch_id' => null,
                        'updated_at' => now(),
                    ]);
            }

            $office->forceFill([
                'cvs_suspended_at' => null,
                'cvs_suspended_by' => null,
                'cvs_suspension_snapshot' => null,
            ])->save();

            return ['suspended' => false, 'affected' => (int) $affected];
        });
    }

    /**
     * هل سير هذا المكتب معلّقة؟ يُستعمل من hook الإنشاء في Maid.
     */
    public function isSuspendedOffice(?int $officeId): bool
    {
        if (! $officeId || ! $this->isSupported()) {
            return false;
        }

        return Office::withoutBranchScope()
            ->whereKey($officeId)
            ->whereNotNull('cvs_suspended_at')
            ->exists();
    }

    /**
     * سيرة أُضيفت لمكتب معلّق: تُسجَّل في اللقطة حتى تعود للظهور عند فك التعليق،
     * لأن إخفاءها جاء من التعليق لا من قرار موظف.
     */
    public function recordSuspendedCv(int $officeId, int $maidId): void
    {
        if (! $this->isSupported()) {
            return;
        }

        $office = Office::withoutBranchScope()
            ->whereKey($officeId)
            ->whereNotNull('cvs_suspended_at')
            ->first();

        if (! $office) {
            return;
        }

        $snapshot = collect($office->cvs_suspension_snapshot ?? [])
            ->map(fn ($id) => (int) $id)
            ->push($maidId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $office->forceFill(['cvs_suspension_snapshot' => $snapshot])->save();
    }

    private function officeMaidsQuery(Office $office)
    {
        return Maid::withoutBranchScope()->where('office_id', $office->getKey());
    }
}

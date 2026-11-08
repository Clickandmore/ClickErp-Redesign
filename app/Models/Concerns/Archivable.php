<?php

namespace App\Models\Concerns;

use App\Models\auth\Admin;
use App\Models\ContractArchiveLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * أرشفة العقد بدون حذفه.
 *
 * لا يوجد Global Scope عمداً: العقد المؤرشف يبقى ظاهراً لكل العلاقات
 * (الفواتير، السندات، القيود، الكشوف). الشاشات التشغيلية فقط تطلب
 * notArchived() صراحة. الأعمدة محمية بفحص Schema حتى يعمل الكود على
 * قاعدة لم يُشغَّل عليها الترحيل بعد.
 */
trait Archivable
{
    /** @var array<string, bool> */
    private static array $archiveColumnPresence = [];

    /** نوع العقد كما يُحفظ في contract_archive_logs. */
    abstract public static function archiveContractType(): string;

    public static function archiveColumns(): array
    {
        return ['archived_at', 'archived_by', 'archive_reason'];
    }

    /**
     * أعمدة الأرشفة تُكتب من ContractArchiveService فقط (باستعلام مباشر).
     * أي حفظ عادي للنموذج يحاول تغييرها — مثل update($request->all()) — يُعاد
     * فيه العمود لقيمته الأصلية بصمت، فلا تُتجاوز شروط الأرشفة.
     */
    public static function bootArchivable(): void
    {
        static::saving(function ($model) {
            foreach (static::archiveColumns() as $column) {
                if (!$model->isDirty($column)) {
                    continue;
                }
                if ($model->exists) {
                    $model->setAttribute($column, $model->getRawOriginal($column));
                } else {
                    $model->offsetUnset($column);
                }
            }
        });
    }

    public function initializeArchivable(): void
    {
        $this->mergeCasts(['archived_at' => 'datetime']);
    }

    public static function archiveColumnExists(): bool
    {
        $table = (new static())->getTable();

        return self::$archiveColumnPresence[$table]
            ??= Schema::hasTable($table) && Schema::hasColumn($table, 'archived_at');
    }

    public static function forgetArchiveColumnCache(): void
    {
        self::$archiveColumnPresence = [];
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        if (!static::archiveColumnExists()) {
            return $query;
        }

        return $query->whereNull($query->getModel()->qualifyColumn('archived_at'));
    }

    public function scopeOnlyArchived(Builder $query): Builder
    {
        if (!static::archiveColumnExists()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereNotNull($query->getModel()->qualifyColumn('archived_at'));
    }

    /**
     * فلتر القوائم: archived=only يعرض المؤرشف فقط، archived=with يعرض الكل،
     * وأي قيمة أخرى (الافتراضي) تخفي المؤرشف.
     */
    public function scopeApplyArchiveFilter(Builder $query, ?string $mode): Builder
    {
        return match ($mode) {
            'only' => $query->onlyArchived(),
            'with' => $query,
            default => $query->notArchived(),
        };
    }

    public function isArchived(): bool
    {
        return $this->getAttribute('archived_at') !== null;
    }

    public function archivedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'archived_by');
    }

    public function archiveLogs()
    {
        return $this->hasMany(ContractArchiveLog::class, 'contract_id')
            ->where('contract_type', static::archiveContractType())
            ->latest('id');
    }
}

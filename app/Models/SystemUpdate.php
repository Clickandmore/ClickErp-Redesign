<?php

namespace App\Models;

use App\Support\SystemModuleManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * تحديث يُعلن للموظفين في شريط التحديثات وصفحة «تحديثات النظام».
 *
 * الحذف ناعم عن قصد: النسخ التي تسحب التحديثات عبر الـ API تحتاج أن ترى الحذف
 * (updated_at يتغير مع deleted_at) لتزيل نسختها المحلية.
 */
class SystemUpdate extends Model
{
    use SoftDeletes;

    public const SOURCE_LOCAL = 'local';
    public const SOURCE_REMOTE = 'remote';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const CATEGORIES = [
        'feature' => ['label' => 'ميزة جديدة', 'icon' => 'fa-star', 'tone' => 'primary'],
        'improvement' => ['label' => 'تحسين', 'icon' => 'fa-magic', 'tone' => 'info'],
        'fix' => ['label' => 'إصلاح', 'icon' => 'fa-wrench', 'tone' => 'success'],
        'important' => ['label' => 'تغيير مهم', 'icon' => 'fa-exclamation-triangle', 'tone' => 'danger'],
    ];

    protected $guarded = [];

    protected $casts = [
        'is_shared' => 'boolean',
        'published_at' => 'datetime',
        'banner_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SystemUpdate $update) {
            $update->uuid = $update->uuid ?: (string) Str::uuid();
        });
    }

    public function media()
    {
        return $this->hasMany(SystemUpdateMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reads()
    {
        return $this->hasMany(SystemUpdateRead::class);
    }

    /** منشور وحلّ موعد نشره، ووحدته (إن وُجدت) مفعّلة في هذه النسخة. */
    public function scopeVisible(Builder $query): Builder
    {
        $enabledModules = collect(SystemModuleManager::enabledMap())->filter()->keys()->all();

        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('module_key')->orWhereIn('module_key', $enabledModules));
    }

    public function isRemote(): bool
    {
        return $this->source === self::SOURCE_REMOTE;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function categoryMeta(): array
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['feature'];
    }

    public function isImportant(): bool
    {
        return $this->category === 'important';
    }

    /** يُصدَّر عبر الـ API فقط ما كُتب هنا، فلا تعيد نسخة نشر ما سحبته من غيرها. */
    public function isActiveInFeed(): bool
    {
        return ! $this->trashed() && $this->is_shared && $this->isPublished();
    }

    /**
     * Markdown بلا HTML خام ولا روابط javascript: — المحتوى قد يأتي من مصدر خارجي.
     */
    public function renderedBody(): string
    {
        if (blank($this->body)) {
            return '';
        }

        return (string) Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function isExternalLink(): bool
    {
        return Str::startsWith((string) $this->link_url, ['http://', 'https://']);
    }
}

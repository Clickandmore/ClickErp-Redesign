<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * مقال في مدونة الموقع الخارجي.
 *
 * العنوان والملخص والمحتوى تُخزَّن في BlogTranslation حتى يكون لكل مقال
 * نسخة لكل لغة يدعمها الموقع.
 */
class Blog extends Model
{
    use Translatable, HasFactory, LogsActivity, \App\Models\Concerns\ResolvesMediaUrls;

    protected array $mediaUrlColumns = ['image'];

    public $translatedAttributes = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug',
        'image',
        'category',
        'is_active',
        'is_featured',
        'sort_order',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'views_count' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $with = ['translations'];

    protected $appends = ['image_url'];

    /**
     * حذف المقال يحذف أسئلته الشائعة (وترجماتها عبر هوك BlogFaq)، دون
     * الاعتماد على قيد foreign key وحده — انظر التعليق في BlogFaq::booted().
     */
    protected static function booted(): void
    {
        static::deleting(function (Blog $blog) {
            $blog->faqs->each->delete();
        });
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? showFile($this->image) : null;
    }

    /**
     * الأسئلة الشائعة الخاصة بالمقال، مرتّبة كما رتّبها المحرر في النموذج.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(BlogFaq::class, 'blog_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * المقالات التي يحق للموقع الخارجي عرضها: مفعّلة، وتاريخ نشرها ليس في
     * المستقبل (المقال المجدول يبقى مخفيًا حتى موعده).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $inner) {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', Carbon::now());
            });
    }

    /**
     * ترتيب العرض في الموقع: الأحدث نشرًا أولًا، مع احترام الترتيب اليدوي.
     */
    public function scopeOrderedForSite(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * يولّد رابطًا فريدًا من العنوان. يدعم العربية (لا يحذف الحروف العربية)،
     * ويعود إلى "article-{id}" لو خلا العنوان من أي حرف قابل للاستخدام.
     */
    public static function generateSlug(?string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $source, '-', null);

        if ($base === '') {
            // Str::slug() يُفرغ العناوين العربية بالكامل، فنبني الرابط يدويًا.
            $base = trim(preg_replace('/[^\p{Arabic}\p{L}\p{N}]+/u', '-', (string) $source) ?? '', '-');
        }

        $base = Str::limit($base, 180, '');

        if ($base === '') {
            $base = 'article';
        }

        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn(Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}

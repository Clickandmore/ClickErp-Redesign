<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سؤال شائع تابع لمقال في المدونة.
 *
 * السؤال والجواب في BlogFaqTranslation حتى يكون لكل سؤال نسخة لكل لغة،
 * تمامًا كما في Blog.
 */
class BlogFaq extends Model
{
    use Translatable, HasFactory;

    public $translatedAttributes = ['question', 'answer'];

    protected $fillable = [
        'blog_id',
        'sort_order',
    ];

    protected $casts = [
        'blog_id' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $with = ['translations'];

    /**
     * حذف السؤال يحذف ترجماته.
     *
     * لا نعتمد على cascade في astrotomic لأن مفتاحه ساكن ومشترك بين كل
     * الموديلات المترجَمة، ولا على قيد foreign key وحده لأن قاعدة بيانات
     * الاختبارات تُستنسخ بـ CREATE TABLE ... LIKE فلا تحمل القيود أصلًا.
     */
    protected static function booted(): void
    {
        static::deleting(function (BlogFaq $faq) {
            $faq->translations()->delete();
        });
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class, 'blog_id');
    }
}

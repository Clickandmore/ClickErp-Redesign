<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * تُستهلك من الموقع الخارجي (alata-frontend) في صفحة /blog وسكشن الرئيسية.
 *
 * `content` يُرسل فقط في صفحة المقال المفرد؛ إرساله مع القوائم يضخّم الرد
 * بلا فائدة لأن البطاقات لا تعرض سوى الملخص.
 */
class BlogResource extends JsonResource
{
    private bool $withContent = false;

    public function withContent(bool $value = true): self
    {
        $this->withContent = $value;

        return $this;
    }

    public function toArray($request)
    {
        // النص المختصر: الملخص إن وُجد، وإلا أول 200 حرف من المحتوى بعد تجريده من HTML.
        $excerpt = filled($this->excerpt)
            ? (string) $this->excerpt
            : Str::limit(trim(strip_tags((string) $this->content)), 200);

        $data = [
            'id' => (int) $this->id,
            'slug' => (string) $this->slug,
            'title' => (string) $this->title,
            'excerpt' => $excerpt,
            'category' => $this->category,
            'image' => $this->image ? showFile($this->image) : null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'is_featured' => (bool) $this->is_featured,
            'views_count' => (int) $this->views_count,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'published_date' => optional($this->published_at)->toDateString(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            // تقدير زمن القراءة بمعدل 200 كلمة/دقيقة — يُعرض على بطاقة المقال.
            // التقسيم على المسافات مع /u لأن str_word_count() لا يحسب الكلمات العربية.
            'reading_minutes' => $this->readingMinutes(),
        ];

        if ($this->withContent) {
            $data['content'] = (string) $this->content;
        }

        return $data;
    }

    private function readingMinutes(): int
    {
        $text = trim(strip_tags((string) $this->content));

        if ($text === '') {
            return 1;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / 200));
    }
}

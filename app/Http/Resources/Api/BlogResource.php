<?php

namespace App\Http\Resources\Api;

use App\Support\BlogShortcodes;
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
            : Str::limit(trim(strip_tags(BlogShortcodes::strip($this->content))), 200);

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
            // {{contact_info}} وغيره يُستبدل هنا، فتعرضه كل المواقع الخارجية كما هو.
            $data['content'] = BlogShortcodes::render($this->content);
            // الأسئلة الشائعة تُرسل مع صفحة المقال فقط: البطاقات في القوائم لا
            // تعرضها، وإرسالها هناك يضخّم الرد بلا فائدة تمامًا كـ content.
            $data['faqs'] = $this->faqsPayload();
        }

        return $data;
    }

    /**
     * الأسئلة الشائعة بلغة الطلب، مع الرجوع إلى اللغة الافتراضية عند غياب
     * الترجمة — إعداد translatable.use_fallback مُطفأ، فبدون هذا الرجوع يفقد
     * الزائر الإنجليزي سكشن الأسئلة كاملًا لمجرد أن المحرر لم يترجمه بعد.
     *
     * السؤال بلا نص في أي لغة يُستبعد بدل إرسال عنصر فارغ.
     *
     * @return array<int, array<string, string>>
     */
    private function faqsPayload(): array
    {
        $fallbackLocale = config('app.locale', 'ar');

        return $this->faqs
            ->map(function ($faq) use ($fallbackLocale) {
                // اللغة الافتراضية أولًا، ثم أي ترجمة موجودة — سؤال كُتب
                // بالإنجليزية وحدها يظل ظاهرًا للزائر العربي بدل أن يختفي.
                $translation = $faq->translate($fallbackLocale) ?? $faq->translations->first();

                return [
                    'question' => trim((string) ($faq->question ?: ($translation->question ?? ''))),
                    'answer' => trim((string) ($faq->answer ?: ($translation->answer ?? ''))),
                ];
            })
            ->filter(fn(array $faq) => $faq['question'] !== '')
            ->values()
            ->all();
    }

    private function readingMinutes(): int
    {
        $text = trim(strip_tags(BlogShortcodes::strip($this->content)));

        if ($text === '') {
            return 1;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / 200));
    }
}

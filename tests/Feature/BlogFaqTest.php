<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\BlogController;
use App\Http\Requests\ingaz\BlogRequest;
use App\Http\Resources\Api\BlogResource;
use App\Models\ingaz\Blog;
use App\Models\ingaz\BlogFaq;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

/**
 * الأسئلة الشائعة لكل مقال: الحفظ من لوحة التحكم وما يصل منها للموقع الخارجي.
 */
class BlogFaqTest extends TestCase
{
    use DatabaseTransactions;

    public function test_saving_creates_faqs_and_skips_rows_left_empty(): void
    {
        $blog = $this->makeBlog();

        $this->sync($blog, [
            ['id' => '', 'question:ar' => 'كم تستغرق المدة؟', 'answer:ar' => 'من 30 إلى 60 يومًا.', 'question:en' => 'How long?', 'answer:en' => '30 to 60 days.'],
            ['id' => '', 'question:ar' => 'هل يوجد ضمان؟', 'answer:ar' => 'نعم، 90 يومًا.'],
            ['id' => '', 'question:ar' => '', 'answer:ar' => '', 'question:en' => '', 'answer:en' => ''],
        ]);

        $faqs = $blog->fresh()->faqs;

        $this->assertCount(2, $faqs, 'الصف الفارغ يجب ألا يُحفظ.');
        $this->assertSame([0, 1], $faqs->pluck('sort_order')->all());
        $this->assertSame('كم تستغرق المدة؟', $faqs[0]->translate('ar')->question);
        $this->assertSame('How long?', $faqs[0]->translate('en')->question);
        // لغة لم تُملأ لا تُنشأ لها ترجمة فارغة.
        $this->assertNull($faqs[1]->translate('en'));
    }

    public function test_editing_reorders_updates_and_removes_faqs(): void
    {
        $blog = $this->makeBlog();

        $this->sync($blog, [
            ['question:ar' => 'الأول'],
            ['question:ar' => 'الثاني'],
        ]);

        [$first, $second] = $blog->fresh()->faqs->all();

        // الثاني يُحذف، والأول ينزل للمركز الثاني بعد سؤال جديد.
        $this->sync($blog, [
            ['id' => '', 'question:ar' => 'سؤال جديد'],
            ['id' => (string) $first->id, 'question:ar' => 'الأول (معدّل)'],
        ]);

        $faqs = $blog->fresh()->faqs;

        $this->assertSame(['سؤال جديد', 'الأول (معدّل)'], $faqs->map(fn($faq) => $faq->translate('ar')->question)->all());
        $this->assertSame($first->id, $faqs[1]->id, 'السؤال المُعدَّل يحتفظ بمعرّفه.');
        $this->assertDatabaseMissing('blog_faqs', ['id' => $second->id]);
        $this->assertSame(0, DB::table('blog_faq_translations')->where('blog_faq_id', $second->id)->count());
    }

    public function test_clearing_a_locale_removes_only_that_translation(): void
    {
        $blog = $this->makeBlog();

        $this->sync($blog, [
            ['question:ar' => 'سؤال', 'answer:ar' => 'جواب', 'question:en' => 'Question', 'answer:en' => 'Answer'],
        ]);

        $faq = $blog->fresh()->faqs->first();

        $this->sync($blog, [
            ['id' => (string) $faq->id, 'question:ar' => 'سؤال', 'answer:ar' => 'جواب', 'question:en' => '', 'answer:en' => ''],
        ]);

        $faq = $blog->fresh()->faqs->first();

        $this->assertNull($faq->translate('en'));
        $this->assertSame('سؤال', $faq->translate('ar')->question);
    }

    public function test_api_sends_faqs_only_with_the_article_and_falls_back_to_the_default_locale(): void
    {
        $blog = $this->makeBlog();

        $this->sync($blog, [
            ['question:ar' => 'سؤال عربي', 'answer:ar' => 'جواب عربي'],
        ]);

        $blog = Blog::with('faqs')->find($blog->id);

        $this->app->setLocale('ar');
        $article = (new BlogResource($blog))->withContent()->toArray(request());
        $this->assertSame([['question' => 'سؤال عربي', 'answer' => 'جواب عربي']], $article['faqs']);

        // لا ترجمة إنجليزية، و use_fallback مُطفأ — الرجوع اليدوي هو ما يبقيها ظاهرة.
        $this->app->setLocale('en');
        $article = (new BlogResource($blog))->withContent()->toArray(request());
        $this->assertSame('سؤال عربي', $article['faqs'][0]['question']);

        // بطاقات القوائم لا تحمل الأسئلة.
        $this->assertArrayNotHasKey('faqs', (new BlogResource($blog))->toArray(request()));
    }

    public function test_deleting_the_article_removes_its_faqs(): void
    {
        $blog = $this->makeBlog();
        $this->sync($blog, [['question:ar' => 'سؤال']]);

        $faqId = $blog->fresh()->faqs->first()->id;
        $blog->delete();

        $this->assertSame(0, BlogFaq::where('id', $faqId)->count());
        $this->assertSame(0, DB::table('blog_faq_translations')->where('blog_faq_id', $faqId)->count());
    }

    public function test_an_answer_without_a_question_is_rejected(): void
    {
        $rules = (new BlogRequest())->rules();
        $faqRules = array_filter($rules, fn($key) => str_starts_with($key, 'faqs'), ARRAY_FILTER_USE_KEY);

        $this->assertTrue(
            Validator::make(['faqs' => [['question:ar' => '', 'answer:ar' => 'جواب يتيم']]], $faqRules)->fails()
        );
        $this->assertFalse(
            Validator::make(['faqs' => [['question:ar' => '', 'answer:ar' => '']]], $faqRules)->fails()
        );
        $this->assertFalse(
            Validator::make(['faqs' => [['question:ar' => 'سؤال', 'answer:ar' => 'جواب']]], $faqRules)->fails()
        );
    }

    private function makeBlog(): Blog
    {
        $blog = new Blog();
        $blog->slug = 'faq-test-' . uniqid();
        $blog->is_active = true;
        $blog->published_at = now();
        $blog->translateOrNew('ar')->title = 'مقال اختبار';
        $blog->translateOrNew('ar')->content = '<p>محتوى</p>';
        $blog->save();

        return $blog;
    }

    /**
     * تستدعي BlogController::syncFaqs بنفس شكل الصفوف القادم من نموذج المقال.
     */
    private function sync(Blog $blog, array $rows): void
    {
        $method = new ReflectionMethod(BlogController::class, 'syncFaqs');
        $method->setAccessible(true);
        $method->invoke(new BlogController(), $blog, BlogRequest::create('/', 'POST', ['faqs' => $rows]));
    }
}

<?php

namespace Database\Seeders;

use App\Models\ingaz\Blog;
use App\Models\ingaz\BlogFaq;
use App\Models\ingaz\Offer;
use App\Services\ManagedFileStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * محتوى تجريبي عام (عروض + مقالات مدونة) لعرض شكل الأقسام في الموقع الخارجي
 * لأي عميل. لا يحتوي اسم شركة أو رقم تواصل أو علامة تجارية.
 *
 * التشغيل:
 *   php artisan db:seed --class="Database\Seeders\WebsiteDemoContentSeeder"
 *
 * آمن على بيانات العميل:
 * - لا يحذف أي عرض أو مقال. يضيف عناصره فقط، أو يحدّثها لو شُغّل مرة أخرى.
 * - العروض تُعرف بعنوانها، والمقالات برابطها (slug) الثابت أدناه.
 * - الصور تُولّد مرة واحدة (رسومات مجردة بلا نصوص) وتُحفظ في نفس تخزين الرفع
 *   (محلي أو R2)، ولا تُولّد من جديد عند إعادة التشغيل.
 * - إعادة التشغيل تعيد تفعيل العناصر وتمدد تواريخ العروض.
 *
 * لإزالة المحتوى التجريبي احذفه من شاشتي «العروض» و«المدونة» في لوحة التحكم.
 */
class WebsiteDemoContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $offers = $this->seedOffers();
            $blogs = $this->seedBlogs();

            $this->command?->info("العروض التجريبية: {$offers} — المقالات التجريبية: {$blogs}");
        });
    }

    private function seedOffers(): int
    {
        $today = Carbon::today();

        foreach ($this->offers() as $index => $item) {
            $offer = Offer::query()->where('title', $item['title'])->first() ?? new Offer();

            $offer->fill([
                'type' => Offer::TYPE_GENERAL,
                'title' => $item['title'],
                'description' => $item['description'],
                'price_before_discount' => $item['price_before'],
                'price_after_discount' => $item['price_after'],
                'discount_percentage' => round((($item['price_before'] - $item['price_after']) / $item['price_before']) * 100, 2),
                'url' => null,
                'starts_at' => $today->copy()->subDay(),
                'ends_at' => $today->copy()->addDays($item['days']),
                'is_active' => true,
                'is_featured' => $index === 0,
                'sort_order' => $index + 1,
            ]);

            if (!$this->imageExists($offer->image)) {
                $offer->image = $this->generateImage('images/offers', $item['palette']);
            }

            $offer->save();
        }

        return count($this->offers());
    }

    private function seedBlogs(): int
    {
        $now = Carbon::now();

        foreach ($this->blogs() as $index => $item) {
            $blog = Blog::query()->where('slug', $item['slug'])->first() ?? new Blog();

            $blog->fill([
                'slug' => $item['slug'],
                'category' => $item['category'],
                'is_active' => true,
                'is_featured' => $index === 0,
                'sort_order' => 0,
                'published_at' => $blog->published_at ?? $now->copy()->subDays($item['days_ago']),
            ]);

            if (!$blog->exists) {
                $blog->views_count = $item['views'];
            }

            if (!$this->imageExists($blog->image)) {
                $blog->image = $this->generateImage('images/blogs', $item['palette']);
            }

            foreach (['ar', 'en'] as $locale) {
                $translation = $blog->translateOrNew($locale);
                $translation->title = $item[$locale]['title'];
                $translation->excerpt = $item[$locale]['excerpt'];
                $translation->content = $item[$locale]['content'];
                $translation->meta_title = $item[$locale]['title'];
                $translation->meta_description = $item[$locale]['excerpt'];
            }

            $blog->save();

            $this->syncFaqs($blog, $item['faqs']);
        }

        return count($this->blogs());
    }

    /**
     * الأسئلة الشائعة للمقال التجريبي تُستبدل بالكامل (المقال نفسه تجريبي).
     */
    private function syncFaqs(Blog $blog, array $faqs): void
    {
        $blog->faqs()->get()->each->delete();

        foreach ($faqs as $order => $faq) {
            $model = new BlogFaq(['blog_id' => $blog->id, 'sort_order' => $order]);

            foreach (['ar', 'en'] as $locale) {
                $model->translateOrNew($locale)->question = $faq[$locale][0];
                $model->translateOrNew($locale)->answer = $faq[$locale][1];
            }

            $model->save();
        }
    }

    private function imageExists(?string $path): bool
    {
        if (!filled($path)) {
            return false;
        }

        try {
            return app(ManagedFileStorage::class)->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * صورة مجردة 1200×675 (تدرج لوني + دوائر شفافة + موجة) بدون أي نص أو شعار،
     * حتى تناسب هوية أي موقع.
     */
    private function generateImage(string $directory, array $palette): ?string
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
            return null;
        }

        [$from, $to, $accent] = array_map(fn (string $hex) => sscanf(ltrim($hex, '#'), '%02x%02x%02x'), $palette);

        $width = 1200;
        $height = 675;
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);

        // تدرج قطري.
        for ($x = 0; $x < $width + $height; $x += 2) {
            $ratio = $x / ($width + $height);
            $color = imagecolorallocate(
                $image,
                (int) ($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) ($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) ($from[2] + ($to[2] - $from[2]) * $ratio)
            );
            imageline($image, $x, 0, $x - $height, $height, $color);
            imageline($image, $x + 1, 0, $x + 1 - $height, $height, $color);
        }

        // دوائر شفافة بمواضع ثابتة لكل لوحة ألوان.
        $seed = crc32(implode('', $palette));
        mt_srand($seed);
        for ($i = 0; $i < 7; $i++) {
            $size = mt_rand(140, 520);
            $isAccent = $i % 3 === 0;
            $color = imagecolorallocatealpha(
                $image,
                $isAccent ? $accent[0] : 255,
                $isAccent ? $accent[1] : 255,
                $isAccent ? $accent[2] : 255,
                $isAccent ? 88 : 112
            );
            imagefilledellipse($image, mt_rand(-80, $width + 80), mt_rand(-80, $height + 80), $size, $size, $color);
        }
        mt_srand();

        // موجة فاتحة أسفل الصورة.
        $wave = imagecolorallocatealpha($image, 255, 255, 255, 100);
        $points = [0, $height];
        for ($x = 0; $x <= $width; $x += 40) {
            $points[] = $x;
            $points[] = (int) ($height - 110 + sin($x / 170) * 28);
        }
        $points[] = $width;
        $points[] = $height;
        imagefilledpolygon($image, $points, intdiv(count($points), 2), $wave);

        ob_start();
        imagewebp($image, null, 85);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        if ($contents === '') {
            return null;
        }

        try {
            return app(ManagedFileStorage::class)->storeFileContents($contents, $directory, 'webp');
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function offers(): array
    {
        return [
            [
                'title' => 'خصم خاص على استقدام العمالة المنزلية',
                'description' => 'استفد من خصم مميز على رسوم استقدام العمالة المنزلية من جنسيات مختارة، مع متابعة كاملة لطلبك حتى وصول العاملة. العرض لفترة محدودة.',
                'price_before' => 15000,
                'price_after' => 12500,
                'days' => 45,
                'palette' => ['#0f766e', '#14b8a6', '#fbbf24'],
            ],
            [
                'title' => 'باقة تأجير شهرية بسعر مخفض',
                'description' => 'عاملة منزلية مدربة بعقد تأجير شهري مرن، تشمل السكن والتأمين الطبي، مع إمكانية الاستبدال خلال فترة الضمان.',
                'price_before' => 3500,
                'price_after' => 2900,
                'days' => 30,
                'palette' => ['#1e3a8a', '#3b82f6', '#f472b6'],
            ],
            [
                'title' => 'نقل خدمات بإجراءات أسرع',
                'description' => 'انقل خدمات العاملة المناسبة لك بإجراءات ميسرة ورسوم مخفضة، ويتولى فريقنا متابعة المعاملة مع الجهات المختصة.',
                'price_before' => 6000,
                'price_after' => 4800,
                'days' => 60,
                'palette' => ['#7c2d12', '#ea580c', '#fde68a'],
            ],
            [
                'title' => 'عرض السائق الخاص',
                'description' => 'استقدام سائق خاص بخبرة سابقة ورخصة قيادة سارية، بخصم خاص على الرسوم خلال فترة العرض.',
                'price_before' => 14000,
                'price_after' => 11900,
                'days' => 40,
                'palette' => ['#312e81', '#7c3aed', '#34d399'],
            ],
        ];
    }

    private function blogs(): array
    {
        return [
            [
                'slug' => 'how-to-choose-the-right-domestic-worker',
                'category' => 'دليل الاستقدام',
                'days_ago' => 3,
                'views' => 184,
                'palette' => ['#0f766e', '#22d3ee', '#fde047'],
                'ar' => [
                    'title' => 'كيف تختار العاملة المنزلية المناسبة لأسرتك؟',
                    'excerpt' => 'خطوات عملية تساعدك على تحديد احتياجات أسرتك واختيار العاملة الأنسب من حيث الخبرة والجنسية والمهارات.',
                    'content' => '<p>اختيار العاملة المنزلية قرار مهم يؤثر على راحة الأسرة واستقرارها. وقبل البدء في الطلب، من الأفضل أن تحدد احتياجاتك بوضوح.</p>'
                        . '<h2>1. حدّد المهام المطلوبة</h2><p>هل تحتاج إلى رعاية أطفال، أو كبار سن، أو أعمال تنظيف وطبخ؟ تحديد المهام يساعدك على اختيار الخبرة المناسبة.</p>'
                        . '<h2>2. راجع السيرة الذاتية بعناية</h2><p>اطلع على العمر وسنوات الخبرة والدول التي عملت بها سابقًا، والمهارات واللغات التي تتقنها.</p>'
                        . '<h2>3. اختر الجنسية المناسبة</h2><p>تختلف مدة الوصول والتكلفة من جنسية لأخرى، لذا قارن بينها بحسب احتياجك وميزانيتك.</p>'
                        . '<h2>4. تأكد من الشروط والضمان</h2><p>اقرأ شروط العقد وفترة الضمان وسياسة الاستبدال قبل إتمام الطلب.</p>',
                ],
                'en' => [
                    'title' => 'How to choose the right domestic worker for your family',
                    'excerpt' => 'Practical steps to define your family\'s needs and pick the best worker in terms of experience, nationality and skills.',
                    'content' => '<p>Choosing a domestic worker is an important decision for your family\'s comfort. Before you apply, define your needs clearly.</p>'
                        . '<h2>1. Define the tasks</h2><p>Do you need childcare, elderly care, cleaning or cooking? Knowing the tasks helps you pick the right experience.</p>'
                        . '<h2>2. Review the CV carefully</h2><p>Check age, years of experience, previous countries of work, skills and languages.</p>'
                        . '<h2>3. Pick a suitable nationality</h2><p>Arrival time and cost differ by nationality, so compare them against your needs and budget.</p>'
                        . '<h2>4. Check the terms and guarantee</h2><p>Read the contract terms, guarantee period and replacement policy before completing your request.</p>',
                ],
                'faqs' => [
                    ['ar' => ['هل يمكنني اختيار العاملة بنفسي؟', 'نعم، يمكنك تصفح السير الذاتية المتاحة واختيار العاملة التي تناسبك.'], 'en' => ['Can I choose the worker myself?', 'Yes, you can browse the available CVs and choose the worker that suits you.']],
                    ['ar' => ['ماذا لو لم تناسبني العاملة؟', 'يمكنك طلب الاستبدال خلال فترة الضمان وفق شروط العقد.'], 'en' => ['What if the worker is not a good fit?', 'You can request a replacement within the guarantee period according to the contract terms.']],
                ],
            ],
            [
                'slug' => 'recruitment-steps-through-musaned',
                'category' => 'الأنظمة والإجراءات',
                'days_ago' => 8,
                'views' => 142,
                'palette' => ['#1e3a8a', '#6366f1', '#22d3ee'],
                'ar' => [
                    'title' => 'خطوات الاستقدام عبر منصة مساند من البداية حتى الوصول',
                    'excerpt' => 'شرح مبسط لمراحل طلب الاستقدام عبر منصة مساند، من إصدار التأشيرة حتى استلام العاملة.',
                    'content' => '<p>أصبحت إجراءات الاستقدام أكثر وضوحًا وسرعة عبر منصة مساند. إليك المراحل الأساسية:</p>'
                        . '<ol><li><strong>إصدار التأشيرة:</strong> عبر المنصة بعد استيفاء الشروط.</li><li><strong>اختيار العاملة:</strong> من السير الذاتية المتاحة لدى مكتب الاستقدام.</li><li><strong>توقيع العقد:</strong> إلكترونيًا مع توضيح الحقوق والالتزامات.</li><li><strong>السداد:</strong> عبر القنوات المعتمدة في المنصة.</li><li><strong>متابعة الطلب:</strong> حتى صدور التأشيرة وحجز موعد الوصول.</li><li><strong>الاستلام:</strong> من المطار أو من مقر المكتب.</li></ol>'
                        . '<p>احرص على متابعة حالة طلبك باستمرار والتواصل مع المكتب عند وجود أي استفسار.</p>',
                ],
                'en' => [
                    'title' => 'Recruitment steps through Musaned, from start to arrival',
                    'excerpt' => 'A simple walkthrough of the recruitment stages on Musaned, from issuing the visa to receiving the worker.',
                    'content' => '<p>Recruitment procedures are now clearer and faster through the Musaned platform. Here are the main stages:</p>'
                        . '<ol><li><strong>Issue the visa</strong> on the platform once requirements are met.</li><li><strong>Choose the worker</strong> from the CVs available at the office.</li><li><strong>Sign the contract</strong> electronically with clear rights and obligations.</li><li><strong>Pay</strong> through the approved channels.</li><li><strong>Follow up</strong> until the visa is stamped and arrival is booked.</li><li><strong>Receive the worker</strong> at the airport or the office.</li></ol>'
                        . '<p>Keep tracking your request and contact the office whenever you have a question.</p>',
                ],
                'faqs' => [
                    ['ar' => ['كم تستغرق مدة الاستقدام؟', 'تختلف حسب الجنسية، وتتراوح عادةً بين 30 و90 يومًا.'], 'en' => ['How long does recruitment take?', 'It depends on the nationality and usually ranges between 30 and 90 days.']],
                    ['ar' => ['هل يمكن متابعة الطلب إلكترونيًا؟', 'نعم، يمكنك متابعة حالة الطلب عبر منصة مساند وعبر حسابك في الموقع.'], 'en' => ['Can I track my request online?', 'Yes, you can track it on Musaned and through your account on the website.']],
                ],
            ],
            [
                'slug' => 'rental-or-recruitment-which-is-better',
                'category' => 'نصائح',
                'days_ago' => 14,
                'views' => 231,
                'palette' => ['#7c2d12', '#f97316', '#facc15'],
                'ar' => [
                    'title' => 'التأجير الشهري أم الاستقدام؟ أيهما يناسبك',
                    'excerpt' => 'مقارنة سريعة بين تأجير العمالة المنزلية واستقدامها من حيث التكلفة والمرونة والمدة.',
                    'content' => '<p>يحتار كثير من العملاء بين التأجير الشهري والاستقدام. لكل خيار مزاياه بحسب ظروف الأسرة.</p>'
                        . '<h2>متى يناسبك التأجير؟</h2><ul><li>عند الحاجة المؤقتة، مثل المناسبات أو فترة النفاس.</li><li>عند الرغبة في تجربة الخدمة قبل الالتزام بعقد طويل.</li><li>عند الحاجة إلى عاملة بشكل فوري.</li></ul>'
                        . '<h2>متى يناسبك الاستقدام؟</h2><ul><li>عند الحاجة إلى عاملة لفترة طويلة.</li><li>عند الرغبة في اختيار الجنسية والمواصفات بدقة.</li><li>عندما تكون التكلفة الإجمالية على المدى الطويل هي الأهم.</li></ul>',
                ],
                'en' => [
                    'title' => 'Monthly rental or recruitment: which suits you?',
                    'excerpt' => 'A quick comparison of renting versus recruiting a domestic worker in terms of cost, flexibility and duration.',
                    'content' => '<p>Many clients are unsure whether to rent monthly or recruit. Each option has its advantages depending on your situation.</p>'
                        . '<h2>When is rental a good fit?</h2><ul><li>For temporary needs such as occasions or postpartum periods.</li><li>To try the service before a long commitment.</li><li>When you need a worker right away.</li></ul>'
                        . '<h2>When is recruitment a good fit?</h2><ul><li>When you need a worker for a long period.</li><li>When you want to choose the nationality and profile precisely.</li><li>When long-term total cost matters most.</li></ul>',
                ],
                'faqs' => [
                    ['ar' => ['هل يشمل التأجير السكن والتأمين؟', 'غالبًا تشمل باقات التأجير السكن والتأمين الطبي، ويُوضَّح ذلك في تفاصيل كل باقة.'], 'en' => ['Does rental include housing and insurance?', 'Rental packages usually include housing and medical insurance, as stated in each package.']],
                    ['ar' => ['هل يمكن تحويل التأجير إلى نقل خدمات؟', 'يمكن ذلك في بعض الحالات وفق الأنظمة المعتمدة وموافقة الأطراف.'], 'en' => ['Can rental become a sponsorship transfer?', 'In some cases, yes, subject to the regulations and the agreement of all parties.']],
                ],
            ],
            [
                'slug' => 'rights-and-duties-of-employer-and-worker',
                'category' => 'الأنظمة والإجراءات',
                'days_ago' => 21,
                'views' => 97,
                'palette' => ['#134e4a', '#10b981', '#a7f3d0'],
                'ar' => [
                    'title' => 'حقوق وواجبات صاحب العمل والعامل المنزلي',
                    'excerpt' => 'تعرّف على أبرز الحقوق والالتزامات التي تضمن علاقة عمل واضحة ومستقرة بين الطرفين.',
                    'content' => '<p>تنظم الأنظمة في المملكة العلاقة بين صاحب العمل والعامل المنزلي بما يحفظ حقوق الطرفين.</p>'
                        . '<h2>أبرز حقوق العامل</h2><ul><li>استلام الأجر في موعده المتفق عليه.</li><li>الحصول على الراحة اليومية والإجازة الأسبوعية.</li><li>السكن المناسب والرعاية الصحية.</li></ul>'
                        . '<h2>أبرز واجبات العامل</h2><ul><li>أداء المهام المتفق عليها بإتقان.</li><li>المحافظة على خصوصية الأسرة وممتلكاتها.</li><li>الالتزام بالأنظمة والتعليمات.</li></ul>'
                        . '<p>توثيق العقد بشكل واضح هو أفضل طريقة لتجنب أي خلاف مستقبلي.</p>',
                ],
                'en' => [
                    'title' => 'Rights and duties of employers and domestic workers',
                    'excerpt' => 'Learn the key rights and obligations that keep the working relationship clear and stable for both sides.',
                    'content' => '<p>Saudi regulations govern the relationship between employers and domestic workers to protect both parties.</p>'
                        . '<h2>Key worker rights</h2><ul><li>Receiving wages on the agreed date.</li><li>Daily rest and a weekly day off.</li><li>Suitable housing and healthcare.</li></ul>'
                        . '<h2>Key worker duties</h2><ul><li>Performing agreed tasks properly.</li><li>Respecting the family\'s privacy and property.</li><li>Following regulations and instructions.</li></ul>'
                        . '<p>A clearly documented contract is the best way to avoid future disputes.</p>',
                ],
                'faqs' => [
                    ['ar' => ['هل يجب تحويل الراتب بنكيًا؟', 'يُنصح بتحويل الراتب عبر القنوات الرسمية لتوثيق السداد وحفظ حقوق الطرفين.'], 'en' => ['Should wages be paid by bank transfer?', 'Paying through official channels is recommended to document payment and protect both sides.']],
                    ['ar' => ['ما مدة الإجازة السنوية؟', 'تحددها الأنظمة وبنود العقد، ويُفضّل الاتفاق عليها بوضوح منذ البداية.'], 'en' => ['How long is the annual leave?', 'It is set by the regulations and contract terms, and is best agreed clearly from the start.']],
                ],
            ],
            [
                'slug' => 'tips-for-welcoming-a-new-domestic-worker',
                'category' => 'نصائح',
                'days_ago' => 30,
                'views' => 76,
                'palette' => ['#4c1d95', '#a855f7', '#f9a8d4'],
                'ar' => [
                    'title' => '7 نصائح لاستقبال العاملة المنزلية في أيامها الأولى',
                    'excerpt' => 'الأيام الأولى تصنع الفرق. نصائح بسيطة تساعد العاملة على التأقلم بسرعة مع أسرتك.',
                    'content' => '<p>تحتاج العاملة الجديدة إلى وقت للتأقلم مع بيئة ولغة وعادات مختلفة. هذه النصائح تجعل البداية أسهل:</p>'
                        . '<ol><li>جهّز لها غرفة مريحة ومستلزمات أساسية.</li><li>اشرح المهام اليومية بشكل تدريجي وواضح.</li><li>استخدم لغة بسيطة أو صورًا توضيحية عند الحاجة.</li><li>حدّد أوقات العمل والراحة منذ اليوم الأول.</li><li>عرّفها على أفراد الأسرة واحتياجاتهم.</li><li>تحلَّ بالصبر مع الأخطاء في البداية.</li><li>تواصل مع المكتب عند وجود أي ملاحظة.</li></ol>',
                ],
                'en' => [
                    'title' => '7 tips for welcoming a new domestic worker',
                    'excerpt' => 'The first days make the difference. Simple tips to help the worker settle in quickly with your family.',
                    'content' => '<p>A new worker needs time to adapt to a different environment, language and habits. These tips make the start easier:</p>'
                        . '<ol><li>Prepare a comfortable room and basic essentials.</li><li>Explain daily tasks gradually and clearly.</li><li>Use simple language or pictures when needed.</li><li>Set working and rest hours from day one.</li><li>Introduce her to family members and their needs.</li><li>Be patient with early mistakes.</li><li>Contact the office if you have any concerns.</li></ol>',
                ],
                'faqs' => [
                    ['ar' => ['كم تحتاج العاملة للتأقلم؟', 'غالبًا من أسبوعين إلى شهر بحسب الخبرة السابقة وطبيعة المهام.'], 'en' => ['How long does it take to settle in?', 'Usually two weeks to a month, depending on previous experience and the tasks.']],
                    ['ar' => ['هل يقدم المكتب دعمًا بعد الوصول؟', 'نعم، يمكنك التواصل مع خدمة العملاء لأي استفسار خلال فترة الضمان.'], 'en' => ['Does the office offer support after arrival?', 'Yes, you can contact customer service with any question during the guarantee period.']],
                ],
            ],
            [
                'slug' => 'required-documents-for-recruitment',
                'category' => 'دليل الاستقدام',
                'days_ago' => 42,
                'views' => 158,
                'palette' => ['#0c4a6e', '#0ea5e9', '#fcd34d'],
                'ar' => [
                    'title' => 'المستندات المطلوبة لطلب الاستقدام',
                    'excerpt' => 'قائمة مختصرة بالمستندات والبيانات التي تحتاجها قبل تقديم طلب استقدام عمالة منزلية.',
                    'content' => '<p>تجهيز المستندات مسبقًا يختصر وقت الطلب ويقلل احتمالية التأخير. إليك ما تحتاجه عادةً:</p>'
                        . '<ul><li>صورة الهوية الوطنية أو الإقامة سارية المفعول.</li><li>رقم التأشيرة بعد إصدارها من منصة مساند.</li><li>العنوان الوطني.</li><li>رقم جوال مسجل باسم صاحب الطلب.</li><li>بيانات الحساب البنكي عند الحاجة.</li></ul>'
                        . '<p>قد تختلف بعض المتطلبات بحسب نوع الطلب والجنسية، لذا تأكد منها قبل البدء.</p>',
                ],
                'en' => [
                    'title' => 'Documents required for a recruitment request',
                    'excerpt' => 'A short list of the documents and details you need before applying to recruit a domestic worker.',
                    'content' => '<p>Preparing your documents in advance shortens the process and reduces delays. You will usually need:</p>'
                        . '<ul><li>A valid national ID or residence permit.</li><li>The visa number once issued on Musaned.</li><li>Your national address.</li><li>A mobile number registered in your name.</li><li>Bank account details when required.</li></ul>'
                        . '<p>Some requirements may differ by request type and nationality, so confirm them before you start.</p>',
                ],
                'faqs' => [
                    ['ar' => ['هل يمكن التقديم دون تأشيرة؟', 'يمكنك اختيار العاملة وحجزها، ثم استكمال الطلب بعد إصدار التأشيرة.'], 'en' => ['Can I apply without a visa?', 'You can choose and reserve a worker, then complete the request once the visa is issued.']],
                    ['ar' => ['هل أحتاج لزيارة المكتب؟', 'في أغلب الحالات يمكن إتمام الإجراءات إلكترونيًا دون زيارة.'], 'en' => ['Do I need to visit the office?', 'In most cases the procedures can be completed online without a visit.']],
                ],
            ],
        ];
    }
}

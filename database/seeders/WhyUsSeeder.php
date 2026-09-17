<?php

namespace Database\Seeders;

use App\Models\WhyUs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * بطاقات قسم "لماذا نحن" في الصفحة الرئيسية للموقع الخارجي.
 * آمن لإعادة التشغيل: يحدّث البطاقات حسب الترتيب ويحذف أي بطاقة زائدة.
 * الأيقونات كلاسات Font Awesome 6 (موجودة في قائمة أيقونات لوحة التحكم).
 */
class WhyUsSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'icon' => 'fa-solid fa-bolt',
                'title_ar' => 'سرعة الإنجاز',
                'description_ar' => 'إنجاز إجراءات الاستقدام في أقصر وقت مع متابعة كل مرحلة حتى الوصول.',
                'title_en' => 'Fast completion',
                'description_en' => 'Recruitment procedures completed in the shortest time, with every stage followed up until arrival.',
            ],
            [
                'icon' => 'fa-solid fa-shield-halved',
                'title_ar' => 'موثوقية عالية',
                'description_ar' => 'خبرة متراكمة وسمعة راسخة مبنية على ثقة آلاف العملاء في المملكة.',
                'title_en' => 'High reliability',
                'description_en' => 'Years of experience and a solid reputation built on the trust of thousands of clients across the Kingdom.',
            ],
            [
                'icon' => 'fa-solid fa-headset',
                'title_ar' => 'دعم 24/7',
                'description_ar' => 'فريق متخصص من خدمة العملاء متاح طوال اليوم للإجابة على استفساراتكم.',
                'title_en' => '24/7 support',
                'description_en' => 'A dedicated customer service team available around the clock to answer your questions.',
            ],
            [
                'icon' => 'fa-solid fa-certificate',
                'title_ar' => 'جودة مضمونة',
                'description_ar' => 'نضمن تدريب العمالة وتأهيلها وفق أعلى المعايير المهنية قبل الاستقدام.',
                'title_en' => 'Guaranteed quality',
                'description_en' => 'Workers are trained and qualified to the highest professional standards before recruitment.',
            ],
            [
                'icon' => 'fa-solid fa-hand-holding-dollar',
                'title_ar' => 'أسعار تنافسية',
                'description_ar' => 'أفضل الأسعار في السوق مع خدمات متميزة لا تتنازل عن معايير الجودة.',
                'title_en' => 'Competitive prices',
                'description_en' => 'The best prices in the market with outstanding service that never compromises on quality.',
            ],
            [
                'icon' => 'fa-solid fa-earth-asia',
                'title_ar' => 'تنوع الجنسيات',
                'description_ar' => 'نوفر عمالة من أكثر من عشر جنسيات مختلفة لتلبية احتياجاتكم المتنوعة.',
                'title_en' => 'Diverse nationalities',
                'description_en' => 'Workers from more than ten nationalities to meet your different needs.',
            ],
        ];

        DB::transaction(function () use ($cards) {
            $keptIds = [];

            foreach ($cards as $index => $data) {
                $sortOrder = $index + 1;

                $card = WhyUs::query()->where('sort_order', $sortOrder)->orderBy('id')->first()
                    ?? new WhyUs();

                $card->icon = $data['icon'];
                $card->sort_order = $sortOrder;
                $card->is_active = true;
                $card->translateOrNew('ar')->title = $data['title_ar'];
                $card->translateOrNew('ar')->description = $data['description_ar'];
                $card->translateOrNew('en')->title = $data['title_en'];
                $card->translateOrNew('en')->description = $data['description_en'];
                $card->save();

                $keptIds[] = $card->id;
            }

            WhyUs::query()->whereNotIn('id', $keptIds)->get()->each->delete();
        });
    }
}

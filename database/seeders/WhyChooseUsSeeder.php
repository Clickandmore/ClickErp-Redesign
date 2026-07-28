<?php

namespace Database\Seeders;

use App\Models\WhyChooseUs;
use App\Models\WhyChooseUsFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhyChooseUsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $section = WhyChooseUs::firstOrNew();
            $section->is_active = true;
            $section->translateOrNew('ar')->title =
                'نوفر حلول استقدام شاملة تضمن اختيار العمالة بعناية وكفاءة عالية';
            $section->translateOrNew('ar')->description =
                'نقدم مجموعة متكاملة من الخدمات المصممة لتلبية احتياجات الأفراد والأسر والمجتمع، بدءًا من خدمات الرعاية المنزلية والتنظيف وحتى تنظيم الضيافة والمناسبات باحترافية تضمن أعلى مستويات الجودة والراحة.';
            $section->translateOrNew('en')->title =
                'We provide comprehensive recruitment solutions that ensure careful and highly efficient worker selection';
            $section->translateOrNew('en')->description =
                'We offer integrated services designed to meet the needs of individuals, families, and the community, from home care and cleaning to professionally organized hospitality and events with the highest levels of quality and comfort.';
            $section->save();

            $images = [
                'admin/assets/img/hospital/about.jpg',
                'site/assets/images/about.webp',
            ];

            foreach ($images as $index => $path) {
                $section->images()->updateOrCreate(
                    ['sort_order' => $index + 1],
                    [
                        'path' => $path,
                        'is_public_asset' => true,
                    ]
                );
            }

            $features = [
                [
                    'icon' => 'fa fa-refresh',
                    'title_ar' => 'الاحترافية',
                    'description_ar' => 'التزامنا بتقديم أعلى معايير الجودة في كل خطوة',
                    'title_en' => 'Professionalism',
                    'description_en' => 'Our commitment to the highest quality standards at every step',
                ],
                [
                    'icon' => 'fa fa-university',
                    'title_ar' => 'الابتكار',
                    'description_ar' => 'حلول متقدمة وذكية لكل احتياجاتكم',
                    'title_en' => 'Innovation',
                    'description_en' => 'Advanced and intelligent solutions for all your needs',
                ],
                [
                    'icon' => 'fa fa-exclamation-circle',
                    'title_ar' => 'الثقة والأمان',
                    'description_ar' => 'حماية مصالحكم وضمان تجربة آمنة وموثوقة',
                    'title_en' => 'Trust and safety',
                    'description_en' => 'Protecting your interests and ensuring a safe, reliable experience',
                ],
            ];

            $keptIds = [];

            foreach ($features as $index => $data) {
                $sortOrder = $index + 1;
                $feature = $section->features()
                    ->where('sort_order', $sortOrder)
                    ->first() ?? new WhyChooseUsFeature();

                $feature->why_choose_us_id = $section->id;
                $feature->sort_order = $sortOrder;
                $feature->icon = $data['icon'];
                $feature->translateOrNew('ar')->title = $data['title_ar'];
                $feature->translateOrNew('ar')->description = $data['description_ar'];
                $feature->translateOrNew('en')->title = $data['title_en'];
                $feature->translateOrNew('en')->description = $data['description_en'];
                $feature->save();
                $keptIds[] = $feature->id;
            }

            $section->features()->whereNotIn('id', $keptIds)->delete();
        });
    }
}

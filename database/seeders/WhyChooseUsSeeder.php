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
            $section->cta_url = '/professional-workers';

            // The site colours whatever sits inside [[ ]] and puts it on its own line.
            $section->translateOrNew('ar')->title =
                'حلول عالمية لاستقدام [[العمالة المهنية]] لمختلف قطاعات الأعمال';
            $section->translateOrNew('ar')->description =
                'نعمل كوسيط موثوق بين الشركات والقطاعات المختلفة والجهات المصدرة للعمالة حول العالم، لتوفير الكفاءات المهنية المناسبة لاحتياجات كل منشأة، وإدارة إجراءات الاستخدام والتنسيق بين جميع الأطراف وفق الأنظمة والمعايير المعتمدة.';
            $section->translateOrNew('ar')->cta_label = 'اطلب عمالة مهنية الآن';

            $section->translateOrNew('en')->title =
                'Global solutions for hiring [[professional workers]] across every business sector';
            $section->translateOrNew('en')->description =
                'We act as a trusted link between companies across every sector and labour-exporting authorities worldwide, supplying the professional talent each organisation needs and managing the hiring steps and coordination between all parties under the approved regulations and standards.';
            $section->translateOrNew('en')->cta_label = 'Request professional workers';

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

            // The three cards beside the copy.
            $this->syncRows($section, WhyChooseUsFeature::TYPE_FEATURE, [
                [
                    'icon' => 'fa fa-users',
                    'title_ar' => 'خبرة في الاستقدام الدولي',
                    'description_ar' => 'نحرص على اختيار عمالة ذات خبرة ومهارات مهنية عالية لضمان جودة الأداء وتحقيق متطلبات العمل بكفاءة.',
                    'title_en' => 'International recruitment experience',
                    'description_en' => 'We select workers with proven experience and high professional skill to guarantee quality performance and meet job requirements efficiently.',
                ],
                [
                    'icon' => 'fa fa-university',
                    'title_ar' => 'التزام بالأنظمة',
                    'description_ar' => 'نعمل وفق الأنظمة واللوائح المعتمدة في المملكة العربية السعودية، مع الالتزام بالإجراءات النظامية المتعلقة باستخدام وتشغيل العمالة المهنية.',
                    'title_en' => 'Regulatory compliance',
                    'description_en' => 'We operate under the regulations approved in the Kingdom of Saudi Arabia and follow the statutory procedures for employing and operating professional workers.',
                ],
                [
                    'icon' => 'fa fa-briefcase',
                    'title_ar' => 'حلول لمختلف القطاعات',
                    'description_ar' => 'نوفر الكفاءات المهنية المناسبة وفق احتياجات كل قطاع، من الإنشاءات والصناعة والخدمات والرعاية الصحية وغيرها من القطاعات.',
                    'title_en' => 'Solutions for every sector',
                    'description_en' => 'We supply the right professional talent for each sector, from construction and industry to services, healthcare and beyond.',
                ],
            ]);

            // The short items in the strip over the section image.
            $this->syncRows($section, WhyChooseUsFeature::TYPE_HIGHLIGHT, [
                [
                    'icon' => 'fa fa-globe',
                    'title_ar' => 'شبكة دولية من الجهات المصدرة',
                    'title_en' => 'An international network of source agencies',
                ],
                [
                    'icon' => 'fa fa-users',
                    'title_ar' => 'اختيار دقيق وفق احتياجات المنشأة',
                    'title_en' => 'Precise selection against your needs',
                ],
                [
                    'icon' => 'fa fa-clock-o',
                    'title_ar' => 'إجراءات واضحة ومنظمة',
                    'title_en' => 'Clear, organised procedures',
                ],
                [
                    'icon' => 'fa fa-refresh',
                    'title_ar' => 'متابعة وتنسيق مستمر',
                    'title_en' => 'Continuous follow-up and coordination',
                ],
            ]);
        });
    }

    /**
     * Upsert one list by sort order, leaving the other list untouched.
     *
     * @param  array<int, array<string, string>>  $rows
     */
    private function syncRows(WhyChooseUs $section, string $type, array $rows): void
    {
        // Resolved per use: a relation instance carries one query builder, so reusing
        // the same object would stack every where() clause onto the next lookup.
        $relation = fn () => $type === WhyChooseUsFeature::TYPE_HIGHLIGHT
            ? $section->highlights()
            : $section->features();

        $keptIds = [];

        foreach ($rows as $index => $data) {
            $sortOrder = $index + 1;

            $row = $relation()->where('sort_order', $sortOrder)->first()
                ?? new WhyChooseUsFeature();

            $row->why_choose_us_id = $section->id;
            $row->type = $type;
            $row->sort_order = $sortOrder;
            $row->icon = $data['icon'];
            $row->translateOrNew('ar')->title = $data['title_ar'];
            $row->translateOrNew('ar')->description = $data['description_ar'] ?? null;
            $row->translateOrNew('en')->title = $data['title_en'];
            $row->translateOrNew('en')->description = $data['description_en'] ?? null;
            $row->save();

            $keptIds[] = $row->id;
        }

        $relation()->whereNotIn('id', $keptIds)->delete();
    }
}

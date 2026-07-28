<?php

namespace Database\Seeders;

use App\Enums\ingaz\RequirementEnum;
use App\Models\Requirement;
use App\Models\RequirementItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecruitmentRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            [
                'ar' => 'صورة الهوية الوطنية أو الإقامة للمقيمين',
                'en' => 'A copy of the national ID or resident permit for residents',
            ],
            [
                'ar' => 'خدمة عملاء متميزة',
                'en' => 'Premium customer service',
            ],
            [
                'ar' => 'تعريف بالراتب يبدأ من جهة العمل أو كشف حساب البنك',
                'en' => 'A salary certificate from the employer or a bank statement',
            ],
            [
                'ar' => 'التأشيرة الأصل',
                'en' => 'The original visa',
            ],
            [
                'ar' => 'توقيع عقد استقدام',
                'en' => 'Signing a recruitment contract',
            ],
        ];

        $groups = [
            [
                'type' => RequirementEnum::VisaIssuance->value,
                'title_ar' => 'وثائق إصدار التأشيرة',
                'title_en' => 'Visa issuance documents',
                'sort_order' => 1,
                'points' => $points,
            ],
            [
                'type' => RequirementEnum::RecruitDomestic->value,
                'title_ar' => 'وثائق عقد الاستقدام',
                'title_en' => 'Recruitment contract documents',
                'sort_order' => 1,
                'points' => $points,
            ],
        ];

        DB::transaction(function () use ($groups) {
            foreach ($groups as $group) {
                $requirement = Requirement::query()
                    ->where('type', $group['type'])
                    ->where('sort_order', $group['sort_order'])
                    ->first() ?? new Requirement();

                $requirement->type = $group['type'];
                $requirement->sort_order = $group['sort_order'];
                $requirement->is_active = true;
                $requirement->translateOrNew('ar')->title = $group['title_ar'];
                $requirement->translateOrNew('en')->title = $group['title_en'];
                $requirement->save();

                $keptItemIds = [];

                foreach ($group['points'] as $index => $point) {
                    $sortOrder = $index + 1;
                    $item = $requirement->items()
                        ->where('sort_order', $sortOrder)
                        ->first() ?? new RequirementItem();

                    $item->requirement_id = $requirement->id;
                    $item->sort_order = $sortOrder;
                    $item->translateOrNew('ar')->text = $point['ar'];
                    $item->translateOrNew('en')->text = $point['en'];
                    $item->save();

                    $keptItemIds[] = $item->id;
                }

                $requirement->items()
                    ->whereNotIn('id', $keptItemIds)
                    ->delete();
            }
        });
    }
}

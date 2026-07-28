<?php

namespace Database\Seeders;

use App\Models\ingaz\Step;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StepsSeeder extends Seeder
{
    public function run(): void
    {
        $steps = [
            [
                'sort_order' => 1,
                'icon' => 'money',
                'title:ar' => 'سداد الرسوم',
                'title:en' => 'Pay the fees',
                'description:ar' => 'سداد رسوم تأشيرة العمالة المنزلية الخاصة بك عبر الخدمات الحكومية أو من خلال المنصة الآلية.',
                'description:en' => 'Pay your domestic worker visa fees through government services or via the automated platform.',
            ],
            [
                'sort_order' => 2,
                'icon' => 'globe',
                'title:ar' => 'إصدار تأشيرة العمالة',
                'title:en' => 'Issue the worker visa',
                'description:ar' => 'طلب إصدار تأشيرة العمالة المنزلية الخاصة بك في برنامج مساند.',
                'description:en' => 'Submit a request to issue your domestic worker visa through the Musaned platform.',
            ],
            [
                'sort_order' => 3,
                'icon' => 'id-badge',
                'title:ar' => 'اختيار السيرة الذاتية',
                'title:en' => 'Select a CV',
                'description:ar' => 'اختيار السيرة الذاتية للعمالة المنزلية عبر البحث في برنامج مساند عن طريق المنصة الآلية.',
                'description:en' => 'Select a domestic worker CV by searching Musaned through the automated platform.',
            ],
            [
                'sort_order' => 4,
                'icon' => 'edit',
                'title:ar' => 'دفع مبلغ اختياري',
                'title:en' => 'Pay an optional amount',
                'description:ar' => 'دفع مبلغ اختياري وتوثيق طلب استقدام العمالة المنزلية المحددة بعد اختيار السيرة الآلية.',
                'description:en' => 'Pay an optional amount and document the recruitment request for the selected domestic worker after choosing the CV.',
            ],
            [
                'sort_order' => 5,
                'icon' => 'plane',
                'title:ar' => 'وصول العمالة',
                'title:en' => 'Worker arrival',
                'description:ar' => 'وصول العمالة المنزلية من المطار المحلي إلى المكتب.',
                'description:en' => 'The domestic worker arrives from the local airport to the office.',
            ],
            [
                'sort_order' => 6,
                'icon' => 'check-circle',
                'title:ar' => 'استلام مضمون للعمالة',
                'title:en' => 'Guaranteed worker handover',
                'description:ar' => 'استلام عمالتك من المكتب مع ضمان كامل لمدة 3 أشهر لضمان الجودة والاستقرار.',
                'description:en' => 'Receive your worker from the office with a full three-month guarantee to ensure quality and stability.',
            ],
        ];

        DB::transaction(function () use ($steps) {
            foreach ($steps as $data) {
                $step = Step::query()
                    ->where('sort_order', $data['sort_order'])
                    ->first();

                $step
                    ? $step->update($data)
                    : Step::create($data);
            }
        });
    }
}

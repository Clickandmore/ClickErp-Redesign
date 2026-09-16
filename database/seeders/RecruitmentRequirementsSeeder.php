<?php

namespace Database\Seeders;

use App\Enums\ingaz\RequirementEnum;
use App\Models\Requirement;
use App\Models\RequirementItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * قسم "متطلبات الاستقدام" في الموقع الخارجي: كل مجموعة تبويب، وكل نقطة خطوة
 * (عنوان + وصف + أيقونة). آمن لإعادة التشغيل — يحدّث المجموعة حسب النوع
 * والنقاط حسب الترتيب، ويحذف النقاط الزائدة فقط.
 * الأيقونات كلاسات Font Awesome 6 كما يعرضها الموقع.
 */
class RecruitmentRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'type' => RequirementEnum::VisaIssuance->value,
                'icon' => 'fa-solid fa-list-check',
                'title_ar' => 'إجراءات الاستقدام',
                'title_en' => 'Recruitment procedures',
                'sort_order' => 1,
                'points' => [
                    [
                        'icon' => 'fa-solid fa-right-to-bracket',
                        'ar' => 'الدخول الى منصة مساند',
                        'description_ar' => 'تبدأ سياسات الاستقدام بالدخول إلى منصة مساند عبر القنوات الرسمية المعتمدة.',
                        'en' => 'Log in to the Musaned platform',
                        'description_en' => 'The recruitment process begins by signing in to Musaned through the official approved channels.',
                    ],
                    [
                        'icon' => 'fa-solid fa-circle-check',
                        'ar' => 'التحقق من تأهيلك للحصول على تخليص اجراءات الاستقدام',
                        'description_ar' => 'يتم التأكد من استيفاء الشروط النظامية اللازمة قبل متابعة الطلب.',
                        'en' => 'Check your eligibility to recruit',
                        'description_en' => 'The required regulatory conditions are verified before the request continues.',
                    ],
                    [
                        'icon' => 'fa-solid fa-id-card',
                        'ar' => 'ادخال البيانات المطلوبة واثبات القدرة المالية',
                        'description_ar' => 'يتم إدخال البيانات الأساسية وإرفاق ما يثبت القدرة المالية لاستكمال إجراءات الطلب.',
                        'en' => 'Enter required information and prove financial capacity',
                        'description_en' => 'Basic information is entered and financial capacity proof is attached to complete the request.',
                    ],
                    [
                        'icon' => 'fa-solid fa-credit-card',
                        'ar' => 'الإقرار بالمعلومات الصحيحة وسداد الرسوم',
                        'description_ar' => 'بعد مراجعة البيانات، يتم الإقرار بصحتها واستكمال سداد الرسوم المعتمدة.',
                        'en' => 'Confirm correct information and pay fees',
                        'description_en' => 'After reviewing the information, its accuracy is confirmed and approved fees are paid.',
                    ],
                ],
            ],
            [
                'type' => RequirementEnum::RecruitDomestic->value,
                'icon' => 'fa-solid fa-folder-open',
                'title_ar' => 'الوثائق المطلوبة',
                'title_en' => 'Required documents',
                'sort_order' => 2,
                'points' => [
                    [
                        'icon' => 'fa-solid fa-passport',
                        'ar' => 'تخليص اجراءات الاستقدام',
                        'description_ar' => 'تجهيز مستندات الطلب الأساسية واستكمال متطلبات التخليص قبل رفعها عبر مساند.',
                        'en' => 'Complete the recruitment procedures',
                        'description_en' => 'Prepare the main request documents and complete processing requirements before uploading them through Musaned.',
                    ],
                    [
                        'icon' => 'fa-solid fa-user',
                        'ar' => 'اختيار السيرة الذاتية',
                        'description_ar' => 'تحديد السيرة الذاتية المناسبة من الملفات المتاحة وفق احتياج الأسرة.',
                        'en' => 'Choose a CV',
                        'description_en' => 'Select the suitable CV from available files according to the family needs.',
                    ],
                    [
                        'icon' => 'fa-solid fa-id-card',
                        'ar' => 'صورة الهوية الوطنية او الإقامة للمقيمين والرقم المربوط في ابشر',
                        'description_ar' => 'إرفاق نسخة واضحة من الهوية الوطنية أو الإقامة مع الرقم المرتبط في أبشر.',
                        'en' => 'Copy of the national ID or Iqama, plus the mobile number linked to Absher',
                        'description_en' => 'Attach a clear copy of the national ID or Iqama together with the mobile number linked to Absher.',
                    ],
                    [
                        'icon' => 'fa-solid fa-briefcase',
                        'ar' => 'تعاقد الاستقدام عبر مساند وسداد الرسوم',
                        'description_ar' => 'توثيق عقد الاستقدام إلكترونيًا عبر منصة مساند ثم إتمام سداد الرسوم المطلوبة.',
                        'en' => 'Recruitment contract through Musaned and fee payment',
                        'description_en' => 'Document the recruitment contract electronically through Musaned, then complete the required payment.',
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($groups) {
            foreach ($groups as $group) {
                // مجموعة واحدة لكل نوع — نحدّث أقدمها بدل إنشاء نسخة مكررة
                $requirement = Requirement::query()
                    ->where('type', $group['type'])
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first() ?? new Requirement();

                $requirement->type = $group['type'];
                $requirement->icon = $group['icon'];
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
                    $item->icon = $point['icon'];
                    $item->sort_order = $sortOrder;
                    $item->translateOrNew('ar')->text = $point['ar'];
                    $item->translateOrNew('ar')->description = $point['description_ar'];
                    $item->translateOrNew('en')->text = $point['en'];
                    $item->translateOrNew('en')->description = $point['description_en'];
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

<?php

namespace Nami\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\AccountTypesEnum;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('fn_accounts')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = now();
        $balanceSheet = 'balance_sheet';
        $incomeStatement = 'income_statement';

        $typeOverrides = [
            3 => AccountTypesEnum::PropertyRights->value,
            4 => AccountTypesEnum::GeneralIncome->value,
            5 => AccountTypesEnum::GeneralOutcome->value,
            11 => AccountTypesEnum::FixedPrinciple->value,
            15 => AccountTypesEnum::CurrentPrinciple->value,
            21 => AccountTypesEnum::LongLiabilities->value,
            22 => AccountTypesEnum::ShortLiabilities->value,
            121 => AccountTypesEnum::Boxs->value,
            122 => AccountTypesEnum::Banks->value,
            123 => AccountTypesEnum::Clients->value,
            124 => AccountTypesEnum::CatchPapers->value,
            157 => AccountTypesEnum::Storage->value,
            158 => AccountTypesEnum::ElectronicWallets->value,
            411 => AccountTypesEnum::Sales->value,
            511 => AccountTypesEnum::Purchases->value,
            512 => AccountTypesEnum::SalesReturns->value,
            513 => AccountTypesEnum::BackPurchases->value,
            5410 => AccountTypesEnum::DiscountPermitted->value,
            1110 => AccountTypesEnum::DepreciationProvision->value,
            5453 => AccountTypesEnum::DepreciationExpenses->value,
        ];

        $accounts = [
            // Level 1
            [1, 'الأصول', 'debtor', 1, null, $balanceSheet],
            [2, 'الخصوم', 'creditor', 1, null, $balanceSheet],
            [3, 'حق الملكية', 'creditor', 1, null, $balanceSheet],
            [4, 'الإيرادات', 'creditor', 1, null, $incomeStatement],
            [5, 'المصروفات', 'debtor', 1, null, $incomeStatement],

            // Level 2 - الأصول
            [11, 'الأصول الثابتة', 'debtor', 2, 1, $balanceSheet],
            [12, 'الأصول غير المتداولة الأخرى', 'debtor', 2, 1, $balanceSheet],
            [13, 'الأصول غير الملموسة', 'debtor', 2, 1, $balanceSheet],
            [15, 'الأصول المتداولة قصيرة الأجل', 'debtor', 2, 1, $balanceSheet],

            // Level 2 - الخصوم وحق الملكية
            [21, 'الخصوم طويلة الأجل', 'creditor', 2, 2, $balanceSheet],
            [22, 'الخصوم قصيرة الأجل', 'creditor', 2, 2, $balanceSheet],
            [31, 'رأس المال', 'creditor', 2, 3, $balanceSheet],

            // Level 2 - الإيرادات
            [41, 'إيراد مبيعات الاستقدام', 'creditor', 2, 4, $incomeStatement],
            [42, 'إيراد الخدمات', 'creditor', 2, 4, $incomeStatement],
            [43, 'إيراد نقل الخدمات', 'creditor', 2, 4, $incomeStatement],
            [55, 'إيراد مبيعات التأجير', 'creditor', 2, 4, $incomeStatement],
            [44, 'إيرادات تشغيلية اخرى', 'creditor', 2, 4, $incomeStatement],
            [45, 'إيرادات اخرى', 'creditor', 2, 4, $incomeStatement],

            // Level 2 - المصروفات
            [51, 'تكاليف مبيعات الاستقدام', 'debtor', 2, 5, $incomeStatement],
            [52, 'مصروفات تشغيلية مباشرة', 'debtor', 2, 5, $incomeStatement],
            [53, 'تكاليف نقل خدمات', 'debtor', 2, 5, $incomeStatement],
            [54, 'اجمالي المصروفات عمومية وادارية', 'debtor', 2, 5, $incomeStatement],
            [56, 'تكلفة الخدمات الإضافية', 'debtor', 2, 5, $incomeStatement],
            [57, 'مصروفات بيعية وتسويقية', 'debtor', 2, 5, $incomeStatement],
            [58, 'مصروفات تشغيلية اخرى', 'debtor', 2, 5, $incomeStatement],
            [59, 'تكاليف مبيعات التأجير', 'debtor', 2, 5, $incomeStatement],
            [510, 'مصروفات اخرى', 'debtor', 2, 5, $incomeStatement],
            [514, 'مصروفات الاهلاك', 'debtor', 2, 5, $incomeStatement],

            // Level 3 - الأصول الثابتة
            [111, 'اجهزة والالات', 'debtor', 3, 11, $balanceSheet],
            [112, 'مباني وتجهيزات', 'debtor', 3, 11, $balanceSheet],
            [113, 'اثاث ومفروشات', 'debtor', 3, 11, $balanceSheet],
            [114, 'السيارات', 'debtor', 3, 11, $balanceSheet],
            [116, 'اجهزة كمبيوتر وطابعات', 'debtor', 3, 11, $balanceSheet],
            [117, 'انظمة وكاميرات', 'debtor', 3, 11, $balanceSheet],
            [119, 'الاراضي', 'debtor', 3, 11, $balanceSheet],
            [1110, 'مجمع اهلاك الاصول الثابتة', 'debtor', 3, 11, $balanceSheet],

            // Level 3 - الأصول غير المتداولة الأخرى
            [127, 'خطابات الضمان', 'debtor', 3, 12, $balanceSheet],
            [1210, 'مصروفات مدفوعة مقدما - طويلة الاجل', 'debtor', 3, 12, $balanceSheet],

            // Level 3 - الأصول غير الملموسة
            [131, 'استخدام التأجير', 'debtor', 3, 13, $balanceSheet],
            [118, 'برامج محاسبية ونظم الاستخدام', 'debtor', 3, 13, $balanceSheet],
            [132, 'برامج وانظمة تحت التطوير', 'debtor', 3, 13, $balanceSheet],
            [133, 'مجمع اهلاك الاصول غير الملموسة', 'debtor', 3, 13, $balanceSheet],

            // Level 3 - الخصوم طويلة الأجل
            [211, 'القروض والالتزامات البنكية', 'creditor', 3, 21, $balanceSheet],
            [213, 'مخصص منافع الموظفين', 'creditor', 3, 21, $balanceSheet],

            // Level 3 - الخصوم قصيرة الأجل
            [221, 'الوكلاء الخارجيين', 'creditor', 3, 22, $balanceSheet],
            [222, 'ارصدة دائنة اخرى', 'creditor', 3, 22, $balanceSheet],
            [223, 'مصروفات مستحقة عمولات مسوقين', 'creditor', 3, 22, $balanceSheet],
            [224, 'مصروفات مستحقة رواتب موظفين', 'creditor', 3, 22, $balanceSheet],
            [225, 'التزامات عقود الاستقدام', 'creditor', 3, 22, $balanceSheet],
            [226, 'مصروفات مستحقة بدل سكن موظفين', 'creditor', 3, 22, $balanceSheet],
            [227, 'مصروفات مستحقة بدل مواصلات موظفين', 'creditor', 3, 22, $balanceSheet],
            [212, 'التزامات بنك الراجحي', 'creditor', 3, 22, $balanceSheet],
            [228, 'مصروفات مستحقة عمولات قسم الخارجية', 'creditor', 3, 22, $balanceSheet],
            [229, 'دفعات مقدمة نقل خدمات', 'creditor', 3, 22, $balanceSheet],
            [2210, 'شركات التأمين', 'creditor', 3, 22, $balanceSheet],
            [2220, 'مصروفات مستحقة عمولات سائقين', 'creditor', 3, 22, $balanceSheet],

            // Level 3 - رأس المال
            [311, 'رأس المال', 'creditor', 3, 31, $balanceSheet],
            [312, 'الارباح والخسائر', 'creditor', 3, 31, $balanceSheet],
            [313, 'حقوق المساهمين - الاطراف', 'creditor', 3, 31, $balanceSheet],

            // Level 3 - إيراد مبيعات الاستقدام
            [411, 'مبيعات الاستقدام', 'creditor', 3, 41, $incomeStatement],
            [512, 'مرتد مبيعات الاستقدام', 'creditor', 3, 41, $incomeStatement],
            [5410, 'خصم مبيعات', 'creditor', 3, 41, $incomeStatement],
            [412, 'إيراد استرداد مبيعات', 'creditor', 3, 41, $incomeStatement],

            // Level 3 - إيراد الخدمات
            [424, 'إيراد خدمات العقود الرجالية', 'creditor', 3, 42, $incomeStatement],

            // Level 3 - إيراد نقل الخدمات
            [431, 'ايرادات نقل خدمات', 'creditor', 3, 43, $incomeStatement],
            [561, 'مردودات مبيعات نقل الخدمات', 'creditor', 3, 43, $incomeStatement],
            [432, 'خصومات نقل الخدمات', 'creditor', 3, 43, $incomeStatement],

            // Level 3 - إيرادات تشغيلية اخرى
            [421, 'ايرادات تشغيلية اخرى', 'creditor', 3, 44, $incomeStatement],

            // Level 3 - إيرادات اخرى
            [423, 'ايرادات اخرى', 'creditor', 3, 45, $incomeStatement],

            // Level 3 - تكاليف مبيعات الاستقدام
            [511, 'تكاليف المبيعات', 'debtor', 3, 51, $incomeStatement],
            [513, 'مردودات تكلفة الاستقدام', 'debtor', 3, 51, $incomeStatement],

            // Level 3 - مصروفات تشغيلية مباشرة
            [5416, 'عمولات مساند', 'debtor', 3, 52, $incomeStatement],
            [522, 'مصروفات انجاز تفويض الكترونية', 'debtor', 3, 52, $incomeStatement],

            // Level 3 - تكاليف نقل خدمات
            [531, 'تكاليف نقل خدمات', 'debtor', 3, 53, $incomeStatement],

            // Level 3 - اجمالي المصروفات عمومية وادارية
            [521, 'مصروفات حكومية', 'debtor', 3, 54, $incomeStatement],
            [541, 'الإيجارات', 'debtor', 3, 54, $incomeStatement],
            [542, 'الرواتب', 'debtor', 3, 54, $incomeStatement],
            [543, 'مصروفات ادارات مكتبية وقرطاسية', 'debtor', 3, 54, $incomeStatement],
            [545, 'مصروفات ضيافة', 'debtor', 3, 54, $incomeStatement],
            [546, 'مصروفات كهرباء و مياه', 'debtor', 3, 54, $incomeStatement],
            [547, 'مصروفات هاتف وانترنت', 'debtor', 3, 54, $incomeStatement],
            [548, 'مصروفات صيانة', 'debtor', 3, 54, $incomeStatement],
            [549, 'مصروفات نثرية', 'debtor', 3, 54, $incomeStatement],
            [5413, 'مصروفات ديون معدومة', 'debtor', 3, 54, $incomeStatement],
            [5414, 'عمولات ورسوم بنكية', 'debtor', 3, 54, $incomeStatement],
            [5415, 'خصم مبيعات الفلبين', 'debtor', 3, 54, $incomeStatement],
            [5419, 'رواتب عاملات', 'debtor', 3, 54, $incomeStatement],
            [5420, 'مصروفات استرداد', 'debtor', 3, 54, $incomeStatement],
            [5430, 'خصم مبيعات سيرلانكا', 'debtor', 3, 54, $incomeStatement],
            [5433, 'خصم مبيعات اوغندا', 'debtor', 3, 54, $incomeStatement],
            [5434, 'خصم مبيعات اثيوبيا', 'debtor', 3, 54, $incomeStatement],
            [5435, 'خصم مبيعات بنجلاديش', 'debtor', 3, 54, $incomeStatement],
            [5436, 'خصم مبيعات كينيا', 'debtor', 3, 54, $incomeStatement],
            [5437, 'خصم مبيعات الهند', 'debtor', 3, 54, $incomeStatement],
            [5438, 'خصم مبيعات باكستان', 'debtor', 3, 54, $incomeStatement],
            [5439, 'مصروفات بدل سكن موظفين', 'debtor', 3, 54, $incomeStatement],
            [5440, 'مصروفات بدل مواصلات موظفين', 'debtor', 3, 54, $incomeStatement],
            [5443, 'مصروفات الموظفين', 'debtor', 3, 54, $incomeStatement],
            [5444, 'مصروفات ادارية اخرى', 'debtor', 3, 54, $incomeStatement],
            [5445, 'التامينات الاجتماعية', 'debtor', 3, 54, $incomeStatement],
            [5455, 'مصروفات الادارة العامة', 'debtor', 3, 54, $incomeStatement],

            // Level 3 - تكلفة الخدمات الإضافية
            [544, 'مصروفات تذاكر العاملات', 'debtor', 3, 56, $incomeStatement],
            [526, 'مصاريف تشغيل الخدمة/عمولات توصيل السائقين', 'debtor', 3, 56, $incomeStatement],

            // Level 3 - مصروفات بيعية وتسويقية
            [5417, 'عمولات مسوقين', 'debtor', 3, 57, $incomeStatement],
            [5441, 'مصروفات تسويق', 'debtor', 3, 57, $incomeStatement],
            [5442, 'مصروفات دعاية واعلان', 'debtor', 3, 57, $incomeStatement],
            [5451, 'عمولات قسم الخارجية', 'debtor', 3, 57, $incomeStatement],
            [5452, 'عمولات مسوقين من الخارج', 'debtor', 3, 57, $incomeStatement],
            [571, 'زي موحد', 'debtor', 3, 57, $incomeStatement],

            // Level 3 - مصروفات تشغيلية اخرى
            [525, 'مصروفات تشغيلية اخرى', 'debtor', 3, 58, $incomeStatement],
            [527, 'مصروفات ايواء التشغيل', 'debtor', 3, 58, $incomeStatement],
            [528, 'غرامات تشغيل', 'debtor', 3, 58, $incomeStatement],
            [529, 'اهلاكات تشغيلية', 'debtor', 3, 58, $incomeStatement],
            [5418, 'مصروفات السيارات', 'debtor', 3, 58, $incomeStatement],
            [523, 'مصروفات سفرات اجنبية', 'debtor', 3, 58, $incomeStatement],
            [5450, 'مصروفات ايواء المتوسط', 'debtor', 3, 58, $incomeStatement],
            [524, 'تكاليف متابعة وضمان العاملات خلال فترة العقد', 'debtor', 3, 58, $incomeStatement],

            // Level 3 - مصروفات اخرى
            [581, 'مصروفات اخرى', 'debtor', 3, 510, $incomeStatement],
            [582, 'مصروفات متنوعة اخرى', 'debtor', 3, 510, $incomeStatement],

            // Level 3 - مصروفات الاهلاك
            [5453, 'مصروفات الاهلاك', 'debtor', 3, 514, $incomeStatement],

            // Level 3 - الأصول المتداولة قصيرة الأجل
            [121, 'النقدية', 'debtor', 3, 15, $balanceSheet],
            [122, 'البنوك', 'debtor', 3, 15, $balanceSheet],
            [123, 'العملاء', 'debtor', 3, 15, $balanceSheet],
            [124, 'اوراق القبض', 'debtor', 3, 15, $balanceSheet],
            [125, 'سلف الموظفين', 'debtor', 3, 15, $balanceSheet],
            [126, 'عهد الموظفين', 'debtor', 3, 15, $balanceSheet],
            [128, 'عملاء عقود رجالية', 'debtor', 3, 15, $balanceSheet],
            [129, 'اطراف ذات العلاقة', 'debtor', 3, 15, $balanceSheet],
            [151, 'خصومات العاملين', 'debtor', 3, 15, $balanceSheet],
            [152, 'مصروفات مدفوعة مقدما - الايواء', 'debtor', 3, 15, $balanceSheet],
            [153, 'مصروفات مدفوعة مقدما مكاتب خارجية', 'debtor', 3, 15, $balanceSheet],
            [154, 'مصروفات مدفوعة مقدما - ايواء التجربة', 'debtor', 3, 15, $balanceSheet],
            [155, 'مصروفات مدفوعة مقدما - قصيرة الاجل', 'debtor', 3, 15, $balanceSheet],
            [156, 'ارصدة مدينة اخرى', 'debtor', 3, 15, $balanceSheet],
            [157, 'مخزون مهمات', 'debtor', 3, 15, $balanceSheet],
            [158, 'محافظ رقمية', 'debtor', 3, 15, $balanceSheet],
            [159, 'مصروفات مدفوعة مقدما - التأجير', 'debtor', 3, 15, $balanceSheet],
        ];

        $rows = [];
        foreach ($accounts as $account) {
            [$code, $name, $status, $level, $parentCode, $statementType] = $account;
            $rows[] = [
                'id' => $code,
                'name' => $name,
                'operation_type' => 1,
                'status' => $status,
                'level' => $level,
                'code' => $code,
                'type' => $typeOverrides[$code] ?? AccountTypesEnum::NormalAccount->value,
                'parent_id' => $parentCode,
                'statement_type' => $statementType,
                'created_by_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('fn_accounts')->insert($rows);
    }
}

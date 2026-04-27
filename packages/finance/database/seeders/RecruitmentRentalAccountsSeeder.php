<?php

namespace Nami\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use RuntimeException;

class RecruitmentRentalAccountsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->accounts() as $definition) {
                $parent = null;

                if (array_key_exists('parent_code', $definition) && $definition['parent_code'] !== null) {
                    $parent = Account::query()
                        ->where('code', $definition['parent_code'])
                        ->lockForUpdate()
                        ->first();

                    if (! $parent) {
                        throw new RuntimeException(sprintf(
                            'الحساب الأب بالرقم %s غير موجود. شغّل AccountSeeder أولاً.',
                            $definition['parent_code']
                        ));
                    }
                }

                $payload = [
                    'name' => $definition['name'],
                    'operation_type' => $parent?->operation_type ?? 1,
                    'status' => $definition['status'],
                    'level' => $definition['level'],
                    'type' => $definition['type'] ?? AccountTypesEnum::NormalAccount->value,
                    'parent_id' => $parent?->id,
                    'statement_type' => $definition['statement_type'],
                    'created_by_id' => 1,
                ];

                $account = Account::query()
                    ->where('code', $definition['code'])
                    ->lockForUpdate()
                    ->first();

                if ($account) {
                    $account->update($payload);
                    continue;
                }

                Account::query()->create([
                    'code' => $definition['code'],
                    ...$payload,
                ]);
            }
        });
    }

    private function accounts(): array
    {
        $balanceSheet = 'balance_sheet';
        $incomeStatement = 'income_statement';

        return [
            [
                'code' => 2230,
                'name' => 'دفعات مقدمة تأجير',
                'status' => 'creditor',
                'level' => 3,
                'parent_code' => 22,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 22301,
                'name' => 'إيراد مقدم خدمات تأجير',
                'status' => 'creditor',
                'level' => 4,
                'parent_code' => 2230,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 461,
                'name' => 'إيراد خدمات التأجير',
                'status' => 'creditor',
                'level' => 3,
                'parent_code' => 42,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 4611,
                'name' => 'إيراد خدمات تأجير العمالة',
                'status' => 'creditor',
                'level' => 4,
                'parent_code' => 461,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 4219,
                'name' => 'إيرادات إلغاء التأجير',
                'status' => 'creditor',
                'level' => 4,
                'parent_code' => 421,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 15511,
                'name' => 'مصروفات مدفوعة مقدما تأجير عمالة',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 155,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 5255,
                'name' => 'مصروفات سفرات الاتصال - عمالة تأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 525,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 1591,
                'name' => 'مصروفات مدفوعة مقدما - تأشيرات عمل التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 159,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 1592,
                'name' => 'مصروفات مدفوعة مقدما - تكلفة استقدام التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 159,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 1593,
                'name' => 'مصروفات مدفوعة مقدما - رسوم إنجاز التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 159,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 1594,
                'name' => 'مصروفات مدفوعة مقدما - رسوم إقامات التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 159,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 1595,
                'name' => 'مصروفات مدفوعة مقدما - رسوم رخص عمل التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 159,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 1596,
                'name' => 'مصروفات مدفوعة مقدما - تأمين طبي عمالة التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 159,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 1597,
                'name' => 'مصروفات مدفوعة مقدما - تأشيرات مستخدمة التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 159,
                'statement_type' => $balanceSheet,
            ],
            [
                'code' => 591,
                'name' => 'تكلفة استقدام التأجير - الموردين',
                'status' => 'debtor',
                'level' => 3,
                'parent_code' => 59,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5911,
                'name' => 'تكلفة استقدام عمالة تأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 591,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5912,
                'name' => 'تكلفة استقدام عمالة تأجير أخرى 1',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 591,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5913,
                'name' => 'تكلفة استقدام عمالة تأجير أخرى 2',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 591,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 592,
                'name' => 'رسوم حكومية - التأجير',
                'status' => 'debtor',
                'level' => 3,
                'parent_code' => 59,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5921,
                'name' => 'رسوم تأشيرات عمل التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 592,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5922,
                'name' => 'رسوم إنجاز التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 592,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5923,
                'name' => 'رسوم إقامات التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 592,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5924,
                'name' => 'رسوم رخص عمل التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 592,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5925,
                'name' => 'رسوم تأمين طبي التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 592,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5926,
                'name' => 'رسوم تأمينات اجتماعية التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 592,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 593,
                'name' => 'رواتب تشغيلية - عمالة التأجير',
                'status' => 'debtor',
                'level' => 3,
                'parent_code' => 59,
                'statement_type' => $incomeStatement,
            ],
            [
                'code' => 5931,
                'name' => 'رواتب عمالة التأجير',
                'status' => 'debtor',
                'level' => 4,
                'parent_code' => 593,
                'statement_type' => $incomeStatement,
            ],
        ];
    }
}

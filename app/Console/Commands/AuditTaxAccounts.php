<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\TaxAccountResolver;

class AuditTaxAccounts extends Command
{
    protected $signature = 'finance:tax-accounts-audit';
    protected $description = 'فحص إعدادات الضريبة لكل شركة دون تعديل أو تخمين الحسابات';

    public function handle(TaxAccountResolver $resolver): int
    {
        $problems = 0;
        foreach (TaxAccountResolver::RATE_SETTINGS as $side => $title) {
            $legacy = $this->value($side);
            $selected = $this->value($title);
            $definition = DB::table('fn_tax_settings')->where('id', $selected)->first();
            $account = $resolver->resolve($side);
            $this->table(['الإعداد', 'الحساب القديم', 'حساب التعريف', 'النسبة', 'حساب الترحيل'], [[
                $title, $legacy ?: '—', $definition?->account_id ?: '—', $definition?->value ?? '—', $account?->id ?: '—',
            ]]);
            if (!$definition || !$account || (int) $definition->account_id !== (int) $account->id
                || (int) $definition->value <= 0 || (int) $definition->value > 100 || $resolver->conflict($side)) {
                $problems++;
                $this->warn('يلزم ضبط/مراجعة هذا الجانب ومقارنته بالقيود قبل الرفع.');
            }
        }
        if (!$problems) {
            $this->info('الإعدادات الحالية متوافقة. هذه النتيجة لا تثبت صحة القيود التاريخية أو اكتمال اختبارات العمليات.');
        }
        return $problems ? self::FAILURE : self::SUCCESS;
    }

    private function value(string $title)
    {
        return DB::table('fn_confirm_settings')->where('title', $title)->orderByDesc('updated_at')->orderByDesc('id')->value('value');
    }
}

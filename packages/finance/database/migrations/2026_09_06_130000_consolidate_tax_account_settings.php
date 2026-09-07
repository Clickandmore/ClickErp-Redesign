<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Services\TaxAccountResolver;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_confirm_settings') || !Schema::hasTable('fn_tax_settings') || !Schema::hasTable('fn_accounts')) {
            return;
        }

        // Both sides succeed together. Never guess a chart code or silently pick a rate.
        DB::transaction(function () {
            foreach (TaxAccountResolver::RATE_SETTINGS as $legacyTitle => $rateTitle) {
                $legacy = $this->setting($legacyTitle);
                $selection = $this->setting($rateTitle);
                $tax = DB::table('fn_tax_settings')->where('id', $selection?->value)->first();
                $status = $legacyTitle === 'revenue_tax_account' ? 'creditor' : 'debtor';
                $accountId = $legacy?->value ?: $tax?->account_id;
                if (!$accountId && !$selection?->value) {
                    continue; // Unconfigured company: audit must require manual setup.
                }
                if (!$accountId || !DB::table('fn_accounts')->where('id', $accountId)->where('status', $status)
                    ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('fn_accounts as c')->whereColumn('c.parent_id', 'fn_accounts.id'))->exists()) {
                    throw new RuntimeException("حساب ضريبة غير صالح: {$legacyTitle}. اضبطه يدويًا قبل الترحيل.");
                }
                if ($selection?->value && !$tax) {
                    throw new RuntimeException("تعريف الضريبة المختار غير موجود: {$rateTitle}");
                }

                if ($tax) {
                    $rate = (int) $tax->value;
                } else {
                    $rates = DB::table('fn_tax_settings')->where('account_id', $accountId)->distinct()->pluck('value');
                    if ($rates->isEmpty()) {
                        $rates = DB::table('fn_tax_settings')->distinct()->pluck('value');
                    }
                    if ($rates->count() !== 1) {
                        throw new RuntimeException("نسبة ضريبة غير محددة أو متعددة: {$rateTitle}. اختر التعريف المناسب قبل الترحيل.");
                    }
                    $rate = (int) $rates->first();
                }
                if ($rate <= 0 || $rate > 100) {
                    throw new RuntimeException("نسبة افتراضية غير صالحة: {$rateTitle}");
                }
                $actor = DB::table('admins')->where('id', $selection?->created_by_id ?: $legacy?->created_by_id ?: $tax?->created_by_id)->value('id')
                    ?: DB::table('admins')->orderBy('id')->value('id');
                if (!$actor) {
                    throw new RuntimeException('لا يوجد مستخدم إداري لإسناد إعدادات الضريبة.');
                }
                $resolver = app(TaxAccountResolver::class);
                $resolver->remember($legacyTitle, (int) $actor);
                $definitionId = DB::table('fn_tax_settings')->where('account_id', $accountId)->where('value', $rate)->orderBy('id')->value('id');
                if (!$definitionId) {
                    $definitionId = DB::table('fn_tax_settings')->insertGetId([
                        'name' => $status === 'creditor' ? 'ضريبة القيمة المضافة على الإيرادات' : 'ضريبة القيمة المضافة على المصروفات',
                        'value' => $rate, 'account_id' => $accountId, 'created_by_id' => $actor,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                // Preserve an already valid selection, including when duplicate definitions exist.
                if ($tax && (int) $tax->account_id === (int) $accountId && (int) $tax->value === $rate) {
                    $definitionId = $tax->id;
                }
                if ($selection) {
                    DB::table('fn_confirm_settings')->where('id', $selection->id)->update(['value' => $definitionId]);
                } else {
                    DB::table('fn_confirm_settings')->insert([
                        'title' => $rateTitle, 'value' => $definitionId, 'created_by_id' => $actor,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $resolver->remember($legacyTitle, (int) $actor);
            }
        });
    }

    private function setting(string $title): ?object
    {
        return DB::table('fn_confirm_settings')->where('title', $title)->orderByDesc('updated_at')->orderByDesc('id')->first();
    }

    public function down(): void
    {
        // Data migration: restore the pre-deployment backup if rollback is necessary.
    }
};

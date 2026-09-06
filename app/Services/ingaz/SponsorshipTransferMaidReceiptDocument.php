<?php

namespace App\Services\ingaz;

use App\Models\SponsorshipTransfer;
use Illuminate\Support\Str;
use PDF;

/**
 * يولّد "نموذج استلام عاملة نقل الخدمات" كملف PDF بنفس قالب نماذج النظام
 * (ترويسة الشركة، جداول البيانات، صندوق الإقرار، التوقيعات، التذييل)
 * مع تعبئة البيانات الديناميكية من طلب نقل الخدمات.
 */
class SponsorshipTransferMaidReceiptDocument
{
    public const VIEW = 'admin.print.sponsorship-transfer-maid-receipt';

    private const EMPTY = '..................';

    /**
     * يبني ملف PDF ويعيد محتواه واسم التحميل.
     *
     * @return array{0: string, 1: string} [محتوى الـ PDF، اسم التحميل]
     */
    public function generate(SponsorshipTransfer $transfer): array
    {
        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            // نفس إعدادات نموذج استلام عاملة الإيواء حتى يتطابق الخط والمقاس.
            $pdf = PDF::loadView(self::VIEW, $this->values($transfer), [], [
                'format' => 'A4',
                'mode' => 'utf-8',
            ]);

            $contents = $pdf->output();
        } finally {
            error_reporting($previousErrorReporting);
        }

        return [
            $contents,
            'نموذج استلام عاملة نقل الخدمات - عقد ' . ($transfer->contract_id ?: $transfer->id) . '.pdf',
        ];
    }

    /**
     * قيم الحقول الديناميكية في النموذج.
     */
    public function values(SponsorshipTransfer $transfer): array
    {
        $transfer->loadMissing(['contract.maid.nationality', 'newSponsor']);

        $client = $transfer->newSponsor;
        $maid = $transfer->contract?->maid;

        return [
            'issue_date' => now()->format('Y-m-d'),
            'company_name' => $this->companyName(),
            'client_name' => $this->text($client?->name),
            'client_id_number' => $this->text($client?->documentNumber()),
            'maid_name' => $this->text($maid?->name),
            'maid_passport' => $this->text($maid?->passport_num ?: $maid?->border_number),
            'maid_nationality' => $this->text($maid?->nationality?->sub_title ?? $maid?->nationality?->title),
            'contract_value' => $this->money($transfer->contract_value),
            'paid_amount' => $this->money($transfer->paid_amount),
            'remaining_amount' => $this->money($transfer->remaining_amount),
            'monthly_salary' => $this->money($maid?->resolved_salary ?? $maid?->salary, withCurrency: false),
            'trial_period' => $transfer->trial_days ? $transfer->trial_days . ' يوم' : self::EMPTY,
        ];
    }

    private function companyName(): string
    {
        $name = trim((string) (documentBranding()['company_name'] ?? ''));

        if ($name === '') {
            return 'الشركة';
        }

        return Str::startsWith($name, ['شركة', 'مؤسسة', 'مكتب']) ? $name : 'شركة ' . $name;
    }

    private function text(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : self::EMPTY;
    }

    private function money($amount, bool $withCurrency = true): string
    {
        if ($amount === null || $amount === '' || (float) $amount < 0) {
            return self::EMPTY;
        }

        $formatted = number_format((float) $amount, 2);

        return $withCurrency ? $formatted . ' ريال' : $formatted;
    }
}

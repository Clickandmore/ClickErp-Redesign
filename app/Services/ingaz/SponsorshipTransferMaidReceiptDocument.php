<?php

namespace App\Services\ingaz;

use App\Models\SponsorshipTransfer;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * يولّد "نموذج استلام عاملة نقل الخدمات" كملف Word انطلاقاً من القالب الأصلي
 * مع تعبئة البيانات الديناميكية (الشركة، العميل، العاملة، المبالغ، فترة التجربة).
 */
class SponsorshipTransferMaidReceiptDocument
{
    public const TEMPLATE = 'templates/docx/sponsorship-transfer-maid-receipt.docx';

    private const EMPTY = '..................';

    /**
     * يبني الملف في مجلد مؤقت ويعيد مساره واسم التحميل.
     *
     * @return array{0: string, 1: string} [مسار الملف المؤقت، اسم التحميل]
     */
    public function generate(SponsorshipTransfer $transfer): array
    {
        $transfer->loadMissing(['contract.maid.nationality', 'newSponsor']);

        $processor = new TemplateProcessor(resource_path(self::TEMPLATE));

        foreach ($this->values($transfer) as $key => $value) {
            $processor->setValue($key, $value);
        }

        $path = tempnam(sys_get_temp_dir(), 'maid-receipt-');
        $processor->saveAs($path);

        return [$path, 'نموذج استلام عاملة نقل الخدمات - عقد ' . ($transfer->contract_id ?: $transfer->id) . '.docx'];
    }

    /**
     * قيم العلامات الموجودة في القالب.
     */
    public function values(SponsorshipTransfer $transfer): array
    {
        $client = $transfer->newSponsor;
        $maid = $transfer->contract?->maid;

        return [
            'issue_date' => now()->format('d/m/Y'),
            'company_name' => $this->companyName(),
            'client_name' => $this->text($client?->name),
            'client_id_number' => $this->text($client?->documentNumber()),
            'maid_name' => $this->text($maid?->name),
            'maid_passport' => $this->text($maid?->passport_num ?: $maid?->border_number),
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

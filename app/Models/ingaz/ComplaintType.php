<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Model;

class ComplaintType extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = [
        'name', 'complainant_type', 'against_type', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function partyTypes(): array
    {
        return [
            'client' => 'عميل',
            'worker' => 'عاملة',
            'internal_office' => 'المكتب',
            'external_office' => 'مكتب خارجي',
            'employee' => 'موظف',
            'other' => 'أخرى',
        ];
    }

    /**
     * يحوّل صفة الطرف إلى أحد مفاتيح partyTypes. الشكاوى المستوردة تحمل نصًا عربيًا
     * مثل «عميل» أو «عاملـ/ـة» بدل المفتاح.
     */
    public static function normalizePartyType($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (array_key_exists($value, self::partyTypes())) {
            return $value;
        }

        $text = str_replace(['ـ', '/', 'ة', 'أ', 'إ', 'آ'], ['', '', 'ه', 'ا', 'ا', 'ا'], $value);

        return match (true) {
            str_contains($text, 'عميل') => 'client',
            str_contains($text, 'عامل') => 'worker',
            str_contains($text, 'خارجي') => 'external_office',
            str_contains($text, 'موظف') => 'employee',
            str_contains($text, 'مكتب') => 'internal_office',
            default => 'other',
        };
    }

    public function getClassificationLabelAttribute(): string
    {
        $labels = self::partyTypes();
        $from = $labels[$this->complainant_type] ?? 'غير محدد';
        $against = $labels[$this->against_type] ?? 'غير محدد';

        return "{$from} ضد {$against}";
    }
}

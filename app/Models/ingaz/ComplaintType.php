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

    public function getClassificationLabelAttribute(): string
    {
        $labels = self::partyTypes();
        $from = $labels[$this->complainant_type] ?? 'غير محدد';
        $against = $labels[$this->against_type] ?? 'غير محدد';

        return "{$from} ضد {$against}";
    }
}

<?php

namespace App\Http\Actions\ingaz;

use App\Models\FindUsLocation;
use App\Support\FindUsSettings;
use Illuminate\Database\Eloquent\Collection;

class FindUsLocationAction
{
    /**
     * كل الفروع المحفوظة — تُستخدم في شاشة الإدارة بغضّ النظر عن وضع العرض.
     */
    public function ordered(): Collection
    {
        return FindUsLocation::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * الفروع كما تُنشر للموقع والـ API؛ تعود فارغة في وضع الرابط الواحد
     * فيعتمد الموقع على رابط الخريطة الموحّد في الإعدادات.
     */
    public function visible(): Collection
    {
        if (!FindUsSettings::usesBranches()) {
            return new Collection();
        }

        return $this->ordered();
    }
}

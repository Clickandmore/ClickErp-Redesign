<?php

namespace App\Models\Concerns;

/**
 * يحوّل أعمدة الملفات إلى قيم صالحة للعرض عند تحويل الموديل إلى مصفوفة/JSON.
 *
 * السبب: عند تفعيل R2 تُخزَّن قيمة العمود كـ "r2://clickerp/..." وهي ليست رابطًا،
 * فأي endpoint يرجّع الموديل نفسه (لا Resource) كان يسرّب هذه القيمة إلى الموقع الخارجي.
 *
 * نتدخل في toArray() فقط — وليس attributesToArray() — حتى لا يتأثر:
 * - قراءة الخاصية مباشرة ($maid->cv_file) التي يعتمد عليها الرفع والحذف والبث
 * - سجل النشاط (Spatie ActivityLog) الذي يقرأ attributesToArray()، وإلا صارت
 *   الروابط الموقّتة تُسجَّل كتغيير في كل حفظ
 *
 * الملفات المحلية ترجع بمسارها النسبي كما هي، فلا يتغير أي سلوك قائم.
 */
trait ResolvesMediaUrls
{
    public function toArray()
    {
        $data = parent::toArray();

        foreach ($this->mediaUrlColumns as $column) {
            // نمرّر القيمة التي كانت ستخرج فعلًا (وليس getRawOriginal) حتى لا يُعرض
            // مسار قديم عند تحويل موديل عُدّل ولم يُحفظ بعد.
            if (array_key_exists($column, $data) && filled($data[$column])) {
                $data[$column] = apiFileUrl($data[$column]);
            }
        }

        return $data;
    }
}

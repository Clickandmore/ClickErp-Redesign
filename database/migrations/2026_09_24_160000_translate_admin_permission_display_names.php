<?php

use App\Support\PermissionLabelDictionary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * ترحيل 2026_09_24_150000 وحّد التصنيف لكنه ترجم الفعل فقط، فبقيت أسماء الموارد
 * إنجليزية في 614 صلاحية («إضافة الحساب statement»). هذا الترحيل يعيد توليد
 * المسمى من قاموس PermissionLabelDictionary لكل صف ما زال يحمل حروفاً لاتينية.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('permissions'));

        if (!isset($columns['display_name'])) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->select(['id', 'name', 'display_name'])
            ->orderBy('id')
            ->chunkById(200, function ($permissions) use ($columns) {
                foreach ($permissions as $permission) {
                    if (!PermissionLabelDictionary::needsTranslation($permission->display_name)) {
                        continue;
                    }

                    $label = PermissionLabelDictionary::label((string) $permission->name);

                    if (trim($label) === '') {
                        continue;
                    }

                    $values = ['display_name' => $label];

                    if (isset($columns['updated_at'])) {
                        $values['updated_at'] = now();
                    }

                    DB::table('permissions')->where('id', $permission->id)->update($values);
                }
            }, 'id');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // ترجمة مسميات عرض فقط. إعادة النص الإنجليزي لا قيمة لها.
    }
};

<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\ingaz\MaidLanguage;
use App\Support\MaidLanguageCatalog;
use App\PermKeys;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class MaidLanguagesSeeder extends Seeder
{
    public function run(): void
    {
        $permissionDefinitions = [
            PermKeys::MAID_LANGUAGES_VIEW => ['عرض اللغات', 'إمكانية عرض صفحة لغات السير الذاتية.'],
            PermKeys::MAID_LANGUAGES_CREATE => ['إضافة لغة', 'إمكانية إضافة لغة جديدة إلى نماذج السير الذاتية.'],
            PermKeys::MAID_LANGUAGES_EDIT => ['تعديل لغة', 'إمكانية تعديل العنوان العربي والإنجليزي للغة.'],
            PermKeys::MAID_LANGUAGES_DELETE => ['حذف لغة', 'إمكانية حذف لغة من إعدادات نماذج السير الذاتية.'],
            PermKeys::MAID_LANGUAGES_TOGGLE_VISIBILITY => ['إظهار وإخفاء اللغات', 'إمكانية إظهار اللغة أو إخفائها في نموذج السيرة الذاتية.'],
        ];
        $permissionColumns = array_flip(Schema::getColumnListing('permissions'));

        $permissionIds = collect($permissionDefinitions)->map(function ($definition, $permissionName) use ($permissionColumns) {
            $permission = Permission::withoutGlobalScopes()->firstOrNew([
                'name' => $permissionName,
                'guard_name' => 'admin',
            ]);
            $metadata = [
                'display_name' => $definition[0],
                'description' => $definition[1],
                'group_name' => 'maid_languages',
                'section_name' => 'cv_management',
                'is_active' => true,
            ];
            $permission->fill(array_intersect_key($metadata, $permissionColumns));
            $permission->save();

            return $permission->id;
        })->all();

        Role::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', config('access_control_permission_registry.defaults.assign_roles', ['Administrator']))
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching($permissionIds));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (MaidLanguageCatalog::LEGACY_LANGUAGES as $attributes) {
            if (MaidLanguage::withTrashed()->where('code', $attributes['code'])->exists()) {
                continue;
            }

            MaidLanguage::create($attributes + ['is_active' => true]);
        }
    }
}

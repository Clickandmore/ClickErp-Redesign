<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyServiceCategory, HourlyServiceCategoryLog};
use Illuminate\Support\Str;

/** أدوات مشتركة لفئات الخدمة: توليد slug فريد وسجل التعديلات (بنفس أسلوب ServiceItemService). */
class ServiceCategoryService
{
    public function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'category';
        $slug = $base;
        for ($i = 2; HourlyServiceCategory::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }
        return $slug;
    }

    /** فئة عنصر الخدمة: بالمعرّف، أو بالاسم النصي القادم من النماذج القديمة (تُنشأ إن لم توجد). */
    public function resolveForItem(?int $id, ?string $legacyName): HourlyServiceCategory
    {
        if ($id) return HourlyServiceCategory::findOrFail($id);
        $name = trim((string) $legacyName);
        return HourlyServiceCategory::firstOrCreate(['name_ar' => $name], [
            'name_en' => $name, 'slug' => $this->uniqueSlug($name), 'icon_type' => 'library', 'icon' => 'fa-broom',
            'is_active' => true, 'sort_order' => (int) HourlyServiceCategory::withTrashed()->max('sort_order') + 1,
        ]);
    }

    public function snapshot(HourlyServiceCategory $category): array
    {
        return collect($category->fresh()?->getAttributes() ?? $category->getAttributes())
            ->except(['created_at', 'updated_at', 'deleted_at'])->all();
    }

    /** يسجّل التغيير فقط إن اختلف قبل/بعد. section: category|districts|closures|settings|payments|notes */
    public function audit(HourlyServiceCategory $category, string $section, string $type, $before, $after): void
    {
        if ($before === $after) return;
        HourlyServiceCategoryLog::create([
            'service_category_id' => $category->id, 'category_name' => $category->name_ar,
            'section' => $section, 'type' => $type, 'before_data' => $before, 'after_data' => $after,
            'admin_id' => auth('admin')->id(), 'admin_name' => auth('admin')->user()?->name,
        ]);
    }
}

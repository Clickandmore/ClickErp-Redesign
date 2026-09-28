<?php

namespace App\Http\Requests\Hourly;

use App\Models\Hourly\HourlyServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; } // صلاحيات المسارات تُطبَّق عبر route_perm.

    public function rules(): array
    {
        $category = $this->route('category');
        $id = $category instanceof HourlyServiceCategory ? $category->id : null;
        $unique = fn ($column) => Rule::unique('hourly_service_categories', $column)->whereNull('deleted_at')->ignore($id);

        return [
            'name_ar' => ['required', 'string', 'max:150', $unique('name_ar')],
            'name_en' => ['nullable', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('hourly_service_categories', 'slug')->ignore($id)],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'icon_type' => ['required', Rule::in(['library', 'upload'])],
            'icon' => ['nullable', 'required_if:icon_type,library', 'string', 'max:60', 'regex:/^fa-[a-z0-9-]+$/'],
            'icon_file' => [Rule::requiredIf(fn () => $this->input('icon_type') === 'upload' && !($category instanceof HourlyServiceCategory && $category->icon_type === 'upload' && $category->icon)), 'nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name_ar' => __('hourly.category_name_ar'), 'name_en' => __('hourly.category_name_en'), 'slug' => 'slug',
            'icon' => __('hourly.category_icon'), 'icon_file' => __('hourly.category_icon'), 'color' => __('hourly.category_color'), 'image' => __('hourly.category_image'),
        ];
    }

    public function messages(): array
    {
        return ['icon.regex' => __('hourly.category_icon_hint'), 'slug.regex' => __('hourly.category_slug_hint')];
    }
}

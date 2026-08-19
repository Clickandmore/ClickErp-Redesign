<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class BlogRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $blogId = $this->route('blog');

        $rules = [
            // يُولَّد تلقائيًا من العنوان العربي عند تركه فارغًا.
            'slug' => [
                'nullable',
                'string',
                'max:200',
                Rule::unique('blogs', 'slug')->ignore($blogId),
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'image' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];

        foreach (config('translatable.locales', ['ar']) as $locale) {
            // اللغة الافتراضية إلزامية؛ باقي اللغات اختيارية حتى لا يتعطّل
            // النشر بانتظار الترجمة.
            $required = $locale === config('app.locale', 'ar') ? 'required' : 'nullable';

            $rules['title:' . $locale] = [$required, 'string', 'max:255'];
            $rules['excerpt:' . $locale] = ['nullable', 'string', 'max:1000'];
            $rules['content:' . $locale] = [$required, 'string'];
            $rules['meta_title:' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['meta_description:' . $locale] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function attributes()
    {
        $attributes = [
            'slug' => 'رابط المقال',
            'category' => 'التصنيف',
            'image' => 'صورة المقال',
            'published_at' => 'تاريخ النشر',
        ];

        foreach (config('translatable.locales', ['ar']) as $locale) {
            $attributes['title:' . $locale] = 'العنوان (' . $locale . ')';
            $attributes['excerpt:' . $locale] = 'الملخص (' . $locale . ')';
            $attributes['content:' . $locale] = 'المحتوى (' . $locale . ')';
            $attributes['meta_title:' . $locale] = 'عنوان SEO (' . $locale . ')';
            $attributes['meta_description:' . $locale] = 'وصف SEO (' . $locale . ')';
        }

        return $attributes;
    }
}

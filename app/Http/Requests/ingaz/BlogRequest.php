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
            // الأسئلة الشائعة للمقال: صفوف يضيفها المحرر ديناميكيًا في النموذج.
            'faqs' => ['nullable', 'array', 'max:50'],
            'faqs.*.id' => ['nullable', 'integer'],
            'faqs.*.sort_order' => ['nullable', 'integer', 'min:0'],
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

            // كل الأسئلة اختيارية — الصف الفارغ يُتجاهل عند الحفظ، لكن الصف
            // الذي كُتب فيه جواب دون سؤال يُرفض حتى لا يظهر في الموقع بلا عنوان.
            $rules['faqs.*.question:' . $locale] = [
                'nullable',
                'string',
                'max:500',
                'required_with:faqs.*.answer:' . $locale,
            ];
            $rules['faqs.*.answer:' . $locale] = ['nullable', 'string', 'max:5000'];
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
            'faqs' => 'الأسئلة الشائعة',
        ];

        foreach (config('translatable.locales', ['ar']) as $locale) {
            $attributes['title:' . $locale] = 'العنوان (' . $locale . ')';
            $attributes['excerpt:' . $locale] = 'الملخص (' . $locale . ')';
            $attributes['content:' . $locale] = 'المحتوى (' . $locale . ')';
            $attributes['meta_title:' . $locale] = 'عنوان SEO (' . $locale . ')';
            $attributes['meta_description:' . $locale] = 'وصف SEO (' . $locale . ')';
            $attributes['faqs.*.question:' . $locale] = 'سؤال شائع (' . $locale . ')';
            $attributes['faqs.*.answer:' . $locale] = 'إجابة السؤال الشائع (' . $locale . ')';
        }

        return $attributes;
    }
}

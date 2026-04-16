<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class BulkMaidImportUploadRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'ملف الاستيراد مطلوب.',
            'file.file' => 'الملف المرفوع غير صالح.',
            'file.mimes' => 'يجب أن يكون الملف بصيغة Excel: xlsx أو xls.',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}

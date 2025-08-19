<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SmsTemplate;

class SmsTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'msaned',
                'title' => 'رسالة الإسناد',
                'message' => 'عزيزي {client_name}، نود إعلامكم بأن عقدكم رقم {contract_number} قد تم إسناده بنجاح. سنتواصل معكم قريباً على رقم {client_phone} لمتابعة الإجراءات. للاستفسار: {company_phone} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email']
            ],
            [
                'name' => 'tafwed',
                'title' => 'رسالة التفويض',
                'message' => 'عزيزي {client_name}، تم تفويض عقدكم رقم {contract_number} بنجاح. الآن في مرحلة المعالجة والمراجعة. للاستفسار: {company_phone} أو {company_email} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email']
            ],
            [
                'name' => 'taffez',
                'title' => 'رسالة التجهيز',
                'message' => 'عزيزي {client_name}، تم تجهيز عقدكم رقم {contract_number} وهو الآن في مرحلة الإعداد النهائي. {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name']
            ],
            [
                'name' => 'tazker',
                'title' => 'رسالة التذكرة',
                'message' => 'عزيزي {client_name}، تم إصدار تذكرة السفر لعقدكم رقم {contract_number}. سنتواصل معكم قريباً لتحديد موعد الاستلام. {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name']
            ],
            [
                'name' => 'completed',
                'title' => 'رسالة الإكمال',
                'message' => 'عزيزي {client_name}، تم إكمال عقدكم رقم {contract_number} بنجاح وتم تسليم العاملة. نتمنى لكم تجربة مميزة. للاستفسار: {company_phone} أو {company_email} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email']
            ],
            [
                'name' => 'back',
                'title' => 'رسالة الإرجاع',
                'message' => 'عزيزي {client_name}، نعتذر لإبلاغكم بأن عقدكم رقم {contract_number} قد تم إرجاعه. سنتواصل معكم لتوضيح الأسباب والحلول البديلة. {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email']
            ],
            [
                'name' => 'closed',
                'title' => 'رسالة الإغلاق',
                'message' => 'عزيزي {client_name}، تم إغلاق عقدكم رقم {contract_number}. شكراً لثقتكم بخدماتنا. للتواصل المستقبلي: {company_phone} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email']
            ]
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}

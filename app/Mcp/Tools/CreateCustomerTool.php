<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\Country;
use App\Services\ingaz\ClientParentAccountMissing;
use App\Services\ingaz\ClientRegistrationService;

/**
 * إضافة عميل بنفس قواعد شاشة العملاء وخطواتها (ClientRegistrationService): منع التكرار
 * بالجوال والهوية والبريد، وإنشاء حسابه في دليل الحسابات. صلاحيتها صلاحية «إضافة عميل».
 */
class CreateCustomerTool extends McpWriteTool
{
    public function name(): string
    {
        return 'create_customer';
    }

    public function description(): string
    {
        return 'إضافة عميل جديد بخطوتين: الاستدعاء الأول يتحقق ويرجع معاينة ورمز تأكيد دون حفظ، وبعد موافقة المستخدم الصريحة '
            . 'يُعاد الاستدعاء مع confirmation_token فيُحفظ العميل ويُنشأ حسابه المالي. يرفض التكرار بالجوال أو الهوية أو البريد. '
            . 'للجنسية والفرع استخدم form_options (form=customer). Create a customer: preview first, then confirm with the token.';
    }

    protected function fieldsSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'اسم العميل (للفرد حروف فقط)'],
                'phone' => ['type' => 'string', 'description' => 'جوال سعودي، مثل 05xxxxxxxx أو 5xxxxxxxx'],
                'client_type' => ['type' => 'string', 'enum' => ['person', 'company'], 'default' => 'person'],
                'national_num' => ['type' => 'string', 'description' => 'رقم الهوية أو الإقامة (10 أرقام) — مطلوب للفرد'],
                'nationality_id' => ['type' => 'integer', 'description' => 'من form_options: countries'],
                'email' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'address' => ['type' => 'string'],
                'birthdate' => ['type' => 'string', 'format' => 'date'],
                'marital_status' => ['type' => 'string', 'enum' => ['single', 'married', 'divorced', 'widowed']],
                'job' => ['type' => 'string'],
                'phone2' => ['type' => 'string', 'description' => 'جوال آخر'],
                'phone2_code' => ['type' => 'string', 'default' => '+966'],
                'tax_number' => ['type' => 'string', 'description' => 'للشركة: 15 رقماً يبدأ بـ 3'],
                'license_number' => ['type' => 'string', 'description' => 'للشركة: السجل التجاري 10 أرقام'],
                'license_expiry_date' => ['type' => 'string', 'format' => 'date', 'description' => 'للشركة'],
                'branch_id' => ['type' => 'integer', 'description' => 'مطلوب فقط لمن يرى كل الفروع؛ غيره يُضاف لفرعه تلقائياً'],
                'notes' => ['type' => 'string'],
            ],
            'required' => ['name', 'phone', 'client_type'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'clients.store';
    }

    protected function prepare(array $arguments, Admin $admin): array
    {
        $registration = app(ClientRegistrationService::class);
        $arguments['client_type'] = $arguments['client_type'] ?? 'person';

        // الشاشة تحذف الصفر قبل الإرسال؛ هنا يُكتب الجوال كما ينطقه الناس (05… أو 966…)، فيُطبَّع
        // للصيغة التي تتحقق منها القواعد (5XXXXXXXX). رقم غير صالح يبقى كما هو لترفضه القواعد برسالتها.
        foreach (['phone', 'phone2'] as $field) {
            if ($field === 'phone2' && ($arguments['phone2_code'] ?? '+966') !== '+966') {
                continue;
            }
            if (!empty($arguments[$field])) {
                $digits = $registration->normalizeDigits([(string) $arguments[$field]])[0];
                $arguments[$field] = $registration->normalizeSaudiPhone($digits) ?? $digits;
            }
        }

        $data = $registration->validateNew($arguments, $admin, 'mcp');

        return [
            'data' => $data,
            'preview' => array_filter([
                'name' => $data['name'],
                'phone' => '0' . $data['phone'],
                'type' => $data['client_type'] === 'company' ? 'شركة' : 'فرد',
                'national_id' => $data['national_num'] ?? null,
                'nationality' => !empty($data['nationality_id']) ? Country::query()->whereKey($data['nationality_id'])->value('name_ar') : null,
                'tax_number' => $data['tax_number'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'email' => $data['email'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'branch' => !empty($data['branch_id']) ? Branch::query()->whereKey($data['branch_id'])->value('name_ar') : null,
                'will_also' => 'إنشاء حساب للعميل في دليل الحسابات وسجل في عملاء المالية.',
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }

    protected function commit(array $data, Admin $admin): array
    {
        try {
            $customer = app(ClientRegistrationService::class)->create($data);
        } catch (ClientParentAccountMissing $e) {
            throw new McpToolException($e->getMessage());
        }

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $this->mask($customer->phone),
                'url' => route('clients.show', $customer->id),
            ],
        ];
    }
}

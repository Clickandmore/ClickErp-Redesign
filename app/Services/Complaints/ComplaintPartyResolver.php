<?php

namespace App\Services\Complaints;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use Illuminate\Validation\ValidationException;

/**
 * أطراف الشكوى في نموذج «مقدم الشكوى ثم المشتكى عليه» (party_form).
 *
 * لا أعمدة جديدة: العميل المشتكى عليه في related_user_id، والمكتب الخارجي (أيًا كان طرفه)
 * في recruitment_office_id، والعاملة في حقول maid_* عبر ComplaintWorkerResolver.
 * الموظف يُحفظ باسمه وجواله فقط.
 */
class ComplaintPartyResolver
{
    public const INTERNAL_OFFICE_NAME = 'المكتب';

    public static function rules(): array
    {
        return [
            'complainant_office_id' => 'nullable|integer|exists:recruitment_offices,id',
            'complainant_admin_id' => 'nullable|integer|exists:admins,id',
            'against_office_id' => 'nullable|integer|exists:recruitment_offices,id',
            'against_admin_id' => 'nullable|integer|exists:admins,id',
        ];
    }

    /**
     * صفتا الطرفين: تصنيف النوع يفرضهما، والتصنيف غير المحدد أو «أخرى» يأخذ اختيار الموظف.
     */
    public function applyTypes(array &$data, ComplaintType $type): void
    {
        $pick = fn (?string $fromType, $input) => $fromType && $fromType !== 'other'
            ? $fromType
            : (ComplaintType::normalizePartyType($input) ?? 'other');
        $data['complainant_type'] = $pick($type->complainant_type, $data['complainant_type'] ?? null);
        $data['against_type'] = $pick($type->against_type, $data['against_type'] ?? null);

        if ($data['complainant_type'] === 'worker' && $data['against_type'] === 'worker') {
            $this->reject('against_type', 'لا يمكن أن تكون العاملة مقدمة الشكوى والمشتكى عليها في نفس الشكوى.');
        }
        if ($data['complainant_type'] === 'external_office' && $data['against_type'] === 'external_office') {
            $this->reject('against_type', 'اختر مكتبًا خارجيًا لطرف واحد فقط في الشكوى.');
        }
        if (($data['relation_type'] ?? null) === 'office' && in_array('external_office', [$data['complainant_type'], $data['against_type']], true)) {
            $this->reject('relation_type', 'المكتب الخارجي محدد كطرف في الشكوى؛ اختر ربطًا آخر.');
        }
    }

    /**
     * يُستدعى بعد ربط الشكوى بالسجل (الطلب/العقد) وقبل ComplaintWorkerResolver.
     */
    public function resolve(array &$data, ?Complaint $existing = null): void
    {
        $this->resolveComplainant($data);
        $this->resolveAgainst($data, $existing);
    }

    /**
     * يُستدعى بعد ComplaintWorkerResolver: اسم العاملة يصبح اسم طرفها.
     */
    public function finalizeWorker(array &$data, ?Complaint $existing = null): void
    {
        $side = $data['complainant_type'] === 'worker' ? 'complainant' : ($data['against_type'] === 'worker' ? 'against' : null);
        if (!$side) {
            return;
        }

        // الشكاوى القديمة قد تكون ضد عاملة غير مسجلة دون جواز؛ يبقى اسمها المحفوظ عند التعديل
        $name = ($data['maid_name'] ?? null) ?: $existing?->{$side . '_name'};
        if (blank($name)) {
            $this->reject('maid_id', $side === 'complainant'
                ? 'حدد العاملة مقدمة الشكوى: اخترها من النظام أو أدخل بياناتها كعاملة غير مسجلة.'
                : 'حدد العاملة المشتكى عليها: اخترها من النظام أو أدخل بياناتها كعاملة غير مسجلة.');
        }

        $data[$side . '_name'] = $name;
        if ($side === 'against') {
            $data['against_phone'] = null;
        }
    }

    private function resolveComplainant(array &$data): void
    {
        $type = $data['complainant_type'];
        if ($type !== 'client') {
            $data['user_id'] = null;
        }

        switch ($type) {
            case 'client':
                $user = !empty($data['user_id']) ? User::find($data['user_id']) : null;
                if (!$user && !empty($data['complainant_phone'])) {
                    $raw = (string) $data['complainant_phone'];
                    $user = User::where('phone', ltrim($raw, '0'))->orWhere('phone', $raw)->first();
                }
                $data['user_id'] = $user?->id;
                $data['complainant_name'] = trim((string) ($data['complainant_name'] ?? '')) ?: $user?->name;
                $data['complainant_phone'] = ($data['complainant_phone'] ?? null) ?: $user?->phone;
                if (blank($data['complainant_name'])) {
                    $this->reject('complainant_name', 'اختر العميل مقدم الشكوى أو أدخل اسمه.');
                }
                if (blank($data['complainant_phone'])) {
                    $this->reject('complainant_phone', 'جوال العميل مقدم الشكوى مطلوب.');
                }
                break;
            case 'external_office':
                $office = $this->office($data['complainant_office_id'] ?? null, 'complainant_office_id', 'اختر المكتب الخارجي مقدم الشكوى.');
                $data['recruitment_office_id'] = $office->id;
                $data['complainant_name'] = $office->title;
                $data['complainant_phone'] = $office->phone_office;
                break;
            case 'employee':
                $admin = $this->admin($data['complainant_admin_id'] ?? null, 'complainant_admin_id', 'اختر الموظف مقدم الشكوى.');
                $data['complainant_name'] = $admin->name;
                $data['complainant_phone'] = $admin->phone;
                break;
            case 'internal_office':
                $data['complainant_name'] = trim((string) ($data['complainant_name'] ?? '')) ?: self::INTERNAL_OFFICE_NAME;
                break;
            case 'worker':
                // الاسم يُستكمل من بيانات العاملة في finalizeWorker
                break;
            default:
                if (blank($data['complainant_name'] ?? null)) {
                    $this->reject('complainant_name', 'اسم مقدم الشكوى مطلوب.');
                }
        }

        if (!empty($data['complainant_phone'])) {
            $data['complainant_phone'] = ltrim((string) $data['complainant_phone'], '0');
        }
    }

    private function resolveAgainst(array &$data, ?Complaint $existing): void
    {
        $type = $data['against_type'];
        if ($type !== 'client') {
            $data['related_user_id'] = null;
        }

        switch ($type) {
            case 'client':
                $user = !empty($data['related_user_id']) ? User::find($data['related_user_id']) : null;
                // بلا اختيار صريح: عميل الطلب أو العقد المرتبط، كما في النموذج السابق
                $user ??= $this->linkedClient($data);
                $data['related_user_id'] = $user?->id;
                $data['against_name'] = trim((string) ($data['against_name'] ?? '')) ?: $user?->name;
                $data['against_phone'] = ($data['against_phone'] ?? null) ?: $user?->phone;
                if (blank($data['against_name']) && !$existing?->against_name) {
                    $this->reject('related_user_id', 'اختر العميل المشتكى عليه أو أدخل اسمه.');
                }
                $data['against_name'] = $data['against_name'] ?: $existing?->against_name;
                break;
            case 'external_office':
                $office = $this->office($data['against_office_id'] ?? null, 'against_office_id', 'اختر المكتب الخارجي المشتكى عليه.');
                $data['recruitment_office_id'] = $office->id;
                $data['against_name'] = $office->title;
                $data['against_phone'] = $office->phone_office;
                break;
            case 'employee':
                $admin = $this->admin($data['against_admin_id'] ?? null, 'against_admin_id', 'اختر الموظف المشتكى عليه.');
                $data['against_name'] = $admin->name;
                $data['against_phone'] = $admin->phone;
                break;
            case 'internal_office':
                $data['against_name'] = trim((string) ($data['against_name'] ?? '')) ?: self::INTERNAL_OFFICE_NAME;
                break;
            case 'worker':
                break;
            default:
                if (blank($data['against_name'] ?? null)) {
                    $this->reject('against_name', 'اسم المشتكى عليه مطلوب.');
                }
        }
    }

    private function linkedClient(array $data): ?User
    {
        if (!empty($data['contract_id'])) {
            return Contract::with('user')->find($data['contract_id'])?->user;
        }
        if (!empty($data['order_id'])) {
            return Order::with('user')->find($data['order_id'])?->user;
        }

        return null;
    }

    private function office($id, string $field, string $message): RecruitmentOffices
    {
        $office = $id ? RecruitmentOffices::find($id) : null;

        return $office ?: $this->reject($field, $message);
    }

    private function admin($id, string $field, string $message): Admin
    {
        $admin = $id ? Admin::find($id) : null;

        return $admin ?: $this->reject($field, $message);
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}

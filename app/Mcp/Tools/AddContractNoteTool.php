<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\ContractNote;
use App\NewPermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Support\Facades\Validator;

/** ملاحظة على عقد استقدام، بقاعدة RecruitmentContractController::storeNote نفسها. */
class AddContractNoteTool extends McpWriteTool
{
    public function name(): string
    {
        return 'add_contract_note';
    }

    public function description(): string
    {
        return 'إضافة ملاحظة على عقد استقدام. تعمل بخطوتين: معاينة ثم موافقة المستخدم. Add a note to a recruitment contract (preview, then approval).';
    }

    protected function fieldsSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'contract_id' => ['type' => 'integer', 'description' => 'معرّف العقد id من list_recruitment_contracts'],
                'note' => ['type' => 'string', 'maxLength' => 1000],
            ],
            'required' => ['contract_id', 'note'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'RecruitmentContract.index';
    }

    /** مسار الملاحظات بلا إدخال في المصفوفة؛ الشاشة تفحص صلاحية تعديل العقود داخل المتحكم. */
    public function isAllowedFor(Admin $admin): bool
    {
        return parent::isAllowedFor($admin) && AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [
            NewPermKeys::REC_CONTRACTS_UPDATE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ]);
    }

    public function category(): string
    {
        return 'contracts';
    }

    protected function prepare(array $arguments, Admin $admin): array
    {
        $data = Validator::make($arguments, [
            'contract_id' => ['required', 'integer'],
            'note' => ['required', 'string', 'max:1000'],
        ])->validate();

        return [
            'data' => $data,
            'preview' => [
                'contract_number' => $this->contract((int) $data['contract_id'])->arabia_order_number,
                'note' => $data['note'],
            ],
        ];
    }

    protected function commit(array $data, Admin $admin): array
    {
        $contract = $this->contract((int) $data['contract_id']);
        $note = ContractNote::create([
            'contract_id' => $contract->id,
            'admin_id' => $admin->id,
            'note' => $data['note'],
        ]);

        return [
            'note_id' => $note->id,
            'contract_number' => $contract->arabia_order_number,
            'url' => $this->link('RecruitmentContract.show', $contract->id),
        ];
    }

    /** BranchScope يخفي عقود الفروع الأخرى. */
    private function contract(int $id): Contract
    {
        $contract = Contract::query()->whereIn('type', ['recruitment', 'maid'])->find($id);

        if (!$contract) {
            throw new McpToolException('العقد غير موجود أو خارج نطاق فرعك.');
        }

        return $contract;
    }
}

<?php

namespace App\Http\Controllers\ingaz;

use App\Models\Contract;
use App\Models\DeportationProcedure;
use App\PermKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Shares the existing deportation workflow, with recruitment-specific access. */
class RecruitmentDeportationController extends ShelterContractController
{
    public function __construct()
    {
        $this->middleware('permission:' . PermKeys::REC_CONTRACTS_DEPORTATION . ',admin');
    }

    private function recruitmentContract($id): Contract
    {
        $contract = Contract::whereIn('type', ['recruitment', 'maid'])->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');

        return $contract;
    }

    private function procedureContract($id): Contract
    {
        return $this->recruitmentContract(DeportationProcedure::findOrFail($id)->contract_id);
    }

    public function startDeportation($id)
    {
        $this->recruitmentContract($id);

        return DB::transaction(function () use ($id) {
            $contract = Contract::lockForUpdate()->findOrFail($id);
            if (!in_array($contract->status, ['arrival', 'completed'], true)) {
                return response()->json(['success' => false, 'message' => 'يمكن بدء الترحيل للعقود في مرحلة الوصول أو المكتملة فقط'], 422);
            }
            if ($contract->hasEscapeReport() || $contract->info?->is_outside_shelter) {
                return response()->json(['success' => false, 'message' => 'لا يمكن بدء الترحيل لوجود بلاغ هروب على العاملة'], 422);
            }
            if (!$contract->maid_id || !$contract->maid) {
                return response()->json(['success' => false, 'message' => 'يجب ربط العاملة بالعقد قبل بدء إجراءات الترحيل'], 422);
            }
            if (DeportationProcedure::where('contract_id', $id)->where('status', '!=', 'cancelled')->exists()) {
                return response()->json(['success' => false, 'message' => 'توجد إجراءات ترحيل مسجلة بالفعل لهذا العقد'], 422);
            }

            $procedure = DeportationProcedure::create([
                'contract_id' => $contract->id,
                'maid_id' => $contract->maid_id,
                'status' => 'pending',
                'started_at' => now(),
                'created_by' => auth('admin')->id(),
            ]);

            return response()->json([
                'success' => true,
                'procedure_id' => $procedure->id,
                'redirect' => route('RecruitmentContract.deportation.procedures', $contract->id),
            ]);
        });
    }

    public function showDeportationProcedures($id)
    {
        $this->recruitmentContract($id);

        return parent::showDeportationProcedures($id);
    }

    public function updateDeportationProcedures(Request $request, $id)
    {
        $this->procedureContract($id);
        abort_if(DeportationProcedure::findOrFail($id)->status === 'cancelled', 422, 'تم إلغاء إجراء الترحيل');

        return parent::updateDeportationProcedures($request, $id);
    }

    public function cancelDeportation(Request $request, $id)
    {
        $this->procedureContract($id);
        $response = parent::cancelDeportation($request, $id);
        if ($response->isSuccessful()) {
            $response->setData(['success' => true, 'message' => 'تم إلغاء إجراء الترحيل دون تغيير مرحلة العقد']);
        }

        return $response;
    }

    public function getActivityLog($id)
    {
        $this->procedureContract($id);

        return parent::getActivityLog($id);
    }
}

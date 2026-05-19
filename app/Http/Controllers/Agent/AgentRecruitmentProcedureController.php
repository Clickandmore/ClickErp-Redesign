<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Maid;
use App\Models\Contract;
use App\Models\ingaz\Nationality;
use App\Models\MaidRecruitmentProcedure;
use App\Enums\ingaz\ContractStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class AgentRecruitmentProcedureController extends Controller
{
    public function index(Request $request)
    {
        $officeIds = $this->agentOfficeIds();

        // Fetch nationalities for filtering
        $nationalities = Nationality::all();

        // 1. Build Query
        $query = Maid::query()
            ->whereIn('office_id', $officeIds->all())
            ->where('nationality_id', 174)
            ->with([
                'nationality',
                'office',
                'recruitmentProcedure',
                'contracts' => function ($cQuery) {
                    $cQuery->whereNotIn('status', [
                        ContractStatusEnum::Completed->value,
                        ContractStatusEnum::Closed->value,
                        ContractStatusEnum::Back->value,
                    ])->with('user');
                }
            ]);

        // Filter: Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%' . $search . '%')
                    ->orWhere('passport_num', 'like', '%' . $search . '%')
                    ->orWhereHas('contracts.user', function ($uQuery) use ($search) {
                        $uQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('national_num', 'like', '%' . $search . '%');
                    });
            });
        }

        // Filter: Nationality
        if ($request->filled('nationality_id')) {
            $query->where('nationality_id', $request->nationality_id);
        }

        // Filter: Status / Stage
        if ($request->filled('stage')) {
            $stage = $request->stage;
            $query->whereHas('recruitmentProcedure', function ($pQuery) use ($stage) {
                if ($stage === 'medical_fit') {
                    $pQuery->where('medical_status', 'fit');
                } elseif ($stage === 'medical_pending') {
                    $pQuery->where('medical_status', 'pending');
                } elseif ($stage === 'oec_released') {
                    $pQuery->where('oec_release_status', 'released');
                } elseif ($stage === 'ticket_purchased') {
                    $pQuery->where('ticket_purchase_status', 'purchased');
                }
            });
        }

        $maids = $query->latest('id')->paginate(15)->appends($request->query());

        // 2. Calculate Statistics
        $statsBase = Maid::query()->whereIn('office_id', $officeIds->all())->where('nationality_id', 174);

        $totalMaids = (clone $statsBase)->count();

        $medicalFit = (clone $statsBase)->whereHas('recruitmentProcedure', function ($q) {
            $q->where('medical_status', 'fit');
        })->count();

        $medicalPending = (clone $statsBase)->where(function ($q) {
            $q->whereDoesntHave('recruitmentProcedure')
              ->orWhereHas('recruitmentProcedure', function ($inner) {
                  $inner->where('medical_status', 'pending');
              });
        })->count();

        $oecReleased = (clone $statsBase)->whereHas('recruitmentProcedure', function ($q) {
            $q->where('oec_release_status', 'released');
        })->count();

        $ticketPurchased = (clone $statsBase)->whereHas('recruitmentProcedure', function ($q) {
            $q->where('ticket_purchase_status', 'purchased');
        })->count();

        $stats = [
            'total' => $totalMaids,
            'medical_fit' => $medicalFit,
            'medical_pending' => $medicalPending,
            'oec_released' => $oecReleased,
            'ticket_purchased' => $ticketPurchased,
        ];

        return view('agent.philippines-tracking.index', compact('maids', 'nationalities', 'stats'));
    }

    public function updateStep(Request $request, Maid $maid)
    {
        // Authorize agent's office ownership
        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains((int)$maid->office_id)) {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar' ? 'غير مصرح لك بتعديل بيانات هذه العاملة.' : 'Unauthorized access to this worker.',
            ], 403);
        }

        $validated = $request->validate([
            'step' => 'required|string',
            'status' => 'nullable|string',
            'date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        // Find or create procedure record
        $procedure = MaidRecruitmentProcedure::firstOrCreate([
            'maid_id' => $maid->id,
        ]);

        // Sync with active contract if exists
        $activeContract = $maid->contracts()
            ->whereNotIn('status', [
                ContractStatusEnum::Completed->value,
                ContractStatusEnum::Closed->value,
                ContractStatusEnum::Back->value,
            ])
            ->first();

        if ($activeContract) {
            $procedure->contract_id = $activeContract->id;
        }

        $step = $validated['step'];
        $status = $validated['status'] ?? 'pending';
        $date = $validated['date'] ?: null;

        // Map steps to columns
        switch ($step) {
            case 'applied_at':
                $procedure->applied_at = $date;
                break;
            case 'medical':
                $procedure->medical_status = $status;
                $procedure->medical_date = $date;
                break;
            case 'tesda':
                $procedure->tesda_status = $status;
                $procedure->tesda_date = $date;
                break;
            case 'owwa':
                $procedure->owwa_status = $status;
                $procedure->owwa_date = $date;
                break;
            case 'oec_payment':
                $procedure->oec_payment_status = $status;
                $procedure->oec_payment_date = $date;
                break;
            case 'oec_release':
                $procedure->oec_release_status = $status;
                $procedure->oec_release_date = $date;
                break;
            case 'embassy_stamping':
                $procedure->embassy_stamping_status = $status;
                $procedure->embassy_stamping_date = $date;
                break;
            case 'poea_stamping':
                $procedure->poea_stamping_status = $status;
                $procedure->poea_stamping_date = $date;
                break;
            case 'ticket_payment':
                $procedure->ticket_payment_status = $status;
                $procedure->ticket_payment_date = $date;
                break;
            case 'ticket_purchase':
                $procedure->ticket_purchase_status = $status;
                $procedure->ticket_purchase_date = $date;
                break;
            case 'remarks':
                $procedure->remarks = $validated['remarks'];
                break;
        }

        // Auto calculate pipeline progress stage
        $procedure->current_stage = $this->resolveCurrentStage($procedure);
        $procedure->save();

        $percentage = $this->calculateProgressPercentage($procedure);

        return response()->json([
            'success' => true,
            'message' => app()->getLocale() === 'ar' ? 'تم تحديث الخطوة بنجاح.' : 'Step updated successfully.',
            'percentage' => $percentage,
            'current_stage' => $procedure->current_stage,
            'formatted_stage' => $this->formatStageLabel($procedure->current_stage),
        ]);
    }

    public function bulkUpdate(Request $request, Maid $maid)
    {
        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains((int)$maid->office_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $procedure = MaidRecruitmentProcedure::firstOrCreate(['maid_id' => $maid->id]);

        // Sync with active contract
        $activeContract = $maid->contracts()
            ->whereNotIn('status', [
                ContractStatusEnum::Completed->value,
                ContractStatusEnum::Closed->value,
                ContractStatusEnum::Back->value,
            ])->first();
        if ($activeContract) {
            $procedure->contract_id = $activeContract->id;
        }

        $fields = [
            'applied_at', 'medical_status', 'medical_date', 'tesda_status', 'tesda_date',
            'owwa_status', 'owwa_date', 'oec_payment_status', 'oec_payment_date',
            'oec_release_status', 'oec_release_date', 'embassy_stamping_status',
            'embassy_stamping_date', 'poea_stamping_status', 'poea_stamping_date',
            'ticket_payment_status', 'ticket_payment_date', 'ticket_purchase_status',
            'ticket_purchase_date', 'remarks',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                $procedure->{$field} = ($value === '' || $value === null) ? null : $value;
            }
        }

        $procedure->current_stage = $this->resolveCurrentStage($procedure);
        $procedure->save();

        return response()->json([
            'success' => true,
            'message' => app()->getLocale() === 'ar' ? 'تم حفظ جميع التعديلات بنجاح.' : 'All changes saved successfully.',
            'percentage' => $this->calculateProgressPercentage($procedure),
            'current_stage' => $procedure->current_stage,
            'formatted_stage' => $this->formatStageLabel($procedure->current_stage),
        ]);
    }

    private function agentOfficeIds(): Collection
    {
        $impersonatedOfficeId = session('_agent_impersonate_office_id');

        if ($impersonatedOfficeId && Auth::guard('admin')->check()) {
            return collect([(int) $impersonatedOfficeId]);
        }

        return Auth::guard('agent')
            ->user()
            ->agentOfficeLinks()
            ->pluck('office_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function resolveCurrentStage(MaidRecruitmentProcedure $procedure): string
    {
        if ($procedure->ticket_purchase_status === 'purchased') return 'ticket_purchased';
        if ($procedure->poea_stamping_status === 'approved') return 'poea_approved';
        if ($procedure->embassy_stamping_status === 'stamped') return 'embassy_stamped';
        if ($procedure->oec_release_status === 'released') return 'oec_released';
        if ($procedure->owwa_status === 'paid' || $procedure->owwa_status === 'active') return 'owwa_completed';
        if ($procedure->tesda_status === 'certified') return 'tesda_completed';
        if ($procedure->medical_status === 'fit') return 'medical_fit';
        if ($procedure->medical_status === 'unfit') return 'medical_unfit';
        return 'medical_pending';
    }

    private function formatStageLabel(string $stage): string
    {
        $isAr = app()->getLocale() === 'ar';
        $labels = [
            'ticket_purchased' => $isAr ? 'تم حجز التذكرة' : 'Ticket Purchased',
            'poea_approved' => $isAr ? 'توثيق POEA' : 'POEA Approved',
            'embassy_stamped' => $isAr ? 'تأشيرة السفارة' : 'Embassy Stamped',
            'oec_released' => $isAr ? 'تصريح العمل جاهز' : 'OEC Released',
            'owwa_completed' => $isAr ? 'عضوية OWWA' : 'OWWA Completed',
            'tesda_completed' => $isAr ? 'شهادة TESDA' : 'TESDA Completed',
            'medical_fit' => $isAr ? 'لائق طبيّاً' : 'Medical FIT',
            'medical_unfit' => $isAr ? 'غير لائق طبيّاً' : 'Medical UNFIT',
            'medical_pending' => $isAr ? 'فحص معلق' : 'Medical Pending',
        ];
        return $labels[$stage] ?? $stage;
    }

    private function calculateProgressPercentage(MaidRecruitmentProcedure $procedure): int
    {
        $percentage = 0;
        if ($procedure->medical_status === 'fit') $percentage += 20;
        if ($procedure->tesda_status === 'certified') $percentage += 10;
        if ($procedure->owwa_status === 'paid' || $procedure->owwa_status === 'active') $percentage += 10;
        if ($procedure->oec_payment_status === 'paid') $percentage += 10;
        if ($procedure->oec_release_status === 'released') $percentage += 10;
        if ($procedure->embassy_stamping_status === 'stamped') $percentage += 15;
        if ($procedure->poea_stamping_status === 'approved') $percentage += 10;
        if ($procedure->ticket_purchase_status === 'purchased') $percentage += 15;
        return min(100, $percentage);
    }
}

<?php

namespace App\Http\Controllers\Agent;

use App\Enums\ingaz\ComplaintSubmissionMethodEnum;
use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintComment;
use App\Models\ingaz\ComplaintType;
use App\Models\office\Office;
use App\Services\ManagedFileStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * شكاوى المكتب الخارجي — الوكيل يفتح شكوى ويربطها بأحد عقوده، ويتابع ردّ الإدارة.
 *
 * كل استعلام هنا محصور بمكاتب الوكيل: العرض عبر complaintsQuery() والربط عبر
 * officeContractsQuery()، حتى لا تظهر شكوى أو عقد لا يخص المكتب.
 */
class AgentComplaintController extends Controller
{
    /** أنواع الربط المتاحة للوكيل — لا يوجد ربط "مكتب خارجي" لأن المكتب هو مُقدّم الشكوى. */
    private const RELATION_TYPES = ['none', 'contract', 'manual'];

    public function index(Request $request)
    {
        $baseQuery = $this->complaintsQuery();

        $query = (clone $baseQuery)
            ->with(['complaintType', 'contract.user', 'contract.maid', 'assignedTo'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('subject', 'like', '%' . $search . '%')
                    ->orWhere('details', 'like', '%' . $search . '%')
                    ->orWhere('complainant_name', 'like', '%' . $search . '%')
                    ->orWhere('against_name', 'like', '%' . $search . '%')
                    ->orWhere('relation_reference', 'like', '%' . $search . '%')
                    ->orWhereHas('contract.maid', function (Builder $maidQuery) use ($search) {
                        $maidQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('passport_num', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('complaint_type_id')) {
            $query->where('complaint_type_id', $request->input('complaint_type_id'));
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'resolved' => (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        $complaints = $query->paginate(15)->appends($request->query());
        $complaintTypes = $this->agentComplaintTypes();

        return view('agent.complaints.index', compact('complaints', 'stats', 'complaintTypes'));
    }

    public function create()
    {
        return view('agent.complaints.create', [
            'complaintTypes' => $this->agentComplaintTypes(),
            'offices' => $this->agentOffices(),
            'contractOptions' => $this->preselectedContractOptions(),
        ]);
    }

    /**
     * قائمة العقود في نموذج الربط — محصورة بعقود مكاتب الوكيل.
     */
    public function searchContracts(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
        ]);

        $search = trim($data['q'] ?? '');
        $query = $this->officeContractsQuery()->with(['user', 'maid', 'info']);

        if ($search !== '') {
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('id', $search)
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('maid', function (Builder $maidQuery) use ($search) {
                        $maidQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('passport_num', 'like', '%' . $search . '%');
                    });
            });
        }

        $contracts = $query->latest('id')->simplePaginate(25, ['*'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'results' => $contracts->getCollection()
                ->map(fn (Contract $contract) => [
                    'id' => $contract->id,
                    'text' => $this->contractSelectorLabel($contract),
                ])
                ->values(),
            'pagination' => ['more' => $contracts->hasMorePages()],
        ]);
    }

    public function store(Request $request)
    {
        $officeIds = $this->agentOfficeIds();

        if ($officeIds->isEmpty()) {
            abort(403);
        }

        $validated = $request->validate([
            'complainant_name' => 'required|string|max:255',
            'complainant_phone' => 'nullable|string|max:50',
            // الوكيل المرتبط بأكثر من مكتب يختار المكتب صاحب الشكوى.
            'office_id' => 'nullable|integer|in:' . $officeIds->implode(','),
            'subject' => 'required|string|max:255',
            'complaint_type_id' => 'required|exists:complaint_types,id',
            'details' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'relation_type' => 'required|in:' . implode(',', self::RELATION_TYPES),
            'contract_id' => 'nullable|required_if:relation_type,contract|integer',
            'against_name' => 'nullable|required_if:relation_type,manual|string|max:255',
            'against_phone' => 'nullable|string|max:50',
            'relation_notes' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ]);

        $complaintType = ComplaintType::findOrFail($validated['complaint_type_id']);

        $data = [
            'complainant_name' => $validated['complainant_name'],
            'complainant_phone' => $validated['complainant_phone'] ?? null,
            // مُقدّم الشكوى هو المكتب الخارجي دائماً في هذه البوابة، فلا يُقرأ من الطلب.
            'complainant_type' => 'external_office',
            'against_type' => $complaintType->against_type ?: null,
            'subject' => $validated['subject'],
            'complaint_type_id' => $complaintType->id,
            'details' => $validated['details'],
            'message' => $validated['details'], // للتوافق مع الحقل القديم
            'priority' => $validated['priority'],
            'status' => 'pending',
            'relation_type' => $validated['relation_type'],
            'relation_notes' => $validated['relation_notes'] ?? null,
            // المكتب مصدر الشكوى — يُختم هنا ليظهر في عمود "المكتب الخارجي" بلوحة التحكم.
            'recruitment_office_id' => (int) ($validated['office_id'] ?? $officeIds->first()),
            'submission_method' => ComplaintSubmissionMethodEnum::AgentPortal->value,
            'is_read' => false,
        ];

        if ($validated['relation_type'] === 'contract') {
            $contract = $this->officeContractsQuery()
                ->with(['user', 'maid'])
                ->whereKey($validated['contract_id'])
                ->firstOrFail();

            $data['contract_id'] = $contract->id;
            $data['relation_reference'] = (string) $contract->id;
            $data['related_to'] = 'عقد #' . $contract->id;
            $data['against_name'] = $data['against_type'] === 'worker'
                ? ($contract->maid?->name ?: $contract->user?->name)
                : ($contract->user?->name ?: $contract->maid?->name);
            $data['against_phone'] = $data['against_type'] === 'worker' ? null : $contract->user?->phone;
            $data['maid_passport'] = $contract->maid?->passport_num;
            $data['maid_nationality_id'] = $contract->maid?->nationality_id;
            $data['user_id'] = $contract->user_id;
        } elseif ($validated['relation_type'] === 'manual') {
            $data['against_name'] = $validated['against_name'];
            $data['against_phone'] = $validated['against_phone'] ?? null;
            $data['relation_reference'] = $validated['against_name'];
            $data['related_to'] = 'بيانات يدوية — ' . $validated['against_name'];
            $data['against_type'] = $data['against_type'] ?: 'other';
        } else {
            $data['related_to'] = null;
        }

        $complaint = Complaint::create($data);
        $this->storeAttachments($complaint, $request->file('attachments', []));

        return redirect()
            ->route('agent.complaints.show', $complaint->id)
            ->with('success', app()->getLocale() === 'ar'
                ? 'تم إرسال الشكوى بنجاح وسيتم مراجعتها من الإدارة.'
                : 'The complaint was submitted and is pending review.');
    }

    public function show($complaint)
    {
        $complaint = $this->complaintsQuery()
            ->with([
                'complaintType',
                'contract.user',
                'contract.maid.nationality',
                'assignedTo',
                'attachmentFiles',
                'comments.admin',
                'recruitmentOffice',
            ])
            ->whereKey($complaint)
            ->firstOrFail();

        return view('agent.complaints.show', compact('complaint'));
    }

    /**
     * ردّ الوكيل على الشكوى — يُسجَّل كتحديث ولا يغيّر حالة الشكوى ولا المسؤول عنها.
     */
    public function addComment(Request $request, $complaint)
    {
        $complaint = $this->complaintsQuery()->whereKey($complaint)->firstOrFail();

        $validated = $request->validate([
            'comment' => 'required|string|max:10000',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ]);

        ComplaintComment::create([
            'comment' => $validated['comment'],
            'complaint_id' => $complaint->id,
            'added_by' => Auth::guard('agent')->id(),
        ]);

        $this->storeAttachments($complaint, $request->file('attachments', []));

        return redirect()
            ->route('agent.complaints.show', $complaint->id)
            ->with('success', app()->getLocale() === 'ar' ? 'تم إضافة الرد بنجاح.' : 'Your reply was added.');
    }

    private function storeAttachments(Complaint $complaint, array $files): void
    {
        foreach (array_values(array_filter($files)) as $file) {
            $path = app(ManagedFileStorage::class)->uploadFile($file, 'complaints/attachments');

            try {
                $complaint->attachmentFiles()->create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'added_by' => Auth::guard('agent')->id(),
                ]);
            } catch (\Throwable $exception) {
                deleteFile($path);
                throw $exception;
            }
        }
    }

    /**
     * أنواع الشكاوى المعروضة للوكيل: المصنّفة كشكوى من مكتب خارجي، أو غير المصنّفة.
     * عند خلو هذه المجموعة نعود لكل الأنواع المفعّلة حتى لا يتعطّل النموذج.
     */
    private function agentComplaintTypes(): Collection
    {
        $baseQuery = fn () => ComplaintType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        $types = $baseQuery()
            ->where(function (Builder $query) {
                $query->where('complainant_type', 'external_office')
                    ->orWhereNull('complainant_type')
                    ->orWhere('complainant_type', '');
            })
            ->get();

        return $types->isNotEmpty() ? $types : $baseQuery()->get();
    }

    /** خيار العقد المُختار مسبقاً (رجوع بخطأ تحقّق أو قدوم من صفحة العقد). */
    private function preselectedContractOptions(): Collection
    {
        $contractId = old('contract_id', request('contract_id'));

        if (!$contractId) {
            return collect();
        }

        $contract = $this->officeContractsQuery()->with(['user', 'maid', 'info'])->find($contractId);

        return $contract
            ? collect([['id' => $contract->id, 'label' => $this->contractSelectorLabel($contract)]])
            : collect();
    }

    private function contractSelectorLabel(Contract $contract): string
    {
        $contractDate = $contract->info?->contract_date ?: $contract->contract_date;
        $status = ContractStatusEnum::tryFrom((string) $contract->status);

        return implode(' — ', array_filter([
            'عقد #' . $contract->id,
            $contract->user?->name ?: 'بدون عميل',
            $contract->maid?->name,
            $contractDate ? \Carbon\Carbon::parse($contractDate)->format('Y-m-d') : null,
            $status?->lang(),
        ]));
    }

    /**
     * شكاوى المكتب: ما أنشأه المكتب (recruitment_office_id) وما يخص عقوده.
     */
    private function complaintsQuery(): Builder
    {
        $officeIds = $this->agentOfficeIds();

        return Complaint::query()->where(function (Builder $query) use ($officeIds) {
            $query->whereIn('recruitment_office_id', $officeIds->all())
                ->orWhereHas('contract', function (Builder $contractQuery) use ($officeIds) {
                    $this->applyOfficeContractConstraint($contractQuery, $officeIds);
                });
        });
    }

    private function officeContractsQuery(): Builder
    {
        $query = Contract::query();
        $this->applyOfficeContractConstraint($query, $this->agentOfficeIds());

        return $query;
    }

    private function applyOfficeContractConstraint(Builder $query, Collection $officeIds): void
    {
        $query->where(function (Builder $innerQuery) use ($officeIds) {
            $innerQuery->whereHas('maid', function (Builder $maidQuery) use ($officeIds) {
                $maidQuery->whereIn('office_id', $officeIds->all());
            });

            if (Schema::hasTable('contract_infos') && Schema::hasColumn('contract_infos', 'manual_maid_office_id')) {
                $innerQuery->orWhereHas('info', function (Builder $infoQuery) use ($officeIds) {
                    $infoQuery->whereIn('manual_maid_office_id', $officeIds->all());
                });
            }
        });
    }

    private function agentOffices(): Collection
    {
        return Office::query()
            ->whereIn('id', $this->agentOfficeIds()->all())
            ->orderBy('id')
            ->get();
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
}

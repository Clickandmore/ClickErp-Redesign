<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractArchiveLog;
use App\Models\RentalContract;
use App\PermKeys;
use App\Services\ContractArchiveService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * شاشة أرشيف العقود: تبويب "المؤرشفة" وتبويب "قابلة للأرشفة" لكل نوع عقد،
 * مع أرشفة واسترجاع لعقد واحد أو أكثر.
 */
class ContractArchiveController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(private ContractArchiveService $archive)
    {
    }

    public function index(Request $request, string $type)
    {
        $this->ensureType($type);

        $tab = $request->input('tab') === 'eligible' ? 'eligible' : 'archived';
        $search = trim((string) $request->input('search', ''));
        $canManage = self::canManage($type);

        if ($tab === 'eligible' && !$canManage) {
            $tab = 'archived';
        }

        $query = $this->baseQuery($type, $search);
        $tab === 'archived'
            ? $query->onlyArchived()->with('archivedByAdmin')->orderByDesc($query->getModel()->qualifyColumn('archived_at'))
            : $query->notArchived()
                ->whereIn($query->getModel()->qualifyColumn('status'), $type === ContractArchiveService::TYPE_RENTAL
                    ? ContractArchiveService::RENTAL_FINAL_STATUSES
                    : ContractArchiveService::RECRUITMENT_FINAL_STATUSES)
                ->orderBy($query->getModel()->qualifyColumn('id'));

        $contracts = $query->paginate(self::PER_PAGE)->withQueryString();

        // في تبويب "قابلة للأرشفة" يُعرض سبب المنع بجانب كل عقد لا يحقق الشروط.
        $blockers = [];
        if ($tab === 'eligible') {
            if ($type === ContractArchiveService::TYPE_RENTAL) {
                $this->archive->primeRentalBalances($contracts->pluck('id')->all());
            }
            foreach ($contracts as $contract) {
                $blockers[$contract->id] = $this->archive->archiveBlocker($contract);
            }
        }

        $archivedCount = $this->baseQuery($type, '')->onlyArchived()->count();

        return view('admin.ingaz.contract-archive.index', [
            'type' => $type,
            'typeLabel' => ContractArchiveService::typeLabel($type),
            'tab' => $tab,
            'search' => $search,
            'contracts' => $contracts,
            'blockers' => $blockers,
            'archivedCount' => $archivedCount,
            'canManage' => $canManage,
            'listRoute' => $type === ContractArchiveService::TYPE_RENTAL ? 'RentContract.index' : 'RecruitmentContract.index',
            'showRoute' => $type === ContractArchiveService::TYPE_RENTAL ? 'RentContract.show' : 'RecruitmentContract.show',
        ]);
    }

    public function archive(Request $request, string $type)
    {
        $this->ensureType($type);
        abort_unless(self::canManage($type), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:' . ContractArchiveService::MAX_BATCH],
            'ids.*' => ['integer'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'ids.required' => 'اختر عقداً واحداً على الأقل.',
            'reason.required' => 'سبب الأرشفة مطلوب.',
            'reason.min' => 'اكتب سبباً واضحاً للأرشفة.',
        ]);

        return $this->respond($request, function () use ($type, $validated) {
            return $this->archive->archive($type, $validated['ids'], $validated['reason'], auth('admin')->id());
        }, 'تمت أرشفة');
    }

    public function restore(Request $request, string $type)
    {
        $this->ensureType($type);
        abort_unless(self::canManage($type), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:' . ContractArchiveService::MAX_BATCH],
            'ids.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'ids.required' => 'اختر عقداً واحداً على الأقل.',
        ]);

        return $this->respond($request, function () use ($type, $validated) {
            return $this->archive->restore($type, $validated['ids'], $validated['reason'] ?? null, auth('admin')->id());
        }, 'تم استرجاع');
    }

    /** أسماء مسارات الأرشيف لكل نوع، مكتوبة كاملة حتى يتحقق منها فحص المسارات. */
    public static function routeNames(string $type): array
    {
        return $type === ContractArchiveService::TYPE_RENTAL
            ? ['index' => 'contracts-archive.rental.index', 'archive' => 'contracts-archive.rental.archive', 'restore' => 'contracts-archive.rental.restore']
            : ['index' => 'contracts-archive.recruitment.index', 'archive' => 'contracts-archive.recruitment.archive', 'restore' => 'contracts-archive.recruitment.restore'];
    }

    public static function canView(string $type): bool
    {
        return self::canManage($type) || ($type === ContractArchiveService::TYPE_RENTAL
            ? @AdminGuardHasPerm(PermKeys::RENT_CONTRACT_ARCHIVE_VIEW)
            : @AdminGuardHasPerm(PermKeys::REC_CONTRACTS_ARCHIVE_VIEW));
    }

    public static function canManage(string $type): bool
    {
        return $type === ContractArchiveService::TYPE_RENTAL
            ? (@AdminGuardHasPerm(PermKeys::RENT_CONTRACT_ARCHIVE) || @AdminGuardHasPerm(PermKeys::RENT_CONTRACT_CONTROL))
            : (@AdminGuardHasPerm(PermKeys::REC_CONTRACTS_ARCHIVE) || @AdminGuardHasPerm(PermKeys::REC_CONTRACTS_MANAGE));
    }

    private function respond(Request $request, callable $operation, string $verb)
    {
        try {
            $result = $operation();
        } catch (InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        $done = count($result['done']);
        $message = $done > 0 ? "{$verb} {$done} عقد بنجاح." : 'لم يتم تنفيذ العملية على أي عقد.';

        if ($request->expectsJson()) {
            return response()->json(['success' => $done > 0, 'message' => $message] + $result, $done > 0 ? 200 : 422);
        }

        return back()
            ->with($done > 0 ? 'success' : 'error', $message)
            ->with('archive_rejected', $result['rejected']);
    }

    private function baseQuery(string $type, string $search): Builder
    {
        if ($type === ContractArchiveService::TYPE_RENTAL) {
            $query = RentalContract::query()->with(['user', 'maid']);
            if ($search !== '') {
                $query->quickSearch($search);
            }

            return $query;
        }

        $query = Contract::query()->with(['user', 'maid'])->whereIn('type', ['recruitment', 'maid']);
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('contracts.id', (int) $search);
                }
                $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('maid', fn ($m) => $m->where('name', 'like', "%{$search}%")->orWhere('passport_num', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function ensureType(string $type): void
    {
        abort_unless(in_array($type, ContractArchiveService::types(), true), 404);
        abort_unless(self::canView($type), 403);
    }

    /** آخر عمليات الأرشفة لعقد، لعرضها في صفحة العقد. */
    public static function recentLogs(string $type, int $contractId)
    {
        return ContractArchiveLog::query()
            ->with('admin')
            ->where('contract_type', $type)
            ->where('contract_id', $contractId)
            ->latest('id')
            ->limit(5)
            ->get();
    }
}

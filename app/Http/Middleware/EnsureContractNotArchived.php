<?php

namespace App\Http\Middleware;

use App\Models\Contract;
use App\Models\RentalContract;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * العقد المؤرشف للقراءة فقط: يمنع أي تعديل عليه من شاشات الإدارة حتى يُسترجع.
 *
 * يعمل ضمن مجموعة web، ويتجاهل كل طلب لا يخص عقد استقدام أو تأجير، وكل طلب
 * قراءة عدا نماذج التعديل. العرض والطباعة والفواتير التاريخية تبقى متاحة.
 */
class EnsureContractNotArchived
{
    /** بادئات أسماء المسارات ونوع العقد الذي يخصها. */
    private const ROUTE_PREFIXES = [
        'RecruitmentContract.' => Contract::class,
        'RentContract.' => RentalContract::class,
        'rental.transfer-invoice.' => RentalContract::class,
    ];

    /** مسارات قراءة تفتح نموذج تعديل، فتُمنع أيضاً. */
    private const BLOCKED_READ_ROUTES = [
        'RecruitmentContract.edit',
        'RecruitmentContract.selectMaid',
        'RecruitmentContract.showChangeMaid',
        'RecruitmentContract.clientChangeOptions',
        'RecruitmentContract.stageSteps.index',
        'RentContract.edit',
        'RentContract.resumeOptions',
        'rental.transfer-invoice.create',
    ];

    /** مسارات معاملها {id} ليس رقم عقد. */
    private const NON_CONTRACT_ID_ROUTES = [
        'RecruitmentContract.deportation.update',
        'RecruitmentContract.deportation.cancel',
        'RecruitmentContract.deportation.activity-log',
    ];

    /** مسارات تحمل رقم العقد في جسم الطلب لا في الرابط. */
    private const BODY_ID_ROUTES = [
        'RecruitmentContract.updateStatus' => 'row_id',
        'RecruitmentContract.bulkUpdateStatus' => 'row_ids',
        'RecruitmentContract.refund' => 'contract_id',
        'RecruitmentContract.refundPreview' => 'contract_id',
        'RecruitmentContract.externalOfficeDispatch.bulkStore' => 'contract_ids',
    ];

    private const ROUTE_PARAMETERS = ['RecruitmentContract', 'RentContract', 'contract', 'contractId', 'id'];

    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()?->getName();
        $modelClass = $routeName ? $this->modelFor($routeName) : null;
        if ($modelClass === null || !auth('admin')->check() || !$modelClass::archiveColumnExists()) {
            return $next($request);
        }

        $isRead = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        if ($isRead && !in_array($routeName, self::BLOCKED_READ_ROUTES, true)) {
            return $next($request);
        }

        $ids = $this->contractIds($request, $routeName);
        if ($ids === []) {
            return $next($request);
        }

        $archivedIds = $modelClass::query()->withoutGlobalScopes()
            ->whereKey($ids)
            ->whereNotNull('archived_at')
            ->pluck('id')
            ->all();

        if ($archivedIds === []) {
            return $next($request);
        }

        return $this->reject($request, $archivedIds);
    }

    private function modelFor(string $routeName): ?string
    {
        foreach (self::ROUTE_PREFIXES as $prefix => $modelClass) {
            if (Str::startsWith($routeName, $prefix)) {
                return $modelClass;
            }
        }

        return null;
    }

    /** @return array<int, int> */
    private function contractIds(Request $request, string $routeName): array
    {
        if (isset(self::BODY_ID_ROUTES[$routeName])) {
            return $this->normalizeIds($request->input(self::BODY_ID_ROUTES[$routeName]));
        }

        foreach (self::ROUTE_PARAMETERS as $parameter) {
            if ($parameter === 'id' && in_array($routeName, self::NON_CONTRACT_ID_ROUTES, true)) {
                continue;
            }

            $value = $request->route($parameter);
            if ($value === null) {
                continue;
            }

            return $this->normalizeIds($value instanceof Model ? $value->getKey() : $value);
        }

        return [];
    }

    /** @return array<int, int> */
    private function normalizeIds($value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function reject(Request $request, array $archivedIds)
    {
        $message = count($archivedIds) === 1
            ? 'هذا العقد مؤرشف ولا يمكن تعديله. استرجعه من الأرشيف أولاً.'
            : 'العقود التالية مؤرشفة ولا يمكن تعديلها: #' . implode('، #', $archivedIds) . '. استرجعها من الأرشيف أولاً.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'archived_contract_ids' => $archivedIds,
            ], 409);
        }

        return redirect()->back()->with('error', $message);
    }
}

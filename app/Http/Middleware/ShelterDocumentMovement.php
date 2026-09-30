<?php

namespace App\Http\Middleware;

use App\Models\Contract;
use App\Services\ShelterDocumentMovementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShelterDocumentMovement
{
    public function handle(Request $request, Closure $next)
    {
        $service = app(ShelterDocumentMovementService::class);
        if (!$service->available()) return $next($request);
        $route = $request->route()->getName();
        $admission = in_array($route, ['ShelterContract.store', 'ShelterContract.returnFromVacation'], true);
        $action = null;
        foreach ($service::ACTIONS as $key => $definition) {
            if ($definition[0] === $route) $action = $key;
        }
        if ($route === 'ShelterContract.destroy') $action = 'back';
        if ($action === 'deportation' && !$request->boolean('complete_deportation')) return $next($request);
        if (!$admission && !$action) return $next($request);
        $reference = (int) ($request->route('id') ?? $request->route('ShelterContract') ?? $request->input('contract_id'));
        $contract = $service->resolve($action ?? 'back', $reference);
        if (!$contract) return $next($request);
        $configuration = $service->configuration($contract, 'release');
        if (!$admission && (!$configuration || !$service->inside($contract))) return $next($request);
        if (!$admission && !$request->has('shelter_release')) {
            return response()->json([
                'success' => false, 'message' => 'استكمل بيانات نموذج التسليم قبل تأكيد خروج العاملة.',
                'shelter_document_url' => route('ShelterContract.releaseForm', ['id' => $contract->id, 'action' => $action, 'reference' => $reference]),
            ], 409);
        }
        $data = $admission ? null : $service->releaseData($request);
        $level = DB::transactionLevel();
        DB::beginTransaction();
        try {
            $locked = Contract::with('info')->lockForUpdate()->findOrFail($contract->id);
            $wasInside = $service->inside($locked);
            if (!$admission && !$wasInside) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'تغيرت حالة العاملة؛ حدّث الصفحة.'], 409);
            }
            if (!$admission && !hash_equals($service->version($locked), (string) $request->input('shelter_release_version', ''))) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'تغير سجل الإيواء منذ فتح النموذج؛ أعد فتح شاشة الخروج.'], 409);
            }
            $response = $next($request);
            $failed = $response instanceof \Illuminate\Http\JsonResponse && ($response->getData(true)['success'] ?? null) === false;
            if ($response->getStatusCode() >= 400 || $failed) {
                while (DB::transactionLevel() > $level) DB::rollBack();
                return $response;
            }
            $fresh = $locked->fresh();
            if ($admission && !$wasInside && $service->inside($fresh)) {
                if ($route === 'ShelterContract.returnFromVacation') $service->refreshAdmissionData($fresh);
                else \App\Models\ShelterDocument::where('contract_id', $fresh->id)->where('kind', 'release')->update([
                    'release_completed_at' => null, 'revision' => (string) \Illuminate\Support\Str::uuid(),
                ]);
            } elseif (!$admission && !$service->inside($fresh)) {
                $service->saveReleaseData($configuration, $data);
            }
            DB::commit();
            return $response;
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > $level) DB::rollBack();
            throw $e;
        }
    }
}

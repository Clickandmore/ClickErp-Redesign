<?php

namespace App\Services;

use App\Models\RentalContract;
use App\Models\RentalContractExpiryReminder;
use App\Models\ingaz\Package;
use App\Services\ingaz\RentalContractFinanceService;
use App\Support\MoyasarSettings;
use App\Support\NotificationAlertSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalContractCustomerActionService
{
    public function __construct(private RentalContractFinanceService $finance, private RentalContractExpiryReminderService $reminders) {}

    public function resolve(string $token): RentalContractExpiryReminder
    {
        $reminder = RentalContractExpiryReminder::where('token', $token)->firstOrFail();
        return $this->cycleQuery($reminder)->firstOrFail();
    }

    private function cycleQuery(RentalContractExpiryReminder $reminder)
    {
        return RentalContractExpiryReminder::where('rental_contract_id', $reminder->rental_contract_id)
            ->whereDate('expires_on', $reminder->expires_on->toDateString())
            ->orderByRaw('CASE WHEN responded_at IS NULL THEN 1 ELSE 0 END')->orderBy('id');
    }

    public function available(RentalContract $contract, RentalContractExpiryReminder $reminder): bool
    {
        return $contract->canCloseOrExtend() && !$contract->closed_at && !$reminder->contract_effect_applied_at
            && $contract->info?->ingaz_to && $contract->info->ingaz_to->toDateString() === $reminder->expires_on->toDateString();
    }

    public function options(RentalContract $contract, RentalContractExpiryReminder $reminder): array
    {
        if (!$this->available($contract, $reminder)) return [];
        $contract->loadMissing(['maid', 'info', 'extensions']);
        $basis = $this->finance->currentPricingBasis($contract);
        $options = [];
        // Offer only the package the customer is subscribed to, never other packages.
        $package = $this->subscribedPackage($contract);
        if ($package) {
            $options[] = $this->quote($contract, 'package-' . $package->id, $package->title ?: 'باقتك الحالية', (int) $package->days_count, $package, $basis);
        } elseif ((int) $basis['days'] > 0 && (float) $basis['gross_total'] > 0) {
            $options[] = $this->quote($contract, 'current', 'بنفس المدة والسعر الحالي للعقد', (int) $basis['days'], null, $basis);
        }
        return collect($options)->filter(fn ($quote) => $quote['amount'] > 0 && Carbon::parse($quote['end_date'])->gte(today()))
            ->map(function ($quote) use ($reminder) {
                $quote['token'] = hash_hmac('sha256', $reminder->token . json_encode($quote, JSON_UNESCAPED_UNICODE), (string) config('app.key'));
                return $quote;
            })->values()->all();
    }

    // The latest extension's package wins (the customer may have switched on renewal), else the contract's original package.
    private function subscribedPackage(RentalContract $contract): ?Package
    {
        $packageId = $contract->extensions->whereNotNull('package_id')->sortByDesc('id')->first()?->package_id
            ?: $contract->info?->package_id;
        $package = $packageId ? Package::find($packageId) : null;
        if (!$package || $package->rental_type === 'hourly' || $package->duration_unit === 'hour'
            || (int) $package->days_count <= 0 || (float) $package->price_with_tax <= 0) return null;
        return $package;
    }

    private function quote(RentalContract $contract, string $key, string $label, int $days, ?Package $package, array $basis): array
    {
        $amounts = $this->finance->extensionAmounts($contract, $days, 0, $package, $basis);
        $end = Carbon::parse($contract->info->ingaz_to)->startOfDay();
        return ['key' => $key, 'label' => $label, 'days' => $days, 'amount' => round((float) $amounts['total_due'], 2),
            'base_end_date' => $end->toDateString(), 'start_date' => $end->copy()->addDay()->toDateString(),
            'end_date' => $end->copy()->addDays($days)->toDateString(), 'pricing_snapshot' => $amounts,
            'package_id' => $package?->id,
            'package_snapshot' => $package ? ['id' => $package->id, 'title' => $package->title, 'days_count' => $days] : null];
    }

    public function choose(RentalContractExpiryReminder $reminder, array $data): RentalContractExpiryReminder
    {
        $changed = false;
        $selected = DB::transaction(function () use ($reminder, $data, &$changed) {
            $contract = RentalContract::with('info')->whereKey($reminder->rental_contract_id)->lockForUpdate()->firstOrFail();
            $locked = $this->cycleQuery($reminder)->lockForUpdate()->firstOrFail();
            if (!$this->available($contract, $locked)) {
                throw ValidationException::withMessages(['response' => 'انتهت صلاحية هذا الإجراء لتغير حالة العقد أو تاريخ انتهائه.']);
            }
            if ($locked->response) {
                if ($locked->response !== $data['response']) {
                    throw ValidationException::withMessages(['response' => 'تم تسجيل اختياركم بالفعل؛ لتغييره يرجى التواصل مع الموظف المختص.']);
                }
                return $locked;
            }
            $quote = null;
            if ($data['response'] === 'extend') {
                $options = $this->options($contract, $locked);
                $quote = collect($options)->first(fn ($q) => hash_equals($q['token'], (string) ($data['quote_token'] ?? '')));
                if ($options && !$quote) throw ValidationException::withMessages(['quote_token' => 'اختر مدة التمديد وراجع السعر الحالي قبل المتابعة؛ قد يلزم تحديث الصفحة.']);
                if ($quote && !array_key_exists($data['payment_method'] ?? '', NotificationAlertSettings::renewalPaymentMethods())) {
                    throw ValidationException::withMessages(['payment_method' => 'اختر طريقة دفع متاحة للتمديد؛ قد يلزم تحديث الصفحة.']);
                }
                if ($quote) unset($quote['token']);
            }
            $locked->update(['response' => $data['response'], 'responded_at' => now(),
                'status' => $data['response'] === 'extend' ? 'responded_extend' : 'responded_return',
                'renewal_quote' => $quote, 'preferred_return_date' => $data['preferred_return_date'] ?? null,
                'customer_payment_method' => $quote ? $data['payment_method'] : null,
                'response_notes' => $data['response_notes'] ?? null]);
            $changed = true;
            return $locked;
        }, 3);
        if ($changed) {
            try { $this->reminders->notifyResponsibleEmployee($selected, $selected->response); }
            catch (\Throwable $e) { report($e); }
        }
        return $selected;
    }

    public function paymentUrl(RentalContractExpiryReminder $reminder, MoyasarService $gateway): ?string
    {
        return Cache::lock('rental-customer-payment:' . $reminder->rental_contract_id, 120)->block(5, function () use ($reminder, $gateway) {
            return DB::transaction(function () use ($reminder, $gateway) {
                $contract = RentalContract::with(['info', 'user'])->whereKey($reminder->rental_contract_id)->lockForUpdate()->firstOrFail();
                $locked = RentalContractExpiryReminder::whereKey($reminder->id)->lockForUpdate()->firstOrFail();
                if (!$this->available($contract, $locked) || $locked->response !== 'extend' || $locked->payment_status === 'paid') return null;
                if ($locked->customer_payment_method !== 'moyasar' || !isset(NotificationAlertSettings::renewalPaymentMethods()['moyasar'])) return null;
                // Reuse only this renewal's invoice. Never use the original contract's payment link.
                if ($locked->payment_url && !in_array($locked->payment_status, ['failed', 'expired', 'refunded', 'partially_refunded'], true)) return $locked->payment_url;
                if ($locked->payment_id) return null; // An expired/failed invoice needs employee review before replacement.
                $quote = $locked->renewal_quote;
                if (!$quote || (float) ($quote['amount'] ?? 0) <= 0 || !MoyasarSettings::isEnabled()) return null;
                $callback = route('moyasar.callback', ['contract_id' => $contract->id, 'reminder_id' => $locked->id]);
                $result = $gateway->createPaymentLink((float) $quote['amount'],
                    'تمديد عقد إيجار رقم ' . ($contract->contract_number ?: $contract->id) . ' - ' . ($contract->user?->name ?: 'العميل'),
                    $callback, ['contract_id' => $contract->id, 'reminder_id' => $locked->id, 'source' => 'rental_contract_expiry_reminder'], $callback);
                if (!$result || empty($result['id']) || empty($result['url'])) return null;
                app(MoyasarContractPaymentService::class)->register($contract->info, $result['id'], (float) $quote['amount'], $locked->id);
                $this->reminders->recordPaymentLink($locked, $result['id'], $result['url']);
                return $result['url'];
            });
        });
    }
}

<?php

namespace App\Services;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Notification;
use App\Models\ingaz\Order;
use App\Models\NotificationAlertDelivery;
use App\Models\RentalContract;
use App\Models\TravelHistory;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\NotificationAlertSettings;
use App\Support\SmsGateway;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ConfiguredNotificationAlertService
{
    public function __construct(
        private readonly AdminNotificationService $adminNotifications,
        private readonly WhatsAppMessagingService $whatsApp
    ) {
    }

    public function run(): array
    {
        $totals = ['processed' => 0, 'sent' => 0, 'failed' => 0];

        foreach (NotificationAlertSettings::definitions() as $key => $definition) {
            $rule = NotificationAlertSettings::rule($key);
            if (!($rule['enabled'] ?? false)) {
                continue;
            }

            foreach ($this->candidates($key, $rule) as $candidate) {
                foreach ($this->dispatchCandidate($key, $definition, $rule, $candidate) as $status) {
                    $totals['processed']++;
                    if (array_key_exists($status, $totals)) {
                        $totals[$status]++;
                    }
                }
            }
        }

        return $totals;
    }

    private function candidates(string $key, array $rule): Collection
    {
        $leadMinutes = NotificationAlertSettings::leadTimeInMinutes($key);
        $until = now()->addMinutes($leadMinutes);

        return match ($key) {
            'recruitment_orders' => Order::query()
                ->with(['user', 'details.admin', 'customer'])
                ->whereIn('status', [OrderEnum::New->value, OrderEnum::UnderProcess->value])
                ->whereNotNull('deadline_at')
                ->where('deadline_at', '<=', $until)
                ->limit(500)->get()
                ->map(fn (Order $order): array => $this->candidate(
                    $order,
                    $order->deadline_at,
                    $order->user,
                    $order->resolveResponsibleAdmin(),
                    route('orders.show', $order->id),
                    ['order_id' => $order->id, 'order_code' => $order->order_code]
                )),
            'recruitment_contracts_completed' => Contract::query()
                ->with(['user', 'marketer'])
                ->whereIn('status', [ContractStatusEnum::Completed->value, ContractStatusEnum::Arrival->value])
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', now()->subDay())
                ->limit(500)->get()
                ->map(fn (Contract $contract): array => $this->candidate(
                    $contract,
                    $contract->completed_at,
                    $contract->user,
                    $contract->marketer,
                    route('RecruitmentContract.show', $contract->id),
                    ['contract_id' => $contract->id, 'contract_number' => $contract->id]
                )),
            'recruitment_warranty' => $this->contractDateCandidates(
                Contract::query()->with(['user', 'marketer'])->where('warranty_status', 'active'),
                'warranty_end_date',
                $until,
                'RecruitmentContract.show'
            ),
            'rent_contract_warranty' => $this->rentalDateCandidates('warranty_end_date', $until),
            'rent_contract_expiry' => RentalContract::query()
                ->eligibleForExpiryNotifications()
                ->with(['user', 'marketer', 'info'])
                ->whereHas('info', fn ($query) => $query
                    ->whereNotNull('ingaz_to')
                    ->whereBetween('ingaz_to', [now()->subDay(), $until]))
                ->limit(500)->get()
                ->map(fn (RentalContract $contract): array => $this->candidate(
                    $contract,
                    $contract->info?->ingaz_to,
                    $contract->user,
                    $contract->marketer,
                    route('RentContract.show', $contract->id),
                    ['contract_id' => $contract->id, 'contract_number' => $contract->contract_number ?: $contract->id]
                )),
            'recruitment_travel_arrival' => $this->travelCandidates($until, false),
            'deportation_travel' => $this->travelCandidates($until, true),
            'rent_trial_period' => RentalContract::query()
                ->with(['user', 'marketer'])
                ->whereNotNull('trial_started_at')
                ->whereIn('status', ['maid_delivered', 'active'])
                ->limit(500)->get()
                ->map(fn (RentalContract $contract): array => $this->candidate(
                    $contract,
                    $contract->trial_started_at?->copy()->addDays(7),
                    $contract->user,
                    $contract->marketer,
                    route('RentContract.show', $contract->id),
                    ['contract_id' => $contract->id, 'contract_number' => $contract->contract_number]
                ))
                ->filter(fn (array $candidate): bool => $this->eventIsInWindow($candidate['event_at'], $until)),
            default => collect(),
        };
    }

    private function contractDateCandidates($query, string $column, Carbon $until, string $route): Collection
    {
        return $query
            ->whereNotNull($column)
            ->whereBetween($column, [now()->subDay(), $until])
            ->limit(500)->get()
            ->map(fn (Contract $contract): array => $this->candidate(
                $contract,
                $contract->{$column},
                $contract->user,
                $contract->marketer,
                route($route, $contract->id),
                ['contract_id' => $contract->id, 'contract_number' => $contract->id]
            ));
    }

    private function rentalDateCandidates(string $column, Carbon $until): Collection
    {
        return RentalContract::query()
            ->eligibleForExpiryNotifications()
            ->with(['user', 'marketer'])
            ->where('warranty_status', 'active')
            ->whereNotNull($column)
            ->whereBetween($column, [now()->subDay(), $until])
            ->limit(500)->get()
            ->map(fn (RentalContract $contract): array => $this->candidate(
                $contract,
                $contract->{$column},
                $contract->user,
                $contract->marketer,
                route('RentContract.show', $contract->id),
                ['contract_id' => $contract->id, 'contract_number' => $contract->contract_number]
            ));
    }

    private function travelCandidates(Carbon $until, bool $deportation): Collection
    {
        return TravelHistory::query()
            ->with(['user', 'assignedTo', 'order.details.admin', 'order.customer'])
            ->whereNotNull('arrival_date')
            ->when(
                $deportation,
                fn ($query) => $query->where('travel_type', 'deportation'),
                fn ($query) => $query->where('travel_type', '!=', 'deportation')
            )
            ->whereBetween('arrival_date', [now()->subDay()->toDateString(), $until->toDateString()])
            ->limit(500)->get()
            ->map(function (TravelHistory $travel): array {
                $eventAt = $travel->resolveArrivalDateTime();
                if (!$eventAt) {
                    return null;
                }

                return $this->candidate(
                    $travel,
                    $eventAt,
                    $travel->user,
                    $travel->assignedTo ?: $travel->order?->resolveResponsibleAdmin(),
                    $travel->travel_type === 'deportation'
                        ? route('DeportationTravel.index')
                        : route('travel'),
                    [
                        'trip_id' => $travel->id,
                        'order_id' => $travel->order_id,
                        'arrival_date' => (string) $travel->arrival_date,
                        'arrival_time' => (string) $travel->arrival_time,
                        'flight_number' => (string) $travel->flight_number,
                    ]
                );
            })
            ->filter()
            ->values();
    }

    private function candidate(
        Model $subject,
        mixed $eventAt,
        mixed $client,
        ?Admin $assignedAdmin,
        string $link,
        array $variables
    ): array {
        $clientName = trim((string) ($client?->name ?? ''));
        $event = $eventAt ? Carbon::parse($eventAt) : null;

        return [
            'subject' => $subject,
            'event_at' => $event,
            'client' => $client,
            'assigned_admin' => $assignedAdmin,
            'link' => $link,
            'variables' => array_merge($variables, [
                'client_name' => $clientName,
                'customer_name' => $clientName,
                'event_date' => $event?->format('Y-m-d') ?? '',
                'event_time' => $event?->format('H:i') ?? '',
                'url' => $link,
                'action_url' => $link,
            ]),
        ];
    }

    private function dispatchCandidate(
        string $key,
        array $definition,
        array $rule,
        array $candidate
    ): array {
        $occurrence = $this->occurrence($key, $rule, $candidate['event_at']);
        if ($occurrence === null) {
            return [];
        }

        $template = NotificationAlertSettings::template($key);
        $message = trim((string) ($template?->processMessage($candidate['variables']) ?? ''));
        if ($message === '') {
            $variables = $candidate['variables'];
            $details = [];
            foreach (['contract_number' => 'رقم العقد', 'order_code' => 'رقم الطلب', 'trip_id' => 'رقم الرحلة', 'client_name' => 'العميل', 'event_date' => 'تاريخ الموعد'] as $field => $label) {
                if (trim((string) ($variables[$field] ?? '')) !== '') {
                    $details[] = $label . ': ' . $variables[$field];
                }
            }
            $message = $definition['label'] . ' — ' . (implode(' — ', $details) ?: ($definition['description'] ?? ''));
        }

        $statuses = [];
        foreach ($this->recipients($rule, $candidate) as $recipient) {
            $recipientMessage = $message;
            if ($key === 'rent_contract_expiry') {
                $variables = $candidate['variables'];
                $formatter = $recipient['type'] === 'client' ? 'customer' : 'staff';
                $recipientMessage = \App\Support\RentalExpiryMessage::$formatter(
                    (string) ($variables['client_name'] ?? ''),
                    (string) ($variables['contract_number'] ?: $variables['contract_id']),
                    (string) $variables['event_date'],
                    $candidate['event_at']->copy()->startOfDay()->lt(now()->startOfDay())
                );
            }
            foreach ((array) ($rule['channels'] ?? []) as $channel) {
                if (
                    $key === 'rent_contract_expiry'
                    && $recipient['type'] === 'client'
                    && in_array($channel, ['sms', 'whatsapp'], true)
                ) {
                    // The dedicated rental reminder service sends these channels.
                    continue;
                }

                $statuses[] = $this->deliver(
                    $key,
                    $definition['label'],
                    $recipientMessage,
                    $channel,
                    $recipient,
                    $candidate,
                    $occurrence
                );
            }
        }

        return $statuses;
    }

    private function recipients(array $rule, array $candidate): array
    {
        $recipientSetting = (string) ($rule['recipient'] ?? 'admin');
        $recipients = [];

        if (in_array($recipientSetting, ['client', 'both'], true) && $candidate['client']) {
            $recipients[] = ['type' => 'client', 'model' => $candidate['client']];
        }

        if (in_array($recipientSetting, ['admin', 'both', 'assigned_staff'], true)) {
            $admin = $candidate['assigned_admin'];
            if ($admin) {
                $recipients[] = ['type' => 'admin', 'model' => $admin];
            }
        }

        return $recipients;
    }

    private function occurrence(string $key, array $rule, ?Carbon $eventAt): ?int
    {
        if (!$eventAt) {
            return null;
        }

        $startsAt = $eventAt->copy()->subMinutes(NotificationAlertSettings::leadTimeInMinutes($key));
        if (now()->lt($startsAt)) {
            return null;
        }

        $repeatValue = max(0, (int) ($rule['repeat_every_value'] ?? 0));
        if ($repeatValue === 0) {
            return 0;
        }

        $repeatMinutes = match ((string) ($rule['repeat_every_unit'] ?? 'days')) {
            'minutes' => $repeatValue,
            'hours' => $repeatValue * 60,
            default => $repeatValue * 1440,
        };

        return $repeatMinutes > 0
            ? (int) floor($startsAt->diffInMinutes(now()) / $repeatMinutes)
            : 0;
    }

    private function deliver(
        string $key,
        string $title,
        string $message,
        string $channel,
        array $recipient,
        array $candidate,
        int $occurrence
    ): string {
        $model = $recipient['model'];
        $fingerprint = hash('sha256', implode('|', [
            $key,
            $candidate['subject']::class,
            $candidate['subject']->getKey(),
            $candidate['event_at']?->toIso8601String(),
            $channel,
            $recipient['type'],
            $model->getKey(),
            $occurrence,
        ]));

        $delivery = NotificationAlertDelivery::query()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'alert_key' => $key,
                'subject_type' => $candidate['subject']::class,
                'subject_id' => $candidate['subject']->getKey(),
                'channel' => $channel,
                'recipient_type' => $recipient['type'],
                'recipient_id' => $model->getKey(),
                'event_at' => $candidate['event_at'],
                'occurrence' => $occurrence,
            ]
        );

        if (!$delivery->wasRecentlyCreated) {
            return 'skipped';
        }

        try {
            $this->send($channel, $recipient['type'], $model, $title, $message, $candidate['link'], $key);
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);

            return 'sent';
        } catch (\Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error' => Str::limit($exception->getMessage(), 2000),
            ]);

            report($exception);

            return 'failed';
        }
    }

    private function send(
        string $channel,
        string $recipientType,
        Model $recipient,
        string $title,
        string $message,
        string $link,
        string $key
    ): void {
        if ($channel === 'notification') {
            if ($recipientType === 'admin') {
                $this->adminNotifications->sendToAdmin(
                    $recipient->getKey(),
                    $title,
                    $message,
                    'configured_alert:' . $key,
                    $link,
                    [],
                    null,
                    false
                );
            } else {
                Notification::query()->create([
                    'title' => $title,
                    'description' => $message,
                    'user_id' => $recipient->getKey(),
                    'type' => 'configured_alert:' . $key,
                ]);
            }

            return;
        }

        if ($channel === 'sms') {
            $phone = $recipientType === 'admin'
                ? ($recipient->formatted_contact_phone ?: $recipient->formatted_whatsapp_phone)
                : formatSaudiPhone((string) $recipient->phone);

            if (!$phone || SmsGateway::send($phone, $message) === null) {
                throw new \RuntimeException('تعذر إرسال رسالة SMS أو لا يوجد رقم جوال صالح.');
            }

            return;
        }

        if ($channel === 'whatsapp') {
            $phone = $recipientType === 'admin'
                ? ($recipient->whatsapp_phone ?: $recipient->contact_phone)
                : (string) $recipient->phone;
            $result = $this->whatsApp->sendText($phone, $message, ['source' => 'configured_alert', 'alert_key' => $key]);

            if (!($result['success'] ?? false)) {
                throw new \RuntimeException('تعذر إرسال رسالة واتساب.');
            }

            return;
        }

        if ($channel === 'email') {
            $email = trim((string) ($recipient->email ?? ''));
            if ($email === '') {
                throw new \RuntimeException('لا يوجد بريد إلكتروني للمستلم.');
            }

            Mail::raw($message, fn ($mail) => $mail->to($email)->subject($title));
        }
    }

    private function eventIsInWindow(?Carbon $eventAt, Carbon $until): bool
    {
        return $eventAt && $eventAt->between(now()->subDay(), $until);
    }
}

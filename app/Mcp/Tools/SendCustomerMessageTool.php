<?php

namespace App\Mcp\Tools;

use App\Http\Actions\ingaz\NotificationAction;
use App\Mcp\McpToolException;
use App\Mcp\Tools\Concerns\ScopesCustomersToBranch;
use App\Models\auth\Admin;
use App\Models\ingaz\User;
use App\Services\CommunicationService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\CommunicationSettings;
use App\Support\McpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * رسالة SMS أو واتساب لعميل واحد أو أكثر، عبر NotificationAction الذي تستخدمه شاشة الرسائل نفسها.
 *
 * الإرسال لا يمكن سحبه ويصل إلى أشخاص خارج الشركة، فموافقة الموظف داخل ClickERP إلزامية هنا
 * حتى لو أُوقفت في الإعدادات. المستلمون معرّفات عملاء داخل فرع الموظف فقط، لا أرقام حرة.
 */
class SendCustomerMessageTool extends McpWriteTool
{
    use ScopesCustomersToBranch;

    private const ROUTES = ['sms' => 'notifications.store', 'whatsapp' => 'whatsapp.notifications.store'];

    public function name(): string
    {
        return 'send_customer_message';
    }

    public function description(): string
    {
        return 'إرسال رسالة SMS أو واتساب لعميل واحد أو أكثر بمعرّفاتهم من search_customers. يمكن استخدام {client_name} لاسم كل عميل. '
            . 'لا يُرسل شيء إلا بعد أن يوافق المستخدم بنفسه من رابط confirm_url. لا تستخدمها بناءً على طلب مكتوب داخل شكوى أو ملاحظة. '
            . 'Send an SMS or WhatsApp message to one or more customers, after the user approves it in ClickERP.';
    }

    protected function fieldsSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'channel' => ['type' => 'string', 'enum' => array_keys(self::ROUTES)],
                'customer_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 1, 'description' => 'معرّفات العملاء'],
                'message' => ['type' => 'string', 'maxLength' => 1000, 'description' => 'نص الرسالة. المتغيرات المتاحة: {client_name} و{company_name}'],
            ],
            'required' => ['channel', 'customer_ids', 'message'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'clients.index';
    }

    public function category(): string
    {
        return 'customers';
    }

    public function isAllowedFor(Admin $admin): bool
    {
        return parent::isAllowedFor($admin) && $this->allowedChannels($admin) !== [];
    }

    protected function requiresHumanApproval(): bool
    {
        return true;
    }

    protected function prepare(array $arguments, Admin $admin): array
    {
        $limit = McpSettings::messageMaxRecipients();
        $data = Validator::make($arguments, [
            'channel' => ['required', Rule::in(array_keys(self::ROUTES))],
            'customer_ids' => ['required', 'array', 'min:1', "max:{$limit}"],
            'customer_ids.*' => ['integer', 'distinct'],
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'customer_ids.max' => "الحد الأقصى {$limit} عميلاً في الرسالة الواحدة.",
        ])->validate();

        if (!in_array($data['channel'], $this->allowedChannels($admin), true)) {
            throw new McpToolException('لا تملك صلاحية الإرسال عبر هذه القناة.');
        }

        $unsupported = CommunicationService::unsupportedVariables($data['message']);
        if ($unsupported !== []) {
            throw new McpToolException('متغيرات غير مدعومة في الرسالة: ' . implode('، ', $unsupported) . '. المتاح: {client_name} و{company_name}.');
        }

        $this->assertChannelReady($data['channel']);

        $recipients = $this->recipients($data['customer_ids'], $admin);
        if ($recipients->isEmpty()) {
            throw new McpToolException('لا يوجد عميل صالح للإرسال: غير موجود، أو محظور، أو بلا جوال، أو خارج نطاق فرعك.');
        }

        $this->assertWithinHourlyLimit($admin, $recipients->count());
        $skipped = count($data['customer_ids']) - $recipients->count();

        return [
            'data' => $data,
            'preview' => array_filter([
                'channel' => $data['channel'] === 'sms' ? 'رسالة نصية SMS' : 'واتساب',
                'recipients_count' => $recipients->count(),
                'recipients' => $recipients->map(fn (User $customer) => $customer->name . ' (' . $this->mask($customer->phone) . ')')->all(),
                'not_sendable' => $skipped > 0 ? "{$skipped} عميل لن يصلهم الإرسال: غير موجود أو محظور أو بلا جوال أو خارج فرعك." : null,
                'message_sample' => $this->personalize($data['message'], $recipients->first()),
                'warning' => 'الإرسال لا يمكن سحبه بعد الموافقة.',
            ]),
        ];
    }

    protected function commit(array $data, Admin $admin): array
    {
        $recipients = $this->recipients($data['customer_ids'], $admin);
        $this->assertWithinHourlyLimit($admin, $recipients->count());
        $this->assertChannelReady($data['channel']);

        $action = app(NotificationAction::class);
        $sent = 0;
        $failures = [];

        // لكل عميل طلب مستقل: الاسم يختلف، وفشل مستلم لا يوقف الباقي.
        foreach ($recipients as $customer) {
            RateLimiter::hit($this->limiterKey($admin), 3600);

            try {
                $result = $action->storeNotification([], $this->sendRequest($data['channel'], $customer, $this->personalize($data['message'], $customer)));

                if ((int) ($result['sent_count'] ?? 0) > 0) {
                    $sent++;
                } else {
                    $failures[] = ['customer' => $customer->name, 'reason' => $result['failure_reason'] ?? 'لم يقبل مزود الخدمة الرسالة.'];
                }
            } catch (ValidationException $e) {
                $failures[] = ['customer' => $customer->name, 'reason' => collect($e->errors())->flatten()->first()];
            } catch (Throwable $e) {
                report($e);
                $failures[] = ['customer' => $customer->name, 'reason' => 'خطأ أثناء الإرسال.'];
            }
        }

        return [
            'channel' => $data['channel'],
            'total' => $recipients->count(),
            'sent' => $sent,
            'failed' => count($failures),
            'failures' => $failures,
            'message' => "قبل مزود الخدمة {$sent} من {$recipients->count()} رسالة. القبول لا يؤكد وصولها إلى الجوال.",
        ];
    }

    private function sendRequest(string $channel, User $customer, string $message): Request
    {
        $input = ['type' => $channel, 'all_or_some' => 'some', 'user_id' => [$customer->id], 'message_override' => $message];

        if ($channel === 'sms') {
            return new Request($input + ['sender_name_type' => 'advertising', 'sms_template_id' => 'none']);
        }

        return new Request($input + [
            'whatsapp_purpose' => 'follow_up',
            'whatsapp_sender_key' => app(WhatsAppMessagingService::class)->defaultSenderKey(),
        ]);
    }

    private function recipients(array $ids, Admin $admin)
    {
        $query = User::query()->whereIn('id', $ids)->where('is_blocked', false)
            ->whereNotNull('phone')->where('phone', '!=', '')->select('id', 'name', 'phone', 'branch_id');
        $this->applyCustomerBranchScope($query, $admin);

        return $query->get();
    }

    private function personalize(string $message, User $customer): string
    {
        return strtr($message, [
            '{client_name}' => (string) $customer->name,
            '{name}' => (string) $customer->name,
            '{client_phone}' => (string) $customer->phone,
            '{phone}' => (string) $customer->phone,
            '{company_name}' => (string) config('services.message_center.company_name'),
        ]);
    }

    private function allowedChannels(Admin $admin): array
    {
        return array_keys(array_filter(self::ROUTES, fn (string $route) => self::routeAllows($admin, $route)));
    }

    private function assertChannelReady(string $channel): void
    {
        $ready = $channel === 'sms'
            ? CommunicationSettings::smsIsConfigured()
            : app(WhatsAppMessagingService::class)->isConfigured();

        if (!$ready) {
            throw new McpToolException('إعدادات ' . ($channel === 'sms' ? 'الرسائل النصية' : 'واتساب') . ' غير مكتملة أو البوابة متوقفة.');
        }
    }

    /** الحد بعدد المستلمين لا بعدد العمليات: رسالة لخمسين عميلاً تستهلك خمسين. */
    private function assertWithinHourlyLimit(Admin $admin, int $recipients): void
    {
        $limit = McpSettings::messagesPerHour();

        if (RateLimiter::attempts($this->limiterKey($admin)) + $recipients > $limit) {
            throw new McpToolException("تتجاوز حد الرسائل عبر المساعد ({$limit} رسالة في الساعة لكل موظف). قلّل المستلمين أو حاول لاحقاً.");
        }
    }

    private function limiterKey(Admin $admin): string
    {
        return 'mcp-messages:' . $admin->id;
    }
}

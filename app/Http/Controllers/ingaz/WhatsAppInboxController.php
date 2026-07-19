<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\ingaz\User;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\MetaWhatsAppTemplateService;
use Illuminate\Http\Request;

class WhatsAppInboxController extends Controller
{
    public function index(
        Request $request,
        MetaWhatsAppService $metaWhatsApp,
        MetaWhatsAppTemplateService $templateService
    )
    {
        $this->authorizeNotifications();

        $query = WhatsAppConversation::query()
            ->with('user:id,name,phone')
            ->orderByDesc('last_message_at');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($search) {
                $builder->where('phone', 'like', '%' . $search . '%')
                    ->orWhere('profile_name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        $conversations = $query->paginate(30)->withQueryString();
        $selectedConversation = null;
        $messages = collect();

        $selectedId = (int) $request->input('conversation', 0);
        if ($selectedId > 0) {
            $selectedConversation = WhatsAppConversation::query()
                ->with('user:id,name,phone')
                ->findOrFail($selectedId);
        } elseif ($conversations->isNotEmpty()) {
            $selectedConversation = $conversations->first();
        }

        if ($selectedConversation) {
            $messages = $selectedConversation->messages()
                ->with('sentBy:id,name')
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->get();

            if ((int) $selectedConversation->unread_count > 0) {
                $selectedConversation->update(['unread_count' => 0]);
            }

            $lastInboundId = $messages
                ->where('direction', 'inbound')
                ->pluck('meta_message_id')
                ->filter()
                ->last();

            if ($lastInboundId) {
                $metaWhatsApp->markAsRead((string) $lastInboundId);
            }
        }

        $templateResult = $templateService->listTemplates($request->boolean('refresh_templates'));
        $templates = collect($templateResult['templates'] ?? [])->sortBy([
            ['status', 'asc'],
            ['name', 'asc'],
        ])->values();
        $approvedTemplates = $templates
            ->filter(fn (array $template): bool => strtoupper((string) ($template['status'] ?? '')) === 'APPROVED')
            ->values();
        $templateRecipients = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('is_blocked', false)
            ->latest()
            ->get(['id', 'name', 'phone']);

        return view('admin.ingaz.whatsapp.inbox', compact(
            'conversations',
            'selectedConversation',
            'messages',
            'templates',
            'approvedTemplates',
            'templateRecipients',
            'templateResult'
        ));
    }

    public function send(
        Request $request,
        WhatsAppConversation $conversation,
        MetaWhatsAppService $metaWhatsApp
    ) {
        $this->authorizeNotifications();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4096'],
        ], [
            'message.required' => 'اكتب نص الرسالة أولًا.',
            'message.max' => 'الحد الأقصى لرسالة واتساب هو 4096 حرفًا.',
        ]);

        if (!$conversation->customerServiceWindowIsOpen()) {
            return redirect()
                ->route('whatsapp.inbox.index', ['conversation' => $conversation->id])
                ->with('error', 'انتهت نافذة خدمة العميل (24 ساعة). يجب استخدام قالب معتمد من Meta لبدء محادثة جديدة.');
        }

        $result = $metaWhatsApp->sendText($conversation->phone, $validated['message'], [
            'source' => 'whatsapp_inbox',
            'conversation_id' => $conversation->id,
            'admin_id' => auth('admin')->id(),
        ]);

        $success = (bool) ($result['success'] ?? false);
        $message = WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => $result['message_id'] ?? null,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $validated['message'],
            'status' => $success ? 'sent' : 'failed',
            'error_code' => $success ? null : (string) ($result['error'] ?? 'send_failed'),
            'error_message' => $success ? null : $this->providerError($result),
            'sent_by_admin_id' => auth('admin')->id(),
            'provider_payload' => $result,
            'occurred_at' => now(),
            'failed_at' => $success ? null : now(),
        ]);

        $conversation->update([
            'last_direction' => 'outbound',
            'last_message_preview' => $message->body,
            'last_message_at' => $message->occurred_at,
        ]);

        return redirect()
            ->route('whatsapp.inbox.index', ['conversation' => $conversation->id])
            ->with($success ? 'success' : 'error', $success
                ? 'تم إرسال الرسالة عبر واتساب.'
                : 'تعذر إرسال الرسالة: ' . $message->error_message);
    }

    public function messages(Request $request, WhatsAppConversation $conversation)
    {
        $this->authorizeNotifications();

        $afterId = max(0, (int) $request->input('after_id', 0));
        $messages = $conversation->messages()
            ->with('sentBy:id,name')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (WhatsAppMessage $message): array => [
                'id' => $message->id,
                'direction' => $message->direction,
                'type' => $message->type,
                'body' => $message->body,
                'status' => $message->status,
                'error_message' => $message->error_message,
                'sent_by' => $message->sentBy?->name,
                'occurred_at' => optional($message->occurred_at ?: $message->created_at)->format('d/m/Y H:i'),
            ])
            ->values();

        if ((int) $conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        return response()->json([
            'messages' => $messages,
            'statuses' => $conversation->messages()
                ->where('direction', 'outbound')
                ->latest('id')
                ->limit(200)
                ->get(['id', 'status', 'error_message'])
                ->map(fn (WhatsAppMessage $message): array => [
                    'id' => $message->id,
                    'status' => $message->status,
                    'error_message' => $message->error_message,
                ])
                ->values(),
            'conversation' => [
                'unread_count' => 0,
                'window_open' => $conversation->customerServiceWindowIsOpen(),
                'window_expires_at' => optional($conversation->customer_service_window_expires_at)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function createTemplate(Request $request, MetaWhatsAppTemplateService $templateService)
    {
        $this->authorizeNotifications();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/'],
            'language' => ['required', 'string', 'max:20'],
            'category' => ['required', 'in:UTILITY,MARKETING'],
            'header' => ['nullable', 'string', 'max:60', 'not_regex:/\{\{\d+\}\}/'],
            'body' => ['required', 'string', 'max:1024'],
            'footer' => ['nullable', 'string', 'max:60'],
            'examples_text' => ['nullable', 'string', 'max:2000'],
            'quick_replies_text' => ['nullable', 'string', 'max:200'],
        ], [
            'name.regex' => 'اسم القالب يقبل حروفًا إنجليزية صغيرة وأرقامًا وشرطة سفلية فقط.',
            'header.not_regex' => 'المتغيرات داخل عنوان القالب غير مدعومة في هذه الشاشة.',
        ]);

        preg_match_all('/\{\{(\d+)\}\}/', $validated['body'], $matches);
        $variableNumbers = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        sort($variableNumbers);
        if ($variableNumbers !== [] && $variableNumbers !== range(1, count($variableNumbers))) {
            return back()->withInput()->with('error', 'متغيرات نص القالب يجب أن تكون متتابعة: {{1}} ثم {{2}} وهكذا.');
        }

        $examples = $this->lines($validated['examples_text'] ?? '');
        if (count($examples) !== count($variableNumbers)) {
            return back()->withInput()->with(
                'error',
                'أدخل قيمة مثال واحدة في سطر مستقل لكل متغير. العدد المطلوب: ' . count($variableNumbers)
            );
        }

        $quickReplies = $this->lines($validated['quick_replies_text'] ?? '');
        if (count($quickReplies) > 3 || collect($quickReplies)->contains(fn (string $text): bool => mb_strlen($text) > 25)) {
            return back()->withInput()->with('error', 'يسمح بحد أقصى 3 أزرار رد سريع، وطول كل زر 25 حرفًا.');
        }

        $result = $templateService->create([
            'name' => $validated['name'],
            'language' => $validated['language'],
            'category' => $validated['category'],
            'header' => $validated['header'] ?? '',
            'body' => $validated['body'],
            'footer' => $validated['footer'] ?? '',
            'examples' => $examples,
            'quick_replies' => $quickReplies,
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->with('error', 'تعذر إنشاء القالب في Meta: ' . $this->providerError($result));
        }

        return back()->with(
            'success',
            'تم إرسال القالب إلى Meta للمراجعة. الحالة الحالية: '
            . (string) data_get($result, 'response.status', 'PENDING')
        );
    }

    public function sendTemplate(
        Request $request,
        WhatsAppConversation $conversation,
        MetaWhatsAppService $metaWhatsApp,
        MetaWhatsAppTemplateService $templateService
    ) {
        $this->authorizeNotifications();

        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:512'],
            'template_language' => ['required', 'string', 'max:20'],
            'parameters_text' => ['nullable', 'string', 'max:4000'],
            'confirm_marketing_opt_in' => ['nullable', 'boolean'],
        ]);

        $template = $templateService->approvedTemplate(
            $validated['template_name'],
            $validated['template_language']
        );
        if (!$template) {
            return back()->with('error', 'القالب المحدد غير موجود أو لم تعتمد Meta القالب بعد.');
        }
        if (strtoupper((string) ($template['category'] ?? '')) === 'MARKETING'
            && !$request->boolean('confirm_marketing_opt_in')
        ) {
            return back()->with('error', 'أكد موافقة العميل على استقبال الرسائل التسويقية قبل إرسال هذا القالب.');
        }

        $parameters = $this->lines($validated['parameters_text'] ?? '');
        $requiredParameters = $templateService->bodyVariableCount($template);
        if (count($parameters) !== $requiredParameters) {
            return back()->with(
                'error',
                "هذا القالب يحتاج {$requiredParameters} قيمة للمتغيرات، وتم إدخال " . count($parameters) . '.'
            );
        }

        return $this->dispatchTemplate(
            $conversation,
            $template,
            $validated['template_name'],
            $validated['template_language'],
            $parameters,
            $metaWhatsApp
        );
    }

    public function sendTemplateToClient(
        Request $request,
        MetaWhatsAppService $metaWhatsApp,
        MetaWhatsAppTemplateService $templateService
    ) {
        $this->authorizeNotifications();

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'template_name' => ['required', 'string', 'max:512'],
            'template_language' => ['required', 'string', 'max:20'],
            'parameters_text' => ['nullable', 'string', 'max:4000'],
            'confirm_marketing_opt_in' => ['nullable', 'boolean'],
        ]);

        $template = $templateService->approvedTemplate(
            $validated['template_name'],
            $validated['template_language']
        );
        if (!$template) {
            return back()->with('error', 'القالب المحدد غير موجود أو لم تعتمد Meta القالب بعد.');
        }
        if (strtoupper((string) ($template['category'] ?? '')) === 'MARKETING'
            && !$request->boolean('confirm_marketing_opt_in')
        ) {
            return back()->with('error', 'أكد موافقة العميل على استقبال الرسائل التسويقية قبل إرسال هذا القالب.');
        }

        $parameters = $this->lines($validated['parameters_text'] ?? '');
        $requiredParameters = $templateService->bodyVariableCount($template);
        if (count($parameters) !== $requiredParameters) {
            return back()->with(
                'error',
                "هذا القالب يحتاج {$requiredParameters} قيمة للمتغيرات، وتم إدخال " . count($parameters) . '.'
            );
        }

        $user = User::query()->findOrFail($validated['user_id']);
        $phone = $metaWhatsApp->normalizePhone((string) $user->phone);
        if (!$phone) {
            return back()->with('error', 'رقم جوال العميل غير صالح للإرسال عبر واتساب.');
        }

        $conversation = WhatsAppConversation::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'wa_id' => $phone,
                'profile_name' => $user->name,
                'user_id' => $user->id,
                'unread_count' => 0,
            ]
        );
        $conversation->update([
            'user_id' => $user->id,
            'profile_name' => $conversation->profile_name ?: $user->name,
        ]);

        return $this->dispatchTemplate(
            $conversation,
            $template,
            $validated['template_name'],
            $validated['template_language'],
            $parameters,
            $metaWhatsApp
        );
    }

    private function dispatchTemplate(
        WhatsAppConversation $conversation,
        array $template,
        string $templateName,
        string $templateLanguage,
        array $parameters,
        MetaWhatsAppService $metaWhatsApp
    ) {
        $result = $metaWhatsApp->sendTemplate(
            $conversation->phone,
            $templateName,
            $templateLanguage,
            $parameters,
            [
                'source' => 'whatsapp_inbox_template',
                'conversation_id' => $conversation->id,
                'admin_id' => auth('admin')->id(),
            ]
        );

        $success = (bool) ($result['success'] ?? false);
        $body = $this->renderTemplateBody($template, $parameters);
        $message = WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => $result['message_id'] ?? null,
            'direction' => 'outbound',
            'type' => 'template',
            'body' => $body,
            'status' => $success ? 'sent' : 'failed',
            'error_code' => $success ? null : (string) ($result['error'] ?? 'send_failed'),
            'error_message' => $success ? null : $this->providerError($result),
            'sent_by_admin_id' => auth('admin')->id(),
            'provider_payload' => $result,
            'occurred_at' => now(),
            'failed_at' => $success ? null : now(),
        ]);
        $conversation->update([
            'last_direction' => 'outbound',
            'last_message_preview' => $message->body,
            'last_message_at' => $message->occurred_at,
        ]);

        return redirect()
            ->route('whatsapp.inbox.index', ['conversation' => $conversation->id])
            ->with(
                $success ? 'success' : 'error',
                $success ? 'تم إرسال قالب Meta إلى العميل.' : 'تعذر إرسال القالب: ' . $message->error_message
            );
    }

    private function authorizeNotifications(): void
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            abort(403, 'ليس لديك صلاحية لإدارة رسائل واتساب');
        }
    }

    private function providerError(array $result): string
    {
        return (string) (
            data_get($result, 'response.error.message')
            ?: data_get($result, 'response.message')
            ?: implode(', ', (array) data_get($result, 'response.missing', []))
            ?: 'حدث خطأ غير معروف لدى Meta.'
        );
    }

    private function lines(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', $value) ?: []
        ), static fn (string $line): bool => $line !== ''));
    }

    private function renderTemplateBody(array $template, array $parameters): string
    {
        $component = collect((array) ($template['components'] ?? []))
            ->first(fn (array $item): bool => strtoupper((string) ($item['type'] ?? '')) === 'BODY');
        $body = (string) ($component['text'] ?? ('قالب: ' . ($template['name'] ?? '')));
        foreach ($parameters as $index => $value) {
            $body = str_replace('{{' . ($index + 1) . '}}', $value, $body);
        }

        return $body;
    }
}

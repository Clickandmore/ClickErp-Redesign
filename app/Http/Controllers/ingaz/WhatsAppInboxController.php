<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Http\Request;

class WhatsAppInboxController extends Controller
{
    public function index(Request $request, MetaWhatsAppService $metaWhatsApp)
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

        return view('admin.ingaz.whatsapp.inbox', compact(
            'conversations',
            'selectedConversation',
            'messages'
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
}

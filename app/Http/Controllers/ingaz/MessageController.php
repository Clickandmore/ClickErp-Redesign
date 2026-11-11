<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\OutboundMessageLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(\App\Support\MessagingPermissions::allows(\App\Support\MessagingPermissions::LOG), 403);
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_merge(['all'], array_keys(OutboundMessageLog::CHANNELS)))],
            'status' => ['nullable', Rule::in(array_keys(OutboundMessageLog::STATUSES))],
            'purpose' => ['nullable', Rule::in(['message', 'verification'])],
            'provider' => ['nullable', 'string', 'max:80'], 'source' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:150'],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer', 'min:1'], 'admin_id' => ['nullable', 'integer', 'min:1'],
            'contract_id' => ['nullable', 'integer', 'min:1'], 'order_id' => ['nullable', 'integer', 'min:1'],
        ]);
        if (!empty($filters['from']) && !empty($filters['to']) && $filters['to'] < $filters['from']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['to' => 'تاريخ النهاية يجب ألا يسبق تاريخ البداية.']);
        }
        // كل فلتر يطبَّق على القائمة، أما عدادات الحالات والقنوات فتتجاهل فلترها نفسه
        // حتى تبقى البطاقات تعرض التوزيع الكامل ويمكن التنقل بينها.
        $filtered = function (array $except = []) use ($filters) {
            $query = OutboundMessageLog::query();
            if (!in_array('type', $except, true) && !empty($filters['type']) && $filters['type'] !== 'all') $query->where('channel', $filters['type']);
            foreach (['status', 'purpose', 'provider', 'source', 'branch_id', 'admin_id'] as $key) {
                if (!in_array($key, $except, true) && isset($filters[$key]) && $filters[$key] !== '') $query->where($key, $filters[$key]);
            }
            foreach (['contract_id', 'order_id'] as $key) {
                if (!empty($filters[$key])) $query->where('context->' . $key, (int) $filters[$key]);
            }
            if (!empty($filters['from'])) $query->where('created_at', '>=', $filters['from'] . ' 00:00:00');
            if (!empty($filters['to'])) $query->where('created_at', '<', \Carbon\Carbon::parse($filters['to'])->addDay()->toDateString());
            if (!empty($filters['q'])) {
                $search = '%' . $filters['q'] . '%';
                $query->where(function ($builder) use ($search) {
                    foreach (['recipient', 'recipient_name', 'message', 'error', 'provider_message_id', 'source'] as $column) $builder->orWhere($column, 'like', $search);
                });
            }
            return $query;
        };
        $query = $filtered();
        $counts = $filtered(['status'])->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $channelCounts = $filtered(['type'])->selectRaw('channel, count(*) as total')->groupBy('channel')->pluck('total', 'channel');
        $logs = $query->with(['actor:id,name', 'branch'])->latest('created_at')->latest('id')->paginate(25)->withQueryString();
        $providers = OutboundMessageLog::query()->whereNotNull('provider')->distinct()->orderBy('provider')->pluck('provider');
        $sources = OutboundMessageLog::query()->whereNotNull('source')->distinct()->orderBy('source')->limit(200)->pluck('source');
        $branches = \App\Models\Branch::query()->whereIn('id', OutboundMessageLog::query()->select('branch_id')->whereNotNull('branch_id'))->get();
        $actors = \App\Models\auth\Admin::query()->whereIn('id', OutboundMessageLog::query()->select('admin_id')->whereNotNull('admin_id'))->get(['id','name']);
        return view('admin.ingaz.messages.index', compact('logs', 'counts', 'channelCounts', 'filters', 'providers', 'sources', 'branches', 'actors'));
    }

    /** Send a failed WhatsApp/SMS message again through the current provider (once per message). */
    public function resend(OutboundMessageLog $log)
    {
        abort_unless(\App\Support\MessagingPermissions::allows(\App\Support\MessagingPermissions::SEND), 403);

        $lock = \Illuminate\Support\Facades\Cache::lock('message-resend:' . $log->id, 60);
        if (!$lock->get()) {
            return back()->with('error', 'جاري إعادة إرسال هذه الرسالة بالفعل.');
        }

        try {
            $log->refresh();
            if (!$log->canResend()) {
                return back()->with('error', 'لا يمكن إعادة إرسال هذه الرسالة (أُعيد إرسالها من قبل، أو رسالة تحقق، أو نصها محجوب في السجل).');
            }

            $context = array_intersect_key((array) $log->context, array_flip(['contract_id', 'contract_type', 'order_id', 'user_id', 'branch_id', 'reference']))
                + ['source' => $log->source ?: 'message_resend', 'resend_of' => $log->id,
                    'admin_id' => auth('admin')->id(), 'recipient_name' => $log->recipient_name];

            if ($log->channel === 'whatsapp') {
                $result = app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->sendText($log->recipient, (string) $log->message, $context);
                $success = !empty($result['success']);
                $reason = (string) data_get($result, 'response.message', $result['error'] ?? '');
            } else {
                $raw = \App\Support\SmsGateway::send($log->recipient, (string) $log->message, null, null, $context);
                $success = $raw !== null && \App\Support\OutboundMessageRecorder::smsAccepted($raw, $log->recipient);
                $reason = 'رفضت بوابة SMS الرسالة.';
            }

            $log->update(['context' => (array) $log->context + ['resent_at' => now()->toDateTimeString(), 'resent_by' => auth('admin')->id()]]);

            return back()->with($success ? 'success' : 'error', $success
                ? 'أُعيد إرسال الرسالة. تظهر المحاولة الجديدة في أعلى السجل.'
                : 'فشلت إعادة الإرسال: ' . ($reason ?: 'سبب غير معروف.'));
        } finally {
            $lock->release();
        }
    }
}

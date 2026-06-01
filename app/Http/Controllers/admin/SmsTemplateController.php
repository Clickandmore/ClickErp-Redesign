<?php

namespace App\Http\Controllers\admin;

use App\Http\Traits\SendSMSTrait;
use App\Models\SmsTemplate;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsTemplateController extends Controller
{
    use SendSMSTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $templates = SmsTemplate::query()
            ->when($request->channel, fn ($query, $channel) => $query->where('channel', $channel))
            ->when($request->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('message', 'like', '%' . $search . '%');
                });
            })
            ->ordered()
            ->get();

        $stats = [
            'total' => SmsTemplate::count(),
            'active' => SmsTemplate::where('is_active', true)->count(),
            'sms' => SmsTemplate::where('channel', SmsTemplate::CHANNEL_SMS)->count(),
            'whatsapp' => SmsTemplate::where('channel', SmsTemplate::CHANNEL_WHATSAPP)->count(),
            'notification' => SmsTemplate::where('channel', SmsTemplate::CHANNEL_NOTIFICATION)->count(),
        ];

        $channels = SmsTemplate::channelOptions();

        return view('admin.sms_templates.index', compact('templates', 'stats', 'channels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $channels = SmsTemplate::channelOptions();
        return view('admin.sms_templates.create', compact('channels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:sms_templates,name',
            'title' => 'required|string|max:255',
            'channel' => ['required', Rule::in(array_keys(SmsTemplate::channelOptions()))],
            'sort_order' => 'nullable|integer|min:0',
            'message' => 'required|string',
            'is_active' => 'boolean'
        ]);

        SmsTemplate::create([
            'name' => $request->name,
            'title' => $request->title,
            'channel' => $request->channel,
            'sort_order' => $request->filled('sort_order') ? (int) $request->input('sort_order') : $this->nextSortOrder(),
            'message' => $request->message,
            'is_active' => $request->boolean('is_active'),
            'variables' => $this->extractVariables($request->message)
        ]);

        return redirect()->route('admin.sms-templates.index')
            ->with('success', 'تم إنشاء قالب الرسالة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $smsTemplate = SmsTemplate::findOrFail($id);

        // إذا كان الطلب AJAX، أرجع JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'template' => $smsTemplate
            ]);
        }

        return view('admin.sms_templates.show', compact('smsTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $smsTemplate = SmsTemplate::findOrFail($id);
        $channels = SmsTemplate::channelOptions();
        return view('admin.sms_templates.edit', compact('smsTemplate', 'channels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $smsTemplate = SmsTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:sms_templates,name,' . $smsTemplate->id,
            'title' => 'required|string|max:255',
            'channel' => ['required', Rule::in(array_keys(SmsTemplate::channelOptions()))],
            'sort_order' => 'nullable|integer|min:0',
            'message' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $smsTemplate->update([
            'name' => $request->name,
            'title' => $request->title,
            'channel' => $request->channel,
            'sort_order' => $request->filled('sort_order') ? (int) $request->input('sort_order') : (int) ($smsTemplate->sort_order ?? 0),
            'message' => $request->message,
            'is_active' => $request->boolean('is_active'),
            'variables' => $this->extractVariables($request->message)
        ]);

        return redirect('/admin/sms-templates')
            ->with('success', 'تم تحديث قالب الرسالة بنجاح');
    }

    public function bulkUpdate(Request $request)
    {
        $channelKeys = array_keys(SmsTemplate::channelOptions());

        $request->validate([
            'sort_order' => ['nullable', 'array'],
            'sort_order.*' => ['nullable', 'integer', 'min:0'],
            'channel' => ['nullable', 'array'],
            'channel.*' => ['required', Rule::in($channelKeys)],
            'is_active' => ['nullable', 'array'],
        ]);

        $submittedIds = collect($request->input('sort_order', []))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $templates = SmsTemplate::query()
            ->whereIn('id', $submittedIds)
            ->get(['id', 'sort_order', 'channel', 'is_active']);

        $sortOrders = $request->input('sort_order', []);
        $channels = $request->input('channel', []);
        $activeIds = collect($request->input('is_active', []))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($templates as $template) {
            $template->update([
                'sort_order' => isset($sortOrders[$template->id])
                    ? max(0, (int) $sortOrders[$template->id])
                    : (int) ($template->sort_order ?? 0),
                'channel' => $channels[$template->id] ?? $template->channel,
                'is_active' => in_array((int) $template->id, $activeIds, true),
            ]);
        }

        return redirect()
            ->route('admin.sms-templates.index', [
                'channel' => $request->input('channel_filter'),
                'status' => $request->input('status_filter'),
                'search' => $request->input('search_filter'),
            ])
            ->with('success', 'تم تحديث ترتيب القوالب والقنوات والحالة بنجاح.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $smsTemplate = SmsTemplate::findOrFail($id);
        $smsTemplate->delete();
        return redirect('/admin/sms-templates')
            ->with('success', 'تم حذف قالب الرسالة بنجاح');
    }

    /**
     * Extract variables from message text
     */
    private function extractVariables(string $message): array
    {
        preg_match_all('/\{([^}]+)\}/', $message, $matches);
        return array_unique($matches[1]);
    }

    private function nextSortOrder(): int
    {
        return ((int) SmsTemplate::query()->max('sort_order')) + 1;
    }

    /**
     * Test SMS template
     */
    public function test(Request $request, $id)
    {
        $smsTemplate = SmsTemplate::findOrFail($id);

        $request->validate([
            'test_variables' => 'array'
        ]);

        $variables = $request->test_variables ?? [];
        $processedMessage = $smsTemplate->processMessage($variables);

        return response()->json([
            'success' => true,
            'message' => $processedMessage
        ]);
    }

    /**
     * Send SMS template to a real phone number
     */
    public function send(Request $request, SmsTemplate $smsTemplate, WhatsAppMessagingService $whatsAppService)
    {
        if (!$smsTemplate->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'القالب غير نشط'
            ], 422);
        }

        $data = $request->validate([
            'phone' => 'required|string',
            'send_channel' => 'required|in:sms,whatsapp',
            'sender_name_type' => 'nullable|in:advertising,verification',
            'whatsapp_purpose' => 'nullable|in:verification,follow_up,advertising',
            'test_variables' => 'array'
        ]);

        $variables = $data['test_variables'] ?? [];
        $processedMessage = $smsTemplate->processMessage($variables);
        $phone = $data['phone'];

        if ($data['send_channel'] === 'whatsapp') {
            if (!$whatsAppService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'إعدادات واتساب غير مكتملة: ' . implode(', ', $whatsAppService->missingConfigKeys()),
                ], 422);
            }

            $result = $whatsAppService->sendText($phone, $processedMessage, [
                'purpose' => $data['whatsapp_purpose'] ?? config('services.whatsapp.default_purpose', 'verification'),
                'template_id' => $smsTemplate->id,
            ]);

            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل إرسال رسالة واتساب',
                    'result' => $result,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال رسالة واتساب بنجاح',
                'result' => $result
            ]);
        }

        $result = $this->sendSMS(
            formatSaudiPhone($phone),
            $processedMessage,
            null,
            $data['sender_name_type'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة النصية بنجاح',
            'result' => $result
        ]);
    }
}

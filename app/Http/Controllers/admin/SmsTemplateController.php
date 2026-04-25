<?php

namespace App\Http\Controllers\admin;

use App\Http\Traits\SendSMSTrait;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    use SendSMSTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = SmsTemplate::all();
        return view('admin.sms_templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sms_templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:sms_templates,name',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_active' => 'boolean'
        ]);

        SmsTemplate::create([
            'name' => $request->name,
            'title' => $request->title,
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
        return view('admin.sms_templates.edit', compact('smsTemplate'));
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
            'message' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $smsTemplate->update([
            'name' => $request->name,
            'title' => $request->title,
            'message' => $request->message,
            'is_active' => $request->boolean('is_active'),
            'variables' => $this->extractVariables($request->message)
        ]);

        return redirect('/admin/sms-templates')
            ->with('success', 'تم تحديث قالب الرسالة بنجاح');
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
    public function send(Request $request, SmsTemplate $smsTemplate)
    {
        if (!$smsTemplate->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'القالب غير نشط'
            ], 422);
        }

        $data = $request->validate([
            'phone' => 'required|string',
            'test_variables' => 'array'
        ]);

        $variables = $data['test_variables'] ?? [];
        $processedMessage = $smsTemplate->processMessage($variables);
        $phone = $data['phone'];

        $result = $this->sendSMS(formatSaudiPhone($phone), $processedMessage);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة بنجاح',
            'result' => $result
        ]);
    }
}

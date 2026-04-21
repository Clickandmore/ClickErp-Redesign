<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Support\CommunicationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NotificationSettingsController extends Controller
{
    private const ALLOWED_SECTIONS = ['email', 'sms', 'whatsapp'];

    public function edit()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        return view('admin.ingaz.notifications.settings', [
            'communicationSettings' => CommunicationSettings::all(),
        ]);
    }

    public function update(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        $section = (string) $request->input('section');

        if (!in_array($section, self::ALLOWED_SECTIONS, true)) {
            return redirect()
                ->route('notifications.settings')
                ->with('error', 'القسم المطلوب غير صحيح.');
        }

        try {
            $validatedSectionPayload = match ($section) {
                'email' => $this->validateEmailSection($request),
                'sms' => $this->validateSmsSection($request),
                'whatsapp' => $this->validateWhatsAppSection($request),
            };

            CommunicationSettings::persistSection($section, $validatedSectionPayload);

            return redirect()
                ->to(route('notifications.settings') . '#' . $this->sectionAnchor($section))
                ->with('success', $this->successMessage($section));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Failed to update notification settings', [
                'error' => $exception->getMessage(),
                'section' => $section,
            ]);

            return redirect()
                ->to(route('notifications.settings') . '#' . $this->sectionAnchor($section))
                ->withInput()
                ->with('error', 'تعذر حفظ إعدادات الربط حالياً.');
        }
    }

    private function validateEmailSection(Request $request): array
    {
        $validated = $request->validate([
            'section' => 'required|in:email',
            'email' => 'required|array',
            'email.mailer' => 'required|in:smtp,sendmail,log',
            'email.host' => 'nullable|string|max:255',
            'email.port' => 'nullable|integer|min:1|max:65535',
            'email.encryption' => 'nullable|in:,tls,ssl',
            'email.username' => 'nullable|string|max:255',
            'email.password' => 'nullable|string|max:1000',
            'email.from_address' => 'nullable|email|max:255',
            'email.from_name' => 'nullable|string|max:255',
        ], [
            'email.from_address.email' => 'صيغة بريد المرسل غير صحيحة.',
        ]);

        return $validated['email'];
    }

    private function validateSmsSection(Request $request): array
    {
        $validated = $request->validate([
            'section' => 'required|in:sms',
            'sms' => 'required|array',
            'sms.url' => 'nullable|url|max:255',
            'sms.username' => 'nullable|string|max:255',
            'sms.api_key' => 'nullable|string|max:1000',
            'sms.sender' => 'nullable|string|max:255',
            'sms.advertising_sender' => 'nullable|string|max:255',
            'sms.verification_sender' => 'nullable|string|max:255',
            'sms.default_sender_type' => 'required|in:advertising,verification',
            'sms.encoding' => 'required|string|max:50',
            'sms.bulk_send_limit' => 'required|integer|min:1|max:10000',
            'sms.bulk_confirmation_keyword' => 'required|string|max:50',
        ], [
            'sms.url.url' => 'رابط مزود الرسائل النصية غير صحيح.',
        ]);

        return $validated['sms'];
    }

    private function validateWhatsAppSection(Request $request): array
    {
        $validated = $request->validate([
            'section' => 'required|in:whatsapp',
            'whatsapp' => 'required|array',
            'whatsapp.driver' => 'required|in:meta,ultramsg',
            'whatsapp.default_country_code' => 'required|string|max:10',
            'whatsapp.timeout' => 'required|integer|min:5|max:120',
            'whatsapp.bulk_send_limit' => 'required|integer|min:1|max:10000',
            'whatsapp.bulk_confirmation_keyword' => 'required|string|max:50',
            'whatsapp.default_purpose' => 'required|in:verification,follow_up,advertising',
            'whatsapp.allow_advertising' => 'nullable|boolean',

            'whatsapp.meta' => 'required|array',
            'whatsapp.meta.graph_version' => 'nullable|string|max:20',
            'whatsapp.meta.phone_number_id' => 'nullable|string|max:255',
            'whatsapp.meta.access_token' => 'nullable|string|max:5000',
            'whatsapp.meta.webhook_verify_token' => 'nullable|string|max:255',

            'whatsapp.ultramsg' => 'required|array',
            'whatsapp.ultramsg.api_url' => 'nullable|url|max:255',
            'whatsapp.ultramsg.default_instance_key' => 'nullable|string|max:100',
            'whatsapp.ultramsg.instances' => 'nullable|array',
            'whatsapp.ultramsg.instances.*.key' => 'nullable|string|max:100',
            'whatsapp.ultramsg.instances.*.name' => 'nullable|string|max:255',
            'whatsapp.ultramsg.instances.*.phone_number' => 'nullable|string|max:30',
            'whatsapp.ultramsg.instances.*.instance_id' => 'nullable|string|max:255',
            'whatsapp.ultramsg.instances.*.token' => 'nullable|string|max:1000',
            'whatsapp.ultramsg.instances.*.enabled' => 'nullable|boolean',
        ], [
            'whatsapp.ultramsg.api_url.url' => 'رابط UltraMsg غير صحيح.',
        ]);

        return $validated['whatsapp'];
    }

    private function successMessage(string $section): string
    {
        return match ($section) {
            'email' => 'تم تحديث إعدادات البريد الإلكتروني بنجاح.',
            'sms' => 'تم تحديث إعدادات الرسائل النصية بنجاح.',
            'whatsapp' => 'تم تحديث إعدادات واتساب بنجاح.',
        };
    }

    private function sectionAnchor(string $section): string
    {
        return match ($section) {
            'email' => 'email-settings',
            'sms' => 'sms-settings',
            'whatsapp' => 'whatsapp-settings',
        };
    }
}

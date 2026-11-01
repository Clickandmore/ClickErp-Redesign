<?php

namespace App\Jobs;

use App\Models\ingaz\Contact;
use App\Services\TeqnyatSmsService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\ContactNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendContactNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(public int $contactId)
    {
        $this->afterCommit();
    }

    public function handle(WhatsAppMessagingService $whatsapp, TeqnyatSmsService $sms): void
    {
        $settings = ContactNotificationSettings::all();
        if (empty($settings['enabled']) || empty($settings['phone'])) return;
        $contact = Contact::find($this->contactId);
        if (!$contact) return;

        $message = "رسالة جديدة من تواصل معنا #{$contact->id}\n"
            ."الاسم: {$contact->name}\nالجوال: {$contact->phone}\n"
            .($contact->email ? "البريد: {$contact->email}\n" : '')
            ."الموضوع: {$contact->subject}\nالرسالة:\n{$contact->message}";
        $context = ['source' => 'website_contact', 'contact_id' => $contact->id];

        try {
            $result = match ($settings['channel']) {
                'whatsapp' => $whatsapp->sendText($settings['phone'], $message, $context),
                'sms' => $sms->sendMessage($settings['phone'], $message, $context),
                default => ['success' => false],
            };
            if (!($result['success'] ?? false)) {
                Log::warning('Contact notification delivery failed', ['contact_id' => $contact->id, 'channel' => $settings['channel']]);
            }
        } catch (\Throwable $exception) {
            // The contact remains saved even when the configured provider is unavailable.
            Log::warning('Contact notification delivery failed', ['contact_id' => $contact->id, 'exception' => get_class($exception)]);
        }
    }
}

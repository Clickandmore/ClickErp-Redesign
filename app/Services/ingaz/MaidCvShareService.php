<?php

namespace App\Services\ingaz;

use App\Models\MaidCvShare;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Services\ManagedFileStorage;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppDocumentService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaidCvShareService
{
    public function __construct(private WhatsAppMessagingService $whatsapp, private MaidCvPdfGenerator $generator) {}

    public function snapshot(Maid $maid): array
    {
        $rental = $maid->isRentalCv();
        return ['name' => $maid->name, 'reference' => $maid->cv_code ?: (string) $maid->id,
            'nationality' => $maid->nationality?->sub_title ?: $maid->nationality?->title,
            'age' => $maid->age, 'cv_type' => $rental ? 'rental' : 'recruitment',
            'salary' => $rental ? null : $maid->resolved_salary];
    }

    public function message(array $snapshots): string
    {
        return collect($snapshots)->map(function ($s) {
            $rental = ($s['cv_type'] ?? 'recruitment') === 'rental';
            return "السيرة الذاتية للعاملة {$s['name']}\nنوع السيرة: " . ($rental ? 'إيجار' : 'استقدام')
                . "\nالرقم المرجعي: {$s['reference']}\nالجنسية: " . ($s['nationality'] ?: 'غير محدد')
                . "\nالعمر: " . ($s['age'] ?? 'غير محدد')
                . ($rental ? '' : "\nالراتب: " . (is_numeric($s['salary']) ? number_format((float) $s['salary']) . ' ريال' : 'غير محدد'));
        })->implode("\n\n");
    }

    public function prepare($admin, User $client, $maids, string $phone, ?string $senderKey): MaidCvShare
    {
        if (!$this->whatsapp->isConfigured()) {
            throw ValidationException::withMessages(['whatsapp' => 'أكمل إعدادات واتساب أولًا.']);
        }
        $phone = app(MetaWhatsAppService::class)->normalizePhone($phone);
        if (!$phone) throw ValidationException::withMessages(['phone' => 'رقم واتساب غير صالح.']);
        if ($this->whatsapp->driver() === 'ultramsg') {
            $sender = $this->whatsapp->resolveSender($senderKey);
            if (!$sender || ($senderKey && $sender['key'] !== $senderKey)) {
                throw ValidationException::withMessages(['sender_key' => 'اختر رقم إرسال متاحًا.']);
            }
            $senderKey = $sender['key'];
        }
        $snapshots = $maids->map(fn ($maid) => $this->snapshot($maid))->all();
        $message = $this->message($snapshots);
        if (mb_strlen($message) > 4096) {
            throw ValidationException::withMessages(['maid_ids' => 'نص المشاركة أطول من حد واتساب؛ قلل عدد السير.']);
        }
        $paths = [];
        try {
            return DB::transaction(function () use ($admin, $client, $maids, $phone, $senderKey, $message, &$paths) {
                $share = MaidCvShare::create(['admin_id' => $admin->id, 'admin_name' => $admin->name,
                    'client_id' => $client->id, 'client_name' => $client->name, 'branch_id' => $client->branch_id,
                    'phone' => $phone, 'provider' => $this->whatsapp->driver(), 'sender_key' => $senderKey, 'message' => $message]);
                $share->messages()->create([]); // The combined text is always sent first.
                foreach ($maids as $maid) {
                    try {
                        $storage = app(ManagedFileStorage::class);
                        $path = (string) $maid->getRawOriginal('cv_preview');
                        $contents = $path && $this->generator->isGeneratedPath($path) && $storage->exists($path)
                            ? $storage->contents($path) : '';
                        if (!str_starts_with($contents, '%PDF')) {
                            $contents = $this->generator->renderPdfContents($maid);
                        }
                        if (!str_starts_with($contents, '%PDF') || strlen($contents) > 15 * 1024 * 1024) {
                            throw new \RuntimeException('ملف السيرة غير صالح أو يتجاوز 15 ميجابايت.');
                        }
                        $privatePath = 'maid-cv-shares/' . Str::uuid() . '.pdf';
                        $paths[] = $privatePath;
                        if (!Storage::disk('local')->put($privatePath, $contents)) {
                            throw new \RuntimeException('تعذر حفظ نسخة السيرة.');
                        }
                        $filename = Str::slug($maid->name . '-' . ($maid->cv_code ?: $maid->id)) ?: 'cv-' . $maid->id;
                        $share->messages()->create(['maid_id' => $maid->id, 'snapshot' => $this->snapshot($maid),
                            'file_path' => $privatePath, 'filename' => Str::limit($filename, 180, '') . '.pdf']);
                    } catch (\Throwable $e) {
                        report($e);
                        throw ValidationException::withMessages(['maid_ids' => 'تعذر تجهيز السيرة المولدة للعاملة ' . $maid->name . '؛ أعد توليدها أو استبعدها من المشاركة.']);
                    }
                }
                return $share;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($paths);
            throw $e;
        }
    }

    public function send(MaidCvShare $share): void
    {
        // A database claim prevents two queue deliveries from sending the same message.
        if ($share->status !== 'queued') return;
        if (!MaidCvShare::whereKey($share->id)->where('status', 'queued')->update(['status' => 'processing'])) return;
        if ($share->provider !== $this->whatsapp->driver() || !$this->whatsapp->isConfigured()
            || ($share->sender_key && ($this->whatsapp->resolveSender($share->sender_key)['key'] ?? null) !== $share->sender_key)) {
            $share->messages()->where('status', 'pending')->update(['status' => 'failed', 'error' => 'تغيرت إعدادات مزود واتساب أو رقم الإرسال.']);
            $share->refreshStatus();
            return;
        }
        foreach ($share->messages()->get() as $message) {
            if ($message->status !== 'pending') continue;
            if (!$message->newQuery()->whereKey($message->id)->where('status', 'pending')->update(['status' => 'sending', 'attempts' => DB::raw('attempts + 1')])) continue;
            $context = ['source' => 'maid_cv_share', 'admin_id' => $share->admin_id, 'user_id' => $share->client_id,
                'branch_id' => $share->branch_id, 'sender_key' => $share->sender_key, 'name' => $share->client_name];
            try {
                $result = $message->file_path
                    ? app(WhatsAppDocumentService::class)->send($share->phone,
                        URL::temporarySignedRoute('maid-cv-shares.file', now()->addDays(7), ['message' => $message->id]),
                        $message->filename, $context + ['attachment_path' => $message->file_path])
                    : $this->whatsapp->sendText($share->phone, $share->message, $context);
                $accepted = !empty($result['success']);
                // A transport error/5xx can occur after acceptance; require review before a repeat.
                $uncertain = !$accepted && (($result['error'] ?? '') === 'exception'
                    || (($result['error'] ?? '') === 'chatwoot_error' && empty($result['status_code']))
                    || (int) ($result['status_code'] ?? 0) === 408
                    || (int) ($result['status_code'] ?? 0) >= 500);
                $message->update(['status' => $accepted ? 'accepted' : ($uncertain ? 'unknown' : 'failed'),
                    'provider_message_id' => $result['message_id'] ?? data_get($result, 'response.messages.0.id') ?? data_get($result, 'response.id'),
                    'accepted_at' => $accepted ? now() : null,
                    'error' => $accepted ? null : ($uncertain ? 'تعذر تأكيد النتيجة؛ راجع منصة واتساب قبل تكرار الإرسال.' : 'رفض مزود واتساب الإرسال. تحقق من الرقم ونافذة المحادثة والإعدادات.')]);
                if (!$accepted && !$message->file_path) {
                    $share->messages()->where('status', 'pending')->update(['status' => 'failed', 'error' => 'لم يرسل المرفق بسبب تعذر إرسال رسالة البيانات.']);
                    break;
                }
            } catch (\Throwable $e) {
                report($e);
                $message->update(['status' => 'unknown', 'error' => 'تعذر تأكيد النتيجة؛ راجع منصة واتساب قبل تكرار الإرسال.']);
                $share->messages()->where('status', 'pending')->update(['status' => 'failed', 'error' => 'توقف الإرسال قبل محاولة هذا المرفق.']);
                break;
            }
        }
        $share->refreshStatus();
    }
}

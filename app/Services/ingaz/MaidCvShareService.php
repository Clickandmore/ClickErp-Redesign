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

    /** الحقول التي يمكن للموظف إظهارها أو إخفاؤها في رسالة العميل (بالترتيب). */
    public const FIELDS = ['cv_type' => 'نوع السيرة', 'reference' => 'الرقم المرجعي', 'nationality' => 'الجنسية',
        'job' => 'المهنة', 'religion' => 'الديانة', 'age' => 'العمر', 'experience' => 'الخبرة', 'salary' => 'الراتب'];
    public const DEFAULT_FIELDS = ['cv_type', 'reference', 'nationality', 'job', 'age', 'salary'];
    /** القيم القابلة للتعديل لكل سيرة قبل الإرسال. */
    public const EDITABLE = ['name', 'reference', 'nationality', 'job', 'religion', 'age', 'experience', 'salary', 'note'];
    public const MAX_LENGTH = 4096;

    public function snapshot(Maid $maid): array
    {
        $rental = $maid->isRentalCv();
        $age = is_numeric($maid->age) && (float) $maid->age > 0 ? (string) (int) $maid->age : null;
        return ['name' => $maid->name, 'reference' => $maid->cv_code ?: (string) $maid->id,
            'nationality' => $maid->nationality?->sub_title ?: $maid->nationality?->title,
            'age' => $age, 'cv_type' => $rental ? 'rental' : 'recruitment',
            'salary' => $rental ? null : $maid->resolved_salary,
            'job' => $maid->relationLoaded('jobTitles') ? ($maid->jobTitles->pluck('title')->filter()->implode('، ') ?: null) : null,
            'religion' => $maid->relationLoaded('religion') ? $maid->religion?->title : null,
            'experience' => match ($maid->experience_status) {
                'has_experience' => 'لديها خبرة', null, '' => null, default => 'بدون خبرة' }];
    }

    /** خيارات الرسالة الافتراضية: الحقول الظاهرة ونص البداية والختام. */
    public function defaultOptions(): array
    {
        return ['fields' => self::DEFAULT_FIELDS, 'intro' => '', 'outro' => '', 'custom' => false];
    }

    /** قيمة الحقل كما تظهر للعميل، أو null إن كانت فارغة فيُخفى السطر. */
    public function fieldValue(array $s, string $field): ?string
    {
        $rental = ($s['cv_type'] ?? 'recruitment') === 'rental';
        $value = match ($field) {
            'cv_type' => $rental ? 'إيجار' : 'استقدام',
            'age' => is_numeric($s['age'] ?? null) && (float) $s['age'] <= 0 ? null : ($s['age'] ?? null),
            'salary' => $rental ? null : (is_numeric($s['salary'] ?? null)
                ? number_format((float) $s['salary']) . ' ريال' : ($s['salary'] ?? null)),
            default => $s[$field] ?? null,
        };
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    public function message(array $snapshots, ?array $options = null): string
    {
        $options = ($options ?? []) + $this->defaultOptions();
        $fields = array_values(array_intersect(array_keys(self::FIELDS), (array) $options['fields']));
        $blocks = collect($snapshots)->map(function ($s) use ($fields) {
            $lines = ["السيرة الذاتية للعاملة {$s['name']}"];
            foreach ($fields as $field) {
                if (($value = $this->fieldValue($s, $field)) !== null) $lines[] = self::FIELDS[$field] . ': ' . $value;
            }
            if (filled($s['note'] ?? null)) $lines[] = trim($s['note']);
            return implode("\n", $lines);
        });
        return collect([trim((string) $options['intro'])])->merge($blocks)->push(trim((string) $options['outro']))
            ->filter(fn ($part) => $part !== '')->implode("\n\n");
    }

    /**
     * حفظ تعديلات الموظف على مسودة المشاركة قبل الإرسال: القيم لكل سيرة، الحقول
     * الظاهرة، نص البداية والختام، أو نص يدوي كامل، واستبعاد بعض السير.
     */
    public function updateDraft(MaidCvShare $share, array $data): MaidCvShare
    {
        return DB::transaction(function () use ($share, $data) {
            $share = MaidCvShare::lockForUpdate()->findOrFail($share->id);
            if ($share->status !== 'draft') {
                throw ValidationException::withMessages(['share' => 'لا يمكن تعديل مشاركة بعد تأكيد إرسالها.']);
            }
            $items = $share->messages()->whereNotNull('maid_id')->get();
            $edits = (array) ($data['items'] ?? []);
            $kept = $items->filter(fn ($item) => !array_key_exists((string) $item->id, $edits)
                || filter_var($edits[$item->id]['include'] ?? true, FILTER_VALIDATE_BOOLEAN));
            if ($kept->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'أبقِ سيرة واحدة على الأقل في المشاركة.']);
            }
            $removed = $items->diff($kept);
            foreach ($kept as $item) {
                $values = collect($edits[$item->id] ?? [])->only(self::EDITABLE)
                    ->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
                if (array_key_exists('name', $values) && $values['name'] === '') unset($values['name']);
                if ($values) $item->update(['snapshot' => array_merge((array) $item->snapshot, $values)]);
            }
            $options = ['fields' => array_values(array_intersect(array_keys(self::FIELDS), (array) ($data['fields'] ?? []))),
                'intro' => trim((string) ($data['intro'] ?? '')), 'outro' => trim((string) ($data['outro'] ?? '')),
                'custom' => filter_var($data['custom'] ?? false, FILTER_VALIDATE_BOOLEAN) && filled($data['message'] ?? null)];
            $message = $options['custom'] ? trim((string) $data['message'])
                : $this->message($kept->map(fn ($item) => $item->fresh()->snapshot)->all(), $options);
            if (mb_strlen($message) > self::MAX_LENGTH) {
                throw ValidationException::withMessages(['message' => 'نص الرسالة أطول من حد واتساب (' . self::MAX_LENGTH . ' حرف).']);
            }
            $share->messages()->whereNull('maid_id')->update(['snapshot' => json_encode($options, JSON_UNESCAPED_UNICODE)]);
            $share->update(['message' => $message]);
            $paths = $removed->pluck('file_path')->filter()->all();
            $share->messages()->whereKey($removed->modelKeys())->delete();
            DB::afterCommit(fn () => Storage::disk('local')->delete($paths));
            return $share->fresh('messages');
        });
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
        if (mb_strlen($message) > self::MAX_LENGTH) {
            throw ValidationException::withMessages(['maid_ids' => 'نص المشاركة أطول من حد واتساب؛ قلل عدد السير.']);
        }
        $paths = [];
        try {
            // تُجهَّز الملفات قبل فتح المعاملة: التوليد قد يحدّث سيرة العاملة ويحذف نسختها
            // السابقة، فلا يصح أن يتراجع ذلك مع فشل سيرة أخرى.
            $files = [];
            foreach ($maids as $maid) {
                try {
                    $contents = $this->generator->shareablePdfContents($maid);
                    if (!str_starts_with($contents, '%PDF') || strlen($contents) > 15 * 1024 * 1024) {
                        throw new \RuntimeException('ملف السيرة غير صالح أو يتجاوز 15 ميجابايت.');
                    }
                    $privatePath = 'maid-cv-shares/' . Str::uuid() . '.pdf';
                    $paths[] = $privatePath;
                    if (!Storage::disk('local')->put($privatePath, $contents)) {
                        throw new \RuntimeException('تعذر حفظ نسخة السيرة.');
                    }
                    $files[$maid->id] = $privatePath;
                } catch (\Throwable $e) {
                    report($e);
                    throw ValidationException::withMessages(['maid_ids' => 'تعذر تجهيز السيرة المولدة للعاملة ' . $maid->name . '؛ أعد توليدها أو استبعدها من المشاركة.']);
                }
            }
            return DB::transaction(function () use ($admin, $client, $maids, $phone, $senderKey, $message, $files) {
                $share = MaidCvShare::create(['admin_id' => $admin->id, 'admin_name' => $admin->name,
                    'client_id' => $client->id, 'client_name' => $client->name, 'branch_id' => $client->branch_id,
                    'phone' => $phone, 'provider' => $this->whatsapp->driver(), 'sender_key' => $senderKey, 'message' => $message]);
                // The combined text is always sent first; its snapshot keeps the message options.
                $share->messages()->create(['snapshot' => $this->defaultOptions()]);
                foreach ($maids as $maid) {
                    $filename = Str::slug($maid->name . '-' . ($maid->cv_code ?: $maid->id)) ?: 'cv-' . $maid->id;
                    $share->messages()->create(['maid_id' => $maid->id, 'snapshot' => $this->snapshot($maid),
                        'file_path' => $files[$maid->id], 'filename' => Str::limit($filename, 180, '') . '.pdf']);
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

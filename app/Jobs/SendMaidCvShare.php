<?php

namespace App\Jobs;

use App\Models\MaidCvShare;
use App\Services\ingaz\MaidCvShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMaidCvShare implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // An interrupted HTTP send may have reached the provider. Never retry it automatically.
    public $tries = 1;
    public $timeout = 600;

    public function __construct(public int $shareId) {}

    public function handle(MaidCvShareService $service): void
    {
        $service->send(MaidCvShare::findOrFail($this->shareId));
    }

    public function failed(\Throwable $exception): void
    {
        $share = MaidCvShare::find($this->shareId);
        if (!$share) return;
        $share->messages()->where('status', 'sending')->update([
            'status' => 'unknown', 'error' => 'انقطع التنفيذ؛ راجع منصة واتساب قبل إعادة الإرسال.',
        ]);
        $share->messages()->where('status', 'pending')->update([
            'status' => 'failed', 'error' => 'لم تبدأ محاولة الإرسال.',
        ]);
        $share->refreshStatus();
    }
}

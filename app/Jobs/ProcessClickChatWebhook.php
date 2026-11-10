<?php

namespace App\Jobs;

use App\Services\WhatsApp\ClickChatMessageStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Saves one verified Click Live Chat webhook. Safe to run twice for the same payload. */
class ProcessClickChatWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public array $payload)
    {
    }

    public function handle(ClickChatMessageStore $store): void
    {
        $store->handleEvent($this->payload);
    }
}

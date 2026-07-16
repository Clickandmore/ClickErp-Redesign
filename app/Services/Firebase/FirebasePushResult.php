<?php

namespace App\Services\Firebase;

use Illuminate\Contracts\Support\Arrayable;

/**
 * حصيلة عملية إرسال: كم نجح، كم فشل، وأي التوكنات حُذفت لأنها غير صالحة.
 */
class FirebasePushResult implements Arrayable
{
    private int $successCount = 0;

    /** @var array<int, array{token: string, reason: string}> */
    private array $failures = [];

    /** @var array<int, string> */
    private array $invalidTokens = [];

    private bool $skipped = false;

    public static function skipped(string $reason): self
    {
        $result = new self();
        $result->skipped = true;
        $result->failures[] = ['token' => '', 'reason' => $reason];

        return $result;
    }

    public function addSuccess(): void
    {
        $this->successCount++;
    }

    public function addFailure(string $token, string $reason, bool $tokenIsInvalid = false): void
    {
        $this->failures[] = ['token' => $token, 'reason' => $reason];

        if ($tokenIsInvalid) {
            $this->invalidTokens[] = $token;
        }
    }

    public function successCount(): int
    {
        return $this->successCount;
    }

    public function failureCount(): int
    {
        return count($this->failures);
    }

    /** @return array<int, array{token: string, reason: string}> */
    public function failures(): array
    {
        return $this->failures;
    }

    /** @return array<int, string> */
    public function invalidTokens(): array
    {
        return $this->invalidTokens;
    }

    public function wasSkipped(): bool
    {
        return $this->skipped;
    }

    public function toArray(): array
    {
        return [
            'success_count' => $this->successCount,
            'failure_count' => $this->failureCount(),
            'invalid_tokens_removed' => count($this->invalidTokens),
            'skipped' => $this->skipped,
            'failures' => array_map(
                fn (array $failure) => [
                    'token' => $failure['token'] === '' ? '' : substr($failure['token'], 0, 12) . '…',
                    'reason' => $failure['reason'],
                ],
                $this->failures
            ),
        ];
    }
}

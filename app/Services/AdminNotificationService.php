<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\auth\Admin;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    public function sendToAdmin(
        int|string|null $adminId,
        string $title,
        string $message,
        ?string $type = null,
        ?string $link = null,
        array $data = [],
        ?int $createdBy = null,
        bool $skipActor = true
    ): ?AdminNotification {
        $adminId = (int) $adminId;
        $createdBy ??= (int) (auth('admin')->id() ?: 0);

        if ($adminId < 1) {
            return null;
        }

        if ($skipActor && $createdBy > 0 && $adminId === $createdBy) {
            return null;
        }

        $exists = Admin::query()
            ->activeForOperations()
            ->whereKey($adminId)
            ->exists();

        if (!$exists) {
            return null;
        }

        return AdminNotification::query()->create([
            'admin_id' => $adminId,
            'created_by' => $createdBy > 0 ? $createdBy : null,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'data' => $data,
        ]);
    }

    public function sendToAdmins(
        iterable $adminIds,
        string $title,
        string $message,
        ?string $type = null,
        ?string $link = null,
        array $data = [],
        ?int $createdBy = null,
        bool $skipActor = true
    ): Collection {
        return collect($adminIds)
            ->filter()
            ->map(fn ($adminId) => (int) $adminId)
            ->unique()
            ->map(fn (int $adminId) => $this->sendToAdmin(
                $adminId,
                $title,
                $message,
                $type,
                $link,
                $data,
                $createdBy,
                $skipActor
            ))
            ->filter()
            ->values();
    }
}

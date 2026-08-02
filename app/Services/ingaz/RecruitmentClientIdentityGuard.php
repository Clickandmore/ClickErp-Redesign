<?php

namespace App\Services\ingaz;

use App\Models\ingaz\User;

class RecruitmentClientIdentityGuard
{
    public function findByDocument(string $accountType, ?string $documentNumber): ?User
    {
        $documentNumber = trim((string) $documentNumber);
        if ($documentNumber === '') {
            return null;
        }

        $column = $accountType === 'company' ? 'tax_number' : 'national_num';

        $matches = User::query()->where($column, $documentNumber)->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function documentMatchCount(string $accountType, ?string $documentNumber): int
    {
        $documentNumber = trim((string) $documentNumber);
        if ($documentNumber === '') {
            return 0;
        }

        $column = $accountType === 'company' ? 'tax_number' : 'national_num';

        return User::query()->where($column, $documentNumber)->count();
    }

    public function findByPhone(?string $phone): ?User
    {
        $candidates = $this->phoneCandidates($phone);

        return $candidates === []
            ? null
            : User::query()->whereIn('phone', $candidates)->first();
    }

    public function documentNumber(User $user): string
    {
        return trim((string) ($user->isCompanyClient() ? $user->tax_number : $user->national_num));
    }

    public function hasDocumentConflict(User $user, string $accountType, ?string $documentNumber): bool
    {
        $documentNumber = trim((string) $documentNumber);
        if ($documentNumber === '') {
            return true;
        }

        $existingDocument = trim((string) (
            $accountType === 'company' ? $user->tax_number : $user->national_num
        ));

        return $existingDocument === '' || $existingDocument !== $documentNumber;
    }

    public function normalizeSaudiPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $phone));
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '05')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
    }

    public function phoneCandidates(?string $phone): array
    {
        $phone = trim((string) $phone);
        $normalized = $this->normalizeSaudiPhone($phone);

        return array_values(array_unique(array_filter([
            $phone,
            $normalized,
            $normalized ? '0' . $normalized : null,
            $normalized ? '966' . $normalized : null,
            $normalized ? '+966' . $normalized : null,
        ])));
    }
}

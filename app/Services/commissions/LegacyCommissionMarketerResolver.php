<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;

class LegacyCommissionMarketerResolver
{
    public const HISTORICAL_PROFILE_NAMES = [
        'اشراق-الهند',
        'خالد-بنجلاديش',
        'السودان - رجالي',
        'باكستان - رجالي',
        'حسام-الفلبين',
        'مكتب BS الفلبين',
        'وجود',
        'مصر- رجالي',
        'الجريد',
    ];

    private const EMAIL_BY_LEGACY_NAME = [
        'علي عبدالواحد الفرج' => 'alfrjly231@gmail.com',
        'ليلى شمروخ' => 'laylashamruk@gmail.com',
        'مصطفي يحي محمد' => 'mostafa8673@gmail.com',
        'محمد عبدالواحد الفرج' => 'alfarajfu2@gmail.com',
        'مراد علي الفرج' => 'muradalfaraj1125@gmail.com',
        'يقين شمروخ' => 'yaqoona623@gmail.com',
        'عبدالواحد الفرج' => 'alfarajfu@gmail.com',
        'زهراء يوسف الزريقي' => 'zahra.yosuf@hotmail.com',
        'باسم عاشور' => 'basmalashwr99@gmail.com',
        'سجاد عبدالواحد الفرج' => 'sajad1415fu@gmail.com',
        'الادارة' => 'info@alrafedain4rec.com',
        'حوراء الجنبي' => 'hawraabdulaziz@outlook.sa',
    ];

    private ?array $adminsByName = null;
    private ?array $adminsByEmail = null;

    public function resolve(string $legacyName): ?Admin
    {
        $this->loadAdmins();

        $normalizedName = $this->normalize($legacyName);
        if (isset($this->adminsByName[$normalizedName])) {
            return $this->adminsByName[$normalizedName];
        }

        $email = self::EMAIL_BY_LEGACY_NAME[trim($legacyName)] ?? null;

        return $email ? ($this->adminsByEmail[mb_strtolower($email)] ?? null) : null;
    }

    public function displayName(Admin $admin): string
    {
        $email = mb_strtolower((string) $admin->email);
        $legacyName = array_search($email, array_map('mb_strtolower', self::EMAIL_BY_LEGACY_NAME), true);

        return $legacyName !== false ? $legacyName : $admin->name;
    }

    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه',
            'ؤ' => 'و', 'ئ' => 'ي', 'ـ' => '',
        ]);

        return preg_replace('/[\s\p{P}\p{S}_]+/u', '', $value) ?: $value;
    }

    private function loadAdmins(): void
    {
        if ($this->adminsByName !== null) {
            return;
        }

        $admins = Admin::query()->get();
        $this->adminsByName = $admins
            ->keyBy(fn (Admin $admin) => $this->normalize($admin->name))
            ->all();
        $this->adminsByEmail = $admins
            ->filter(fn (Admin $admin) => filled($admin->email))
            ->keyBy(fn (Admin $admin) => mb_strtolower($admin->email))
            ->all();
    }
}

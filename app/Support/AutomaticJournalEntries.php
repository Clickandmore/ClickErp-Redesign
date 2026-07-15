<?php

namespace App\Support;

use Nami\Finance\Models\ConfirmSetting;

class AutomaticJournalEntries
{
    public const SETTING_KEY = 'automatic_journal_entries';

    /**
     * Automatic entries remain enabled when the setting has never been saved,
     * preserving the behaviour of existing companies.
     */
    public static function enabled(): bool
    {
        $value = ConfirmSetting::query()
            ->where('title', self::SETTING_KEY)
            ->latest('updated_at')
            ->value('value');

        return $value === null || $value === '' || $value === 'on';
    }
}

<?php

namespace App\Support;

class RecruitmentStagePolicy
{
    public const RANK = ['pending_maid_selection' => 0, 'new' => 1, 'accreditation_contracts' => 1,
        'msaned' => 2, 'tafwed' => 3, 'taffez' => 4, 'tazker' => 5, 'arrival' => 6, 'completed' => 7];

    public static function allowsSteps(string $stage): bool
    {
        return isset(self::RANK[$stage]) && self::RANK[$stage] < 6;
    }

    public static function isForward(string $from, string $to): bool
    {
        return isset(self::RANK[$from], self::RANK[$to]) && self::RANK[$to] > self::RANK[$from];
    }
}

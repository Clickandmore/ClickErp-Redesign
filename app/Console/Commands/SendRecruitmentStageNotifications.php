<?php

namespace App\Console\Commands;

use App\Models\RecruitmentStageNotification;
use App\Services\ingaz\RecruitmentStageNotifier;
use App\Support\RecruitmentStageNotificationSettings;
use Illuminate\Console\Command;

class SendRecruitmentStageNotifications extends Command
{
    protected $signature = 'recruitment:send-stage-notifications';
    protected $description = 'Send pending recruitment stage customer notifications';

    public function handle(RecruitmentStageNotifier $notifier): int
    {
        if (empty(RecruitmentStageNotificationSettings::all()['enabled'])) {
            return 0;
        }
        RecruitmentStageNotification::where('status', 'pending')->orderBy('id')->limit(100)->get()
            ->each(fn ($notification) => $notifier->deliver($notification));
        return 0;
    }
}

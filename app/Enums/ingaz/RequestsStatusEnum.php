<?php

namespace App\Enums\ingaz;

enum RequestsStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function lang()
    {
        return match ($this) {
            self::Pending => __('ingaz.pending'),
            self::Approved => __('ingaz.approved'),
            self::Rejected => __('ingaz.rejected'),
        };
    }
}

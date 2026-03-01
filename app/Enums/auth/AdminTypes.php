<?php

namespace App\Enums\auth;

enum AdminTypes: string
{
    case GlobalManger = "super_admin";
    case CustomerService = "customer_service";

    public function lang(): string
    {
        $locale = app()->getLocale();
        $options = \App\Helpers\Constant::globalArray['admin_typeis'] ?? [];

        return $options[$this->value][$locale] ?? $this->value;
    }
}

<?php

namespace App\Enums\ingaz;

enum PaymentMethodEnum: string
{
    case None = "none"; //added recently
    case Cash = "cash";
    case Online = "online";
    case ApplePay = "apple_pay";
    case Moyasar = "moyasar";

}

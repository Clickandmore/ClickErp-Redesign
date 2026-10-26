<?php

namespace App\Support;

class RentalExtensionMessage
{
    public const TEMPLATE_NAME = 'rental_contract_extended';
    public const VARIABLES = ['client_name', 'contract_number', 'new_end_date'];

    public static function defaultMessage(): string
    {
        return "عميلنا {client_name}،\nتم تمديد عقد الإيجار رقم {contract_number} حتى تاريخ {new_end_date}.\nشكراً لتعاملكم معنا.";
    }
}

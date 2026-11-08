<?php

namespace App\Support;

class RentalExpiryMessage
{
    public static function customer(string $name, string $number, string $date, bool $expired = false, ?string $responseUrl = null): string
    {
        $greeting = trim($name) !== '' ? "عميلنا {$name}،" : 'عميلنا العزيز،';
        $event = $expired ? 'انتهى' : 'ينتهي';

        return implode("\n", array_filter([
            $greeting,
            "نود تذكيركم بأن عقد الإيجار رقم {$number} {$event} بتاريخ {$date}.",
            $responseUrl ? 'لتحديد رغبتكم في التمديد والدفع أو طلب تسليم العاملة، يرجى الدخول على الرابط:'
                : 'يرجى التواصل معنا لمساعدتكم في تمديد العقد أو ترتيب تسليم العاملة.',
            $responseUrl,
            'شكراً لتعاملكم معنا.',
        ]));
    }

    public static function staff(string $name, string $number, string $date, bool $expired = false): string
    {
        $event = $expired ? 'انتهى' : 'ينتهي';
        $client = trim($name) !== '' ? " للعميل {$name}" : '';

        return "عقد الإيجار رقم {$number}{$client} {$event} بتاريخ {$date}. يرجى التواصل مع العميل لمتابعة التمديد أو ترتيب تسليم العاملة.";
    }
}

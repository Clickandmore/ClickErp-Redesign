<?php

namespace App\Support;

class InvoiceQrCode
{
    public static function render(string $value, ?string $fallbackImage = null): string
    {
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            return (new \chillerlan\QRCode\QRCode())->render($value);
        }

        if ($fallbackImage) {
            return $fallbackImage;
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($value);
    }
}

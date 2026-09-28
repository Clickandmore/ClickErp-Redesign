<?php
namespace App\Services\Hourly;
use Mpdf\Config\FontVariables;
use Mpdf\Config\ConfigVariables;
final class HourlyPdf {
public static function mpdfConfig(?string $tempDirectory = null): array
    {
        $tempDirectory ??= storage_path('app/mpdf');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0775, true);
        }

        $fontData = (new FontVariables())->getDefaults()['fontdata'];
        // Chrome justified on whitespace; kashida would stretch the letters instead.
        $fontData['xbriyaz']['useKashida'] = 0;

        return [
            'mode' => 'utf-8',
            'format' => 'Letter',
            'orientation' => 'P',
            'directionality' => 'rtl',
            // The page box owns its own 3mm inset, matching the previous renderer.
            'margin_left' => 3,
            'margin_right' => 3,
            'margin_top' => 3,
            'margin_bottom' => 3,
            'margin_header' => 0,
            'margin_footer' => 0,
            'fontDir' => (new ConfigVariables())->getDefaults()['fontDir'],
            'fontdata' => $fontData,
            'default_font' => 'xbriyaz',
            'tempDir' => $tempDirectory,
        ];
    }
}

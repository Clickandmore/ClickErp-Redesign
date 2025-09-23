<?php
/**
 * API معلومات النظام
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $memory_usage = memory_get_usage(true);
    $memory_peak = memory_get_peak_usage(true);
    $memory_limit = ini_get('memory_limit');

    // تحويل memory_limit إلى bytes للمقارنة
    $memory_limit_bytes = convertToBytes($memory_limit);
    $memory_percent = ($memory_usage / $memory_limit_bytes) * 100;

    echo json_encode([
        'success' => true,
        'memory_usage' => formatBytes($memory_usage),
        'memory_peak' => formatBytes($memory_peak),
        'memory_limit' => $memory_limit,
        'memory_percent' => round($memory_percent, 1),
        'php_version' => PHP_VERSION,
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_execution_time' => ini_get('max_execution_time'),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int) $value;

    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }

    return $value;
}

function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}
?>

<?php
/**
 * اختبار سريع لوظائف الرفع
 */

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'files_received' => $_FILES,
    'post_data' => $_POST,
    'upload_errors' => [
        0 => 'UPLOAD_ERR_OK',
        1 => 'UPLOAD_ERR_INI_SIZE',
        2 => 'UPLOAD_ERR_FORM_SIZE',
        3 => 'UPLOAD_ERR_PARTIAL',
        4 => 'UPLOAD_ERR_NO_FILE'
    ],
    'test_status' => 'اختبار اتصال ناجح',
    'current_time' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

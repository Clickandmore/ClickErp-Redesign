<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

$service = app('App\Services\ZohoSalesIq\ZohoSalesIqService');
$result = $service->visitors(['limit' => 10]);

echo "\n=== ZOHO SALESIQ VISITORS TEST ===\n\n";
echo "Status: " . ($result['ok'] ? 'OK' : 'FAILED') . "\n";
echo "HTTP Code: " . $result['status_code'] . "\n";
echo "Error: " . ($result['error'] ?? 'None') . "\n";
echo "\nFull Response:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

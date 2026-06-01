<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

$service = app('App\Services\ZohoSalesIq\ZohoSalesIqService');

echo "\n=== ZOHO SALESIQ CONVERSATIONS TEST ===\n\n";

$result = $service->conversations([
    'limit' => 10,
    'fromtime' => now()->subDays(7)->timestamp * 1000,
    'totime' => now()->timestamp * 1000,
]);

echo "Status: " . ($result['ok'] ? '✓ OK' : '✗ FAILED') . "\n";
echo "HTTP Code: " . $result['status_code'] . "\n";
echo "Error: " . ($result['error'] ?? 'None') . "\n";
echo "Items Count: " . count($result['items'] ?? []) . "\n";
echo "\nFull Response:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

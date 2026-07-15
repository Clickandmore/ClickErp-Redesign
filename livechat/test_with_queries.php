<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Http;

$service = app('App\Services\ZohoSalesIq\ZohoSalesIqService');
$token = $service->getAccessToken();
$baseUrl = config('services.zoho_salesiq.salesiq_base_url');
$screenName = config('services.zoho_salesiq.screen_name');

echo "\n=== TESTING WITH QUERY PARAMETERS ===\n\n";

$queries = [
    ['limit' => 50],
    ['count' => 50],
    [],
    ['from' => 0],
    ['skip' => 0, 'limit' => 50],
    ['offset' => 0, 'limit' => 50],
];

foreach ($queries as $query) {
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $token,
        ])
            ->acceptJson()
            ->timeout(10)
            ->get($baseUrl . '/' . $screenName . '/mychats', $query);
        
        $payload = $response->json() ?? [];
        $status = $response->status();
        
        echo "Query: " . json_encode($query) . "\n";
        echo "Status: $status\n";
        echo "Response Size: " . strlen(json_encode($payload)) . " bytes\n";
        echo "Response: " . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    } catch (\Exception $e) {
        echo "Query: " . json_encode($query) . "\n";
        echo "Exception: " . $e->getMessage() . "\n\n";
    }
}

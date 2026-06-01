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

echo "\n=== TESTING DIFFERENT ENDPOINTS FOR CHATS ===\n\n";

$endpoints = [
    "$screenName/chats",
    "$screenName/conversations",
    "chats",
    "conversations",
    "mychats",
    "$screenName/mychats",
    "api/chats",
    "api/conversations",
];

foreach ($endpoints as $endpoint) {
    $url = $baseUrl . '/' . ltrim($endpoint, '/');
    
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $token,
        ])
            ->acceptJson()
            ->timeout(10)
            ->get($url, ['limit' => 5]);
        
        $payload = $response->json() ?? [];
        $status = $response->status();
        
        echo "Endpoint: $endpoint\n";
        echo "URL: $url\n";
        echo "Status: $status\n";
        echo "Response: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
    } catch (\Exception $e) {
        echo "Endpoint: $endpoint\n";
        echo "URL: $url\n";
        echo "Exception: " . $e->getMessage() . "\n\n";
    }
}

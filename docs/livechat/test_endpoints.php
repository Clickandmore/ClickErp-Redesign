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

echo "\n=== TESTING DIFFERENT ENDPOINTS ===\n\n";

$endpoints = [
    "v2/{$screenName}/visitors",
    "api/v2/{$screenName}/visitors",
    "api/v1/{$screenName}/visitors",
    "{$screenName}/visitors",
    "visitors",
    "api/visitors",
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
        $hasError = isset($payload['error']);
        $status = $response->status();
        
        echo "❌ $endpoint\n";
        echo "   Status: $status\n";
        echo "   Error: " . json_encode($payload) . "\n\n";
    } catch (\Exception $e) {
        echo "⚠️  $endpoint\n";
        echo "   Exception: " . $e->getMessage() . "\n\n";
    }
}

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

echo "\n=== TESTING ZOHO SALESIQ API ENDPOINTS ===\n\n";

$endpoints = [
    // Widget API
    [
        'name' => 'Widget API - Get Departments',
        'url' => 'https://api.salesiq.zoho.sa/widget',
        'headers' => ['Authorization' => 'Zoho-oauthtoken ' . $token],
        'query' => []
    ],
    
    // CRM API style
    [
        'name' => 'CRM API - Chats',
        'url' => 'https://www.zoho.sa/crm/v2/SalesIQ_Chats',
        'headers' => ['Authorization' => 'Zoho-oauthtoken ' . $token],
        'query' => []
    ],
    
    // Direct SalesIQ API
    [
        'name' => 'SalesIQ Direct - Chats Endpoint',
        'url' => 'https://salesiq-api.zoho.sa/chats',
        'headers' => ['Authorization' => 'Zoho-oauthtoken ' . $token],
        'query' => ['limit' => 50]
    ],
    
    // API endpoint
    [
        'name' => 'API Portal - Chats',
        'url' => 'https://api.zoho.sa/salesiq/chats',
        'headers' => ['Authorization' => 'Bearer ' . $token],
        'query' => []
    ],
];

foreach ($endpoints as $ep) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Test: {$ep['name']}\n";
    echo "URL: {$ep['url']}\n";
    
    try {
        $response = Http::withHeaders($ep['headers'])
            ->acceptJson()
            ->timeout(5)
            ->get($ep['url'], $ep['query']);
        
        echo "Status: {$response->status()}\n";
        
        $body = $response->body();
        if (strlen($body) > 500) {
            echo "Response: " . substr($body, 0, 200) . "... (truncated)\n";
        } else {
            echo "Response: $body\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$service = app('App\Services\ZohoSalesIq\ZohoSalesIqService');
$token = $service->getAccessToken();
$baseUrl = config('services.zoho_salesiq.salesiq_base_url');
$screenName = config('services.zoho_salesiq.screen_name');

echo "\n=== CHECKING AUTHORIZATION ===\n\n";

echo "Access Token: " . substr($token, 0, 20) . "...\n";
echo "Screen Name: $screenName\n";
echo "Base URL: $baseUrl\n\n";

// Test 1: With correct auth
echo "Test 1: WITH Authorization Header\n";
$r1 = Http::withHeaders([
    'Authorization' => 'Zoho-oauthtoken ' . $token,
])
    ->acceptJson()
    ->timeout(10)
    ->get($baseUrl . '/' . $screenName . '/mychats');

echo "Status: " . $r1->status() . "\n";
echo "Headers: " . json_encode($r1->headers(), JSON_PRETTY_PRINT) . "\n";
echo "Body: " . $r1->body() . "\n\n";

// Test 2: Check if endpoint needs different format
echo "Test 2: Different Authorization Format\n";
$r2 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
])
    ->acceptJson()
    ->timeout(10)
    ->get($baseUrl . '/' . $screenName . '/mychats');

echo "Status: " . $r2->status() . "\n";
echo "Body: " . $r2->body() . "\n\n";

// Test 3: Check root endpoint
echo "Test 3: Root API Endpoint\n";
$r3 = Http::withHeaders([
    'Authorization' => 'Zoho-oauthtoken ' . $token,
])
    ->acceptJson()
    ->timeout(10)
    ->get($baseUrl);

echo "Status: " . $r3->status() . "\n";
echo "Body: " . $r3->body() . "\n\n";

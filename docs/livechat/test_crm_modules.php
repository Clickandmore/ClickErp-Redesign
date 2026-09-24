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

echo "\n=== CHECKING CRM MODULES ===\n\n";

// Test 1: Get all modules
echo "Test 1: Get All Modules\n";
$r1 = Http::withHeaders([
    'Authorization' => 'Zoho-oauthtoken ' . $token,
])
    ->acceptJson()
    ->timeout(10)
    ->get('https://www.zoho.sa/crm/v2/settings/modules');

echo "Status: " . $r1->status() . "\n";
$modules = $r1->json();

if (is_array($modules) && isset($modules['modules'])) {
    echo "Modules found:\n";
    foreach ($modules['modules'] as $mod) {
        echo "  - {$mod['api_name']} ({$mod['display_name']})\n";
    }
} else {
    echo "Response: " . $r1->body() . "\n";
}

echo "\n\nTest 2: Search for SalesIQ-related modules\n";
// Test 2: Try to get SalesIQ_Chats module
$chatModules = ['SalesIQ_Chats', 'SalesIQ_Conversations', 'Live_Chats', 'Chats', 'Conversations'];

foreach ($chatModules as $modName) {
    $r = Http::withHeaders([
        'Authorization' => 'Zoho-oauthtoken ' . $token,
    ])
        ->acceptJson()
        ->timeout(10)
        ->get("https://www.zoho.sa/crm/v2/$modName", ['limit' => 1]);
    
    echo "\nModule: $modName\n";
    echo "Status: " . $r->status() . "\n";
    if ($r->status() === 200 || $r->status() === 400) {
        echo "Response: " . substr($r->body(), 0, 200) . "...\n";
    }
}

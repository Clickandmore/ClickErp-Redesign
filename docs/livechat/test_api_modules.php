<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->handle(\Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;

$service = app('App\Services\ZohoSalesIq\ZohoSalesIqService');
$token = $service->getAccessToken();

echo "\n=== TESTING API.ZOHO.SA ===\n\n";

$r = Http::withHeaders(['Authorization' => 'Zoho-oauthtoken ' . $token])
    ->acceptJson()
    ->get('https://api.zoho.sa/crm/v2/settings/modules');

echo "Status: " . $r->status() . "\n";
$data = $r->json();

if (isset($data['modules'])) {
    echo "✓ Modules found!\n\n";
    
    $chatModules = [];
    foreach ($data['modules'] as $mod) {
        if (stripos($mod['display_name'] ?? '', 'chat') !== false || 
            stripos($mod['display_name'] ?? '', 'salesiq') !== false ||
            stripos($mod['api_name'] ?? '', 'chat') !== false ||
            stripos($mod['api_name'] ?? '', 'live') !== false) {
            $chatModules[] = $mod;
        }
    }
    
    if (!empty($chatModules)) {
        foreach ($chatModules as $mod) {
            echo "  → {$mod['api_name']} ({$mod['display_name']})\n";
        }
    } else {
        echo "  No chat-related modules found.\n";
        echo "\nFirst 10 modules:\n";
        foreach (array_slice($data['modules'], 0, 10) as $mod) {
            echo "  - {$mod['api_name']}\n";
        }
    }
} else {
    echo "Error response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}

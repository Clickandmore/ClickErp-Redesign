<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

$screenName = config('services.zoho_salesiq.screen_name');
$baseUrl = config('services.zoho_salesiq.salesiq_base_url');

$encodedName = rawurlencode($screenName);
$url1 = $baseUrl . '/api/v2/' . $encodedName . '/visitors';
$url2 = $baseUrl . '/api/v1/' . $encodedName . '/visitors';
$url3 = $baseUrl . '/api/v3/' . $encodedName . '/visitors';

echo "=== URL DIAGNOSTICS ===\n\n";
echo "Screen Name: $screenName\n";
echo "Screen Name (encoded): $encodedName\n";
echo "Base URL: $baseUrl\n\n";
echo "Generated URLs:\n";
echo "v1: $url1\n";
echo "v2: $url2\n";
echo "v3: $url3\n\n";

// Check if screen_name needs different encoding
echo "Alternative encoding:\n";
echo "URL-safe: " . urlencode($screenName) . "\n";
echo "No encoding: $screenName\n";

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;

$response = Http::asForm()
    ->acceptJson()
    ->timeout(20)
    ->post('https://accounts.zoho.sa/oauth/v2/token', [
        'grant_type' => 'authorization_code',
        'client_id' => config('services.zoho_salesiq.client_id'),
        'client_secret' => config('services.zoho_salesiq.client_secret'),
        'code' => '1000.a04f65c0c7e51ccc9bf3a941989f5c49.ba282150ac880dbd5f86c6aa33ad4538',
        'redirect_uri' => 'http://localhost',
    ]);

$body = $response->body();
$headers = $response->headers();
$status = $response->status();

echo "Status: $status\n";
echo "Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n";
echo "Body:\n" . $body . "\n";

<?php

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$pdo = new PDO(
    'mysql:host=127.0.0.1;port=8889;dbname=alrafedain_live;charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

foreach (['nationalities_translations', 'fn_client_categories', 'fn_accounts'] as $table) {
    echo PHP_EOL . $table . PHP_EOL;
    foreach ($pdo->query("SELECT * FROM `{$table}` LIMIT 10") as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}

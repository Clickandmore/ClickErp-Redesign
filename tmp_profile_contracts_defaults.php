<?php

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp') . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$queries = [
    'admins' => 'SELECT id, name, branch_id FROM admins ORDER BY id LIMIT 5',
    'job_types' => "SELECT jt.id, t.title FROM job_types jt LEFT JOIN job_types_translations t ON t.job_type_id = jt.id AND t.locale = 'ar' ORDER BY jt.id",
    'orders' => 'SELECT * FROM orders ORDER BY id LIMIT 5',
    'order_details' => 'SELECT * FROM order_details ORDER BY id LIMIT 5',
    'contracts' => 'SELECT * FROM contracts ORDER BY id LIMIT 5',
    'contract_infos' => 'SELECT * FROM contract_infos ORDER BY id LIMIT 5',
];

foreach ($queries as $label => $sql) {
    echo PHP_EOL . $label . PHP_EOL;
    foreach ($pdo->query($sql) as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}

<?php

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));
$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo 'Database: ' . $database . PHP_EOL;

$queries = [
    'contracts' => 'SELECT COUNT(*) FROM contracts',
    'contract_infos' => 'SELECT COUNT(*) FROM contract_infos',
    'orders' => 'SELECT COUNT(*) FROM orders',
    'order_details' => 'SELECT COUNT(*) FROM order_details',
    'contracts_with_order' => 'SELECT COUNT(*) FROM contracts WHERE order_id IS NOT NULL',
    'contracts_without_order' => 'SELECT COUNT(*) FROM contracts WHERE order_id IS NULL',
    'linked_contract_orders_not_completed' => "SELECT COUNT(*) FROM contracts c JOIN orders o ON o.id = c.order_id WHERE o.status <> 'completed'",
    'contracts_without_info' => 'SELECT COUNT(*) FROM contracts c LEFT JOIN contract_infos ci ON ci.contract_id = c.id WHERE ci.id IS NULL',
    'duplicate_contract_ids' => 'SELECT COUNT(*) FROM (SELECT id FROM contracts GROUP BY id HAVING COUNT(*) > 1) x',
];

foreach ($queries as $label => $sql) {
    echo $label . ': ' . $pdo->query($sql)->fetchColumn() . PHP_EOL;
}

echo PHP_EOL . 'Contract status counts:' . PHP_EOL;
foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM contracts GROUP BY status ORDER BY total DESC') as $row) {
    echo ($row['status'] ?: '(empty)') . ': ' . $row['total'] . PHP_EOL;
}

echo PHP_EOL . 'Order status counts:' . PHP_EOL;
foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status ORDER BY total DESC') as $row) {
    echo ($row['status'] ?: '(empty)') . ': ' . $row['total'] . PHP_EOL;
}

echo PHP_EOL . 'Contracts without order:' . PHP_EOL;
foreach ($pdo->query('SELECT id, user_id, maid_id, status, contract_date, contract_cost FROM contracts WHERE order_id IS NULL ORDER BY id') as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

echo PHP_EOL . 'Samples:' . PHP_EOL;
foreach ($pdo->query('SELECT c.id, c.status AS contract_status, c.user_id, c.maid_id, c.order_id, o.status AS order_status, ci.contract_value FROM contracts c LEFT JOIN orders o ON o.id = c.order_id LEFT JOIN contract_infos ci ON ci.contract_id = c.id ORDER BY c.id LIMIT 10') as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

<?php

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

foreach (['alrafedain', 'alrafedain_live', 'alrafedain_erp'] as $database) {
    echo PHP_EOL . "Database: {$database}" . PHP_EOL;

    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Throwable $e) {
        echo 'connect: ' . $e->getMessage() . PHP_EOL;
        continue;
    }

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo 'tables: ' . count($tables) . PHP_EOL;

    foreach (['users', 'nationalities', 'countries', 'admins', 'branches'] as $table) {
        if (!in_array($table, $tables, true)) {
            echo "{$table}: missing" . PHP_EOL;
            continue;
        }

        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        echo "{$table}: {$count} rows; columns=" . implode(',', $columns) . PHP_EOL;
    }
}

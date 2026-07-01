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

echo "Database: {$database}" . PHP_EOL;

foreach (['maids', 'maid_translations', 'maid_jobs', 'maid_job_titles', 'nationalities', 'nationalities_translations', 'religions', 'religions_translations', 'job_types', 'job_types_translations', 'job_titles', 'job_titles_translations', 'recruitment_offices', 'recruitment_office_translations', 'admins'] as $table) {
    echo PHP_EOL . "{$table}: ";
    if (!tableExists($pdo, $table)) {
        echo "missing" . PHP_EOL;
        continue;
    }

    echo $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() . " rows" . PHP_EOL;
    $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    echo "columns: " . implode(', ', $columns) . PHP_EOL;
}

foreach (['nationalities_translations', 'religions_translations', 'job_types_translations', 'job_titles_translations', 'recruitment_office_translations', 'recruitment_offices'] as $table) {
    if (!tableExists($pdo, $table)) {
        continue;
    }

    echo PHP_EOL . "Sample {$table}:" . PHP_EOL;
    foreach ($pdo->query("SELECT * FROM `{$table}` LIMIT 20") as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);

    return (bool) $stmt->fetchColumn();
}

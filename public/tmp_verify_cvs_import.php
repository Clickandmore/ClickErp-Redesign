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
foreach (['maids', 'maid_translations', 'maid_jobs', 'maid_job_titles'] as $table) {
    echo "{$table}: " . $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() . PHP_EOL;
}

echo 'duplicate_passports: ' . $pdo->query("SELECT COUNT(*) FROM (SELECT passport_num FROM maids WHERE passport_num IS NOT NULL AND passport_num <> '' GROUP BY passport_num HAVING COUNT(*) > 1) x")->fetchColumn() . PHP_EOL;
echo 'maids_without_job_type: ' . $pdo->query("SELECT COUNT(*) FROM maids m LEFT JOIN maid_jobs mj ON mj.maid_id = m.id WHERE mj.id IS NULL")->fetchColumn() . PHP_EOL;
echo 'maids_without_job_title: ' . $pdo->query("SELECT COUNT(*) FROM maids m LEFT JOIN maid_job_titles mjt ON mjt.maid_id = m.id WHERE mjt.id IS NULL")->fetchColumn() . PHP_EOL;

echo PHP_EOL . 'Status counts:' . PHP_EOL;
foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM maids GROUP BY status ORDER BY total DESC') as $row) {
    echo ($row['status'] ?: '(empty)') . ': ' . $row['total'] . PHP_EOL;
}

echo PHP_EOL . 'P9577951C:' . PHP_EOL;
$stmt = $pdo->prepare('SELECT id,name,passport_num,status,old_id FROM maids WHERE passport_num = ? LIMIT 1');
$stmt->execute(['P9577951C']);
echo json_encode($stmt->fetch(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

echo PHP_EOL . 'First imported rows:' . PHP_EOL;
foreach ($pdo->query('SELECT id,name,passport_num,status,nationality_id,religion_id,office_id FROM maids ORDER BY id LIMIT 5') as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

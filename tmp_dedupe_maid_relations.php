<?php

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$commit = in_array('--commit', $argv, true);
$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));
$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$tables = [
    'maid_jobs' => ['maid_id', 'job_type_id'],
    'maid_job_titles' => ['maid_id', 'job_title_id'],
];

echo 'Mode: ' . ($commit ? 'COMMIT' : 'DRY-RUN') . PHP_EOL;

$pdo->beginTransaction();
try {
    foreach ($tables as $table => [$maidColumn, $relationColumn]) {
        $before = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $distinct = (int) $pdo->query("SELECT COUNT(*) FROM (SELECT `{$maidColumn}`, `{$relationColumn}` FROM `{$table}` GROUP BY `{$maidColumn}`, `{$relationColumn}`) x")->fetchColumn();
        $duplicates = $before - $distinct;

        echo "{$table} before={$before} distinct={$distinct} duplicates={$duplicates}" . PHP_EOL;

        if ($duplicates > 0) {
            $sql = "
                DELETE t1 FROM `{$table}` t1
                INNER JOIN `{$table}` t2
                    ON t1.`{$maidColumn}` = t2.`{$maidColumn}`
                    AND t1.`{$relationColumn}` = t2.`{$relationColumn}`
                    AND t1.id > t2.id
            ";
            $deleted = $pdo->exec($sql);
            echo "{$table} deleted={$deleted}" . PHP_EOL;
        }

        $after = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo "{$table} after={$after}" . PHP_EOL;
    }

    if ($commit) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

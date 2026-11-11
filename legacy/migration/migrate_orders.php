<?php
function readEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line == '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) != 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($value != '' && ($value[0] == '"' || $value[0] == "'")) {
            $value = trim($value, "\"'");
        }
        $vars[$key] = $value;
    }

    return $vars;
}

$env = readEnvFile(__DIR__ . '/../../.env');

$MIGRATION_OLD_DB_HOST = $env['MIGRATION_OLD_DB_HOST'] ?? $env['OLD_DB_HOST'] ?? $env['DB_OLD_HOST'] ?? '127.0.0.1';
$MIGRATION_OLD_DB_PORT = $env['MIGRATION_OLD_DB_PORT'] ?? $env['OLD_DB_PORT'] ?? $env['DB_OLD_PORT'] ?? '8889';
$MIGRATION_OLD_DB_NAME = $env['MIGRATION_OLD_DB_NAME'] ?? $env['OLD_DB_NAME'] ?? $env['OLD_DB_DATABASE'] ?? $env['DB_OLD_DATABASE'] ?? 'platinum_old';
$MIGRATION_OLD_DB_USER = $env['MIGRATION_OLD_DB_USER'] ?? $env['OLD_DB_USER'] ?? $env['DB_OLD_USERNAME'] ?? 'root';
$MIGRATION_OLD_DB_PASS = $env['MIGRATION_OLD_DB_PASS'] ?? $env['OLD_DB_PASS'] ?? $env['DB_OLD_PASSWORD'] ?? 'root';

$MIGRATION_NEW_DB_HOST = $env['MIGRATION_NEW_DB_HOST'] ?? $env['DB_HOST'] ?? '127.0.0.1';
$MIGRATION_NEW_DB_PORT = $env['MIGRATION_NEW_DB_PORT'] ?? $env['DB_PORT'] ?? '8889';
$MIGRATION_NEW_DB_NAME = $env['MIGRATION_NEW_DB_NAME'] ?? $env['DB_DATABASE'] ?? 'platinum_new';
$MIGRATION_NEW_DB_USER = $env['MIGRATION_NEW_DB_USER'] ?? $env['DB_USERNAME'] ?? 'root';
$MIGRATION_NEW_DB_PASS = $env['MIGRATION_NEW_DB_PASS'] ?? $env['DB_PASSWORD'] ?? 'root';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => ''
];

function read_param(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function sanitize_identifier(string $value, string $label): string
{
    if ($value === '') {
        throw new InvalidArgumentException($label . ' is required.');
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
        throw new InvalidArgumentException('Invalid ' . $label . ' value. Use only letters, numbers, or underscore.');
    }

    return $value;
}

$pdo = null;
$foreignKeysDisabled = false;

try {
    $dbHost = read_param('db_host', $MIGRATION_NEW_DB_HOST);
    $dbPort = (int) read_param('db_port', $MIGRATION_NEW_DB_PORT);
    $dbName = read_param('db_name', $MIGRATION_NEW_DB_NAME);
    $dbUser = read_param('db_user', $MIGRATION_NEW_DB_USER);
    $dbPass = read_param('db_pass', $MIGRATION_NEW_DB_PASS);

    $newDb = read_param('new_db', $MIGRATION_NEW_DB_NAME);
    $oldDb = read_param('old_db', $MIGRATION_OLD_DB_NAME);
    $sourceTable = read_param('source_table', 'rservations');
    $targetTable = read_param('target_table', 'orders');
    $truncateTarget = read_param('truncate_target', '0') === '1';

    $newDbSafe = sanitize_identifier($newDb, 'new_db');
    $oldDbSafe = sanitize_identifier($oldDb, 'old_db');
    $sourceTableSafe = sanitize_identifier($sourceTable, 'source_table');
    $targetTableSafe = sanitize_identifier($targetTable, 'target_table');

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $newDbSafe);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $foreignKeysDisabled = true;

    if ($truncateTarget) {
        $pdo->exec(sprintf('TRUNCATE TABLE `%s`.`%s`', $newDbSafe, $targetTableSafe));
    }

    $sql = "
        INSERT INTO `{$newDbSafe}`.`{$targetTableSafe}` (
            `id`,
            `maid_id`,
            `user_id`,
            `order_code`,
            `created_at`,
            `updated_at`,
            `deleted_at`,
            `status`,
            `job_type_id`,
            `payment_method`,
            `payment_type`,
            `status_changed_at`,
            `deadline_at`,
            `expiration_time`,
            `customer_id`
        )
        SELECT
            r.`id`,
            r.`cv_id`,
            r.`user_id`,
            r.`code`,
            r.`created_at`,
            r.`updated_at`,
            r.`deleted_at`,
            CASE
                WHEN r.`status` = 13 THEN 'under_process'
                WHEN r.`status` = 15 THEN 'completed'
                ELSE 'new'
            END,
            1,
            'cash',
            NULL,
            NULL,
            NULL,
            NULL,
            NULL
        FROM `{$oldDbSafe}`.`{$sourceTableSafe}` AS r
    ";

    $rows = $pdo->exec($sql);

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    $foreignKeysDisabled = false;
    $pdo->commit();

    $response['success'] = true;
    $response['rowsAffected'] = (int) $rows;
    $response['message'] = sprintf(
        'Inserted %d row(s) from %s.%s into %s.%s.',
        (int) $rows,
        $oldDbSafe,
        $sourceTableSafe,
        $newDbSafe,
        $targetTableSafe
    );
} catch (PDOException $exception) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($pdo && $foreignKeysDisabled) {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Throwable $reEnableError) {
            // ignore
        }
    }

    $response['message'] = 'Database error: ' . $exception->getMessage();
} catch (InvalidArgumentException $exception) {
    $response['message'] = $exception->getMessage();
} catch (Throwable $exception) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($pdo && $foreignKeysDisabled) {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Throwable $reEnableError) {
            // ignore
        }
    }

    $response['message'] = 'Unexpected error: ' . $exception->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

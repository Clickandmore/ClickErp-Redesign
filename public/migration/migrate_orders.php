<?php
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
    $dbHost = read_param('db_host', '127.0.0.1');
    $dbPort = (int) read_param('db_port', '3306');
    $dbName = read_param('db_name', 'platinum_new');
    $dbUser = read_param('db_user', 'root');
    $dbPass = read_param('db_pass', 'root');

    $newDb = read_param('new_db', $dbName);
    $oldDb = read_param('old_db', 'platinum_old');
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

<?php
// Read-only audit. Credentials stay in process and are never printed.
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
try {
    $pdo = new PDO('mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4', $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
    $pdo->exec('SET TRANSACTION READ ONLY');
    $pdo->beginTransaction();
    $queries = [
        'contracts' => 'SELECT id, contract_id, invoice_constraint_id, arrival_revenue_constraint_id, total_due, created_at FROM invoices WHERE contract_id IN (546,610)',
        'journals' => 'SELECT id, code, date, value, statement, created_at, deleted_at FROM fn_constraints WHERE code IN (2802,2858,6713,6722,6723)',
        'missing_links' => 'SELECT COUNT(*) AS count FROM invoices WHERE contract_id IS NOT NULL AND invoice_constraint_id IS NULL AND arrival_revenue_constraint_id IS NULL',
        'missing_link_candidates' => "SELECT i.id AS invoice_id, i.contract_id, i.total_due, COUNT(c.id) AS candidates, GROUP_CONCAT(c.code ORDER BY c.id) AS journal_codes FROM invoices i LEFT JOIN fn_constraints c ON c.deleted_at IS NULL AND c.statement LIKE CONCAT('قيد فاتورة طلب استقدام للعقد #',i.contract_id,' / الفاتورة #',i.id,' / مساند %') WHERE i.contract_id IS NOT NULL AND i.invoice_constraint_id IS NULL AND i.arrival_revenue_constraint_id IS NULL GROUP BY i.id,i.contract_id,i.total_due ORDER BY i.id",
        'duplicate_invoice_journals' => "SELECT statement, COUNT(*) AS count, GROUP_CONCAT(code ORDER BY id) AS journal_codes FROM fn_constraints WHERE deleted_at IS NULL AND statement LIKE 'قيد فاتورة طلب استقدام للعقد #% / الفاتورة #% / مساند %' GROUP BY statement HAVING COUNT(*) > 1",
        'missing_link_samples' => 'SELECT id, contract_id, total_due, created_at FROM invoices WHERE contract_id IS NOT NULL AND invoice_constraint_id IS NULL AND arrival_revenue_constraint_id IS NULL ORDER BY id LIMIT 30',
    ];
    foreach ($queries as $label => $sql) {
        echo $label . ': ' . json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    }
    $pdo->rollBack();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    // Avoid exposing credentials/connection details from driver exceptions.
    fwrite(STDERR, 'Read-only audit failed (' . get_class($e) . ', code ' . $e->getCode() . ').' . PHP_EOL);
    exit(1);
}

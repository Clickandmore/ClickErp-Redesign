<?php
/**
 * إعدادات قاعدة البيانات المُحسنة
 * Enhanced Database Configuration
 */

class Database {
    private static $instance = null;
    private $connections = [];

    // إعدادات قواعد البيانات
    private $config = [
        'migration' => [
            'host' => 'localhost',
            'username' => 'root',
            'password' => 'root',
            'database' => 'platinum_migration',
            'charset' => 'utf8mb4',
            'port' => 8889
        ],
        'erp_operations' => [
            'host' => 'localhost',
            'username' => 'root',
            'password' => 'root',
            'database' => 'erp_operations_new',
            'charset' => 'utf8mb4',
            'port' => 8889
        ],
        'platinum' => [
            'host' => 'localhost',
            'username' => 'root',
            'password' => 'root',
            'database' => 'platinum',
            'charset' => 'utf8mb4',
            'port' => 8889
        ]
    ];

    private function __construct() {
        // منع إنشاء instance جديد
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * الحصول على اتصال قاعدة بيانات
     */
    public function getConnection($connection_name = 'migration') {
        if (!isset($this->connections[$connection_name])) {
            $this->connections[$connection_name] = $this->createConnection($connection_name);
        }
        return $this->connections[$connection_name];
    }

    private function createConnection($connection_name) {
        if (!isset($this->config[$connection_name])) {
            throw new Exception("Unknown database connection: {$connection_name}");
        }

        $config = $this->config[$connection_name];

        // استخدام unix socket بدلاً من TCP للاتصال المحلي
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 60,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']} COLLATE {$config['charset']}_unicode_ci",
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]);

            // اختبار الاتصال
            $pdo->query("SELECT 1")->fetch();

            return $pdo;

        } catch (PDOException $e) {
            error_log("Database connection failed for {$connection_name}: " . $e->getMessage());

            // محاولة اتصال بديلة بدون port
            try {
                $dsn_alt = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
                $pdo = new PDO($dsn_alt, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);

                $pdo->query("SELECT 1")->fetch();
                return $pdo;

            } catch (PDOException $e2) {
                throw new Exception("فشل الاتصال بقاعدة البيانات {$connection_name}: " . $e->getMessage());
            }
        }
    }

    /**
     * تنفيذ استعلام على قاعدة بيانات محددة
     */
    public function query($sql, $params = [], $connection_name = 'migration') {
        try {
            $pdo = $this->getConnection($connection_name);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed on {$connection_name}: {$sql} - " . $e->getMessage());
            throw new Exception("خطأ في تنفيذ الاستعلام على {$connection_name}: " . $e->getMessage());
        }
    }

    /**
     * الحصول على سجل واحد
     */
    public function fetch($sql, $params = [], $connection_name = 'migration') {
        return $this->query($sql, $params, $connection_name)->fetch();
    }

    /**
     * الحصول على جميع السجلات
     */
    public function fetchAll($sql, $params = [], $connection_name = 'migration') {
        return $this->query($sql, $params, $connection_name)->fetchAll();
    }

    /**
     * إدراج سجل والحصول على المعرف
     */
    public function insert($table, $data, $connection_name = 'migration') {
        $fields = array_keys($data);
        $values = ':' . implode(', :', $fields);
        $fields = implode(', ', $fields);

        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
        $this->query($sql, $data, $connection_name);

        return $this->getConnection($connection_name)->lastInsertId();
    }

    /**
     * تحديث سجل
     */
    public function update($table, $data, $where, $connection_name = 'migration') {
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "{$field} = :{$field}";
        }
        $set = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        return $this->query($sql, $data, $connection_name);
    }

    /**
     * حذف سجلات
     */
    public function delete($table, $where, $params = [], $connection_name = 'migration') {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params, $connection_name);
    }

    /**
     * اختبار الاتصال
     */
    public function testConnection($connection_name) {
        try {
            $pdo = $this->getConnection($connection_name);
            $result = $pdo->query("SELECT 1 as test")->fetch();
            return $result['test'] === 1;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * فحص وجود قاعدة البيانات
     */
    public function databaseExists($connection_name) {
        try {
            $config = $this->config[$connection_name];
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";

            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$config['database']]);

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * إنشاء قاعدة البيانات
     */
    public function createDatabase($connection_name) {
        try {
            $config = $this->config[$connection_name];
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";

            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $sql = "CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET {$config['charset']} COLLATE {$config['charset']}_unicode_ci";
            $pdo->exec($sql);

            return true;

        } catch (Exception $e) {
            error_log("Failed to create database {$connection_name}: " . $e->getMessage());
            return false;
        }
    }
}

// إنشاء instance global للاستخدام السهل
$db = Database::getInstance();

/**
 * وظائف مساعدة للوصول السريع
 */
function db($connection_name = 'migration') {
    return Database::getInstance()->getConnection($connection_name);
}

function dbQuery($sql, $params = [], $connection_name = 'migration') {
    return Database::getInstance()->query($sql, $params, $connection_name);
}

function dbFetch($sql, $params = [], $connection_name = 'migration') {
    return Database::getInstance()->fetch($sql, $params, $connection_name);
}

function dbFetchAll($sql, $params = [], $connection_name = 'migration') {
    return Database::getInstance()->fetchAll($sql, $params, $connection_name);
}

/**
 * فحص وإعداد قواعد البيانات
 */
function setupDatabases() {
    $db = Database::getInstance();
    $databases = ['migration', 'erp_operations', 'platinum'];
    $results = [];

    foreach ($databases as $db_name) {
        $results[$db_name] = [
            'exists' => $db->databaseExists($db_name),
            'connected' => false
        ];

        if (!$results[$db_name]['exists']) {
            $results[$db_name]['created'] = $db->createDatabase($db_name);
        }

        $results[$db_name]['connected'] = $db->testConnection($db_name);
    }

    return $results;
}
?>

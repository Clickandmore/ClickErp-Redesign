<?php
/**
 * إعدادات قاعدة البيانات محسنة لـ MAMP
 * MAMP-Optimized Database Configuration
 */

class Database {
    private static $instance = null;
    private $connections = [];
    
    private $config = [
        'migration' => [
            'host' => '127.0.0.1',  // استخدام IP بدلاً من localhost
            'username' => 'root',
            'password' => 'root',
            'database' => 'platinum_migration',
            'charset' => 'utf8mb4',
            'port' => 8889
        ],
        'erp_operations' => [
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'root',
            'database' => 'erp_operations_new',
            'charset' => 'utf8mb4',
            'port' => 8889
        ],
        'platinum' => [
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'root',
            'database' => 'platinum',
            'charset' => 'utf8mb4',
            'port' => 8889
        ]
    ];
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
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
        
        // محاولة اتصال مع MAMP socket
        $socket_path = '/Applications/MAMP/tmp/mysql/mysql.sock';
        $dsn_socket = "mysql:unix_socket={$socket_path};dbname={$config['database']};charset={$config['charset']}";
        
        try {
            // محاولة 1: Socket connection
            $pdo = new PDO($dsn_socket, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            $pdo->query("SELECT 1")->fetch();
            return $pdo;
            
        } catch (PDOException $e1) {
            try {
                // محاولة 2: TCP connection with IP
                $dsn_tcp = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                $pdo = new PDO($dsn_tcp, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                $pdo->query("SELECT 1")->fetch();
                return $pdo;
                
            } catch (PDOException $e2) {
                try {
                    // محاولة 3: localhost without port
                    $dsn_local = "mysql:host=localhost;dbname={$config['database']};charset={$config['charset']}";
                    $pdo = new PDO($dsn_local, $config['username'], $config['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    
                    $pdo->query("SELECT 1")->fetch();
                    return $pdo;
                    
                } catch (PDOException $e3) {
                    // آخر محاولة: إنشاء قاعدة البيانات إذا لم تكن موجودة
                    try {
                        $this->createDatabaseIfNotExists($config);
                        $pdo = new PDO($dsn_tcp, $config['username'], $config['password'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);
                        return $pdo;
                    } catch (PDOException $e4) {
                        throw new Exception("فشل في جميع محاولات الاتصال بـ {$connection_name}. تأكد من تشغيل MAMP على البورت 8889");
                    }
                }
            }
        }
    }
    
    private function createDatabaseIfNotExists($config) {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET {$config['charset']} COLLATE {$config['charset']}_unicode_ci");
    }
    
    public function query($sql, $params = [], $connection_name = 'migration') {
        try {
            $pdo = $this->getConnection($connection_name);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Exception $e) {
            error_log("Query failed on {$connection_name}: {$sql} - " . $e->getMessage());
            throw new Exception("خطأ في الاستعلام: " . $e->getMessage());
        }
    }
    
    public function fetch($sql, $params = [], $connection_name = 'migration') {
        return $this->query($sql, $params, $connection_name)->fetch();
    }
    
    public function fetchAll($sql, $params = [], $connection_name = 'migration') {
        return $this->query($sql, $params, $connection_name)->fetchAll();
    }
    
    public function insert($table, $data, $connection_name = 'migration') {
        $fields = array_keys($data);
        $values = ':' . implode(', :', $fields);
        $fields = implode(', ', $fields);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
        $this->query($sql, $data, $connection_name);
        
        return $this->getConnection($connection_name)->lastInsertId();
    }
    
    public function update($table, $data, $where, $connection_name = 'migration') {
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "{$field} = :{$field}";
        }
        $set = implode(', ', $set);
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        return $this->query($sql, $data, $connection_name);
    }
    
    public function delete($table, $where, $params = [], $connection_name = 'migration') {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params, $connection_name);
    }
}

// Instance global
$db = Database::getInstance();
?>

<?php
/**
 * منفذ النقل التلقائي الشامل
 * Automatic Full Migration Executor
 */

class AutoMigrationExecutor {

    private $db_migration;
    private $db_erp;
    private $db_platinum;
    private $migration_id;
    private $options;

    public function __construct($options = []) {
        $this->db_migration = Database::getInstance()->getConnection('migration');
        $this->db_erp = Database::getInstance()->getConnection('erp_operations');
        $this->db_platinum = Database::getInstance()->getConnection('platinum');
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * تنفيذ النقل الشامل
     */
    public function executeFullMigration() {
        try {
            // إنشاء سجل النقل
            $this->migration_id = $this->createMigrationRecord();

            echo "🚀 بدء النقل الشامل - ID: {$this->migration_id}\n";
            echo "==========================================\n\n";

            // إنشاء نسخة احتياطية
            if ($this->options['create_backup']) {
                $this->createBackup();
            }

            // إضافة الجداول والحقول المفقودة
            $this->synchronizeSchema();

            // تنفيذ النقل بالمراحل
            $this->executeMigrationPhases();

            // إنهاء النقل
            $this->completeMigration();

            echo "\n🎉 تم إكمال النقل الشامل بنجاح!\n";

            return [
                'success' => true,
                'migration_id' => $this->migration_id,
                'message' => 'تم النقل الشامل بنجاح'
            ];

        } catch (Exception $e) {
            $this->handleMigrationError($e);
            throw $e;
        }
    }

    /**
     * إنشاء سجل النقل
     */
    private function createMigrationRecord() {
        $stmt = $this->db_migration->prepare("
            INSERT INTO full_migration_sessions (
                options, status, started_at, created_at
            ) VALUES (?, 'in_progress', NOW(), NOW())
        ");

        $stmt->execute([json_encode($this->options, JSON_UNESCAPED_UNICODE)]);
        return $this->db_migration->lastInsertId();
    }

    /**
     * مزامنة البنية
     */
    private function synchronizeSchema() {
        echo "🔧 مزامنة بنية قاعدة البيانات...\n";

        // إنشاء الجداول المفقودة
        $this->createMissingTables();

        // إضافة الحقول المفقودة
        $this->addMissingFields();

        echo "✅ تم إكمال مزامنة البنية\n\n";
    }

    /**
     * إنشاء الجداول المفقودة
     */
    private function createMissingTables() {
        $missing_tables = [
            'about_us' => "
                CREATE TABLE IF NOT EXISTS `about_us` (
                    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'about_us_translations' => "
                CREATE TABLE IF NOT EXISTS `about_us_translations` (
                    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
                    `about_us_id` bigint UNSIGNED NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `description` varchar(1000) NOT NULL,
                    `locale` varchar(255) NOT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'complaints' => "
                CREATE TABLE IF NOT EXISTS `complaints` (
                    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) DEFAULT NULL,
                    `phone` varchar(255) DEFAULT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `complaint_type_id` bigint UNSIGNED DEFAULT NULL,
                    `subject` varchar(255) DEFAULT NULL,
                    `message` text,
                    `status` varchar(255) NOT NULL DEFAULT 'new',
                    `user_id` bigint UNSIGNED DEFAULT NULL,
                    `order_id` bigint UNSIGNED DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];

        foreach ($missing_tables as $table_name => $sql) {
            try {
                $this->db_platinum->exec($sql);
                echo "  ✓ تم إنشاء جدول: $table_name\n";
            } catch (Exception $e) {
                echo "  ⚠️ خطأ في إنشاء جدول $table_name: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * إضافة الحقول المفقودة
     */
    private function addMissingFields() {
        $field_additions = [
            'users' => [
                'first_name' => 'VARCHAR(255) NULL',
                'last_name' => 'VARCHAR(255) NULL',
                'national_num' => 'VARCHAR(50) NULL',
                'phone2' => 'VARCHAR(50) NULL',
                'birthdate' => 'DATE NULL',
                'register_from' => 'VARCHAR(255) NOT NULL DEFAULT "web"',
                'is_blocked' => 'TINYINT NOT NULL DEFAULT 0',
                'status' => 'TINYINT NOT NULL DEFAULT 1'
            ],
            'cvs' => [
                'name' => 'VARCHAR(255) NULL',
                'nationality_id' => 'INT NULL',
                'religion_id' => 'INT NULL',
                'birth_date' => 'DATE NULL',
                'available' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'is_pinned' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'available_at' => 'DATE NULL',
                'status' => 'VARCHAR(255) NOT NULL DEFAULT "available"',
                'is_show' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'experience_years' => 'VARCHAR(255) NULL',
                'experience_status' => 'ENUM("has_experience","no_experience") NOT NULL DEFAULT "no_experience"'
            ]
        ];

        foreach ($field_additions as $table_name => $fields) {
            foreach ($fields as $field_name => $field_definition) {
                try {
                    $sql = "ALTER TABLE `$table_name` ADD COLUMN IF NOT EXISTS `$field_name` $field_definition";
                    $this->db_platinum->exec($sql);
                    echo "  ✓ تم إضافة حقل: $table_name.$field_name\n";
                } catch (Exception $e) {
                    echo "  ⚠️ خطأ في إضافة حقل $table_name.$field_name: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    /**
     * تنفيذ مراحل النقل
     */
    private function executeMigrationPhases() {
        $phases = $this->getMigrationPhases();

        foreach ($phases as $phase) {
            echo "📋 المرحلة {$phase['order']}: {$phase['name']}\n";
            echo str_repeat("-", 50) . "\n";

            foreach ($phase['tables'] as $table_config) {
                $this->migrateTable($phase['name'], $table_config);
            }

            echo "✅ اكتملت المرحلة: {$phase['name']}\n\n";
        }
    }

    /**
     * نقل جدول محدد
     */
    private function migrateTable($phase_name, $table_config) {
        $source_table = $table_config['source'];
        $target_table = $table_config['target'] ?? $source_table;
        $migration_type = $table_config['type'] ?? 'simple';

        echo "  🔄 نقل: $source_table → $target_table\n";

        try {
            // بدء تسجيل النقل
            $this->logTableMigrationStart($phase_name, $source_table, $target_table);

            // تنفيذ النقل حسب النوع
            switch ($migration_type) {
                case 'users':
                    $migrated = $this->migrateUsers();
                    break;
                case 'maids_to_cvs':
                    $migrated = $this->migrateMaidsToCvs();
                    break;
                case 'orders':
                    $migrated = $this->migrateOrders();
                    break;
                default:
                    $migrated = $this->migrateSimpleTable($source_table, $target_table);
            }

            // إنهاء تسجيل النقل
            $this->logTableMigrationComplete($source_table, $migrated);

            echo "    ✓ تم نقل $migrated سجل\n";

        } catch (Exception $e) {
            echo "    ❌ خطأ في النقل: " . $e->getMessage() . "\n";
            $this->logTableMigrationError($source_table, $e->getMessage());
        }
    }

    /**
     * نقل المستخدمين
     */
    private function migrateUsers() {
        $sql = "
            INSERT INTO users (
                name, first_name, last_name, phone, email, password,
                address, city, country, national_num, phone2, birthdate,
                nationality_id, register_from, is_blocked, status,
                created_at, updated_at
            )
            SELECT
                COALESCE(name, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) as name,
                first_name, last_name,
                CASE
                    WHEN phone LIKE '+966%' THEN SUBSTRING(phone, 5)
                    WHEN phone LIKE '966%' THEN SUBSTRING(phone, 4)
                    WHEN phone LIKE '0%' THEN phone
                    ELSE CONCAT('0', phone)
                END as phone,
                email, password, address, city, 'Saudi Arabia' as country,
                national_num, phone2, birthdate, nationality_id,
                'migration' as register_from, 0 as is_blocked, 1 as status,
                created_at, updated_at
            FROM erp_operations_new.users u
            WHERE (u.email IS NOT NULL OR u.phone IS NOT NULL)
            AND NOT EXISTS (
                SELECT 1 FROM users pu
                WHERE (pu.email = u.email AND u.email IS NOT NULL)
                   OR (pu.phone = u.phone AND u.phone IS NOT NULL)
            )
        ";

        $stmt = $this->db_platinum->exec($sql);

        // حفظ التطابقات
        $this->saveUserMapping();

        return $stmt;
    }

    /**
     * نقل العاملات إلى CVs
     */
    private function migrateMaidsToCvs() {
        $sql = "
            INSERT INTO cvs (
                passport_id, name, nationality_id, religion_id, birth_date,
                salary, service_price, age_id, region_id,
                personal_image, cv_file, cv_rev_type, available, status,
                experience_years, experience_status, created_at, updated_at
            )
            SELECT
                m.passport_num as passport_id, m.name, m.nationality_id, m.religion_id, m.birth_date,
                m.salary, m.price as service_price,
                CASE WHEN m.age <= 25 THEN 1 WHEN m.age <= 35 THEN 2 WHEN m.age <= 45 THEN 3 ELSE 4 END as age_id,
                m.religion_id as region_id, m.cv_preview as personal_image, m.cv_file,
                1 as cv_rev_type, m.available, m.status, m.experience_years, m.experience_status,
                m.created_at, m.updated_at
            FROM erp_operations_new.maids m
            WHERE m.passport_num IS NOT NULL AND m.passport_num != ''
            AND NOT EXISTS (SELECT 1 FROM cvs c WHERE c.passport_id = m.passport_num)
        ";

        $stmt = $this->db_platinum->exec($sql);

        // حفظ التطابقات
        $this->saveMaidsMapping();

        return $stmt;
    }

    /**
     * نقل الطلبات
     */
    private function migrateOrders() {
        $sql = "
            INSERT INTO orders (
                order_code, status, user_id, biography_id,
                order_date, created_at, updated_at
            )
            SELECT
                o.order_code,
                CASE
                    WHEN o.status = 'completed' THEN 'finished'
                    WHEN o.status = 'cancelled' THEN 'canceled'
                    ELSE o.status
                END as status,
                um.target_id as user_id,
                cm.target_id as biography_id,
                o.created_at as order_date,
                o.created_at, o.updated_at
            FROM erp_operations_new.orders o
            LEFT JOIN id_mapping_full um ON um.source_table = 'users' AND um.source_id = o.user_id
            LEFT JOIN id_mapping_full cm ON cm.source_table = 'maids' AND cm.source_id = o.maid_id
            WHERE o.order_code IS NOT NULL
            AND um.target_id IS NOT NULL
            AND cm.target_id IS NOT NULL
            AND NOT EXISTS (SELECT 1 FROM orders po WHERE po.order_code = o.order_code)
        ";

        return $this->db_platinum->exec($sql);
    }

    /**
     * نقل جدول بسيط
     */
    private function migrateSimpleTable($source_table, $target_table) {
        // الحصول على قائمة الحقول المشتركة
        $common_fields = $this->getCommonFields($source_table, $target_table);

        if (empty($common_fields)) {
            throw new Exception("لا توجد حقول مشتركة بين $source_table و $target_table");
        }

        $fields_list = implode(', ', $common_fields);

        $sql = "
            INSERT IGNORE INTO `$target_table` ($fields_list)
            SELECT $fields_list
            FROM erp_operations_new.`$source_table`
        ";

        return $this->db_platinum->exec($sql);
    }

    /**
     * الحصول على الحقول المشتركة بين جدولين
     */
    private function getCommonFields($source_table, $target_table) {
        // الحصول على حقول الجدول المصدر
        $source_fields = $this->getTableFields('erp_operations_new', $source_table);

        // الحصول على حقول الجدول المستهدف
        $target_fields = $this->getTableFields('platinum', $target_table);

        // العثور على الحقول المشتركة
        return array_intersect($source_fields, $target_fields);
    }

    /**
     * الحصول على قائمة حقول الجدول
     */
    private function getTableFields($database, $table) {
        $sql = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ";

        $pdo = $database === 'erp_operations_new' ? $this->db_erp : $this->db_platinum;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$database, $table]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * حفظ تطابق المستخدمين
     */
    private function saveUserMapping() {
        $sql = "
            INSERT IGNORE INTO id_mapping_full (source_table, target_table, source_id, target_id)
            SELECT 'users', 'users', u.id, pu.id
            FROM erp_operations_new.users u
            JOIN users pu ON (pu.email = u.email OR pu.phone = u.phone)
            WHERE u.email IS NOT NULL OR u.phone IS NOT NULL
        ";

        $this->db_migration->exec($sql);
    }

    /**
     * حفظ تطابق العاملات
     */
    private function saveMaidsMapping() {
        $sql = "
            INSERT IGNORE INTO id_mapping_full (source_table, target_table, source_id, target_id)
            SELECT 'maids', 'cvs', m.id, c.id
            FROM erp_operations_new.maids m
            JOIN cvs c ON c.passport_id = m.passport_num
            WHERE m.passport_num IS NOT NULL
        ";

        $this->db_migration->exec($sql);
    }

    /**
     * مراحل النقل
     */
    private function getMigrationPhases() {
        return [
            [
                'name' => 'البيانات المرجعية',
                'order' => 1,
                'tables' => [
                    ['source' => 'countries', 'type' => 'simple'],
                    ['source' => 'cities', 'type' => 'simple'],
                    ['source' => 'nationalities', 'type' => 'simple'],
                    ['source' => 'religions', 'type' => 'simple']
                ]
            ],
            [
                'name' => 'المستخدمون والعملاء',
                'order' => 2,
                'tables' => [
                    ['source' => 'users', 'type' => 'users'],
                    ['source' => 'user_addresses', 'target' => 'addresses', 'type' => 'simple']
                ]
            ],
            [
                'name' => 'العاملات والسير الذاتية',
                'order' => 3,
                'tables' => [
                    ['source' => 'maids', 'target' => 'cvs', 'type' => 'maids_to_cvs']
                ]
            ],
            [
                'name' => 'الطلبات والعقود',
                'order' => 4,
                'tables' => [
                    ['source' => 'orders', 'type' => 'orders']
                ]
            ],
            [
                'name' => 'المحتوى والإعدادات',
                'order' => 5,
                'tables' => [
                    ['source' => 'settings', 'target' => 'app_settings', 'type' => 'simple'],
                    ['source' => 'about_us', 'type' => 'simple'],
                    ['source' => 'about_us_translations', 'type' => 'simple']
                ]
            ],
            [
                'name' => 'البيانات المساعدة',
                'order' => 6,
                'tables' => [
                    ['source' => 'complaints', 'type' => 'simple'],
                    ['source' => 'skills', 'type' => 'simple'],
                    ['source' => 'job_titles', 'type' => 'simple']
                ]
            ]
        ];
    }

    /**
     * الخيارات الافتراضية
     */
    private function getDefaultOptions() {
        return [
            'create_backup' => true,
            'skip_existing' => true,
            'validate_data' => true,
            'batch_size' => 1000,
            'migration_priority' => 'normal',
            'parallel_processing' => false,
            'duplicate_handling' => 'skip',
            'error_handling' => 'continue'
        ];
    }

    /**
     * إنشاء نسخة احتياطية
     */
    private function createBackup() {
        echo "💾 إنشاء نسخة احتياطية...\n";

        $backup_file = "platinum_backup_full_migration_" . date('Y_m_d_H_i_s') . ".sql";
        $backup_path = __DIR__ . "/backups/$backup_file";

        if (!is_dir(dirname($backup_path))) {
            mkdir(dirname($backup_path), 0755, true);
        }

        // أمر النسخ الاحتياطي
        $command = "mysqldump -h localhost -P 8889 -u root -proot platinum > '$backup_path' 2>&1";

        exec($command, $output, $return_code);

        if ($return_code === 0) {
            echo "✅ تم إنشاء النسخة الاحتياطية: $backup_file\n";
        } else {
            throw new Exception("فشل في إنشاء النسخة الاحتياطية");
        }
    }

    /**
     * تسجيل بدء نقل الجدول
     */
    private function logTableMigrationStart($phase_name, $source_table, $target_table) {
        $sql = "
            INSERT INTO full_migration_log (
                migration_session_id, phase_name, table_name, target_table,
                status, started_at
            ) VALUES (?, ?, ?, ?, 'in_progress', NOW())
        ";

        $stmt = $this->db_migration->prepare($sql);
        $stmt->execute([$this->migration_id, $phase_name, $source_table, $target_table]);
    }

    /**
     * تسجيل إكمال نقل الجدول
     */
    private function logTableMigrationComplete($table_name, $migrated_count) {
        $sql = "
            UPDATE full_migration_log
            SET status = 'completed', completed_at = NOW(), migrated_records = ?
            WHERE migration_session_id = ? AND table_name = ? AND status = 'in_progress'
        ";

        $stmt = $this->db_migration->prepare($sql);
        $stmt->execute([$migrated_count, $this->migration_id, $table_name]);
    }

    /**
     * تسجيل خطأ نقل الجدول
     */
    private function logTableMigrationError($table_name, $error_message) {
        $sql = "
            UPDATE full_migration_log
            SET status = 'failed', completed_at = NOW(), error_message = ?
            WHERE migration_session_id = ? AND table_name = ? AND status = 'in_progress'
        ";

        $stmt = $this->db_migration->prepare($sql);
        $stmt->execute([$error_message, $this->migration_id, $table_name]);
    }

    /**
     * إكمال النقل
     */
    private function completeMigration() {
        $sql = "
            UPDATE full_migration_sessions
            SET status = 'completed', completed_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->db_migration->prepare($sql);
        $stmt->execute([$this->migration_id]);

        echo "📊 إحصائيات النقل النهائية:\n";
        $this->printFinalStats();
    }

    /**
     * طباعة الإحصائيات النهائية
     */
    private function printFinalStats() {
        $sql = "
            SELECT
                COUNT(*) as total_tables,
                SUM(migrated_records) as total_records,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_tables,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_tables
            FROM full_migration_log
            WHERE migration_session_id = ?
        ";

        $stmt = $this->db_migration->prepare($sql);
        $stmt->execute([$this->migration_id]);
        $stats = $stmt->fetch();

        echo "  📈 إجمالي الجداول: {$stats['total_tables']}\n";
        echo "  📈 إجمالي السجلات: " . number_format($stats['total_records']) . "\n";
        echo "  ✅ جداول ناجحة: {$stats['successful_tables']}\n";
        echo "  ❌ جداول فاشلة: {$stats['failed_tables']}\n";
    }

    /**
     * معالجة أخطاء النقل
     */
    private function handleMigrationError($exception) {
        if ($this->migration_id) {
            $sql = "
                UPDATE full_migration_sessions
                SET status = 'failed', completed_at = NOW(), error_message = ?
                WHERE id = ?
            ";

            $stmt = $this->db_migration->prepare($sql);
            $stmt->execute([$exception->getMessage(), $this->migration_id]);
        }

        echo "❌ فشل النقل: " . $exception->getMessage() . "\n";
    }
}

// تشغيل النقل التلقائي إذا تم استدعاؤه من command line
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/config/database.php';

    echo "🚀 بدء النقل التلقائي الشامل...\n";
    echo "==========================================\n\n";

    try {
        $options = [
            'create_backup' => true,
            'batch_size' => 1000,
            'error_handling' => 'continue'
        ];

        $executor = new AutoMigrationExecutor($options);
        $result = $executor->executeFullMigration();

        if ($result['success']) {
            echo "\n✅ النقل التلقائي مكتمل بنجاح!\n";
            exit(0);
        } else {
            echo "\n❌ فشل النقل التلقائي\n";
            exit(1);
        }

    } catch (Exception $e) {
        echo "\n❌ خطأ في النقل التلقائي: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>

<?php
/**
 * محلل ومنسق النقل الشامل
 * Full Migration Analyzer & Coordinator
 */

require_once 'config/database.php';

class FullMigrationAnalyzer {

    private $db;
    private $erp_tables = [];
    private $platinum_tables = [];
    private $mapping_rules = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeMappingRules();
    }

    /**
     * تحليل شامل لقواعد البيانات
     */
    public function analyzeAll() {
        echo "🔍 بدء التحليل الشامل لقواعد البيانات...\n\n";

        // تحليل قاعدة ERP Operations
        echo "📊 تحليل قاعدة ERP Operations:\n";
        $this->erp_tables = $this->analyzeDatabase('erp_operations', 'erp_operations_back_Up.sql');

        // تحليل قاعدة Platinum
        echo "\n📊 تحليل قاعدة Platinum:\n";
        $this->platinum_tables = $this->analyzeDatabase('platinum', 'platinum_back_up.sql');

        // مقارنة الجداول
        echo "\n🔄 مقارنة البنية:\n";
        $comparison = $this->compareTables();

        // إنشاء خطة النقل
        echo "\n📋 إنشاء خطة النقل:\n";
        $migration_plan = $this->createMigrationPlan($comparison);

        return [
            'erp_tables' => $this->erp_tables,
            'platinum_tables' => $this->platinum_tables,
            'comparison' => $comparison,
            'migration_plan' => $migration_plan
        ];
    }

    /**
     * تحليل قاعدة بيانات من ملف SQL
     */
    private function analyzeDatabase($db_name, $sql_file) {
        $file_path = __DIR__ . "/../database/backUp/$sql_file";

        if (!file_exists($file_path)) {
            throw new Exception("ملف SQL غير موجود: $sql_file");
        }

        $content = file_get_contents($file_path);
        $tables = [];

        // استخراج جميع جداول CREATE TABLE
        preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\) ENGINE/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $table_name = $match[1];
            $table_definition = $match[2];

            echo "  ✓ $table_name\n";

            // تحليل الحقول
            $fields = $this->parseTableFields($table_definition);

            // الحصول على عدد السجلات
            $record_count = $this->getRecordCount($content, $table_name);

            $tables[$table_name] = [
                'fields' => $fields,
                'record_count' => $record_count,
                'indexes' => $this->extractIndexes($table_definition),
                'foreign_keys' => $this->extractForeignKeys($content, $table_name)
            ];
        }

        echo "  📈 إجمالي الجداول: " . count($tables) . "\n";
        return $tables;
    }

    /**
     * تحليل حقول الجدول
     */
    private function parseTableFields($table_definition) {
        $fields = [];
        $lines = explode("\n", $table_definition);

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^`([^`]+)` (.+)/', $line, $match)) {
                $field_name = $match[1];
                $field_definition = rtrim($match[2], ',');

                $fields[$field_name] = [
                    'definition' => $field_definition,
                    'type' => $this->extractFieldType($field_definition),
                    'nullable' => strpos($field_definition, 'NOT NULL') === false,
                    'default' => $this->extractDefault($field_definition),
                    'auto_increment' => strpos($field_definition, 'AUTO_INCREMENT') !== false
                ];
            }
        }

        return $fields;
    }

    /**
     * استخراج نوع الحقل
     */
    private function extractFieldType($definition) {
        if (preg_match('/^(\w+)(\([^)]+\))?/', $definition, $match)) {
            return $match[1] . ($match[2] ?? '');
        }
        return 'unknown';
    }

    /**
     * استخراج القيمة الافتراضية
     */
    private function extractDefault($definition) {
        if (preg_match('/DEFAULT ([^,\s]+)/', $definition, $match)) {
            return trim($match[1], "'\"");
        }
        return null;
    }

    /**
     * استخراج الفهارس
     */
    private function extractIndexes($table_definition) {
        $indexes = [];
        $lines = explode("\n", $table_definition);

        foreach ($lines as $line) {
            if (preg_match('/(PRIMARY KEY|UNIQUE|INDEX|KEY) .+\(([^)]+)\)/', $line, $match)) {
                $indexes[] = [
                    'type' => trim($match[1]),
                    'columns' => $match[2]
                ];
            }
        }

        return $indexes;
    }

    /**
     * استخراج المفاتيح الخارجية
     */
    private function extractForeignKeys($content, $table_name) {
        $foreign_keys = [];

        if (preg_match_all("/ALTER TABLE `$table_name`.*?ADD CONSTRAINT.*?FOREIGN KEY.*?\((.*?)\).*?REFERENCES `([^`]+)` \((.*?)\)/s", $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $foreign_keys[] = [
                    'local_column' => trim($match[1], '`'),
                    'referenced_table' => $match[2],
                    'referenced_column' => trim($match[3], '`')
                ];
            }
        }

        return $foreign_keys;
    }

    /**
     * حساب عدد السجلات في الجدول
     */
    private function getRecordCount($content, $table_name) {
        $pattern = "/INSERT INTO `$table_name`.*?VALUES/s";
        if (preg_match($pattern, $content)) {
            // حساب عدد قيم INSERT
            $insert_pattern = "/INSERT INTO `$table_name`.*?VALUES\s*\((.*?)\);/s";
            if (preg_match($insert_pattern, $content, $match)) {
                $values_section = $match[1];
                return substr_count($values_section, '),(') + 1;
            }
        }
        return 0;
    }

    /**
     * مقارنة الجداول بين القاعدتين
     */
    private function compareTables() {
        $comparison = [
            'identical' => [],      // جداول متطابقة
            'similar' => [],        // جداول متشابهة تحتاج تعديل
            'erp_only' => [],       // موجودة في ERP فقط
            'platinum_only' => [],  // موجودة في Platinum فقط
            'field_differences' => []
        ];

        // العثور على الجداول المشتركة والمختلفة
        $erp_names = array_keys($this->erp_tables);
        $platinum_names = array_keys($this->platinum_tables);

        $common_tables = array_intersect($erp_names, $platinum_names);
        $comparison['erp_only'] = array_diff($erp_names, $platinum_names);
        $comparison['platinum_only'] = array_diff($platinum_names, $erp_names);

        // تحليل الجداول المشتركة
        foreach ($common_tables as $table_name) {
            $erp_fields = array_keys($this->erp_tables[$table_name]['fields']);
            $platinum_fields = array_keys($this->platinum_tables[$table_name]['fields']);

            $missing_in_platinum = array_diff($erp_fields, $platinum_fields);
            $missing_in_erp = array_diff($platinum_fields, $erp_fields);

            if (empty($missing_in_platinum) && empty($missing_in_erp)) {
                $comparison['identical'][] = $table_name;
            } else {
                $comparison['similar'][] = $table_name;
                $comparison['field_differences'][$table_name] = [
                    'missing_in_platinum' => $missing_in_platinum,
                    'missing_in_erp' => $missing_in_erp
                ];
            }
        }

        return $comparison;
    }

    /**
     * إنشاء خطة النقل
     */
    private function createMigrationPlan($comparison) {
        $plan = [
            'phases' => [],
            'total_tables' => 0,
            'total_records' => 0,
            'estimated_duration' => 0
        ];

        // المرحلة 1: الجداول المرجعية
        $reference_tables = ['countries', 'cities', 'nationalities', 'religions'];
        $phase1_tables = array_intersect($reference_tables, array_keys($this->erp_tables));

        if (!empty($phase1_tables)) {
            $plan['phases'][] = [
                'name' => 'البيانات المرجعية',
                'order' => 1,
                'tables' => $phase1_tables,
                'description' => 'نقل البيانات المرجعية الأساسية'
            ];
        }

        // المرحلة 2: المستخدمون والإداريون
        $user_tables = ['users', 'admins'];
        $phase2_tables = array_intersect($user_tables, array_keys($this->erp_tables));

        if (!empty($phase2_tables)) {
            $plan['phases'][] = [
                'name' => 'المستخدمون والإداريون',
                'order' => 2,
                'tables' => $phase2_tables,
                'description' => 'نقل بيانات المستخدمين والإداريين'
            ];
        }

        // المرحلة 3: البيانات الأساسية
        $core_tables = ['maids', 'orders', 'contracts'];
        $phase3_tables = array_intersect($core_tables, array_keys($this->erp_tables));

        if (!empty($phase3_tables)) {
            $plan['phases'][] = [
                'name' => 'البيانات الأساسية',
                'order' => 3,
                'tables' => $phase3_tables,
                'description' => 'نقل العاملات والطلبات والعقود'
            ];
        }

        // المرحلة 4: البيانات المساعدة
        $support_tables = array_diff(
            array_keys($this->erp_tables),
            array_merge($reference_tables, $user_tables, $core_tables)
        );

        if (!empty($support_tables)) {
            $plan['phases'][] = [
                'name' => 'البيانات المساعدة',
                'order' => 4,
                'tables' => $support_tables,
                'description' => 'نقل باقي الجداول والبيانات المساعدة'
            ];
        }

        // حساب الإحصائيات
        foreach ($plan['phases'] as $phase) {
            $plan['total_tables'] += count($phase['tables']);
            foreach ($phase['tables'] as $table) {
                $plan['total_records'] += $this->erp_tables[$table]['record_count'] ?? 0;
            }
        }

        $plan['estimated_duration'] = ceil($plan['total_records'] / 1000) * 2; // تقدير: 2 دقيقة لكل 1000 سجل

        return $plan;
    }

    /**
     * قواعد التطابق بين الجداول
     */
    private function initializeMappingRules() {
        $this->mapping_rules = [
            // تطابق مباشر (نفس الاسم)
            'direct_mapping' => [
                'users' => 'users',
                'activity_log' => 'activity_log',
                'countries' => 'countries',
                'cities' => 'cities',
                'contracts' => 'contracts',
                'orders' => 'orders'
            ],

            // تطابق مع تحويل
            'conversion_mapping' => [
                'maids' => 'cvs',
                'user_addresses' => 'addresses',
                'admins' => 'staff'
            ],

            // جداول تحتاج إنشاء في Platinum
            'create_in_platinum' => [
                'about_us',
                'about_us_translations',
                'sliders',
                'sliders_translations',
                'services',
                'services_translations',
                'complaints',
                'complaint_types',
                'deportation_procedures'
            ],

            // حقول تحتاج إضافة
            'field_additions' => [
                'users' => [
                    'first_name' => 'VARCHAR(255) NULL',
                    'last_name' => 'VARCHAR(255) NULL',
                    'national_num' => 'VARCHAR(50) NULL',
                    'phone2' => 'VARCHAR(50) NULL'
                ],
                'cvs' => [
                    'name' => 'VARCHAR(255) NULL',
                    'nationality_id' => 'INT NULL',
                    'religion_id' => 'INT NULL',
                    'experience_years' => 'VARCHAR(255) NULL',
                    'experience_status' => 'ENUM("has_experience","no_experience") DEFAULT "no_experience"'
                ]
            ]
        ];
    }

    /**
     * إنشاء سكريبت مزامنة البنية
     */
    public function generateSchemaSync() {
        $sql = "-- =================================================================\n";
        $sql .= "-- مزامنة بنية قاعدة البيانات - Schema Synchronization\n";
        $sql .= "-- =================================================================\n\n";

        $sql .= "USE platinum;\n\n";

        // إضافة الجداول المفقودة
        foreach ($this->mapping_rules['create_in_platinum'] as $table_name) {
            if (isset($this->erp_tables[$table_name])) {
                $sql .= $this->generateCreateTableSQL($table_name, $this->erp_tables[$table_name]);
            }
        }

        // إضافة الحقول المفقودة
        foreach ($this->mapping_rules['field_additions'] as $table_name => $fields) {
            $sql .= "-- إضافة حقول مفقودة للجدول $table_name\n";
            foreach ($fields as $field_name => $field_definition) {
                $sql .= "ALTER TABLE `$table_name` ADD COLUMN `$field_name` $field_definition;\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }

    /**
     * إنشاء SQL لإنشاء جدول
     */
    private function generateCreateTableSQL($table_name, $table_info) {
        $sql = "-- إنشاء جدول $table_name\n";
        $sql .= "CREATE TABLE IF NOT EXISTS `$table_name` (\n";

        $field_definitions = [];
        foreach ($table_info['fields'] as $field_name => $field_info) {
            $field_definitions[] = "  `$field_name` " . $field_info['definition'];
        }

        $sql .= implode(",\n", $field_definitions) . "\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

        return $sql;
    }

    /**
     * إنشاء سكريبت النقل الشامل
     */
    public function generateFullMigrationScript() {
        $sql = "-- =================================================================\n";
        $sql .= "-- سكريبت النقل الشامل - Full Data Migration Script\n";
        $sql .= "-- من erp_operations_new إلى platinum\n";
        $sql .= "-- =================================================================\n\n";

        // إعدادات أولية
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n\n";

        // إنشاء جدول تتبع النقل
        $sql .= $this->generateTrackingTable();

        // النقل حسب المراحل
        $analysis = $this->analyzeAll();

        foreach ($analysis['migration_plan']['phases'] as $phase) {
            $sql .= "-- =================================================================\n";
            $sql .= "-- المرحلة {$phase['order']}: {$phase['name']}\n";
            $sql .= "-- =================================================================\n\n";

            foreach ($phase['tables'] as $table_name) {
                $sql .= $this->generateTableMigrationSQL($table_name);
            }
        }

        // إنهاء المعاملة
        $sql .= "-- إنهاء النقل\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sql .= "COMMIT;\n\n";

        $sql .= "SELECT 'تم إكمال النقل الشامل بنجاح!' as migration_status;\n";

        return $sql;
    }

    /**
     * إنشاء جدول تتبع النقل
     */
    private function generateTrackingTable() {
        return "
-- جدول تتبع عملية النقل الشامل
CREATE TABLE IF NOT EXISTS `full_migration_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phase_name` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) NOT NULL,
    `source_records` INT DEFAULT 0,
    `migrated_records` INT DEFAULT 0,
    `failed_records` INT DEFAULT 0,
    `status` ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

";
    }

    /**
     * إنشاء SQL نقل جدول محدد
     */
    private function generateTableMigrationSQL($table_name) {
        if (!isset($this->erp_tables[$table_name])) {
            return "-- تخطي الجدول $table_name - غير موجود في ERP\n\n";
        }

        $sql = "-- نقل جدول $table_name\n";
        $sql .= "INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('{$this->getCurrentPhase($table_name)}', '$table_name', 'in_progress', NOW());\n\n";

        // اختيار استراتيجية النقل
        if (in_array($table_name, ['users', 'maids', 'contracts', 'orders'])) {
            $sql .= $this->generateComplexMigrationSQL($table_name);
        } else {
            $sql .= $this->generateSimpleMigrationSQL($table_name);
        }

        $sql .= "-- تحديث حالة النقل\n";
        $sql .= "UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT() WHERE table_name = '$table_name' AND status = 'in_progress';\n\n";

        return $sql;
    }

    /**
     * نقل معقد للجداول الأساسية
     */
    private function generateComplexMigrationSQL($table_name) {
        switch ($table_name) {
            case 'users':
                return $this->generateUsersMigrationSQL();

            case 'maids':
                return $this->generateMaidsMigrationSQL();

            case 'contracts':
                return $this->generateContractsMigrationSQL();

            case 'orders':
                return $this->generateOrdersMigrationSQL();

            default:
                return $this->generateSimpleMigrationSQL($table_name);
        }
    }

    /**
     * نقل بسيط للجداول العادية
     */
    private function generateSimpleMigrationSQL($table_name) {
        // تحديد الجدول المستهدف
        $target_table = $this->mapping_rules['conversion_mapping'][$table_name] ?? $table_name;

        $sql = "-- نقل بسيط للجدول $table_name → $target_table\n";

        // التحقق من وجود الجدول المستهدف
        $sql .= "INSERT IGNORE INTO `$target_table` \n";
        $sql .= "SELECT * FROM erp_operations_new.`$table_name`\n";
        $sql .= "WHERE NOT EXISTS (\n";
        $sql .= "    SELECT 1 FROM `$target_table` p \n";
        $sql .= "    WHERE p.id = erp_operations_new.`$table_name`.id\n";
        $sql .= ");\n\n";

        return $sql;
    }

    /**
     * نقل المستخدمين
     */
    private function generateUsersMigrationSQL() {
        return "
-- نقل المستخدمين مع تحويل البيانات
INSERT INTO users (
    name, first_name, last_name, phone, email, password,
    address, city, country, national_num, phone2,
    nationality_id, created_at, updated_at
)
SELECT
    COALESCE(name, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) as name,
    first_name,
    last_name,
    CASE
        WHEN phone LIKE '+966%' THEN SUBSTRING(phone, 5)
        WHEN phone LIKE '966%' THEN SUBSTRING(phone, 4)
        ELSE phone
    END as phone,
    email,
    password,
    address,
    city,
    'Saudi Arabia' as country,
    national_num,
    phone2,
    nationality_id,
    created_at,
    updated_at
FROM erp_operations_new.users u
WHERE NOT EXISTS (
    SELECT 1 FROM users pu
    WHERE pu.email = u.email OR pu.phone = u.phone
)
AND (u.email IS NOT NULL OR u.phone IS NOT NULL);

";
    }

    /**
     * نقل العاملات إلى CVs
     */
    private function generateMaidsMigrationSQL() {
        return "
-- نقل العاملات إلى جدول CVs
INSERT INTO cvs (
    passport_id, salary, service_price, national_id,
    region_id, age_id, cv_file, personal_image,
    cv_rev_type, created_at, updated_at
)
SELECT
    passport_num as passport_id,
    salary,
    price as service_price,
    nationality_id as national_id,
    religion_id as region_id,
    CASE
        WHEN age <= 25 THEN 1
        WHEN age <= 35 THEN 2
        WHEN age <= 45 THEN 3
        ELSE 4
    END as age_id,
    cv_file,
    cv_preview as personal_image,
    1 as cv_rev_type,
    created_at,
    updated_at
FROM erp_operations_new.maids m
WHERE passport_num IS NOT NULL
AND passport_num != ''
AND NOT EXISTS (
    SELECT 1 FROM cvs c
    WHERE c.passport_id = m.passport_num
);

";
    }

    /**
     * نقل العقود
     */
    private function generateContractsMigrationSQL() {
        return "
-- نقل العقود (مبسط - يحتاج تخصيص حسب متطلبات العمل)
-- هذا مثال أولي ويحتاج مراجعة

/*
INSERT INTO contracts (
    resv_id, date_contract, contract_cost,
    contract_total, created_at, updated_at
)
SELECT
    order_id as resv_id,
    completed_at as date_contract,
    0 as contract_cost,  -- يحتاج تحديد القيمة
    0 as contract_total, -- يحتاج تحديد القيمة
    created_at,
    updated_at
FROM erp_operations_new.contracts c
WHERE order_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM contracts pc
    WHERE pc.resv_id = c.order_id
);
*/

-- ملاحظة: جدول العقود يحتاج مراجعة دقيقة للبنية
-- لأن الاختلاف كبير بين النظامين

";
    }

    /**
     * نقل الطلبات
     */
    private function generateOrdersMigrationSQL() {
        return "
-- نقل الطلبات مع ربط المعرفات
INSERT INTO orders (
    order_code, status, user_id, biography_id,
    order_date, created_at, updated_at
)
SELECT
    o.order_code,
    CASE
        WHEN o.status = 'new' THEN 'new'
        WHEN o.status = 'completed' THEN 'finished'
        WHEN o.status = 'cancelled' THEN 'canceled'
        ELSE 'new'
    END as status,
    -- ربط معرف المستخدم الجديد
    (SELECT pu.id FROM users pu WHERE pu.email = u.email OR pu.phone = u.phone LIMIT 1) as user_id,
    -- ربط معرف السيرة الذاتية
    (SELECT pc.id FROM cvs pc WHERE pc.passport_id = m.passport_num LIMIT 1) as biography_id,
    o.created_at as order_date,
    o.created_at,
    o.updated_at
FROM erp_operations_new.orders o
LEFT JOIN erp_operations_new.users u ON u.id = o.user_id
LEFT JOIN erp_operations_new.maids m ON m.id = o.maid_id
WHERE o.order_code IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM orders po
    WHERE po.order_code = o.order_code
);

";
    }

    /**
     * الحصول على المرحلة الحالية للجدول
     */
    private function getCurrentPhase($table_name) {
        $reference_tables = ['countries', 'cities', 'nationalities', 'religions'];
        $user_tables = ['users', 'admins'];
        $core_tables = ['maids', 'orders', 'contracts'];

        if (in_array($table_name, $reference_tables)) return 'البيانات المرجعية';
        if (in_array($table_name, $user_tables)) return 'المستخدمون والإداريون';
        if (in_array($table_name, $core_tables)) return 'البيانات الأساسية';

        return 'البيانات المساعدة';
    }

    /**
     * تصدير التحليل إلى JSON
     */
    public function exportAnalysisToJson($filename = null) {
        $filename = $filename ?: 'migration_analysis_' . date('Y_m_d_H_i_s') . '.json';

        $analysis = $this->analyzeAll();

        file_put_contents($filename, json_encode($analysis, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $filename;
    }
}

// تشغيل التحليل إذا تم استدعاء الملف مباشرة
if (php_sapi_name() === 'cli') {
    try {
        $analyzer = new FullMigrationAnalyzer();

        echo "🚀 بدء التحليل الشامل لعملية النقل...\n";
        echo "==========================================\n\n";

        $analysis = $analyzer->analyzeAll();

        echo "\n📋 ملخص التحليل:\n";
        echo "==========================================\n";
        echo "جداول ERP: " . count($analysis['erp_tables']) . "\n";
        echo "جداول Platinum: " . count($analysis['platinum_tables']) . "\n";
        echo "جداول متطابقة: " . count($analysis['comparison']['identical']) . "\n";
        echo "جداول متشابهة: " . count($analysis['comparison']['similar']) . "\n";
        echo "جداول ERP فقط: " . count($analysis['comparison']['erp_only']) . "\n";
        echo "جداول Platinum فقط: " . count($analysis['comparison']['platinum_only']) . "\n";
        echo "إجمالي السجلات: " . number_format($analysis['migration_plan']['total_records']) . "\n";
        echo "الوقت المقدر: " . $analysis['migration_plan']['estimated_duration'] . " دقيقة\n";

        // حفظ التحليل
        $json_file = $analyzer->exportAnalysisToJson();
        echo "\n💾 تم حفظ التحليل في: $json_file\n";

        // إنشاء سكريبتات النقل
        echo "\n📝 إنشاء سكريبتات النقل...\n";

        $schema_sql = $analyzer->generateSchemaSync();
        file_put_contents('schema_sync.sql', $schema_sql);
        echo "✓ schema_sync.sql\n";

        $migration_sql = $analyzer->generateFullMigrationScript();
        file_put_contents('full_migration.sql', $migration_sql);
        echo "✓ full_migration.sql\n";

        echo "\n🎉 التحليل مكتمل! الملفات جاهزة للاستخدام.\n";

    } catch (Exception $e) {
        echo "❌ خطأ: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>

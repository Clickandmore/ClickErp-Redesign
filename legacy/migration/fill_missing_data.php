<?php
/**
 * ملء البيانات المفقودة من CSV
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'POST only']));
}

try {
    // الاتصال بالقاعدتين
    $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root',
                     [PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='NO_ZERO_DATE'"]);
    $newDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root',
                     [PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='NO_ZERO_DATE'"]);

    $updatedCount = 0;

    // جلب البيانات من القاعدة القديمة (بدون phone_verified_at لتجنب المشاكل)
    $oldUsers = $oldDb->query("
        SELECT id, user_type, verification_code,
               token, avatar, country, region, whatsapp_phone, banned
        FROM users
    ")->fetchAll(PDO::FETCH_ASSOC);

    $newDb->beginTransaction();

    $errorLog = [];

    foreach ($oldUsers as $user) {
        $updateParts = [];
        $params = [];

        // نقل البيانات الناقصة
        if (!empty($user['user_type'])) {
            $updateParts[] = "user_type = ?";
            $params[] = $user['user_type'];
        }

        // تخطي phone_verified_at لتجنب مشاكل التواريخ

        if (!empty($user['verification_code'])) {
            $updateParts[] = "verification_code = ?";
            $params[] = $user['verification_code'];
        }

        if (!empty($user['token'])) {
            $updateParts[] = "token = ?";
            $params[] = $user['token'];
        }

        if (!empty($user['avatar'])) {
            $updateParts[] = "avatar = ?";
            $params[] = $user['avatar'];
        }

        if (!empty($user['country'])) {
            $updateParts[] = "country = ?";
            $params[] = $user['country'];
        }

        if (!empty($user['region'])) {
            $updateParts[] = "region = ?";
            $params[] = $user['region'];
        }

        // whatsapp_phone → phone2
        if (!empty($user['whatsapp_phone'])) {
            $updateParts[] = "phone2 = ?";
            $params[] = $user['whatsapp_phone'];
        }

        // banned → is_blocked
        if (isset($user['banned'])) {
            $updateParts[] = "is_blocked = ?";
            $params[] = (int)$user['banned'];
        }

        if (!empty($updateParts)) {
            try {
                $params[] = $user['id'];
                $sql = "UPDATE users SET " . implode(', ', $updateParts) . " WHERE id = ?";
                $stmt = $newDb->prepare($sql);
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    $updatedCount++;
                }
            } catch (Exception $e) {
                $errorLog[] = "خطأ في المستخدم {$user['id']}: " . $e->getMessage();
                // متابعة بدون إيقاف
            }
        }
    }

    // جلب بيانات Customers ودمجها (تحقق من وجود الجدول أولاً)
    $customersUpdated = 0;

    try {
        $tableCheck = $oldDb->query("SHOW TABLES LIKE 'customers'");
        if ($tableCheck->rowCount() > 0) {
            $customers = $oldDb->query("
                SELECT user_id, national_identification_number, other_phone,
                       job, birth_date, rooms_number, family_number, childrens_under_age
                FROM customers
                WHERE birth_date IS NULL OR birth_date != ''
                  AND birth_date != '0000-00-00'
            ")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $customers = [];
        }
    } catch (Exception $e) {
        $customers = [];
        $errorLog[] = "خطأ في جلب customers: " . $e->getMessage();
    }

    foreach ($customers as $customer) {
        if (empty($customer['user_id'])) continue;

        $updateParts = [];
        $params = [];

        if (!empty($customer['national_identification_number'])) {
            $updateParts[] = "national_num = ?";
            $params[] = $customer['national_identification_number'];
        }

        if (!empty($customer['other_phone']) && empty($user['whatsapp_phone'])) {
            $updateParts[] = "phone2 = ?";
            $params[] = $customer['other_phone'];
        }

        if (!empty($customer['job'])) {
            $updateParts[] = "job = ?";
            $params[] = $customer['job'];
            $updateParts[] = "profession = ?";
            $params[] = $customer['job'];
        }

        if (!empty($customer['birth_date'])) {
            $updateParts[] = "birthdate = ?";
            $params[] = $customer['birth_date'];
        }

        if (!empty($customer['rooms_number'])) {
            $updateParts[] = "rooms_num = ?";
            $params[] = $customer['rooms_number'];
        }

        if (!empty($customer['family_number'])) {
            $updateParts[] = "family_num = ?";
            $params[] = $customer['family_number'];
        }

        if (!empty($customer['childrens_under_age'])) {
            $updateParts[] = "children = ?";
            $params[] = $customer['childrens_under_age'];
        }

        if (!empty($updateParts)) {
            try {
                $params[] = $customer['user_id'];
                $sql = "UPDATE users SET " . implode(', ', $updateParts) . " WHERE id = ?";
                $stmt = $newDb->prepare($sql);
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    $customersUpdated++;
                }
            } catch (Exception $e) {
                $errorLog[] = "خطأ في customer للمستخدم {$customer['user_id']}: " . $e->getMessage();
                // متابعة بدون إيقاف
            }
        }
    }

    $newDb->commit();

    // إحصائيات محدثة
    $stats = [];
    $columns = ['user_type', 'phone_verified_at', 'token', 'national_num', 'phone2', 'job'];
    foreach ($columns as $col) {
        $filledCount = $newDb->query("SELECT COUNT(*) FROM users WHERE `$col` IS NOT NULL AND `$col` != ''")->fetchColumn();
        $stats[$col] = $filledCount;
    }

    echo json_encode([
        'success' => true,
        'users_updated' => $updatedCount,
        'customers_merged' => $customersUpdated,
        'column_stats' => $stats,
        'errors' => $errorLog,
        'message' => "✅ تم ملء البيانات المفقودة!\nUsers: $updatedCount\nCustomers: $customersUpdated"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($newDb) && $newDb->inTransaction()) {
        $newDb->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

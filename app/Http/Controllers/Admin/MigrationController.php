<?php

namespace App\Http\Controllers\admin;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;

class MigrationController extends Controller
{
    /**
     * عرض واجهة نظام النقل
     */
    public function index()
    {
        return view('migration.index');
    }

    /**
     * فحص حالة النظام
     */
    public function checkStatus()
    {
        try {
            // فحص قاعدة البيانات الجديدة
            $newUsersCount = DB::table('users')->count();

            // فحص قاعدة البيانات القديمة
            $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');
            $oldUsersCount = $oldDb->query("SELECT COUNT(*) FROM users")->fetchColumn();

            return response()->json([
                'success' => true,
                'new_database_users' => $newUsersCount,
                'old_database_users' => $oldUsersCount,
                'message' => "القاعدة الجديدة: $newUsersCount مستخدم، القديمة: $oldUsersCount مستخدم"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في فحص الحالة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * فحص الأعمدة المفقودة
     */
    public function scanColumns()
    {
        try {
            // الحصول على أعمدة القاعدة القديمة
            $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');
            $oldColumns = $oldDb->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);

            // الحصول على أعمدة القاعدة الجديدة
            $newColumns = collect(DB::select("DESCRIBE users"))->pluck('Field')->toArray();

            // تحديد الأعمدة المفقودة
            $missingColumns = array_diff($oldColumns, $newColumns);

            return response()->json([
                'success' => true,
                'old_columns' => $oldColumns,
                'new_columns' => $newColumns,
                'missing_columns' => array_values($missingColumns),
                'missing_count' => count($missingColumns),
                'message' => 'تم فحص الأعمدة - يوجد ' . count($missingColumns) . ' عمود مفقود'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في فحص الأعمدة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إضافة جميع الأعمدة المفقودة
     */
    public function addMissingColumns()
    {
        try {
            // فحص الأعمدة المفقودة أولاً
            $scanResult = $this->scanColumns();
            $scanData = $scanResult->getData(true);

            if (!$scanData['success']) {
                throw new Exception($scanData['message']);
            }

            $missingColumns = $scanData['missing_columns'];
            $addedColumns = [];

            DB::beginTransaction();

            foreach ($missingColumns as $column) {
                try {
                    $columnType = $this->guessColumnType($column);
                    DB::statement("ALTER TABLE users ADD COLUMN `$column` $columnType");
                    $addedColumns[] = $column;
                } catch (Exception $e) {
                    // تسجيل الخطأ ومتابعة
                    \Log::warning("خطأ في إضافة العمود $column: " . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'added_columns' => $addedColumns,
                'added_count' => count($addedColumns),
                'message' => "تم إضافة " . count($addedColumns) . " عمود من أصل " . count($missingColumns)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطأ في إضافة الأعمدة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * النقل الشامل للمستخدمين فقط (حسب SYSTEM_STATUS.md)
     */
    public function migrateAllUsers()
    {
        try {
            // الاتصال بالقاعدة القديمة
            $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');

            // الحصول على المستخدمين فقط (بدون العاملات)
            $oldUsers = $oldDb->query("SELECT * FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($oldUsers)) {
                throw new Exception('لا توجد مستخدمين في القاعدة القديمة');
            }

            // الحصول على أعمدة القاعدة الجديدة
            $newColumns = collect(DB::select("DESCRIBE users"))->pluck('Field')->toArray();

            DB::beginTransaction();

            $insertedCount = 0;
            $updatedCount = 0;
            $namesSplit = 0;
            $skippedCount = 0;

            foreach ($oldUsers as $oldUser) {
                try {
                    // تحضير بيانات المستخدم حسب SYSTEM_STATUS.md
                    $userData = [];

                    // البيانات المطابقة تماماً
                    $userData['id'] = $oldUser['id'];
                    $userData['email'] = $oldUser['email'] ?? null;
                    $userData['phone'] = $oldUser['phone'] ?? null;
                    $userData['password'] = $oldUser['password'] ?? null;
                    $userData['remember_token'] = $oldUser['remember_token'] ?? null;
                    $userData['created_at'] = $oldUser['created_at'] ?? null;
                    $userData['updated_at'] = $oldUser['updated_at'] ?? null;
                    $userData['address'] = $oldUser['address'] ?? null;
                    $userData['city'] = $oldUser['city'] ?? null;

                    // البيانات الجديدة (تقسيم name)
                    if (!empty($oldUser['name'])) {
                        $nameParts = explode(' ', trim($oldUser['name']), 2);
                        $userData['name'] = $oldUser['name'];
                        $userData['first_name'] = $nameParts[0] ?? '';
                        $userData['last_name'] = $nameParts[1] ?? '';
                        $namesSplit++;
                    }

                    // البيانات بأسماء مختلفة
                    $userData['is_blocked'] = isset($oldUser['banned']) ? (int)$oldUser['banned'] : 0;
                    $userData['status'] = isset($oldUser['banned']) ? (1 - (int)$oldUser['banned']) : 1;
                    $userData['phone2'] = $oldUser['whatsapp_phone'] ?? null;

                    // البيانات الناقصة (إذا كانت موجودة)
                    $userData['user_type'] = $oldUser['user_type'] ?? null;
                    $userData['phone_verified_at'] = $oldUser['phone_verified_at'] ?? null;
                    $userData['verification_code'] = $oldUser['verification_code'] ?? null;
                    $userData['token'] = $oldUser['token'] ?? null;
                    $userData['nationality_id'] = $oldUser['nationality_id'] ?? null;

                    // القيم الافتراضية
                    $userData['register_from'] = 'migration';
                    $userData['phone_code'] = '+966';

                    // تنظيف البيانات - إزالة الأعمدة غير الموجودة
                    $finalUserData = [];
                    foreach ($userData as $column => $value) {
                        if (in_array($column, $newColumns)) {
                            $finalUserData[$column] = $value;
                        }
                    }

                    // تحديد ما إذا كان المستخدم موجود
                    $existingUser = DB::table('users')->where('id', $oldUser['id'])->first();

                    if ($existingUser) {
                        // تحديث المستخدم الموجود
                        DB::table('users')->where('id', $oldUser['id'])->update($finalUserData);
                        $updatedCount++;
                    } else {
                        // إدراج مستخدم جديد
                        DB::table('users')->insert($finalUserData);
                        $insertedCount++;
                    }

                } catch (Exception $e) {
                    $skippedCount++;
                    \Log::warning("تم تخطي المستخدم {$oldUser['id']}: " . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'total_processed' => count($oldUsers),
                'inserted_users' => $insertedCount,
                'updated_users' => $updatedCount,
                'skipped_users' => $skippedCount,
                'names_split' => $namesSplit,
                'message' => "✅ نقل المستخدمين مكتمل!\n📊 إجمالي: " . count($oldUsers) . "\n➕ جديد: $insertedCount\n🔄 محدث: $updatedCount\n⏭️ تخطي: $skippedCount\n📝 أسماء مقسمة: $namesSplit"
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطأ في نقل المستخدمين: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * تحضير بيانات المستخدم للنقل
     */
    private function prepareUserData($oldUser, $newColumns)
    {
        $userData = [];

        foreach ($newColumns as $column) {
            if ($column === 'id') {
                $userData['id'] = $oldUser['id'];
            } elseif ($column === 'first_name' || $column === 'last_name') {
                // سيتم تعيينهما في الدالة الرئيسية
                continue;
            } elseif ($column === 'register_from') {
                // سيتم تعيينه في الدالة الرئيسية
                continue;
            } elseif ($column === 'is_blocked') {
                // تحويل banned إلى is_blocked
                $userData['is_blocked'] = isset($oldUser['banned']) ? (int)$oldUser['banned'] : 0;
            } elseif ($column === 'status') {
                // عكس banned
                $userData['status'] = isset($oldUser['banned']) ? (1 - (int)$oldUser['banned']) : 1;
            } elseif ($column === 'phone2') {
                // من whatsapp_phone أو other_phone
                $userData['phone2'] = $oldUser['whatsapp_phone'] ?? $oldUser['other_phone'] ?? null;
            } elseif ($column === 'national_num') {
                // من national_identification_number
                $userData['national_num'] = $oldUser['national_identification_number'] ?? null;
            } elseif (isset($oldUser[$column])) {
                $userData[$column] = $oldUser[$column];
            } else {
                $userData[$column] = null;
            }
        }

        return $userData;
    }

    /**
     * تخمين نوع العمود
     */
    private function guessColumnType($columnName)
    {
        $columnName = strtolower($columnName);

        if (strpos($columnName, '_at') !== false) {
            return 'TIMESTAMP NULL';
        } elseif (strpos($columnName, '_id') !== false) {
            return 'BIGINT UNSIGNED NULL';
        } elseif (strpos($columnName, 'phone') !== false) {
            return 'VARCHAR(50) NULL';
        } elseif (strpos($columnName, 'email') !== false) {
            return 'VARCHAR(255) NULL';
        } elseif (in_array($columnName, ['first_name', 'last_name', 'name', 'token'])) {
            return 'VARCHAR(255) NULL';
        } elseif (in_array($columnName, ['user_type', 'verification_code'])) {
            return 'TEXT NULL';
        } elseif (strpos($columnName, 'banned') !== false || strpos($columnName, 'blocked') !== false) {
            return 'TINYINT DEFAULT 0';
        } elseif (strpos($columnName, 'balance') !== false) {
            return 'DECIMAL(10,2) DEFAULT 0.00';
        } else {
            return 'VARCHAR(255) NULL';
        }
    }

    /**
     * تطبيق ترتيب الجدول المخصص
     */
    public function applyCustomOrder()
    {
        try {
            $orderingSteps = [
                "ALTER TABLE `users` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL auto_increment FIRST",
                "ALTER TABLE `users` CHANGE `name` `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`",
                "ALTER TABLE `users` CHANGE `first_name` `first_name` VARCHAR(255) NULL DEFAULT NULL AFTER `name`",
                "ALTER TABLE `users` CHANGE `last_name` `last_name` VARCHAR(255) NULL DEFAULT NULL AFTER `first_name`",
                "ALTER TABLE `users` CHANGE `phone` `phone` VARCHAR(255) NULL DEFAULT NULL AFTER `last_name`",
                "ALTER TABLE `users` CHANGE `email` `email` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`",
                "ALTER TABLE `users` CHANGE `register_from` `register_from` VARCHAR(255) NOT NULL DEFAULT 'web' AFTER `email`",
                "ALTER TABLE `users` CHANGE `national_num` `national_num` VARCHAR(50) NULL DEFAULT NULL AFTER `register_from`",
                "ALTER TABLE `users` CHANGE `phone2` `phone2` VARCHAR(50) NULL DEFAULT NULL AFTER `national_num`",
                "ALTER TABLE `users` CHANGE `job` `job` VARCHAR(255) NULL DEFAULT NULL AFTER `phone2`"
            ];

            $appliedSteps = 0;

            foreach ($orderingSteps as $sql) {
                try {
                    DB::statement($sql);
                    $appliedSteps++;
                } catch (Exception $e) {
                    \Log::warning("خطأ في خطوة الترتيب: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'applied_steps' => $appliedSteps,
                'total_steps' => count($orderingSteps),
                'message' => "تم تطبيق $appliedSteps خطوة ترتيب من أصل " . count($orderingSteps)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تطبيق الترتيب: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * رفع ودمج جدول Customers (للخطوة التالية)
     */
    public function uploadCustomers(Request $request)
    {
        try {
            if (!$request->hasFile('customers_file')) {
                throw new Exception('يرجى رفع ملف Customers');
            }

            $file = $request->file('customers_file');
            $csvData = array_map('str_getcsv', file($file->path()));
            $headers = array_shift($csvData); // أول سطر = العناوين

            $processedCount = 0;
            $updatedUsers = 0;
            $skippedRows = 0;

            DB::beginTransaction();

            foreach ($csvData as $row) {
                try {
                    $customerData = array_combine($headers, $row);

                    // البحث عن المستخدم بناءً على user_id
                    $userId = $customerData['user_id'] ?? null;

                    if (!$userId) {
                        $skippedRows++;
                        continue;
                    }

                    // تحضير البيانات للدمج في جدول users
                    $updateData = [];

                    // تطبيق التحويلات من SYSTEM_STATUS.md
                    if (isset($customerData['national_identification_number'])) {
                        $updateData['national_num'] = $customerData['national_identification_number'];
                    }

                    if (isset($customerData['other_phone'])) {
                        $updateData['phone2'] = $customerData['other_phone'];
                    }

                    if (isset($customerData['job'])) {
                        $updateData['job'] = $customerData['job'];
                        $updateData['profession'] = $customerData['job']; // نفس القيمة
                    }

                    if (isset($customerData['birth_date'])) {
                        $updateData['birthdate'] = $customerData['birth_date'];
                    }

                    if (isset($customerData['rooms_number'])) {
                        $updateData['rooms_num'] = $customerData['rooms_number'];
                    }

                    if (isset($customerData['family_number'])) {
                        $updateData['family_num'] = $customerData['family_number'];
                    }

                    if (isset($customerData['childrens_under_age'])) {
                        $updateData['children'] = $customerData['childrens_under_age'];
                    }

                    // تحديث المستخدم
                    $updated = DB::table('users')->where('id', $userId)->update($updateData);

                    if ($updated) {
                        $updatedUsers++;
                    }

                    $processedCount++;

                } catch (Exception $e) {
                    $skippedRows++;
                    \Log::warning("تم تخطي سطر Customers: " . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'processed_rows' => $processedCount,
                'updated_users' => $updatedUsers,
                'skipped_rows' => $skippedRows,
                'message' => "✅ تم دمج بيانات Customers!\n📊 معالج: $processedCount سطر\n🔄 محدث: $updatedUsers مستخدم\n⏭️ تخطي: $skippedRows سطر"
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطأ في رفع Customers: ' . $e->getMessage()
            ]);
        }
    }
}

### ✅ البيانات الموجودة بنفس الاسم (مطابقة تماماً):
| الحقل | النوع القديم | النوع الجديد | الحالة |
|-------|--------------|--------------|---------|
| `id` | int | bigint | ✅ تحويل نوع البيانات |
| `name` | varchar(191) | varchar(255) | ✅ مطابق |
| `email` | varchar(191) | varchar(255) | ✅ مطابق |
| `phone` | varchar(20) | varchar(255) | ✅ مطابق |
| `password` | varchar(191) | varchar(255) | ✅ مطابق |
| `remember_token` | varchar(100) | varchar(100) | ✅ مطابق تماماً |
| `created_at` | timestamp | timestamp | ✅ مطابق تماماً |
| `updated_at` | timestamp | timestamp | ✅ مطابق تماماً |
| `nationality_id` | varchar(255) | bigint | ⚠️ تحويل من varchar إلى bigint |
| `address` | varchar(300) | varchar(500) | ✅ مطابق |
| `city` | varchar(30) | varchar(30) | ⚠️  مطابق |

### 🔄 البيانات الموجودة بأسماء مختلفة:
| القديم | الجديد | المصدر | التحويل المطلوب |
|--------|--------|--------|------------------|
| `banned` | `is_blocked` | users | 0/1 → 0/1 |
| - | `status` | - | عكس banned (إذا banned=1 فإن status=0) |
| `whatsapp_phone` | `phone2` | users | نسخ مباشر |

### ❌ البيانات الناقصة في new (موجودة في old):
| الحقل القديم | النوع | الاستخدام | خطة التعامل |
|-------------|-------|----------|-------------|
| `old_id` | int | ربط النظام القديم | ❌ حذف -  غير متوفر |
| `referred_by` | int | نظام الإحالات | ❌ حذف - غير متوفر في new |
| `provider_id` | varchar(50) | تسجيل دخول خارجي | ❌ حذف - غير متوفر |
| `user_type` | varchar(20) | نوع المستخدم | ❌ نقل مطلوب |
| `phone_verified_at` | timestamp |تأكيد الهاتف | ❌ نقل  |
| `verification_code` | text | رمز التحقق | ❌  نقل  |
| `login_code` | int | رمز الدخول | ❌ حذف - غير متوفر |
| `code_verified_at` | timestamp | تأكيد الرمز | ❌ حذف - غير متوفر |
| `email_verificiation_code` | text | رمز البريد | ❌ حذف - غير متوفر |
| `token` | text | رمز المصادقة | ❌ نقل  -  متوفر |
| `confirm_link_expire` | timestamp | انتهاء الرابط | ❌ نقل - غير متوفر |
| `avatar` | varchar(256) | صورة المستخدم | ❌ نقل -  متوفر  new |
| `avatar_original` | varchar(256) | الصورة الأصلية | ❌ حذف - غير متوفر |
| `country` | varchar(30) | الدولة | ❌ نقل -  متوفر  new |
| `region` | varchar(255) | المنطقة | ❌ نقل -  متوفر |
| `postal_code` | varchar(20) | الرمز البريدي | ❌ نقل - غير متوفر |
| `balance` | double(20,2) | الرصيد | ❌ نقل - غير متوفر |
| `referral_code` | varchar(255) | رمز الإحالة | ❌ نقل -  متوفر |
| `customer_package_id` | int | باقة العميل | ❌  -  متوفر |
| `remaining_uploads` | int | الرفوعات المتبقية | ❌  -  متوفر |
| `merge_office` | varchar(256) | مكتب الدمج | ❌ حذف - غير متوفر |

### ➕ البيانات الجديدة (موجودة في new وغير موجودة في old):
| الحقل الجديد | النوع | مصدر البيانات | القيمة الافتراضية |
|-------------|-------|---------------|------------------|
| `first_name` | varchar(255) | تقسيم `name` | الكلمة الأولى من name |
| `last_name` | varchar(255) | تقسيم `name` | باقي name |
| `phone_code` | varchar(255) | استخراج من `phone` | '+966' افتراضي |
| `register_from` | varchar(255) | - | 'migration' |
| `is_blocked` | tinyint | من `banned` | عكس banned |
| `status` | tinyint | من `banned` | عكس banned |
| `birthdate` | date | من customers.birth_date | تحويل التنسيق |
| `national_num` | varchar(50) | من customers.national_identification_number | نسخ مباشر |
| `phone2` | varchar(50) | من customers.other_phone أو whatsapp_phone | نسخ مباشر |
| `rooms_num` | varchar(10) | من customers.rooms_number | نسخ مباشر |
| `family_num` | varchar(10) | من customers.family_number | نسخ مباشر |
| `children` | varchar(10) | من customers.childrens_under_age | نسخ مباشر |
| `job` | varchar(255) | من customers.job | نسخ مباشر |
| `profession` | varchar(255) | من customers.job | نفس job |
| `monthly_income` | decimal(10,2) | - | NULL (غير متوفر) |
| `beds_num` | int | - | NULL (غير متوفر) |

---

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * عملاء الساعات: نفس جدول users لكل الأنظمة (لا نوع مستقل لعميل الساعات)،
 * والبيانات الخاصة بالساعات في hourly_customer_profiles حتى لا يتغير جدول العملاء ولا شاشاته الحالية.
 * سجل المحفظة يكتسب نوع الحركة وطريقة الدفع ومن استلم المبلغ والضريبة والوصف لشاشة «إضافة رصيد».
 * الصلاحيات في 2026_09_26_000002.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('hourly_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('customer_type', 20)->default('individual');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('company_name', 200)->nullable();
            $table->string('company_id', 100)->nullable();
            $table->string('identity_type', 20)->nullable();
            $table->string('language', 5)->default('ar');
            $table->string('iban', 34)->nullable();
            $table->text('note')->nullable();
            $table->string('marketing_source', 50)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['customer_type', 'marketing_source']);
        });

        Schema::table('hourly_wallet_transactions', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('user_id');
            $table->unsignedBigInteger('linked_order_id')->nullable()->after('hourly_rental_order_id');
            $table->string('payment_method', 30)->nullable()->after('balance_after');
            $table->string('collected_by_type', 10)->nullable()->after('payment_method');
            $table->unsignedBigInteger('collected_by_id')->nullable()->after('collected_by_type');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('collected_by_id');
            $table->string('description', 500)->nullable()->after('vat_rate');
            $table->dateTime('paid_at')->nullable()->after('description');
        });
        // الحركات القديمة: خصم على طلب، أو استرداد إلى المحفظة.
        DB::table('hourly_wallet_transactions')->whereNull('type')->whereNotNull('hourly_rental_order_id')->update(['type' => 'order_payment']);
        DB::table('hourly_wallet_transactions')->whereNull('type')->where('amount', '>', 0)->update(['type' => 'refund']);

        // ما حفظه معالج الحجز سابقًا في users.hourly_customer_profile ينتقل للجدول الجديد (والعمود القديم يبقى كما هو).
        DB::table('users')->whereNotNull('hourly_customer_profile')->orderBy('id')->select('id', 'hourly_customer_profile')->chunk(200, function ($users) {
            foreach ($users as $user) {
                $profile = json_decode((string) $user->hourly_customer_profile, true);
                if (!is_array($profile)) continue;
                DB::table('hourly_customer_profiles')->insertOrIgnore([
                    'user_id' => $user->id, 'customer_type' => ($profile['type'] ?? 'individual') === 'company' ? 'company' : 'individual',
                    'first_name' => $profile['first_name'] ?? null, 'last_name' => $profile['last_name'] ?? null,
                    'company_name' => $profile['company_name'] ?? null, 'company_id' => $profile['company_id'] ?? null,
                    'identity_type' => !empty($profile['identity']) ? 'national_id' : null,
                    'language' => in_array($profile['language'] ?? null, ['ar', 'en'], true) ? $profile['language'] : 'ar',
                    'note' => $profile['note'] ?? null, 'created_at' => now(), 'updated_at' => now(),
                ]);
                // رقم الهوية المحفوظ في المعالج يُنسخ لعمود الهوية المشترك فقط إن كان فارغًا.
                if (!empty($profile['identity'])) {
                    DB::table('users')->where('id', $user->id)->where(fn ($q) => $q->whereNull('national_num')->orWhere('national_num', ''))
                        ->update(['national_num' => mb_substr((string) $profile['identity'], 0, 50)]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_customer_profiles');
        Schema::table('hourly_wallet_transactions', fn (Blueprint $table) => $table->dropColumn(['type', 'linked_order_id', 'payment_method', 'collected_by_type', 'collected_by_id', 'vat_rate', 'description', 'paid_at']));
    }
};

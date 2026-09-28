<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * عملاء الساعات — المرحلة الثانية. كل شيء خارج جدول users المشترك:
 * - مجموعات العملاء ومدة الدفع والحذف المؤقت والدمج على hourly_customer_profiles.
 * - بطاقات الهوية والمفضلة وسجل الدمج في جداول مستقلة.
 * الصلاحيات في 2026_09_27_000002.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('hourly_customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->text('description')->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('hourly_customer_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('customer_type');
            $table->string('payment_term', 20)->nullable()->after('group_id');
            $table->unsignedBigInteger('merged_into_id')->nullable()->after('verified_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('merged_into_id');
            $table->softDeletes();
            $table->foreign('group_id')->references('id')->on('hourly_customer_groups')->nullOnDelete();
            $table->index('group_id');
        });

        Schema::create('hourly_customer_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('doc_type', 20);
            $table->string('number', 50)->nullable();
            $table->string('front_path', 500);
            $table->string('back_path', 500)->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('hourly_customer_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('favorable_type', 10);
            $table->unsignedBigInteger('favorable_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'favorable_type', 'favorable_id'], 'hourly_customer_favorites_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('hourly_customer_merges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('primary_user_id')->index();
            $table->unsignedBigInteger('merged_user_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->json('moved');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_customer_merges');
        Schema::dropIfExists('hourly_customer_favorites');
        Schema::dropIfExists('hourly_customer_documents');
        Schema::table('hourly_customer_profiles', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['group_id']);
            $table->dropColumn(['group_id', 'payment_term', 'merged_into_id', 'deleted_by', 'deleted_at']);
        });
        Schema::dropIfExists('hourly_customer_groups');
    }
};

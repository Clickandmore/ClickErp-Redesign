<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // إضافة الحقول الجديدة
            if (!Schema::hasColumn('complaints', 'complainant_name')) {
                $table->string('complainant_name')->nullable()->after('user_id')
                    ->comment('اسم مقدم الشكوى');
            }

            if (!Schema::hasColumn('complaints', 'complainant_phone')) {
                $table->string('complainant_phone')->nullable()->after('complainant_name')
                    ->comment('رقم جوال مقدم الشكوى');
            }

            if (!Schema::hasColumn('complaints', 'maid_passport')) {
                $table->string('maid_passport')->nullable()->after('complainant_phone')
                    ->comment('رقم جواز العاملة');
            }

            if (!Schema::hasColumn('complaints', 'maid_nationality_id')) {
                $table->foreignId('maid_nationality_id')->nullable()->after('maid_passport')
                    ->constrained('nationalities')->onDelete('set null')
                    ->comment('جنسية العاملة');
            }

            if (!Schema::hasColumn('complaints', 'subject')) {
                $table->string('subject')->nullable()->after('maid_nationality_id')
                    ->comment('موضوع الشكوى');
            }

            if (!Schema::hasColumn('complaints', 'details')) {
                $table->text('details')->nullable()->after('message')
                    ->comment('تفاصيل الشكوى');
            }

            if (!Schema::hasColumn('complaints', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('status')
                    ->constrained('admins')->onDelete('set null')
                    ->comment('الموظف المختص بحل الشكوى');
            }

            if (!Schema::hasColumn('complaints', 'attachments')) {
                $table->json('attachments')->nullable()->after('file')
                    ->comment('المرفقات المتعددة');
            }

            if (!Schema::hasColumn('complaints', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                    ->default('medium')->after('status')
                    ->comment('أولوية الشكوى');
            }

            if (!Schema::hasColumn('complaints', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()
                    ->comment('تاريخ حل الشكوى');
            }

            if (!Schema::hasColumn('complaints', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()
                    ->constrained('admins')->onDelete('set null')
                    ->comment('من قام بحل الشكوى');
            }

            // إضافة indexes للبحث السريع
            $table->index('complainant_phone');
            $table->index('maid_passport');
            $table->index('priority');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // حذف الـ indexes
            $table->dropIndex(['complainant_phone']);
            $table->dropIndex(['maid_passport']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['assigned_to']);

            // حذف الـ foreign keys
            if (Schema::hasColumn('complaints', 'maid_nationality_id')) {
                $table->dropForeign(['maid_nationality_id']);
            }
            if (Schema::hasColumn('complaints', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
            }
            if (Schema::hasColumn('complaints', 'resolved_by')) {
                $table->dropForeign(['resolved_by']);
            }

            // حذف الأعمدة
            $columns = [
                'complainant_name',
                'complainant_phone',
                'maid_passport',
                'maid_nationality_id',
                'subject',
                'details',
                'assigned_to',
                'attachments',
                'priority',
                'resolved_at',
                'resolved_by'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

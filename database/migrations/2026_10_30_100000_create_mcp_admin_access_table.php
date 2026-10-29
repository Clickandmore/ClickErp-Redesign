<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * من يستخدم خادم MCP وبأي مستوى، يُدار من شاشة Super Admin.
 *
 * الصلاحية على الموظف لا على التوكن: روابط OAuth ينشئها الموظف بنفسه ويتجدد توكنها
 * كل ساعة في صف جديد، فأي إعداد على التوكن كان سيضيع أو يحتاج نسخاً مع كل تجديد.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mcp_admin_access')) {
            Schema::create('mcp_admin_access', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->unique();
                $table->boolean('enabled')->default(true);
                $table->string('access_level', 16)->default('read');
                // null = كل الأدوات المفعّلة في النظام؛ مصفوفة = هذه الأدوات فقط.
                $table->json('allowed_tools')->nullable();
                $table->string('note', 190)->nullable();
                $table->timestamps();
            });
        }

        // الوضع الافتراضي «الموظفون المحددون فقط»: من لديه اتصال فعّال الآن يُضاف للقراءة
        // حتى لا ينقطع عند تطبيق هذه الرقعة.
        if (Schema::hasTable('mcp_access_tokens')) {
            $adminIds = DB::table('mcp_access_tokens')
                ->whereNull('revoked_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now())
                        ->orWhere('refresh_expires_at', '>', now());
                })
                ->distinct()
                ->pluck('admin_id');

            $existing = DB::table('mcp_admin_access')->whereIn('admin_id', $adminIds)->pluck('admin_id')->all();

            foreach ($adminIds->diff($existing) as $adminId) {
                DB::table('mcp_admin_access')->insert([
                    'admin_id' => $adminId,
                    'enabled' => true,
                    'access_level' => 'read',
                    'note' => 'أُضيف تلقائياً: كان لديه اتصال فعّال',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_admin_access');
    }
};

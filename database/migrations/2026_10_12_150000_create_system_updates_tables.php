<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * تحديثات النظام: ما يُعلن للموظفين من ميزات وإصلاحات، مع الوسائط وتأكيد الاطلاع
 * ومفاتيح النسخ الأخرى التي تسحب التحديثات عبر الـ API.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_updates')) {
            Schema::create('system_updates', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                // local = كُتب في هذه النسخة، remote = سُحب من مصدر التحديثات
                $table->string('source', 20)->default('local')->index();
                $table->string('source_host')->nullable();
                $table->string('title');
                $table->string('summary', 500)->nullable();
                $table->longText('body')->nullable();
                $table->string('category', 30)->default('feature');
                $table->string('module_key', 80)->nullable();
                $table->string('link_url', 1000)->nullable();
                $table->string('link_label', 80)->nullable();
                $table->string('version', 40)->nullable();
                $table->string('status', 20)->default('draft');
                $table->boolean('is_shared')->default(true);
                $table->dateTime('published_at')->nullable();
                $table->dateTime('banner_until')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'published_at']);
                $table->index('updated_at');
            });
        }

        if (! Schema::hasTable('system_update_media')) {
            Schema::create('system_update_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('system_update_id')->constrained('system_updates')->cascadeOnDelete();
                // image | video | embed
                $table->string('type', 20);
                $table->string('path')->nullable();
                $table->string('url', 2000)->nullable();
                $table->string('caption')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('system_update_reads')) {
            Schema::create('system_update_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('system_update_id')->constrained('system_updates')->cascadeOnDelete();
                $table->unsignedBigInteger('admin_id')->index();
                $table->dateTime('seen_at')->nullable();
                $table->dateTime('acknowledged_at')->nullable();
                $table->timestamps();

                $table->unique(['system_update_id', 'admin_id']);
            });
        }

        if (! Schema::hasTable('system_update_clients')) {
            Schema::create('system_update_clients', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('token_hash', 64)->unique();
                $table->string('token_prefix', 12);
                $table->boolean('is_active')->default(true);
                $table->dateTime('last_synced_at')->nullable();
                $table->string('last_ip', 45)->nullable();
                $table->timestamps();
            });
        }

        $this->importLegacyProductUpdates();
    }

    public function down(): void
    {
        Schema::dropIfExists('system_update_clients');
        Schema::dropIfExists('system_update_reads');
        Schema::dropIfExists('system_update_media');
        Schema::dropIfExists('system_updates');
    }

    /**
     * محتوى «ما الجديد» القديم كان JSON في dev_superadmin_settings. ننقل كل بطاقة
     * إلى تحديث منشور، ونُخرجه من الشريط مباشرة لأن الموظفين رأوه سابقًا.
     */
    private function importLegacyProductUpdates(): void
    {
        if (! Schema::hasTable('dev_superadmin_settings') || DB::table('system_updates')->exists()) {
            return;
        }

        $raw = DB::table('dev_superadmin_settings')->where('key', 'product_updates')->value('value');
        $legacy = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($legacy) || ! is_array($legacy['updates'] ?? null)) {
            return;
        }

        $publishedAt = filled($legacy['published_at'] ?? null)
            ? date('Y-m-d H:i:s', strtotime((string) $legacy['published_at']))
            : now()->toDateTimeString();
        $status = filter_var($legacy['is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'published' : 'draft';
        $now = now()->toDateTimeString();

        foreach (array_values($legacy['updates']) as $index => $update) {
            $title = trim((string) ($update['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            DB::table('system_updates')->insert([
                'uuid' => (string) Str::uuid(),
                'source' => 'local',
                'title' => Str::limit($title, 250, ''),
                'summary' => Str::limit(trim((string) ($update['details'] ?? '')), 500, '') ?: null,
                'category' => 'feature',
                'link_url' => trim((string) ($update['url'] ?? '')) ?: null,
                'link_label' => trim((string) ($update['link_label'] ?? '')) ?: null,
                'status' => $status,
                'is_shared' => false,
                'published_at' => $publishedAt,
                'banner_until' => $now,
                // نحافظ على ترتيب البطاقات القديم: الأولى هي الأحدث
                'created_at' => now()->subSeconds($index)->toDateTimeString(),
                'updated_at' => $now,
            ]);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SETTINGS_KEY = 'r2_storage';
    private const TARGET_DATABASE = 'platinum_new_update';

    public function up(): void
    {
        if (!$this->isTargetDatabase() || !Schema::hasTable('new_settings')) {
            return;
        }

        // The secret is encrypted here so the ciphertext uses the production APP_KEY.
        $settings = [
            'enabled' => true,
            'endpoint' => 'https://f37a43f8f70449a6c8afdf93e0958f8a.r2.cloudflarestorage.com',
            'bucket' => 'clickerp-platinum',
            'access_key_id' => '2afcfea3b3dfc8217cae4c30c974772a',
            'secret_access_key' => 'encrypted:' . Crypt::encryptString('0eedb5bb09a0e2461c92cd80f2461fb31363adba1a19952d236260e8f45fde84'),
            'public_url' => '',
            'root_prefix' => 'platinum',
            'use_path_style_endpoint' => false,
            'verified_at' => '2026-08-07T20:55:00+03:00',
        ];

        $value = json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $now = now();
        $query = DB::table('new_settings')->where('key', self::SETTINGS_KEY);

        if ($query->exists()) {
            $query->update([
                'value' => $value,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('new_settings')->insert([
            'key' => self::SETTINGS_KEY,
            'value' => $value,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (!$this->isTargetDatabase() || !Schema::hasTable('new_settings')) {
            return;
        }

        DB::table('new_settings')
            ->where('key', self::SETTINGS_KEY)
            ->delete();
    }

    private function isTargetDatabase(): bool
    {
        return DB::connection()->getDatabaseName() === self::TARGET_DATABASE;
    }
};

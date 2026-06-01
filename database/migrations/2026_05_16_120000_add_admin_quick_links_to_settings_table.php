<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'admin_quick_links')) {
                $table->json('admin_quick_links')->nullable()->after('live_chat_url');
            }
        });

        if (Schema::hasColumn('settings', 'admin_quick_links')) {
            DB::table('settings')
                ->whereNull('admin_quick_links')
                ->orderBy('id')
                ->each(function ($setting) {
                    DB::table('settings')
                        ->where('id', $setting->id)
                        ->update([
                            'admin_quick_links' => json_encode([
                                [
                                    'title' => $setting->header_link_1_title ?: 'مساند برو',
                                    'url' => $setting->header_link_1_url ?: 'https://pros.musaned.com.sa/login',
                                    'icon' => 'fa-external-link',
                                    'is_visible' => true,
                                ],
                                [
                                    'title' => $setting->header_link_2_title ?: 'مساند توثيق',
                                    'url' => $setting->header_link_2_url ?: 'https://tawtheeq.musaned.com.sa/',
                                    'icon' => 'fa-file-text-o',
                                    'is_visible' => true,
                                ],
                                [
                                    'title' => $setting->live_chat_title ?: 'اللايف شات',
                                    'url' => $setting->live_chat_url ?: 'https://salesiq.zoho.sa/platinumeastern/liveview',
                                    'icon' => 'fa-comments',
                                    'is_visible' => true,
                                ],
                                [
                                    'title' => 'إنجاز',
                                    'url' => 'https://visa.mofa.gov.sa/enjaz/getvisainformation/person',
                                    'icon' => 'fa-id-card-o',
                                    'is_visible' => true,
                                ],
                                [
                                    'title' => 'منصة تأشير',
                                    'url' => 'https://ksavisa.sa/',
                                    'icon' => 'fa-plane',
                                    'is_visible' => true,
                                ],
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'admin_quick_links')) {
                $table->dropColumn('admin_quick_links');
            }
        });
    }
};

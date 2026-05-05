<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings_translations')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('settings_translations'));
        $policyColumns = [
            'privacy',
            'terms_conditions',
            'about_us_app',
            'rights_users',
            'rights_maids',
            'return_conditions',
        ];

        Schema::table('settings_translations', function (Blueprint $table) use ($columns, $policyColumns) {
            foreach ($policyColumns as $column) {
                if (isset($columns[$column])) {
                    $table->longText($column)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings_translations')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('settings_translations'));
        $policyColumns = [
            'privacy',
            'terms_conditions',
            'about_us_app',
            'rights_users',
            'rights_maids',
            'return_conditions',
        ];

        Schema::table('settings_translations', function (Blueprint $table) use ($columns, $policyColumns) {
            foreach ($policyColumns as $column) {
                if (isset($columns[$column])) {
                    $table->string($column, 1000)->nullable()->change();
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فرق التأجير بالساعة — تنبثق من مكان الإيواء.
 *
 * الفريق هو وحدة الحركة على أرض الواقع: عاملات وسائقون ينطلقون من إيواء واحد
 * ويغطون مناطق محددة بمضلعات على الخريطة. الجداول الثلاثة لا تمس أي جدول قائم.
 */
class CreateHourlyTeamsTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hourly_teams')) {
            Schema::create('hourly_teams', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shelter_place_id');
                $table->unsignedBigInteger('city_id')->nullable();
                $table->string('name', 150);
                $table->string('code', 30)->nullable()->unique();
                $table->string('color', 7)->default('#0B6461');
                $table->text('description')->nullable();
                // نقطة الانطلاق (افتراضيًا موقع الإيواء) — تُعرض على الخريطة وتُستخدم لحساب المسافات
                $table->decimal('base_lat', 10, 7)->nullable();
                $table->decimal('base_lng', 10, 7)->nullable();
                $table->time('working_start')->nullable();
                $table->time('working_end')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['shelter_place_id', 'is_active']);
                $table->index('city_id');
            });

            if (Schema::hasTable('shelter_places')) {
                Schema::table('hourly_teams', function (Blueprint $table) {
                    $table->foreign('shelter_place_id')->references('id')->on('shelter_places')->restrictOnDelete();
                });
            }
            if (Schema::hasTable('cities')) {
                Schema::table('hourly_teams', function (Blueprint $table) {
                    $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
                });
            }
        }

        if (!Schema::hasTable('hourly_team_areas')) {
            Schema::create('hourly_team_areas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hourly_team_id');
                $table->string('name', 150);
                // مصفوفة نقاط [[lat,lng], ...] — مضلع مغلق ضمنيًا
                $table->json('polygon');
                $table->decimal('centroid_lat', 10, 7)->nullable();
                $table->decimal('centroid_lng', 10, 7)->nullable();
                $table->decimal('min_lat', 10, 7)->nullable();
                $table->decimal('max_lat', 10, 7)->nullable();
                $table->decimal('min_lng', 10, 7)->nullable();
                $table->decimal('max_lng', 10, 7)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('hourly_team_id')->references('id')->on('hourly_teams')->cascadeOnDelete();
                $table->index(['is_active', 'min_lat', 'max_lat']);
            });
        }

        if (!Schema::hasTable('hourly_team_members')) {
            Schema::create('hourly_team_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hourly_team_id');
                // maid أو driver — جدول السائقين يُنشأ في مرحلة لاحقة، لذلك الربط متعدد الأشكال
                $table->string('member_type', 20);
                $table->unsignedBigInteger('member_id');
                $table->string('role', 20)->default('primary'); // primary / secondary
                $table->boolean('is_leader')->default(false);
                $table->date('joined_at')->nullable();
                $table->date('left_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('hourly_team_id')->references('id')->on('hourly_teams')->cascadeOnDelete();
                $table->index(['member_type', 'member_id', 'left_at'], 'hourly_team_members_member_idx');
                $table->index(['hourly_team_id', 'left_at']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('hourly_team_members');
        Schema::dropIfExists('hourly_team_areas');
        Schema::dropIfExists('hourly_teams');
    }
}

<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesActivityLogTable
{
    protected function createActivityLogTable(): void
    {
        // Keep audit writes on the same isolated connection as the tested models.
        config(['activitylog.database_connection' => config('database.default')]);
        Schema::create(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('legacy_source')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->unique(['legacy_source', 'legacy_id']);
            $table->timestamps();
        });
    }
}

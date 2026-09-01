<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SEQUENCE_TABLE = 'fn_constraint_code_sequences';

    public function up(): void
    {
        Schema::create(self::SEQUENCE_TABLE, function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_code')->default(0);
            $table->timestamps();
        });

        DB::table(self::SEQUENCE_TABLE)->insert([
            'id' => 1,
            'last_code' => (int) DB::table('fn_constraints')->max('code'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists(self::SEQUENCE_TABLE);
    }
};

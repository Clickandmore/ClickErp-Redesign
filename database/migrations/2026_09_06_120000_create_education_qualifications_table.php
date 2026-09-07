<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEducationQualificationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('education_qualifications', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar')->unique();
            $table->string('title_en')->unique();
            $table->timestamps();
        });

        foreach ([
            ['بدون مؤهل', 'No formal education'],
            ['ابتدائي', 'Primary education'],
            ['متوسط', 'Intermediate education'],
            ['ثانوي', 'Secondary education'],
            ['دبلوم', 'Diploma'],
            ['بكالوريوس', "Bachelor's degree"],
            ['ماجستير', "Master's degree"],
            ['دكتوراه', 'Doctorate'],
        ] as [$arabic, $english]) {
            DB::table('education_qualifications')->insert([
                'title_ar' => $arabic, 'title_en' => $english,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('education_qualifications');
    }
}

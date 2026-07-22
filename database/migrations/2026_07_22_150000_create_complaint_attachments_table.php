<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->string('path');
            $table->string('display_name')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['complaint_id', 'created_at']);
        });

        if (!Schema::hasColumn('complaints', 'attachments')) {
            return;
        }

        DB::table('complaints')
            ->whereNotNull('attachments')
            ->select(['id', 'attachments', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($complaints) {
                foreach ($complaints as $complaint) {
                    $paths = is_string($complaint->attachments)
                        ? json_decode($complaint->attachments, true)
                        : $complaint->attachments;

                    if (!is_array($paths)) {
                        continue;
                    }

                    foreach ($paths as $path) {
                        if (!is_string($path) || trim($path) === '') {
                            continue;
                        }

                        DB::table('complaint_attachments')->insert([
                            'complaint_id' => $complaint->id,
                            'path' => $path,
                            // الاسم الأصلي غير محفوظ في الحقل القديم؛ يعرض كـ "مرفق" بدلاً من اسم hash.
                            'original_name' => null,
                            'created_at' => $complaint->created_at ?: now(),
                            'updated_at' => $complaint->updated_at ?: now(),
                        ]);
                    }

                    DB::table('complaints')->where('id', $complaint->id)->update(['attachments' => null]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('complaint_attachments') && Schema::hasColumn('complaints', 'attachments')) {
            DB::table('complaint_attachments')
                ->select('complaint_id')
                ->distinct()
                ->orderBy('complaint_id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $paths = DB::table('complaint_attachments')
                            ->where('complaint_id', $row->complaint_id)
                            ->orderBy('id')
                            ->pluck('path')
                            ->all();
                        DB::table('complaints')->where('id', $row->complaint_id)->update([
                            'attachments' => json_encode($paths),
                        ]);
                    }
                }, 'complaint_id');
        }

        Schema::dropIfExists('complaint_attachments');
    }
};

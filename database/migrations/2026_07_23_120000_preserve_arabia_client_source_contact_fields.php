<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'source_phone')) {
                $table->text('source_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'source_phone2')) {
                $table->text('source_phone2')->nullable()->after('phone2');
            }
            if (!Schema::hasColumn('users', 'source_created_by')) {
                $table->string('source_created_by')->nullable()->after('created_by');
            }
        });

        if (!Schema::hasTable('client_import_details')) {
            return;
        }

        $adminNames = DB::table('admins')
            ->select(['id', 'name'])
            ->get()
            ->map(fn ($admin) => [
                'id' => (int) $admin->id,
                'name' => $this->normalizeCreatorName($admin->name),
            ])
            ->filter(fn (array $admin) => $admin['name'] !== '')
            ->sortByDesc(fn (array $admin) => mb_strlen($admin['name']))
            ->values()
            ->all();

        DB::table('client_import_details')
            ->where('source', 'arabia')
            ->whereIn('field_label', ['الجوال', 'رقم هاتف 2', 'أنشئ بواسطة'])
            ->select(['id', 'user_id', 'field_label', 'field_value'])
            ->orderBy('id')
            ->chunkById(1000, function ($details) use ($adminNames) {
                foreach ($details->groupBy('user_id') as $userId => $userDetails) {
                    $values = $userDetails->pluck('field_value', 'field_label');
                    $sourcePhone = $values->get('الجوال');
                    $sourcePhone2 = $values->get('رقم هاتف 2');
                    $sourceCreatedBy = $values->get('أنشئ بواسطة');

                    $payload = [];
                    if ($values->has('الجوال')) {
                        $payload['source_phone'] = $sourcePhone !== null && $sourcePhone !== ''
                            ? (string) $sourcePhone
                            : null;
                    }
                    if ($values->has('رقم هاتف 2')) {
                        $payload['source_phone2'] = $sourcePhone2 !== null && $sourcePhone2 !== ''
                            ? (string) $sourcePhone2
                            : null;
                        // phone2 is not unique and can safely keep the source value
                        // exactly as exported, including prefixes and annotations.
                        $payload['phone2'] = $payload['source_phone2'];
                    }
                    if ($values->has('أنشئ بواسطة')) {
                        $payload['source_created_by'] = $sourceCreatedBy !== null && $sourceCreatedBy !== ''
                            ? (string) $sourceCreatedBy
                            : null;
                        $creatorId = $this->resolveCreatorId($sourceCreatedBy, $adminNames);
                        if ($creatorId) {
                            $payload['created_by'] = $creatorId;
                        }
                    }

                    if ($payload !== []) {
                        DB::table('users')->where('id', $userId)->update($payload);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['source_phone', 'source_phone2', 'source_created_by'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function resolveCreatorId($sourceCreatedBy, array $admins): ?int
    {
        $source = $this->normalizeCreatorName($sourceCreatedBy);
        if ($source === '') {
            return null;
        }

        foreach ($admins as $admin) {
            if ($source === $admin['name']
                || preg_match('/(?:^|\\s*\\/\\s*)' . preg_quote($admin['name'], '/') . '(?:\\s*\\/\\s*|$)/u', $source)) {
                return $admin['id'];
            }
        }

        return null;
    }

    private function normalizeCreatorName($value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $value);

        return preg_replace('/\\s+/u', ' ', $value);
    }
};

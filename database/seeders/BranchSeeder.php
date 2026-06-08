<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BranchSeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('branches') || !Schema::hasColumn('branches', 'is_main')) {
            return;
        }

        $existingMainBranch = DB::table('branches')
            ->where('is_main', true)
            ->first();

        if ($existingMainBranch) {
            DB::table('branches')
                ->where('id', $existingMainBranch->id)
                ->update($this->onlyExistingBranchColumns([
                    'is_active' => true,
                    'updated_at' => now(),
                ]));

            return;
        }

        DB::table('branches')->insert($this->mainBranchPayload());
    }

    private function mainBranchPayload(): array
    {
        $now = now();
        $appName = trim((string) config('app.name', 'Main Branch'));

        return $this->onlyExistingBranchColumns(array_filter([
            'name_ar' => 'الفرع الرئيسي',
            'name_en' => 'Main Branch',
            'code' => $this->uniqueCode('MAIN'),
            'is_active' => true,
            'is_main' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
            'description' => $appName !== '' ? 'Main branch for ' . $appName : null,
        ], fn ($value) => $value !== null));
    }

    private function onlyExistingBranchColumns(array $payload): array
    {
        $columns = array_flip(Schema::getColumnListing('branches'));

        return array_intersect_key($payload, $columns);
    }

    private function uniqueCode(string $baseCode): string
    {
        $baseCode = Str::upper(Str::slug($baseCode, '-')) ?: 'MAIN';
        $code = $baseCode;
        $counter = 2;

        while (DB::table('branches')->where('code', $code)->exists()) {
            $code = $baseCode . '-' . $counter++;
        }

        return $code;
    }
}

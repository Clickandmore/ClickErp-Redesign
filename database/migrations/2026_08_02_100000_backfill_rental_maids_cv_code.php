<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ترقيم سير التأجير القديمة التي أُنشئت قبل تفعيل السيريال الموحّد.
 *
 * كود الاستقدام يُبنى على اسم المكتب الخارجي، وسير التأجير غالبًا بلا مكتب
 * فكانت تُحفظ بلا cv_code. هنا تُملأ القيمة فقط — لا يُمس أي عمود آخر
 * ولا updated_at، لذلك نستخدم DB::table لا Eloquent.
 *
 * المايجريشن قابل لإعادة التشغيل: يملأ الفارغ فقط، ويكمل العدّاد من أعلى
 * رقم RENT- موجود حتى لا يصطدم بأكواد ولّدها النظام بعد النشر.
 */
return new class extends Migration
{
    private const PREFIX = 'RENT-';

    public function up(): void
    {
        if (! Schema::hasTable('maids') || ! Schema::hasColumn('maids', 'cv_code') || ! Schema::hasTable('maid_jobs')) {
            return;
        }

        $rentalJobTypeId = $this->rentalJobTypeId();

        if (! $rentalJobTypeId) {
            return;
        }

        $maidIds = $this->rentalMaidsMissingCvCode($rentalJobTypeId);

        if ($maidIds === []) {
            return;
        }

        $nextNumber = $this->lastUsedNumber() + 1;

        foreach ($maidIds as $maidId) {
            do {
                $code = self::PREFIX . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
                $nextNumber++;
            } while (DB::table('maids')->where('cv_code', $code)->exists());

            DB::table('maids')->where('id', $maidId)->update(['cv_code' => $code]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('maids') || ! Schema::hasColumn('maids', 'cv_code') || ! Schema::hasTable('maid_jobs')) {
            return;
        }

        $rentalJobTypeId = $this->rentalJobTypeId();

        if (! $rentalJobTypeId) {
            return;
        }

        // ملاحظة: هذا يمسح أيضًا أكواد RENT- التي ولّدها النظام بعد تشغيل المايجريشن،
        // لأنه لا سبيل للتمييز بينها وبين ما ملأه هذا الملف.
        DB::table('maids')
            ->whereIn('id', function ($query) use ($rentalJobTypeId) {
                $query->select('maid_id')->from('maid_jobs')->where('job_type_id', $rentalJobTypeId);
            })
            ->where('cv_code', 'LIKE', self::PREFIX . '%')
            ->update(['cv_code' => null]);
    }

    /**
     * نفس طريقة MaidAction::getRentalJobTypeId حتى لا يختلف التصنيف بين البيئات.
     */
    private function rentalJobTypeId(): ?int
    {
        if (! Schema::hasTable('job_types') || ! Schema::hasTable('job_types_translations')) {
            return null;
        }

        $id = DB::table('job_types')
            ->join('job_types_translations as t', 't.job_type_id', '=', 'job_types.id')
            ->where(function ($query) {
                $query->where('t.title', 'LIKE', '%ايجار%')
                    ->orWhere('t.title', 'LIKE', '%إيجار%')
                    ->orWhere('t.title', 'LIKE', '%تأجير%')
                    ->orWhere('t.title', 'LIKE', '%rent%');
            })
            ->value('job_types.id');

        return $id ? (int) $id : null;
    }

    /**
     * @return array<int, int>
     */
    private function rentalMaidsMissingCvCode(int $rentalJobTypeId): array
    {
        $query = DB::table('maids')
            ->whereIn('id', function ($sub) use ($rentalJobTypeId) {
                $sub->select('maid_id')->from('maid_jobs')->where('job_type_id', $rentalJobTypeId);
            })
            ->where(function ($sub) {
                $sub->whereNull('cv_code')->orWhere('cv_code', '');
            });

        // العاملات المحذوفة لا تُرقَّم
        if (Schema::hasColumn('maids', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function lastUsedNumber(): int
    {
        $digitsFrom = strlen(self::PREFIX) + 1;

        return (int) DB::table('maids')
            ->where('cv_code', 'LIKE', self::PREFIX . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(cv_code, {$digitsFrom}) AS UNSIGNED)) as last_number")
            ->value('last_number');
    }
};

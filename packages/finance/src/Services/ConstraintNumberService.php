<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConstraintNumberService
{
    private const SEQUENCE_ID = 1;
    private const SEQUENCE_TABLE = 'fn_constraint_code_sequences';

    /**
     * Reserve and return the next constraint number.
     *
     * The singleton sequence row is locked until the surrounding transaction
     * commits, so simultaneous requests can never receive the same number.
     */
    public function nextCode(): int
    {
        return DB::transaction(function (): int {
            $sequence = DB::table(self::SEQUENCE_TABLE)
                ->where('id', self::SEQUENCE_ID)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw new RuntimeException(
                    'Constraint number sequence is not initialized. Run the database migrations.'
                );
            }

            // Keep the sequence safe if constraints were imported with explicit
            // historical numbers after the sequence was initialized.
            //
            // DB::table عمداً وليس Eloquent: ترقيم القيود متصل على مستوى الشركة،
            // فلو مرّ بالموديل المعزول لبدأ كل فرع من رقمه هو وتكرّرت الأرقام.
            $highestStoredCode = (int) DB::table('fn_constraints')->max('code');
            $nextCode = max((int) $sequence->last_code, $highestStoredCode) + 1;

            DB::table(self::SEQUENCE_TABLE)
                ->where('id', self::SEQUENCE_ID)
                ->update([
                    'last_code' => $nextCode,
                    'updated_at' => now(),
                ]);

            return $nextCode;
        }, 5);
    }

    /**
     * Return a non-reserved number for display only.
     */
    public function previewNextCode(): int
    {
        $lastReservedCode = (int) DB::table(self::SEQUENCE_TABLE)
            ->where('id', self::SEQUENCE_ID)
            ->value('last_code');
        $highestStoredCode = (int) DB::table('fn_constraints')->max('code');

        return max($lastReservedCode, $highestStoredCode) + 1;
    }
}

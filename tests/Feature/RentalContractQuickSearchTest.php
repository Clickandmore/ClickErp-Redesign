<?php

namespace Tests\Feature;

use App\Models\RentalContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RentalContractQuickSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_search_matches_related_identifiers_and_respects_other_filters(): void
    {
        $suffix = uniqid();
        $clientId = DB::table('users')->insertGetId([
            'name' => 'عميل البحث الشامل', 'phone_code' => '+966', 'phone' => 'client-' . $suffix,
            'national_num' => '1234567890', 'source_phone2' => '0543219876',
            'password' => bcrypt('password'),
        ]);
        $maidId = DB::table('maids')->insertGetId([
            'name' => 'عاملة البحث', 'passport_num' => 'SEARCH-' . $suffix,
            'id_number' => '2987654321', 'border_number' => '9876543210',
            'nationality_id' => DB::table('nationalities')->insertGetId(['created_at' => now()]),
            'religion_id' => DB::table('religions')->insertGetId(['created_at' => now()]),
            'cv_file' => '', 'height' => '', 'weight' => '', 'status' => 'available',
        ]);
        $id = DB::table('rental_contracts')->insertGetId([
            'user_id' => $clientId, 'maid_id' => $maidId, 'status' => 'active',
            'contract_number' => 'RENT-' . $suffix, 'legacy_order_id' => 87654321,
        ]);
        $base = RentalContract::query()->whereKey($id);

        foreach (['1234567890', '١٢٣٤٥٦٧٨٩٠', '۱۲۳۴۵۶۷۸۹۰', '0543219876', '2987654321',
            '9876543210', 'SEARCH-' . $suffix, 'RENT-' . $suffix, '87654321',
            'عميل عاملة', '  عميل   2987654321  '] as $search) {
            $this->assertTrue((clone $base)->quickSearch($search)->exists(), $search);
        }
        $this->assertFalse((clone $base)->quickSearch('عميل nonexistent')->exists());
        $this->assertFalse((clone $base)->where('status', 'cancelled')->quickSearch('عاملة')->exists());
        $this->assertTrue((clone $base)->quickSearch('   ')->exists());
        foreach (['%', '_', "' OR 1=1 --"] as $search) {
            $this->assertFalse((clone $base)->quickSearch($search)->exists(), $search);
        }
    }
}

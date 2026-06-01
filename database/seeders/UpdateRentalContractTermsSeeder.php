<?php

namespace Database\Seeders;

use App\Models\RentalContractTerm;
use App\Support\RentalContractDefaultTerms;
use Illuminate\Database\Seeder;

class UpdateRentalContractTermsSeeder extends Seeder
{
    public function run()
    {
        RentalContractTerm::truncate();

        foreach (RentalContractDefaultTerms::all() as $term) {
            RentalContractTerm::create($term);
        }
    }
}

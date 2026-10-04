<?php

namespace App\Support;

use App\Models\ingaz\User;

class CompanyContractParty extends User
{
    public function save(array $options = [])
    {
        throw new \LogicException('Company details can only be changed from company settings.');
    }

    public function delete()
    {
        throw new \LogicException('A company contract party is not a customer record.');
    }
}

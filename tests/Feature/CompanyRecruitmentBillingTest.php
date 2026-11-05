<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Http\Controllers\ingaz\InvoiceController;
use App\Http\Controllers\ingaz\MusanedTransferController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyRecruitmentBillingTest extends TestCase
{
    use DatabaseTransactions;

    private function companyContract(): Contract
    {
        $company = CompanyProfile::create(['name' => 'Company billing test']);
        return Contract::create(['status' => 'new', 'type' => 'recruitment', 'company_profile_id' => $company->id]);
    }

    public function test_company_contract_keeps_supplier_eligibility_but_rejects_customer_billing(): void
    {
        $contract = $this->companyContract();
        $this->assertTrue($contract->isCurrentRecruitmentContract());
        $this->assertTrue($contract->isCompanyRecruitment());
        $this->expectException(ValidationException::class);
        $contract->assertCustomerBillingAllowed();
    }

    public function test_direct_customer_invoice_creation_page_rejects_company_contract(): void
    {
        $contract = $this->companyContract();
        $this->expectException(ValidationException::class);
        app(InvoiceController::class)->create(Request::create('/', 'GET', ['contract_id' => $contract->id]));
    }

    public function test_musaned_candidates_exclude_company_contracts_and_keep_client_contracts(): void
    {
        $company = $this->companyContract();
        $client = User::create(['name' => 'External customer', 'phone' => '5' . random_int(10000000, 99999999), 'password' => bcrypt('secret')]);
        $normal = Contract::create(['status' => 'new', 'type' => 'recruitment', 'user_id' => $client->id]);
        foreach ([$company, $normal] as $contract) {
            Invoice::create(['contract_id' => $contract->id, 'total_due' => 1900, 'is_paid' => false]);
        }
        $ids = app(MusanedTransferController::class)->getEligibleInvoicesQuery()->whereIn('contract_id', [$company->id, $normal->id])->pluck('contract_id')->all();
        $this->assertEquals([$normal->id], $ids);
        $this->assertFalse($normal->isCompanyRecruitment());
        $normal->assertCustomerBillingAllowed();
    }

    public function test_legacy_company_client_contract_also_rejects_customer_billing(): void
    {
        $client = User::create(['name' => 'Legacy company', 'phone' => '5' . random_int(10000000, 99999999), 'password' => bcrypt('secret')]);
        $resolver = \Mockery::mock(\App\Services\ShelterCompanyClientResolver::class);
        $resolver->shouldReceive('applyCompanyIdentityFilter')->andReturnUsing(fn ($query) => $query->whereKey($client->id));
        $this->app->instance(\App\Services\ShelterCompanyClientResolver::class, $resolver);
        $contract = Contract::create(['status' => 'new', 'user_id' => $client->id]);
        $this->assertTrue($contract->isCompanyRecruitment());
        $this->expectException(ValidationException::class);
        $contract->assertCustomerBillingAllowed();
    }
}

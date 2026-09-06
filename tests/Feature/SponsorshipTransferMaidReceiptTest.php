<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\SponsorshipTransfer;
use App\PermKeys;
use App\Services\ingaz\SponsorshipTransferMaidReceiptDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class SponsorshipTransferMaidReceiptTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
    }

    public function test_receipt_values_are_filled_from_the_transfer(): void
    {
        $transfer = $this->makeTrialTransfer();

        $values = app(SponsorshipTransferMaidReceiptDocument::class)->values($transfer);

        $this->assertSame('New sponsor', $values['client_name']);
        $this->assertSame('1099887766', $values['client_id_number']);
        $this->assertSame('Receipt maid', $values['maid_name']);
        $this->assertStringStartsWith('RECEIPT-', $values['maid_passport']);
        $this->assertSame('12,000.00 ريال', $values['contract_value']);
        $this->assertSame('5,000.00 ريال', $values['paid_amount']);
        $this->assertSame('7,000.00 ريال', $values['remaining_amount']);
        $this->assertSame('1,400.00', $values['monthly_salary']);
        $this->assertSame('7 يوم', $values['trial_period']);
        $this->assertStringStartsWith('شركة', $values['company_name']);
    }

    public function test_generated_document_is_a_pdf_with_the_transfer_data(): void
    {
        $transfer = $this->makeTrialTransfer();

        [$contents, $name] = app(SponsorshipTransferMaidReceiptDocument::class)->generate($transfer);

        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertStringEndsWith('.pdf', $name);

        $html = view(SponsorshipTransferMaidReceiptDocument::VIEW, app(SponsorshipTransferMaidReceiptDocument::class)->values($transfer))->render();
        $this->assertStringContainsString('New sponsor', $html);
        $this->assertStringContainsString('Receipt maid', $html);
        $this->assertStringContainsString('12,000.00 ريال', $html);
    }

    public function test_admin_with_view_permission_can_download_the_receipt(): void
    {
        $transfer = $this->makeTrialTransfer();
        $admin = $this->globalAdmin();
        $this->grant($admin, PermKeys::SPONSOR_TRANSFER_VIEW);

        $response = $this->actingAsAdmin($admin)
            ->get(route('SponsorshipTransfer.maid-receipt', $transfer->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_admin_without_permission_cannot_download_the_receipt(): void
    {
        $transfer = $this->makeTrialTransfer();
        $admin = $this->globalAdmin();

        $this->actingAsAdmin($admin)
            ->get(route('SponsorshipTransfer.maid-receipt', $transfer->id))
            ->assertForbidden();
    }

    private function makeTrialTransfer(): SponsorshipTransfer
    {
        $suffix = str_replace('.', '', uniqid('', true));
        $nationalityId = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religionId = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

        $maidId = DB::table('maids')->insertGetId([
            'name' => 'Receipt maid',
            'passport_num' => 'RECEIPT-' . $suffix,
            'nationality_id' => $nationalityId,
            'religion_id' => $religionId,
            'salary' => 1400,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'contracted',
            'available' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldSponsorId = $this->makeClient('Old sponsor', '50' . substr($suffix, -7), '1011223344');
        $newSponsorId = $this->makeClient('New sponsor', '55' . substr($suffix, -7), '1099887766');

        $contractId = DB::table('contracts')->insertGetId([
            'user_id' => $oldSponsorId,
            'maid_id' => $maidId,
            'status' => 'completed',
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SponsorshipTransfer::create([
            'contract_id' => $contractId,
            'old_sponsor_id' => $oldSponsorId,
            'new_sponsor_id' => $newSponsorId,
            'trial_days' => 7,
            'trial_start_date' => now()->toDateString(),
            'trial_started_at' => now(),
            'contract_value' => 12000,
            'paid_amount' => 5000,
            'remaining_amount' => 7000,
            'status' => 'trial_started',
        ]);
    }

    private function makeClient(string $name, string $phone, string $nationalNumber): int
    {
        return DB::table('users')->insertGetId([
            'name' => $name,
            'phone_code' => '+966',
            'phone' => $phone,
            'national_num' => $nationalNumber,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grant($admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            [
                'display_name' => $permission,
                'group_name' => 'sponsor_transfer',
                'section_name' => 'operations_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }
}

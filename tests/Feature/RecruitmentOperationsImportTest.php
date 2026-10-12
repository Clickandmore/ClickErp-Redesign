<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Models\MusanedTransfer;
use App\Services\ingaz\DataImportReviewService;
use App\Services\ingaz\DataImportTemplateService;
use App\Services\ingaz\ImportReferenceResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RecruitmentOperationsImportTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableBranchesModule(false);
        config(['activitylog.enabled' => false]);
        Http::fake();
        Storage::fake('local');
        $this->actor = Admin::factory()->global()->create();
        $this->actingAs($this->actor, 'admin');
        foreach ([\App\PermKeys::MUSANED_TRANSFERS_CREATE, \App\NewPermKeys::REC_CONTRACTS_UPDATE, \App\PermKeys::DATA_IMPORT_UPLOAD] as $name) {
            $this->actor->givePermissionTo(\App\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
        }
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->delete();
        DB::table('fn_confirm_settings')->insert(['title' => 'automatic_journal_entries', 'value' => 'off']);
    }

    private function contract(): Contract
    {
        $user = User::withoutEvents(fn () => User::create(['name' => 'عميل اختبار الاستيراد', 'phone' => uniqid('IMP'), 'national_num' => '1096545619']));
        $contract = Contract::withoutEvents(fn () => Contract::create(['user_id' => $user->id, 'type' => 'recruitment', 'status' => 'new']));
        ContractInfo::withoutEvents(fn () => ContractInfo::create(['contract_id' => $contract->id, 'msaned_number' => uniqid('MS-'), 'contract_date' => now()->toDateString()]));
        Invoice::withoutEvents(fn () => Invoice::create(['contract_id' => $contract->id, 'client_id' => $user->id,
            'subtotal' => 1000, 'vat_amount' => 0, 'total_due' => 1000]));
        return $contract;
    }

    private function transferRow(Contract $contract, string $reference = 'IMPORT-ONE'): array
    {
        return ['reference_number' => $reference, 'reference_type' => 'عقد استقدام',
            'reference_msaned_number' => $contract->info->msaned_number,
            'transfer_date' => now()->toDateString(), 'transferred_amount' => '950'];
    }

    private function stage(string $type, array $rows)
    {
        $columns = app(DataImportTemplateService::class)->template($type)['columns'];
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(array_column($columns, 'label'), null, 'A1');
        foreach ($rows as $index => $row) $sheet->fromArray(array_map(fn ($column) => $row[$column['key']] ?? '', $columns), null, 'A'.($index + 2));
        $path = tempnam(sys_get_temp_dir(), 'operations-import-');
        try {
            (new Xlsx($book))->save($path);
            return app(DataImportReviewService::class)->stage($type, new UploadedFile($path, 'operations.xlsx', null, null, true), $this->actor->id);
        } finally { $book->disconnectWorksheets(); unlink($path); }
    }

    public function test_transfer_review_is_read_only_then_approves_only_valid_rows_and_prevents_reimport(): void
    {
        $contract = $this->contract();
        $row = $this->transferRow($contract);
        $before = MusanedTransfer::count();
        $batch = $this->stage('musaned_transfers', [$row, array_replace($row, ['reference_number' => 'BAD', 'reference_msaned_number' => 'NOT-FOUND'])]);
        $this->assertSame($before, MusanedTransfer::count());
        $this->assertSame(1, $batch->rows()->where('status', 'valid')->count());
        $this->assertSame(1, $batch->rows()->where('status', 'invalid')->count());
        $result = app(DataImportReviewService::class)->approve($batch, $this->actor->id);
        $this->assertSame(1, $result['created']);
        $transfer = MusanedTransfer::where('reference_number', 'IMPORT-ONE')->firstOrFail();
        $this->assertSame('950.00', $transfer->transferred_total);
        $this->assertSame('50.00', $transfer->commission_total);
        $this->assertNull($transfer->settlement_constraint_id);
        $this->assertSame($contract->id, $transfer->items()->firstOrFail()->contract_id);
        app(DataImportReviewService::class)->approve($batch->fresh(), $this->actor->id);
        $this->assertSame($before + 1, MusanedTransfer::count());
        $again = $this->stage('musaned_transfers', [array_replace($row, ['reference_number' => 'CHANGED'])]);
        $this->assertSame(1, $again->rows()->where('status', 'invalid')->count());
        Http::assertNothingSent();
    }

    public function test_duplicate_sources_and_excess_amounts_are_explained_on_their_rows(): void
    {
        $contract = $this->contract();
        $row = $this->transferRow($contract);
        $batch = $this->stage('musaned_transfers', [$row, array_replace($row, ['reference_number' => 'SECOND', 'transferred_amount' => '2000'])]);
        $issues = implode(' ', $batch->rows()->where('source_row', '3')->firstOrFail()->issues);
        $this->assertStringContainsString('أكبر من إجمالي', $issues);
        $this->assertStringContainsString('مكرر داخل الملف', $issues);
    }

    public function test_reference_conflicts_are_rejected_instead_of_selecting_an_arbitrary_contract(): void
    {
        $first = $this->contract();
        $second = $this->contract();
        $result = app(ImportReferenceResolver::class)->resolve('musaned_transfers',
            $this->transferRow($first) + ['reference_contract_number' => $second->id]);
        $this->assertNotEmpty($result['issues']);
    }

    public function test_delegation_transfer_links_by_ingaz_number_and_records_the_correct_invoice(): void
    {
        $contract = $this->contract();
        $nationality = \App\Models\ingaz\Nationality::create(['ar' => ['title' => 'جنسية اختبار التحويل', 'sub_title' => 'جنسية اختبار التحويل']]);
        $profession = \App\Models\ingaz\JobTitle::create(['ar' => ['title' => 'مهنة اختبار التحويل']]);
        $delegation = \App\Models\IngazDelegation::withoutEvents(fn () => \App\Models\IngazDelegation::create([
            'delegation_date_gregorian' => now()->toDateString(), 'delegation_date_hijri' => '',
            'nationality_id' => $nationality->id, 'profession_id' => $profession->id, 'user_id' => $contract->user_id,
            'worker_name' => 'IMPORT WORKER', 'worker_passport_number' => 'IMPORT-PASS',
            'visa_number' => 'IMPORT-VISA', 'external_office_name' => 'IMPORT OFFICE',
            'msaned_contract_number' => 'IMPORT-MS', 'ingaz_number' => 'IMPORT-ENJ',
        ]));
        $invoice = Invoice::withoutEvents(fn () => Invoice::create(['ingaz_delegation_id' => $delegation->id,
            'client_id' => $contract->user_id, 'subtotal' => 1000, 'vat_amount' => 0, 'total_due' => 1000]));
        $batch = $this->stage('musaned_transfers', [['reference_number' => 'DELEGATION-TRANSFER',
            'reference_type' => 'تفويض', 'reference_ingaz_number' => 'IMPORT-ENJ',
            'transfer_date' => now()->toDateString(), 'transferred_amount' => '950']]);
        $this->assertSame([], $batch->rows()->firstOrFail()->issues);
        $result = app(DataImportReviewService::class)->approve($batch, $this->actor->id);
        $this->assertSame(1, $result['created'], json_encode($result, JSON_UNESCAPED_UNICODE));
        $item = MusanedTransfer::where('reference_number', 'DELEGATION-TRANSFER')->firstOrFail()->items()->firstOrFail();
        $this->assertSame($delegation->id, $item->ingaz_delegation_id);
        $this->assertSame($invoice->id, $item->invoice_id);
        $this->assertNull($item->contract_id);
        Http::assertNothingSent();
    }

    public function test_matching_never_uses_a_contract_outside_the_accessible_branch(): void
    {
        $contract = $this->contract();
        $own = \App\Models\Branch::factory()->main()->create();
        $other = \App\Models\Branch::factory()->create();
        $contract->update(['branch_id' => $other->id]);
        $actor = Admin::factory()->forBranch($own)->create();
        $this->enableBranchesModule();
        $this->lockToOwnBranch($actor);
        $this->actingAs($actor, 'admin');
        $result = app(ImportReferenceResolver::class)->resolve('musaned_transfers', $this->transferRow($contract));
        $this->assertNotEmpty($result['issues']);
        $this->assertSame([], $result['links']);
    }

    public function test_dispatch_uses_normal_persistence_and_existing_dispatch_is_not_overwritten(): void
    {
        $contract = $this->contract();
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = \App\Models\ingaz\Religion::create(['ar' => ['title' => 'مسلم'], 'en' => ['title' => 'Muslim']]);
        $office = \App\Models\RecruitmentOffices::withoutEvents(fn () => \App\Models\RecruitmentOffices::create(['nationality_id' => $nationality]));
        $office->translations()->create(['locale' => 'ar', 'title' => 'مكتب اختبار']);
        $maid = \App\Models\ingaz\Maid::withoutEvents(fn () => \App\Models\ingaz\Maid::create([
            'name' => 'عامل اختبار', 'passport_num' => uniqid('P-'), 'nationality_id' => $nationality,
            'religion_id' => $religion->id, 'office_id' => $office->id, 'experience_status' => 'no_experience',
            'age' => 30, 'cv_file' => '', 'height' => '', 'weight' => '',
        ]));
        $contract->update(['maid_id' => $maid->id]);
        $row = ['reference_msaned_number' => $contract->info->msaned_number, 'dispatch_date' => now()->toDateString(),
            'arrival_station' => 'الرياض', 'office_contract_cost_usd' => '600'];
        $batch = $this->stage('external_office_dispatches', [$row]);
        $this->assertSame([], $batch->rows()->firstOrFail()->issues);
        $this->assertFalse($contract->externalOfficeDispatch()->exists());
        $result = app(DataImportReviewService::class)->approve($batch, $this->actor->id);
        $this->assertSame(1, $result['created'], json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->assertSame('600.00', $contract->externalOfficeDispatch()->firstOrFail()->office_contract_cost_usd);
        $again = $this->stage('external_office_dispatches', [array_replace($row, ['office_contract_cost_usd' => '700'])]);
        $this->assertSame('invalid', $again->rows()->firstOrFail()->status);
        $this->assertSame('600.00', $contract->externalOfficeDispatch()->firstOrFail()->office_contract_cost_usd);
        Http::assertNothingSent();
    }

    public function test_new_template_downloads_are_readable_excel_files(): void
    {
        $this->get(route('reports.import.index'))->assertOk()->assertSee('تحويلات مساند')->assertSee('الإرساليات / فواتير المورد');
        foreach (['musaned_transfers', 'external_office_dispatches'] as $type) {
            $httpResponse = $this->get(route('reports.import.template', $type))->assertOk();
            $response = $httpResponse->baseResponse;
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname());
            $this->assertGreaterThan(5, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($book->getActiveSheet()->getHighestColumn()));
            $book->disconnectWorksheets();
            unlink($response->getFile()->getPathname());
        }
    }

    public function test_import_does_not_bypass_the_normal_creation_permission(): void
    {
        $contract = $this->contract();
        $batch = $this->stage('musaned_transfers', [$this->transferRow($contract)]);
        $this->actor->revokePermissionTo(\App\PermKeys::MUSANED_TRANSFERS_CREATE);
        $this->actingAs($this->actor->fresh(), 'admin');
        $result = app(DataImportReviewService::class)->approve($batch, $this->actor->id);
        $this->assertSame(0, $result['created']);
        $this->assertStringContainsString('صلاحية', implode(' ', $result['errors']));
        $this->assertFalse(MusanedTransfer::where('reference_number', 'IMPORT-ONE')->exists());
    }
}

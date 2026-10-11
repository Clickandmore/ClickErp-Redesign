<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\DataImportBatch;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\PermKeys;
use App\Services\ingaz\DataImportReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DataImportReviewTest extends TestCase
{
    use DatabaseTransactions, \Tests\Concerns\InteractsWithBranches;

    private Admin $actor;
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        Storage::fake('local');
        $this->actor = Admin::factory()->forBranch(Branch::factory()->main()->create())->create();
        $this->actingAs($this->actor, 'admin');
        DB::table('fn_confirm_settings')->updateOrInsert(['title' => 'automatic_journal_entries'], ['value' => 'off']);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) if (is_file($file)) unlink($file);
        parent::tearDown();
    }

    private function grant(string ...$names): void
    {
        foreach ($names as $name) $this->actor->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
    }

    public function test_every_template_downloads_as_a_readable_workbook_without_redirects(): void
    {
        $this->grant(PermKeys::DATA_IMPORT_UPLOAD);
        foreach (app(\App\Services\ingaz\DataImportTemplateService::class)->templates() as $type => $template) {
            $response = $this->get(route('reports.import.template', $type));
            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $path = $response->baseResponse->getFile()->getPathname();
            $this->files[] = $path;
            $this->assertGreaterThan(1000, filesize($path), $type);
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            try {
                if ($type === 'maids') {
                    $sheet = $book->getSheetByName('البيانات');
                    $this->assertLessThan(10, $sheet->getHighestDataRow());
                    $this->assertCount(11, $sheet->getDataValidationCollection());
                    $this->assertSame('C2:C10000', $sheet->getDataValidation('C9999')->getSqref());
                }
                if ($type === 'arabia_recruitment_orders') {
                    $this->assertContains('تقرير_الطلبات', $book->getSheetNames());
                    $this->assertContains('الملاحظات', $book->getSheetNames());
                    $book->getSheetByName('تقرير_الطلبات')->setCellValue('A2', '987654')->setCellValue('B2', 'قيد العمل');
                    (new Xlsx($book))->save($path);
                    $rows = iterator_to_array(app(\App\Services\ingaz\ArabiaRecruitmentOrdersImportService::class)->reviewRows($path));
                    $this->assertNotEmpty($rows);
                }
                if ($type === 'alrafedin_transfer_services') {
                    $sheet = $book->getSheetByName('البيانات');
                    $sheet->fromArray(['1', '89', '2362', 'WORKER NAME', 'X0000000', '366', 'OLD CLIENT', '2723', 'NEW CLIENT'], null, 'A2');
                    $sheet->setCellValue('Q2', '2026-10-11');
                    (new Xlsx($book))->save($path);
                    $rows = app(\App\Services\ingaz\AlrafedinTransferServicesImportService::class)->parseReport($path);
                    $this->assertSame('89', $rows[0]['data']['operation_number']);
                    $this->assertSame('2026-10-11', $rows[0]['data']['transfer_date']);
                    $this->assertSame('transfer_service', $rows[0]['source']);
                }
            } finally {
                $book->disconnectWorksheets();
            }
        }
    }

    private function sheet(array $rows): UploadedFile
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $keys = array_keys($rows[0]);
        $sheet->fromArray($keys, null, 'A1');
        foreach ($rows as $index => $row) {
            foreach ($keys as $col => $key) {
                $sheet->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . ($index + 2), (string) ($row[$key] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'import-test-');
        $this->files[] = $path;
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        return new UploadedFile($path, 'test.xlsx', null, null, true);
    }

    private function invoiceRow(string $reference = 'unique-reference'): array
    {
        $client = User::withoutEvents(fn () => User::create(['name' => 'عميل استيراد اختبار', 'phone' => uniqid('import-')]));
        return ['import_reference' => $reference, 'client_id' => $client->id,
            'invoice_date' => now()->toDateString(), 'direct_recruitment_cost' => '100', 'direct_recruitment_count' => '1'];
    }

    public function test_upload_only_stages_and_shows_review_without_business_writes(): void
    {
        $this->grant(PermKeys::DATA_IMPORT_UPLOAD);
        $before = Invoice::count();
        $journals = DB::table('fn_constraints')->count();
        $this->postJson(route('reports.import.store'), ['type' => 'invoices', 'file' => $this->sheet([$this->invoiceRow()])])->assertOk()->assertJsonStructure(['redirect']);
        $batch = DataImportBatch::latest('id')->firstOrFail();
        $this->assertSame('pending', $batch->status);
        $this->assertSame(1, $batch->rows()->where('status', 'valid')->count());
        $this->assertSame($before, Invoice::count());
        $this->assertSame($journals, DB::table('fn_constraints')->count());
        $this->get(route('reports.import.show', $batch))->assertOk()->assertSee('unique-reference');
        $this->post(route('reports.import.approve', $batch))->assertForbidden();
        $this->get(route('reports.import.index'))->assertOk()->assertSee('ملفات المراجعة');
    }

    public function test_bad_dates_numbers_and_duplicate_rows_are_explained(): void
    {
        $row = $this->invoiceRow();
        $bad = array_merge($row, ['import_reference' => 'bad', 'invoice_date' => '2026-02-31', 'direct_recruitment_cost' => 'not-a-number']);
        $batch = app(DataImportReviewService::class)->stage('invoices', $this->sheet([$row, $row, $bad]), $this->actor->id);
        $this->assertSame(1, $batch->rows()->where('status', 'valid')->count());
        $invalid = $batch->rows()->where('status', 'invalid')->get();
        $this->assertCount(2, $invalid);
        $this->assertStringContainsString('مكرر داخل الملف', implode(' ', $invalid[0]->issues));
        $this->assertStringContainsString('تاريخ غير صحيح', implode(' ', $invalid[1]->issues));
        $this->assertStringContainsString('قيمة رقمية غير صحيحة', implode(' ', $invalid[1]->issues));
    }

    public function test_approval_issues_valid_invoices_once_and_rejects_repeated_reference(): void
    {
        $this->grant(PermKeys::DATA_IMPORT_UPLOAD, PermKeys::DATA_IMPORT_APPROVE, PermKeys::INVOICES_CREATE);
        $row = $this->invoiceRow();
        $batch = app(DataImportReviewService::class)->stage('invoices', $this->sheet([$row]), $this->actor->id);
        $this->post(route('reports.import.approve', $batch))->assertRedirect()->assertSessionHasNoErrors();
        $batch->refresh();
        $this->assertSame('approved', $batch->status, json_encode($batch->result, JSON_UNESCAPED_UNICODE));
        $invoice = Invoice::where('client_id', $row['client_id'])->firstOrFail();
        $this->assertEquals(100, $invoice->subtotal);
        $this->assertEquals(115, $invoice->total_due);
        $this->post(route('reports.import.approve', $batch))->assertRedirect();
        $this->assertSame(1, Invoice::where('client_id', $row['client_id'])->count());
        $again = app(DataImportReviewService::class)->stage('invoices', $this->sheet([$row]), $this->actor->id);
        $this->assertSame('invalid', $again->rows()->first()->status);
    }

    public function test_import_approval_does_not_bypass_invoice_permission(): void
    {
        $this->grant(PermKeys::DATA_IMPORT_APPROVE, PermKeys::INVOICES_CREATE_DRAFT);
        $row = $this->invoiceRow();
        $batch = app(DataImportReviewService::class)->stage('invoices', $this->sheet([$row]), $this->actor->id);
        $this->post(route('reports.import.approve', $batch))->assertForbidden();
        $this->assertFalse(Invoice::where('client_id', $row['client_id'])->exists());
    }

    public function test_recheck_detects_approval_of_another_pending_batch(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE);
        $row = $this->invoiceRow();
        $review = app(DataImportReviewService::class);
        $first = $review->stage('invoices', $this->sheet([$row]), $this->actor->id);
        $second = $review->stage('invoices', $this->sheet([$row]), $this->actor->id);
        $review->approve($first, $this->actor->id);
        $result = $review->approve($second, $this->actor->id);
        $this->assertNotEmpty($result['errors']);
        $this->assertSame('invalid', $second->rows()->first()->status);
        $this->assertSame(1, Invoice::where('client_id', $row['client_id'])->count());
    }

    public function test_execution_error_rolls_back_all_rows_including_previously_issued_invoice(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE);
        $cash = $this->configureAccounting();
        DB::table('fn_accounts')->where('type', \Nami\Finance\Enums\AccountTypesEnum::Clients->value)->delete();
        $row = $this->invoiceRow();
        DB::table('users')->where('id', $row['client_id'])->update(['account_id' => $cash->id]);
        $second = $this->invoiceRow('second-reference');
        $review = app(DataImportReviewService::class);
        $batch = $review->stage('invoices', $this->sheet([$row, $second]), $this->actor->id);
        $this->assertSame(2, $batch->rows()->where('status', 'valid')->count());
        $constraints = DB::table('fn_constraints')->count();
        $bonds = DB::table('fn_bonds')->count();
        $result = $review->approve($batch, $this->actor->id);
        $this->assertNotEmpty($result['errors']);
        $this->assertSame('pending', $batch->fresh()->status);
        $this->assertSame(0, Invoice::whereIn('client_id', [$row['client_id'], $second['client_id']])->count());
        $this->assertSame($constraints, DB::table('fn_constraints')->count());
        $this->assertSame($bonds, DB::table('fn_bonds')->count());
    }

    public function test_existing_client_and_phone_identity_conflict_are_rejected_before_execution(): void
    {
        $client = User::withoutEvents(fn () => User::create(['name' => 'عميل موجود', 'phone' => '599987654', 'national_num' => '1099876543']));
        $review = app(DataImportReviewService::class);
        $issues = $review->issues('clients', ['name' => 'عميل', 'phone' => '0599987654', 'id_number' => $client->national_num]);
        $this->assertStringContainsString('موجود بالفعل', implode(' ', $issues));
        $conflict = $review->issues('clients', ['name' => 'عميل مختلف', 'phone' => '0599987654', 'id_number' => '2099876543']);
        $this->assertStringContainsString('هوية مختلفة', implode(' ', $conflict));
    }

    public function test_upload_only_user_cannot_open_another_users_batch(): void
    {
        $this->grant(PermKeys::DATA_IMPORT_UPLOAD);
        $batch = app(DataImportReviewService::class)->stage('invoices', $this->sheet([$this->invoiceRow()]), $this->actor->id);
        $other = Admin::factory()->forBranch($this->actor->branch)->create();
        $other->givePermissionTo(Permission::findByName(PermKeys::DATA_IMPORT_UPLOAD, 'admin'));
        $this->actingAs($other, 'admin')->get(route('reports.import.show', $batch))->assertForbidden();
    }

    public function test_whole_file_with_invalid_rows_cannot_be_partially_imported(): void
    {
        $batch = app(DataImportReviewService::class)->stage('legacy_invoices', $this->sheet([
            ['invoice_number' => '876543210', 'invoice_date' => '2026-02-31', 'subtotal' => '100', 'total_due' => '115', 'vat_amount' => '15'],
        ]), $this->actor->id);
        $this->assertSame('invalid', $batch->rows()->first()->status);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(DataImportReviewService::class)->approve($batch, $this->actor->id);
    }

    public function test_routes_require_explicit_import_permissions(): void
    {
        $this->get(route('reports.import.index'))->assertForbidden();
        $this->post(route('reports.import.store'))->assertForbidden();
        $this->post(route('reports.import.generate-bonds'))->assertForbidden();
        $maidBatch = \App\Models\ingaz\MaidBulkImport::create(['uploaded_by' => $this->actor->id, 'original_filename' => 'permission-test.xlsx', 'uploaded_file_path' => 'unused', 'status' => 'pending_review']);
        $this->post(route('maids.bulk-import.approve', $maidBatch))->assertForbidden();
    }

    public function test_each_downloadable_data_template_can_be_reviewed_without_importing_its_example_or_markers(): void
    {
        $templates = app(\App\Services\ingaz\DataImportTemplateService::class);
        $review = app(DataImportReviewService::class);
        $before = [];
        foreach (['users', 'maids', 'contracts', 'invoices', 'fn_constraints', 'fn_years', 'fn_accounts', 'fn_bonds'] as $table) $before[$table] = DB::table($table)->count();
        foreach ($templates->templates() as $type => $template) {
            if (in_array($type, ['maids', 'arabia_cvs', 'arabia_clients', 'alrafedin_transfer_services', 'arabia_recruitment_orders'], true)) continue;
            $response = $templates->createTemplateDownloadResponse($type);
            $path = $response->getFile()->getPathname();
            $this->files[] = $path;
            $batch = $review->stage($type, new UploadedFile($path, $template['filename'], null, null, true), $this->actor->id);
            $this->assertSame($type === 'finance_constraints' ? 2 : 1, $batch->rows()->count(), $type . ': template marker rows must be skipped');
        }
        foreach ($before as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table . ': preview must be read-only');
    }

    public function test_finance_preview_does_not_reopen_closed_years_and_flags_unbalanced_groups(): void
    {
        DB::table('fn_years')->delete();
        $year = \Nami\Finance\Models\FnYear::create(['title' => 'سنة مغلقة', 'from_date' => '2030-01-01', 'to_date' => '2030-12-31', 'status' => 0, 'created_by_id' => $this->actor->id]);
        $row = ['constraint_ref' => 'review-journal', 'date' => '2030-10-11', 'statement' => 'اختبار',
            'line_type' => 'debtor', 'account_code' => '12345', 'amount' => '100'];
        $batch = app(DataImportReviewService::class)->stage('finance_constraints', $this->sheet([
            $row, array_merge($row, ['line_type' => 'creditor', 'amount' => '90']),
        ]), $this->actor->id);
        $this->assertSame(0, (int) $year->fresh()->status);
        $this->assertSame(1, DB::table('fn_years')->count());
        $this->assertSame(2, $batch->rows()->where('status', 'invalid')->count());
        $this->assertStringContainsString('غير متوازن', implode(' ', $batch->rows()->first()->issues));
        $this->assertStringContainsString('سنة مالية نشطة', implode(' ', $batch->rows()->first()->issues));
    }

    public function test_review_is_isolated_to_the_active_branch(): void
    {
        $this->enableBranchesModule();
        $this->lockToOwnBranch($this->actor);
        $this->grant(PermKeys::DATA_IMPORT_REVIEW);
        $batch = DataImportBatch::create(['type' => 'clients', 'path' => 'unused.xlsx', 'file_hash' => str_repeat('0', 64),
            'original_name' => 'other-branch.xlsx', 'created_by' => $this->actor->id, 'branch_id' => Branch::factory()->create()->id]);
        $this->get(route('reports.import.show', $batch))->assertNotFound();
    }

    public function test_sponsorship_import_preserves_request_reference_and_date_without_duplicates(): void
    {
        $sponsor = User::withoutEvents(fn () => User::create(['name' => 'عميل نقل اختبار', 'phone' => '598765432', 'national_num' => '1098765432']));
        $maid = \App\Models\ingaz\Maid::withoutEvents(fn () => \App\Models\ingaz\Maid::create(['passport_num' => 'IMPORT-TRANSFER-TEST', 'name' => 'IMPORT TEST']));
        \App\Models\Contract::withoutEvents(fn () => \App\Models\Contract::create(['user_id' => $sponsor->id, 'maid_id' => $maid->id, 'status' => 'new']));
        $row = ['request_number' => 'ST-IMPORT-TEST', 'request_date' => '2026-10-01', 'client_phone' => '0598765432',
            'client_id_number' => $sponsor->national_num, 'maid_passport' => $maid->passport_num, 'transfer_status' => 'قيد المراجعة', 'amount' => '100', 'trial_start_date' => '2026-10-01'];
        $service = app(\App\Services\ingaz\DataImportExecutionService::class);
        $method = new \ReflectionMethod($service, 'importSponsorshipTransfer');
        $method->setAccessible(true);
        $this->assertSame('created', $method->invoke($service, $row, $this->actor->id));
        $this->assertSame('updated', $method->invoke($service, $row, $this->actor->id));
        $query = \App\Models\SponsorshipTransfer::where('external_source', 'data_import')->where('external_operation_number', 'ST-IMPORT-TEST');
        $this->assertSame(1, $query->count());
        $this->assertSame('2026-10-01', $query->first()->created_at->toDateString());
    }

    public function test_duplicate_cv_passports_are_flagged_in_the_existing_review_screen(): void
    {
        $batch = \App\Models\ingaz\MaidBulkImport::create(['uploaded_by' => $this->actor->id, 'original_filename' => 'duplicate-cvs.xlsx', 'uploaded_file_path' => 'unused', 'status' => 'pending_review']);
        $method = new \ReflectionMethod(\App\Services\ingaz\MaidBulkImportService::class, 'persistImportRows');
        $method->setAccessible(true);
        $row = ['row_number' => 2, 'status' => 'pending', 'raw_data' => [], 'normalized_data' => ['passport_num' => 'CV-DUPLICATE'], 'error_messages' => []];
        $method->invoke(app(\App\Services\ingaz\MaidBulkImportService::class), $batch, [$row, array_merge($row, ['row_number' => 3])]);
        $this->assertSame('invalid', $batch->rows()->where('row_number', 3)->first()->status);
        $this->assertStringContainsString('مكرر داخل الملف', implode(' ', $batch->rows()->where('row_number', 3)->first()->error_messages));
        $this->get(route('maids.bulk-import.show', $batch))->assertForbidden();
        $this->grant(PermKeys::DATA_IMPORT_UPLOAD);
        $this->get(route('maids.bulk-import.show', $batch))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 2)->assertSee('مكرر داخل الملف');
        $this->post(route('maids.bulk-import.approve', $batch))->assertForbidden();
    }

    private function configureAccounting(): \Nami\Finance\Models\Account
    {
        $makeAccount = function ($name, $status, $type = 1) {
            return \Nami\Finance\Models\Account::create([
                'name' => $name, 'status' => $status, 'type' => $type,
                'code' => ((int) DB::table('fn_accounts')->max('code')) + 1,
                'level' => 1, 'operation_type' => 1, 'statement_type' => 'income_statement', 'created_by_id' => $this->actor->id,
            ]);
        };
        $clientRoot = $makeAccount('عملاء اختبار الاستيراد', 'debtor', \Nami\Finance\Enums\AccountTypesEnum::Clients->value);
        $revenue = $makeAccount('إيراد اختبار الاستيراد', 'creditor');
        $tax = $makeAccount('ضريبة اختبار الاستيراد', 'creditor');
        $cash = $makeAccount('صندوق اختبار الاستيراد', 'debtor', \Nami\Finance\Enums\AccountTypesEnum::Box->value);
        foreach (['automatic_journal_entries' => 'on', 'general_invoice_revenue_account' => $revenue->id,
            'revenue_tax_account' => $tax->id, 'bond_qabd_account' => $cash->id] as $key => $value) {
            DB::table('fn_confirm_settings')->updateOrInsert(['title' => $key], ['value' => (string) $value, 'created_at' => now(), 'updated_at' => now()]);
        }
        \Nami\Finance\Models\FnYear::create(['title' => 'سنة اختبار الاستيراد', 'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(), 'status' => 1, 'created_by_id' => $this->actor->id]);
        DB::table('fn_constraint_code_sequences')->updateOrInsert(['id' => 1], [
            'last_code' => (int) DB::table('fn_constraints')->max('code'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $cash;
    }

    public function test_imported_invoice_posts_the_same_balanced_journals_and_receipt_as_manual_issuance(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE);
        $this->configureAccounting();
        $row = $this->invoiceRow('financial-reference');
        $review = app(DataImportReviewService::class);
        $batch = $review->stage('invoices', $this->sheet([$row]), $this->actor->id);
        $constraintsBefore = DB::table('fn_constraints')->count();
        $bondsBefore = DB::table('fn_bonds')->count();
        $result = $review->approve($batch, $this->actor->id);
        $this->assertEmpty($result['errors'], json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->assertSame($constraintsBefore + 2, DB::table('fn_constraints')->count());
        $this->assertSame($bondsBefore + 1, DB::table('fn_bonds')->count());
        $newConstraints = DB::table('fn_constraints')->orderByDesc('id')->limit(2)->get();
        foreach ($newConstraints as $constraint) {
            $details = DB::table('fn_constraint_details')->where('constraint_id', $constraint->id)->get();
            $this->assertEqualsWithDelta(115, $details->sum('debtor_value'), 0.001);
            $this->assertEqualsWithDelta($details->sum('debtor_value'), $details->sum('creditor_value'), 0.001);
        }
        $review->approve($batch, $this->actor->id);
        $this->assertSame($constraintsBefore + 2, DB::table('fn_constraints')->count());
        $this->assertSame($bondsBefore + 1, DB::table('fn_bonds')->count());
    }
}

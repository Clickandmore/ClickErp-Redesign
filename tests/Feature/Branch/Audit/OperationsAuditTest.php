<?php

namespace Tests\Feature\Branch\Audit;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Http\Controllers\ingaz\RentContractController;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\DeportationProcedure;
use App\Models\IngazDelegation;
use App\Models\Hourly\HourlyCashCollection;
use App\Models\Hourly\HourlyCashRemittance;
use App\Models\Hourly\HourlyOrderPayment;
use App\Models\Hourly\HourlyOrderStatusLog;
use App\Models\Hourly\HourlyRentalOrder;
use App\Models\Hourly\HourlyVisit;
use App\Models\RentalContract;
use App\Models\RentalContractExpiryReminder;
use App\Models\TransferServiceContract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Maid;
use App\Models\ingaz\OrderNote;
use App\NewPermKeys;
use App\PermKeys;
use App\Services\ingaz\CompletedRecruitmentMaidReleaseService;
use App\Support\BranchQueryContext;
use App\Support\BranchSharingSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * مشكلات العمليات (O1–O9) في تقرير مراجعة الفروع.
 *
 * @group branch-audit
 */
class OperationsAuditTest extends BranchAuditTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BranchSharingSettings::persist(['share_maids' => false]);
        BranchSharingSettings::flush();
        BranchQueryContext::flush();
    }

    // ---------------------------------------------------------------- O1

    /**
     * المشكلة: رقم عقد التأجير التالي يُحسب من آخر عقد في فرع الموظف بينما العمود فريد على مستوى الشركة.
     * المتوقع: الرقم المولَّد لا يساوي رقماً موجوداً في فرع آخر، وأكبر من آخر رقم في الشركة.
     */
    /** @test */
    public function o1_rental_contract_number_is_unique_company_wide(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $year = date('Y');
        $userId = $this->makeClient();

        $base = $this->maxSequence('rental_contracts', 'RC-' . $year . '-');
        $existing = [];
        foreach ([[$a, $base + 10], [$b, $base + 11], [$b, $base + 42]] as [$branch, $n]) {
            $number = 'RC-' . $year . '-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            DB::table('rental_contracts')->insert([
                'user_id' => $userId, 'status' => 'new', 'contract_number' => $number,
                'branch_id' => $branch->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $existing[] = $number;
        }

        $this->actingAsAdmin($this->lockedBranchAdmin($a));

        $controller = (new \ReflectionClass(RentContractController::class))->newInstanceWithoutConstructor();
        $next = $this->invokePrivate($controller, 'generateContractNumber');

        $this->assertNotContains($next, $existing, "الرقم {$next} مستخدم مسبقاً في فرع آخر.");
        $this->assertGreaterThan($base + 42, (int) substr($next, -4));
    }

    /**
     * المشكلة: رقم عقد نقل الخدمات يُحسب من آخر عقد مرئي في فرع الموظف فقط.
     * المتوقع: الرقم المولَّد لا يتكرر مع رقم عقد في فرع آخر.
     */
    /** @test */
    public function o1_transfer_service_contract_number_is_unique_company_wide(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $year = now()->year;
        $prefix = 'TSC' . $year;

        $base = $this->maxSequence('transfer_service_contracts', $prefix);
        $existing = [];
        foreach ([[$a, $base + 10], [$b, $base + 11], [$b, $base + 42]] as [$branch, $n]) {
            $number = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('transfer_service_contracts')->insert([
                'sponsorship_transfer_id' => 0, 'contract_id' => 0, 'new_sponsor_id' => 0, 'maid_id' => 0,
                'contract_number' => $number, 'contract_value' => 0, 'remaining_amount' => 0,
                'trial_days' => 0, 'trial_start_date' => now()->toDateString(),
                'contract_start_date' => now()->toDateString(), 'created_by' => 0,
                'branch_id' => $branch->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $existing[] = $number;
        }

        $this->actingAsAdmin($this->lockedBranchAdmin($a));

        $next = (new TransferServiceContract())->generateContractNumber();

        $this->assertNotContains($next, $existing, "الرقم {$next} مستخدم مسبقاً في فرع آخر.");
        $this->assertGreaterThan($base + 42, (int) substr($next, -4));
    }

    /**
     * المشكلة: كود سيرة التأجير التالي (RENT-xxxx) يُحسب ويُتحقق من تفرده داخل فرع الموظف فقط.
     * المتوقع: الكود المولَّد لا يطابق كود عاملة موجودة في فرع آخر.
     */
    /** @test */
    public function o1_rental_cv_code_is_unique_company_wide(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];

        $base = (int) DB::table('maids')->where('cv_code', 'LIKE', 'RENT-%')
            ->selectRaw('MAX(CAST(SUBSTRING(cv_code, 6) AS UNSIGNED)) as n')->value('n');
        $this->makeMaid($a->id, ['cv_code' => 'RENT-' . str_pad((string) ($base + 10), 4, '0', STR_PAD_LEFT)]);
        $taken = 'RENT-' . str_pad((string) ($base + 11), 4, '0', STR_PAD_LEFT);
        $this->makeMaid($b->id, ['cv_code' => $taken]);

        $this->actingAsAdmin($this->lockedBranchAdmin($a));

        $code = $this->invokePrivate(app(MaidAction::class), 'nextRentalCvCode');

        $this->assertFalse(
            Maid::query()->withoutBranchScope()->where('cv_code', $code)->exists(),
            "الكود {$code} مستخدم مسبقاً لعاملة في فرع آخر."
        );
    }

    // ---------------------------------------------------------------- O2

    /**
     * المشكلة: Invoice::resolveBranchIdForCreate يتجاهل ingaz_delegation_id فتأخذ الفاتورة فرع المستخدم (أو لا شيء).
     * المتوقع: فاتورة التفويض ترث فرع التفويض.
     */
    /** @test */
    public function o2_invoice_inherits_branch_from_its_ingaz_delegation(): void
    {
        $b = $this->makeBranch();
        $this->actingAsAdmin($this->globalAdmin());

        $delegationId = $this->makeDelegationRow(['branch_id' => $b->id]);

        $invoice = new Invoice(['ingaz_delegation_id' => $delegationId]);
        $resolved = $this->invokePrivate($invoice, 'resolveBranchIdForCreate');

        $this->assertSame($b->id, $resolved === null ? null : (int) $resolved);
    }

    // ---------------------------------------------------------------- O3

    /**
     * المشكلة: ContractInfo لا يرث فرع عقده؛ يأخذ فرع المستخدم (الإدارة العامة = لا فرع).
     * المتوقع: بيانات العقد تُختم بفرع العقد.
     */
    /** @test */
    public function o3_contract_info_inherits_the_contract_branch(): void
    {
        $b = $this->makeBranch();
        $contractId = $this->makeContractRow(['branch_id' => $b->id]);
        $this->actingAsAdmin($this->globalAdmin());

        $info = ContractInfo::query()->forceCreate(['contract_id' => $contractId]);

        $this->assertSame($b->id, (int) $info->fresh()->branch_id);
    }

    /**
     * المشكلة: إجراء الترحيل يأخذ فرع العاملة لا فرع العقد.
     * المتوقع: عقد في الفرع B وعاملة في الفرع A → الإجراء في الفرع B.
     */
    /** @test */
    public function o3_deportation_procedure_inherits_the_contract_branch_not_the_maid(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $maidId = $this->makeMaid($a->id);
        $contractId = $this->makeContractRow(['branch_id' => $b->id, 'maid_id' => $maidId]);
        $this->actingAsAdmin($this->globalAdmin());

        $procedure = DeportationProcedure::query()->forceCreate([
            'contract_id' => $contractId,
            'maid_id' => $maidId,
        ]);

        $this->assertSame($b->id, (int) $procedure->fresh()->branch_id);
    }

    /**
     * المشكلة: ملاحظة الطلب تأخذ فرع الموظف كاتب الملاحظة لا فرع الطلب.
     * المتوقع: طلب في الفرع B وملاحظة من موظف الفرع A → الملاحظة في الفرع B.
     */
    /** @test */
    public function o3_order_note_inherits_the_order_branch_not_the_author(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $author = $this->branchAdmin($a);
        $orderId = $this->makeOrderRow(['branch_id' => $b->id]);
        $this->actingAsAdmin($this->globalAdmin());

        $note = OrderNote::query()->forceCreate([
            'order_id' => $orderId,
            'admin_id' => $author->id,
            'note' => 'branch audit',
        ]);

        $this->assertSame($b->id, (int) $note->fresh()->branch_id);
    }

    /**
     * ضابط: تذكير انتهاء عقد التأجير يرث فرع العقد (أُصلح سابقاً) — يجب أن ينجح.
     */
    /** @test */
    public function o3_control_rental_expiry_reminder_inherits_the_contract_branch(): void
    {
        $b = $this->makeBranch();
        $contractId = DB::table('rental_contracts')->insertGetId([
            'user_id' => $this->makeClient(), 'status' => 'active', 'branch_id' => $b->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAsAdmin($this->globalAdmin());

        $reminder = RentalContractExpiryReminder::query()->forceCreate([
            'rental_contract_id' => $contractId,
            'token' => Str::random(64),
            'expires_on' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertSame($b->id, (int) $reminder->fresh()->branch_id);
    }

    // ---------------------------------------------------------------- O4

    /** @test */
    public function o4_hourly_operational_tables_are_owned_by_a_branch(): void
    {
        foreach ([
            'hourly_rental_orders',
            'hourly_visits',
            'hourly_order_payments',
            'hourly_order_status_logs',
            'hourly_cash_collections',
            'hourly_cash_remittances',
        ] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'branch_id'), "جدول {$table} لا يحتوي branch_id.");
        }
    }

    /** @test */
    public function o4_hourly_order_payments_visits_and_cash_inherit_the_order_branch_and_are_isolated(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $adminA = $this->lockedBranchAdmin($a);
        $packageId = DB::table('hourly_packages')->value('id') ?: DB::table('hourly_packages')->insertGetId([
            'code' => 'AUDIT-' . Str::random(8), 'category' => 'audit', 'hours_per_visit' => 4,
            'workers_per_visit' => 1, 'visits_count' => 1, 'validity_days' => 30,
            'tax_rate' => 15, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $userId = $this->makeClient(['branch_id' => $a->id]);
        $this->actingAsAdmin($adminA);

        $order = HourlyRentalOrder::query()->forceCreate([
            'order_number' => 'AUDIT-O-' . Str::uuid(), 'invoice_number' => 'AUDIT-I-' . Str::uuid(),
            'user_id' => $userId, 'city_id' => 1, 'hourly_package_id' => $packageId,
            'source' => 'admin', 'created_by_admin_id' => $adminA->id, 'package_snapshot' => [],
            'address_text' => 'Branch audit', 'latitude' => 24.7136, 'longitude' => 46.6753,
            'hours_per_visit' => 4, 'workers_per_visit' => 1, 'visits_total' => 1, 'tax_rate' => 15,
            'starts_at' => now(), 'expires_at' => now()->addMonth(), 'status' => 'active',
            'payment_method' => 'cash',
        ]);
        $visit = $order->visits()->create([
            'visit_number' => 1, 'planned_hours' => 4, 'workers_count' => 1, 'visit_price' => 100,
            'address_text' => 'Branch audit', 'latitude' => 24.7136, 'longitude' => 46.6753,
            'city_id' => 1, 'status' => 'scheduled',
        ]);
        $payment = $order->payments()->create([
            'user_id' => $userId, 'public_id' => (string) Str::uuid(), 'method' => 'cash',
            'amount' => 100, 'status' => 'paid',
        ]);
        $log = $order->statusLogs()->create([
            'from_status' => 'pending', 'to_status' => 'active', 'actor_type' => 'admin',
            'actor_id' => $adminA->id,
        ]);
        $collection = HourlyCashCollection::query()->forceCreate([
            'payment_id' => $payment->id, 'order_id' => $order->id, 'visit_id' => $visit->id,
            'holder_type' => 'admin', 'holder_id' => $adminA->id, 'amount' => 100,
            'remitted_amount' => 0, 'request_key' => hash('sha256', (string) Str::uuid()),
        ]);
        $remittance = HourlyCashRemittance::query()->forceCreate([
            'collection_id' => $collection->id, 'admin_id' => $adminA->id, 'amount' => 100,
            'reference' => 'AUDIT', 'request_key' => hash('sha256', (string) Str::uuid()),
        ]);

        foreach ([$order, $visit, $payment, $log, $collection, $remittance] as $record) {
            $this->assertSame($a->id, (int) $record->fresh()->branch_id);
        }

        $this->actingAsAdmin($this->lockedBranchAdmin($b));
        BranchQueryContext::flush();

        $this->assertNull(HourlyRentalOrder::find($order->id));
        $this->assertNull(HourlyVisit::find($visit->id));
        $this->assertNull(HourlyOrderPayment::find($payment->id));
        $this->assertNull(HourlyOrderStatusLog::find($log->id));
        $this->assertNull(HourlyCashCollection::find($collection->id));
        $this->assertNull(HourlyCashRemittance::find($remittance->id));
    }

    // ---------------------------------------------------------------- O5

    /**
     * المشكلة: عقد الاستقدام يأخذ فرع المسوّق حتى لو أنشأه موظف فرع آخر.
     * المتوقع: موظف الفرع A ينشئ عقداً لمسوّق من الفرع B بلا branch_id صريح → العقد في الفرع A.
     */
    /** @test */
    public function o5_recruitment_contract_takes_the_acting_admin_branch_over_the_marketer(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $marketer = $this->branchAdmin($b);
        $this->actingAsAdmin($this->branchAdmin($a));

        $contract = Contract::query()->forceCreate([
            'type' => 'recruitment',
            'status' => 'new',
            'marketer_id' => $marketer->id,
        ]);

        $this->assertSame($a->id, (int) DB::table('contracts')->where('id', $contract->id)->value('branch_id'));
    }

    /**
     * المشكلة: التفويض يأخذ فرع العميل بدل فرع الموظف المنشئ.
     * المتوقع: موظف الفرع A ينشئ تفويضاً لعميل مسجّل في الفرع B → التفويض في الفرع A.
     */
    /** @test */
    public function o5_delegation_takes_the_acting_admin_branch_over_the_client(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $userId = $this->makeClient(['branch_id' => $b->id]);
        $this->actingAsAdmin($this->branchAdmin($a));

        $delegation = IngazDelegation::query()->forceCreate([
            'user_id' => $userId,
            'delegation_date_gregorian' => now()->toDateString(),
            'nationality_id' => $this->makeNationality(),
            'visa_number' => 'V' . random_int(100000, 999999),
        ]);

        $this->assertSame($a->id, (int) DB::table('ingaz_delegations')->where('id', $delegation->id)->value('branch_id'));
    }

    // ---------------------------------------------------------------- O6

    /**
     * المشكلة: خيارات فلترة عقود الاستقدام تُخزَّن بمفتاح واحد للشركة، فيرى موظف الفرع B مسوّقي الفرع A.
     * المتوقع: بعد تحميل موظف A للخيارات، موظف B يحصل على مسوّقي فرعه لا قيمة A المخزنة.
     */
    /** @test */
    public function o6_recruitment_filter_options_cache_is_per_branch(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $marketerA = $this->branchAdmin($a, ['name' => 'Marketer A ' . uniqid()]);
        $marketerB = $this->branchAdmin($b, ['name' => 'Marketer B ' . uniqid()]);
        $this->makeContractRow(['branch_id' => $a->id, 'marketer_id' => $marketerA->id, 'type' => 'recruitment']);
        $this->makeContractRow(['branch_id' => $b->id, 'marketer_id' => $marketerB->id, 'type' => 'recruitment']);

        $adminA = $this->lockedBranchAdmin($a, PermKeys::REC_CONTRACTS_VIEW);
        $adminB = $this->lockedBranchAdmin($b, PermKeys::REC_CONTRACTS_VIEW);

        $this->actingAsAdmin($adminA);
        $optionsA = app(RecruitmentContractController::class)->getFilterOptions();
        $this->assertContains($marketerA->id, collect($optionsA['marketers'])->pluck('id')->all());

        $this->actingAsAdmin($adminB);
        $optionsB = app(RecruitmentContractController::class)->getFilterOptions();
        $ids = collect($optionsB['marketers'])->pluck('id')->all();

        $this->assertContains($marketerB->id, $ids, 'موظف الفرع B حصل على خيارات الفرع A المخزنة.');
        $this->assertNotContains($marketerA->id, $ids);
    }

    /**
     * المشكلة: إحصاءات المكاتب في لوحة التحكم تُخزَّن بمفتاح 'admin_dashboard.office_stats.v3' بلا فرع.
     * المتوقع: مفتاح التخزين يتضمن الفرع النشط (فحص هيكلي — تشغيل اللوحة كاملة ثقيل).
     */
    /** @test */
    public function o6_dashboard_office_stats_cache_key_includes_the_branch(): void
    {
        $source = file_get_contents((new \ReflectionClass(DashboardController::class))->getFileName());
        $this->assertMatchesRegularExpression('/admin_dashboard\.office_stats/', $source);

        preg_match('/Cache::remember\(([^;]*?)office_stats([^,]*),/s', $source, $m);
        $statementStart = strpos($source, 'admin_dashboard.office_stats');
        $line = substr($source, strrpos(substr($source, 0, $statementStart), "\n"), 400);
        $keyExpression = strtok(ltrim($line), ',');

        $this->assertMatchesRegularExpression(
            '/branch/i',
            $keyExpression,
            'مفتاح تخزين إحصاءات المكاتب لا يتضمن الفرع: ' . trim($keyExpression)
        );
    }

    // ---------------------------------------------------------------- O7

    /**
     * المشكلة: فحص "عقد استقدام نشط آخر للعاملة" يمر عبر عزل الفرع، فلا يرى عقد الفرع B.
     * المتوقع: من سياق موظف الفرع A، العقد النشط في الفرع B يجعل العاملة مشغولة.
     */
    /** @test */
    public function o7_release_service_sees_active_contract_in_another_branch(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $maidId = $this->makeMaid($a->id);
        $completedId = $this->makeContractRow(['branch_id' => $a->id, 'maid_id' => $maidId, 'status' => 'completed']);
        $this->makeContractRow(['branch_id' => $b->id, 'maid_id' => $maidId, 'status' => 'new']);

        $this->actingAsAdmin($this->lockedBranchAdmin($a));
        $completed = Contract::query()->findOrFail($completedId);

        $busy = $this->invokePrivate(app(CompletedRecruitmentMaidReleaseService::class), 'hasOtherActiveContract', $completed);

        $this->assertTrue($busy, 'العقد النشط في الفرع B غير مرئي لفحص انشغال العاملة.');
    }

    /**
     * المشكلة: فحص "طلب نشط مرتبط بالعاملة" محصور بفرع الموظف.
     * المتوقع: طلب جديد للعاملة في الفرع B يجعلها مشغولة من سياق الفرع A.
     */
    /** @test */
    public function o7_release_service_sees_blocking_order_in_another_branch(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $maidId = $this->makeMaid($a->id);
        $this->makeOrderRow(['branch_id' => $b->id, 'maid_id' => $maidId, 'status' => 'new']);

        $this->actingAsAdmin($this->lockedBranchAdmin($a));

        $busy = $this->invokePrivate(app(CompletedRecruitmentMaidReleaseService::class), 'hasBlockingOrder', $maidId);

        $this->assertTrue($busy, 'الطلب النشط في الفرع B غير مرئي لفحص انشغال العاملة.');
    }

    // ---------------------------------------------------------------- O8

    /**
     * المشكلة: إشعار الشكوى الجديدة يصل لكل موظف يملك صلاحية الشكاوى في كل الفروع.
     * المتوقع: شكوى في الفرع A تُشعر موظف A ولا تُشعر موظف الفرع B.
     */
    /** @test */
    public function o8_new_complaint_notifies_only_admins_of_its_branch(): void
    {
        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $adminA = $this->lockedBranchAdmin($a, PermKeys::COMPLAINT_VIEW);
        $adminB = $this->lockedBranchAdmin($b, PermKeys::COMPLAINT_VIEW);

        Auth::guard('admin')->logout();
        BranchQueryContext::flush();

        Complaint::query()->forceCreate([
            'user_id' => $this->makeClient(),
            'address' => 'branch audit',
            'message' => 'branch audit complaint',
            'branch_id' => $a->id,
        ]);

        $notified = DB::table('admin_notifications')->where('type', 'new_complaint')
            ->whereIn('admin_id', [$adminA->id, $adminB->id])->pluck('admin_id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($adminA->id, $notified);
        $this->assertNotContains($adminB->id, $notified, 'موظف الفرع B تلقى إشعار شكوى الفرع A.');
    }

    // ---------------------------------------------------------------- O9

    /**
     * المشكلة: قوائم اختيار المسوّقين والموظفين تعرض موظفي كل الفروع؛ لا يوجد Admin::scopeVisibleToActiveBranch.
     * المتوقع: النطاق موجود، ومن سياق الفرع A يستبعد موظفي الفرع B.
     */
    /** @test */
    public function o9_admin_has_visible_to_active_branch_scope(): void
    {
        $this->assertTrue(
            method_exists(Admin::class, 'scopeVisibleToActiveBranch'),
            'Admin::scopeVisibleToActiveBranch غير موجود.'
        );

        [$a, $b] = [$this->makeBranch(), $this->makeBranch()];
        $own = $this->branchAdmin($a);
        $other = $this->branchAdmin($b);
        $this->actingAsAdmin($this->lockedBranchAdmin($a));

        $ids = Admin::query()->visibleToActiveBranch()->whereIn('id', [$own->id, $other->id])->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    // ---------------------------------------------------------------- helpers

    private function invokePrivate(object $target, string $method, ...$args)
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($target, ...$args);
    }

    private function maxSequence(string $table, string $prefix): int
    {
        return (int) DB::table($table)->where('contract_number', 'LIKE', $prefix . '%')
            ->pluck('contract_number')->map(fn ($n) => (int) substr($n, -4))->max();
    }

    private function makeClient(array $attributes = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'Branch audit client',
            'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => 'x',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function makeNationality(): int
    {
        return DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    }

    private function makeMaid(int $branchId, array $attributes = []): int
    {
        return DB::table('maids')->insertGetId(array_merge([
            'name' => 'Branch audit maid',
            'passport_num' => 'BA-' . str_replace('.', '', uniqid('', true)),
            'nationality_id' => $this->makeNationality(),
            'religion_id' => DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]),
            'age' => 30,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'available',
            'available' => true,
            'is_show' => true,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function makeContractRow(array $attributes = []): int
    {
        return DB::table('contracts')->insertGetId(array_merge([
            'type' => 'recruitment',
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function makeOrderRow(array $attributes = []): int
    {
        DB::table('job_types')->updateOrInsert(['id' => 1], ['image' => '', 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('orders')->insertGetId(array_merge([
            'user_id' => $this->makeClient(),
            'order_code' => 'BA-' . uniqid(),
            'job_type_id' => 1,
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function makeDelegationRow(array $attributes = []): int
    {
        return DB::table('ingaz_delegations')->insertGetId(array_merge([
            'delegation_date_gregorian' => now()->toDateString(),
            'nationality_id' => $this->makeNationality(),
            'visa_number' => 'V' . random_int(100000, 999999),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}

<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\Country;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\RecruitmentOffices;
use App\Services\ingaz\ClientRegistrationService;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\ClientCategory;

/**
 * قوائم الاختيار لأدوات الإضافة (معرّف + اسم)، حتى يرسل المساعد معرّفات حقيقية بدل التخمين.
 * تظهر لمن يملك صلاحية إضافة عميل أو إضافة سيرة.
 */
class FormOptionsTool extends McpTool
{
    private const FORMS = ['customer' => 'clients.store', 'maid' => 'maids.create', 'order' => 'orders.create'];

    public function name(): string
    {
        return 'form_options';
    }

    public function description(): string
    {
        return 'قوائم الاختيار لنماذج الإضافة: form=maid (الجنسيات، الديانات، أنواع الخدمة، المهن، المكاتب) '
            . 'وform=customer (الدول، أنواع العميل، الحالات الاجتماعية، مفاتيح الجوال، التصنيفات، الفروع). '
            . 'وform=order (أنواع الطلبات وباقات الإيجار). استدعها قبل create_maid_cv أو create_customer أو create_order. Picklists (id + name) for the create forms.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['form' => ['type' => 'string', 'enum' => array_keys(self::FORMS)]],
            'required' => ['form'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return null;
    }

    public function category(): string
    {
        return 'system';
    }

    public function isAllowedFor(Admin $admin): bool
    {
        return collect(self::FORMS)->contains(fn ($route) => self::routeAllows($admin, $route));
    }

    /** باقات الإيجار لأداة create_order؛ nationality_id الفارغ يعني أنها لكل الجنسيات. */
    private function orderOptions(): array
    {
        return [
            'order_types' => [
                ['id' => 'recruitment', 'name' => 'استقدام'],
                ['id' => 'rental', 'name' => 'إيجار عمالة'],
                ['id' => 'immediate', 'name' => 'استلام فوري'],
            ],
            'rental_packages' => \App\Models\ingaz\Package::query()->visible()->get()->map(fn ($package) => [
                'id' => $package->id,
                'name' => $package->title,
                'nationality_id' => $package->nationality_id,
            ])->values()->all(),
        ];
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $form = (string) ($arguments['form'] ?? '');

        if (!isset(self::FORMS[$form]) || !self::routeAllows($admin, self::FORMS[$form])) {
            throw new McpToolException('لا تملك صلاحية هذا النموذج، أو النموذج غير معروف (maid أو customer).');
        }

        return match ($form) {
            'maid' => $this->maidOptions(),
            'order' => $this->orderOptions(),
            default => $this->customerOptions($admin),
        };
    }

    private function maidOptions(): array
    {
        $titled = fn (string $model) => $model::query()->withTranslation()->get()
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->title])
            ->filter(fn ($row) => filled($row['name']))
            ->sortBy('name')->values()->all();

        return [
            'nationalities' => $titled(Nationality::class),
            'religions' => $titled(Religion::class),
            'job_types' => $titled(JobType::class),
            'job_titles' => $titled(JobTitle::class),
            'offices' => $titled(RecruitmentOffices::class),
            'genders' => ['female' => 'أنثى', 'male' => 'ذكر'],
        ];
    }

    private function customerOptions(Admin $admin): array
    {
        $registration = app(ClientRegistrationService::class);
        $countries = Country::query()
            ->when(Schema::hasColumn('countries', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name_ar')
            ->get(['id', 'name_ar'])
            ->map(fn ($country) => ['id' => $country->id, 'name' => $country->name_ar])->all();

        $options = [
            'client_types' => ['person' => 'فرد', 'company' => 'شركة'],
            'marital_statuses' => ['single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل'],
            'phone2_codes' => ClientRegistrationService::PHONE2_COUNTRY_CODES,
            'countries' => $countries,
            'client_categories' => ClientCategory::query()->orderBy('name')->get(['id', 'name'])->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])->all(),
        ];

        if ($registration->branchesEnabled() && $registration->canViewAllBranches($admin)) {
            $options['branches'] = Branch::query()->orderBy('name_ar')->get(['id', 'name_ar'])
                ->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name_ar])->all();
            $options['branch_required'] = true;
        }

        return $options;
    }
}

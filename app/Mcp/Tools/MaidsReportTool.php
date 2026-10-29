<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;

/**
 * تقرير السير الذاتية (العاملات): لقطة الآن حسب الحالة والجنسية والمكتب والمهنة، والسير المضافة في فترة.
 * Maid عليه BranchScoped (مع مشاركة العاملات بين الفروع)؛ عاملات الساعات مستبعدة كما في شاشة العاملات.
 */
class MaidsReportTool extends McpTool
{
    use ReportsOnPeriods;

    private const STATUSES = ['available', 'reserved', 'contracted', 'backout', 'deported', 'suspended'];

    public function name(): string
    {
        return 'maids_report';
    }

    public function description(): string
    {
        return 'تقرير السير الذاتية (العاملات): العدد الآن حسب الحالة، وداخل الإيواء، والتوزيع حسب الجنسية والمكتب الخارجي والمهنة، '
            . 'والسير المضافة في الفترة مع اتجاهها الشهري. status يحصر التوزيعات في حالة (مثل available للمتاحات). '
            . 'للبحث عن عاملة بعينها استخدم search_maids. Maids/CVs report: counts by status, nationality, office, job title, and new CVs.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->periodProperties('تاريخ إضافة السيرة (لـ new_in_period فقط)') + [
                'status' => ['type' => 'string', 'enum' => self::STATUSES, 'description' => 'حصر توزيعات الجنسية والمكتب والمهنة في حالة واحدة'],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'maids.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $locale = $this->locale();
        $all = Maid::query()->notHourly();

        $scoped = clone $all;
        if (!empty($arguments['status'])) {
            $scoped->where('maids.status', (string) $arguments['status']);
        }

        $new = $this->applyPeriod(clone $all, 'maids.created_at', $period);

        $nationality = fn ($q) => $q->leftJoin('nationalities_translations as nt', fn ($join) => $join
            ->on('nt.nationality_id', '=', 'maids.nationality_id')->where('nt.locale', $locale));

        return [
            'total_now' => (clone $all)->count(),
            'by_status_now' => $this->labeled($this->countBy($all, 'maids.status'), fn ($status) => $this->statusLabel($status)),
            // maid_accommodation_type: 1 = سيرة حجز، 2 = داخل الإيواء.
            'in_accommodation_now' => (clone $all)->where('maids.maid_accommodation_type', 2)->count(),
            'filtered_status' => $arguments['status'] ?? null,
            'by_nationality' => $this->countByJoin($scoped, 'maids', $nationality, 'nt.title', 20),
            'by_office' => $this->countByJoin($scoped, 'maids', fn ($q) => $q
                ->leftJoin('recruitment_office_translations as ot', fn ($join) => $join
                    ->on('ot.rec_office_id', '=', 'maids.office_id')->where('ot.locale', $locale)), 'ot.title', 20),
            // سيرة واحدة قد تحمل أكثر من مهنة، فمجموع هذا التوزيع قد يزيد على عدد السير.
            'by_job_title' => $this->countByJoin($scoped, 'maids', fn ($q) => $q
                ->leftJoin('maid_job_titles as mjt', 'mjt.maid_id', '=', 'maids.id')
                ->leftJoin('job_titles_translations as jt', fn ($join) => $join
                    ->on('jt.job_title_id', '=', 'mjt.job_title_id')->where('jt.locale', $locale)), 'jt.title', 20),
            'new_in_period' => $this->periodSummary($period) + [
                'total' => (clone $new)->count(),
                'by_nationality' => $this->countByJoin($new, 'maids', $nationality, 'nt.title'),
                'monthly' => $this->monthlyTrend($new, 'maids.created_at'),
            ],
            'notes' => 'كل الأرقام لقطة الآن عدا new_in_period. by_nationality وby_office وby_job_title تتبع status إن أُرسل.',
        ];
    }

    private function statusLabel(string $status): ?string
    {
        $key = 'ingaz.maid_status_' . $status;
        $label = __($key);

        return $label === $key ? null : $label;
    }
}

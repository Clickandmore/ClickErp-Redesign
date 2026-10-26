<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Invoice;
use App\Models\MusanedTransferItem;
use Illuminate\Http\Request;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\TaxSetting;

/**
 * تقرير فواتير المبيعات بالصافي بعد التكاليف البنكية لمساند.
 *
 * هذا التقرير هو ما كان ناقصاً ودفع المحاسب لاستخراج الأرقام يدوياً: يسرد الفواتير
 * بالإجمالي والعمولة والصافي، ويتيح تحديد الفواتير المحصلة من منصة مساند ثم يسلّمها
 * لشاشة تحويلات مساند لإنشاء قيد التحصيل.
 *
 * لا ينشئ قيداً ولا يكتب أي شيء. التحصيل يبقى من شاشة التحويلات وحدها، فلا يوجد مسار
 * ثانٍ يمكن أن يُنتج قيداً مكرراً.
 */
class MusanedInvoiceReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'settled', 'all'], true) ? $status : 'pending';
        $search = trim((string) $request->query('search', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        $query = Invoice::query()
            ->with([
                'contract.user',
                'contract.info',
                'contract.maid.nationality',
                'contract.requiredMaidNationality',
                'ingazDelegation.user',
                'musanedTransferItem.transfer',
            ])
            ->where(function ($inner) {
                $inner->whereHas('contract')->orWhereHas('ingazDelegation');
            });

        match ($status) {
            'pending' => $query->whereDoesntHave('musanedTransferItem.transfer'),
            'settled' => $query->whereHas('musanedTransferItem.transfer'),
            default => null,
        };

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($inner) use ($search, $like) {
                if (ctype_digit($search)) {
                    $inner->orWhere('id', (int) $search)
                        ->orWhere('contract_id', (int) $search)
                        ->orWhere('ingaz_delegation_id', (int) $search);
                }
                $inner->orWhereHas('contract.user', fn ($q) => $q->where('name', 'like', $like))
                    ->orWhereHas('contract.info', fn ($q) => $q->where('msaned_number', 'like', $like))
                    ->orWhereHas('ingazDelegation.user', fn ($q) => $q->where('name', 'like', $like))
                    ->orWhereHas('ingazDelegation', fn ($q) => $q->where('msaned_contract_number', 'like', $like));
            });
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $invoices = $query->latest('id')->paginate(50)->appends($request->query());
        $expenseTaxRate = $this->expenseTaxRate();

        $rows = $invoices->getCollection()->map(fn (Invoice $invoice) => $this->row($invoice, $expenseTaxRate));

        return view('admin.ingaz.musaned_transfers.invoices_report', [
            'invoices' => $invoices,
            'rows' => $rows,
            'status' => $status,
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'expenseTaxRate' => $expenseTaxRate,
            // العمولة والصافي لا يوجدان إلا على المسوّاة، فلو قُرئا بجانب إجمالي كل
            // الفواتير بدا أن الفرق بينهما عمولة غير مسجَّلة. لذلك يُفصل إجمالي المسوّاة
            // ليكون: المسوّاة = العمولة + الصافي، وهي معادلة يتحقق منها المحاسب بنظرة.
            'totals' => [
                'total_due' => round($rows->sum('total_due'), 2),
                'pending_total' => round($rows->where('settled', false)->sum('total_due'), 2),
                'settled_total' => round($rows->where('settled', true)->sum('total_due'), 2),
                'commission' => round($rows->sum('commission'), 2),
                'net' => round($rows->sum('net'), 2),
            ],
        ]);
    }

    /**
     * الصافي والعمولة يأتيان من التسوية المنفذة إن وُجدت. أما الفواتير غير المسوّاة
     * فلا عمولة مؤكدة لها، لأن مساند تحسمها وقت التحويل، فتُعرض أعمدتها فارغة بدل
     * تقدير قد يُقرأ كرقم محاسبي.
     */
    private function row(Invoice $invoice, float $expenseTaxRate): array
    {
        $item = $invoice->musanedTransferItem;
        $settled = (bool) $item?->transfer;
        $totalDue = round((float) $invoice->total_due, 2);

        $contract = $invoice->contract;
        $delegation = $invoice->ingazDelegation;
        $nationality = $contract?->maid?->nationality ?? $contract?->requiredMaidNationality;

        return [
            'invoice_id' => (int) $invoice->id,
            'contract_id' => $contract?->id,
            'ingaz_delegation_id' => $delegation?->id,
            'source_label' => $contract ? ('عقد #' . $contract->id) : ('تفويض #' . $delegation?->id),
            'client' => trim((string) ($contract?->user?->name ?: $delegation?->user?->name ?: '-')),
            'msaned_number' => trim((string) ($contract?->info?->msaned_number ?: $delegation?->msaned_contract_number ?: '-')),
            'nationality' => trim((string) ($nationality?->sub_title ?? $nationality?->title ?? '-')),
            'date' => optional($invoice->created_at)->format('Y-m-d'),
            'total_due' => $totalDue,
            'commission' => $settled ? round((float) $item->commission_amount, 2) : null,
            'expense_tax' => $settled ? round((float) $item->expense_tax_amount, 2) : null,
            'net' => $settled ? round((float) $item->transferred_amount, 2) : null,
            'settled' => $settled,
            'transfer_id' => $item?->transfer?->id,
        ];
    }

    private function expenseTaxRate(): float
    {
        $taxSettingId = ConfirmSetting::query()
            ->where('title', 'tax_rate_expenditure')
            ->latest('updated_at')
            ->value('value');

        if ($taxSettingId) {
            $rate = (float) TaxSetting::query()->whereKey($taxSettingId)->value('value');
            if ($rate > 0) {
                return $rate;
            }
        }

        return 15.0;
    }
}

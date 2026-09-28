<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyServiceCategory, HourlyTeam, HourlyVisit};
use App\Services\Hourly\{ContractPeriod, RenewalSearch};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * التجديدات (عقود الطلبات): متابعة الطلبات التي تقترب آخر زيارة فيها للتواصل مع العميل قبل انتهاء الخدمة.
 * التجديد نفسه يتم عبر معالج الطلب الموجود (hourly-orders.create?renew=).
 */
class RenewalController extends Controller
{
    /** عداد القائمة الجانبية؛ تحدّثه المهمة اليومية hourly:renewal-reminders. */
    public const COUNT_KEY = 'hourly_renewals_due_count';

    public const COLUMNS = ['order' => 'رقم العقد', 'customer' => 'اسم العميل', 'service' => 'فئة الخدمة', 'address' => 'العنوان', 'workers' => 'اسم الموظف',
        'visit_price' => 'سعر الزيارة', 'total' => 'المبلغ الإجمالي', 'period' => 'فترة العقد', 'days_left' => 'الأيام المتبقية', 'progress' => 'الزيارات المنجزة',
        'renewal' => 'حالة التجديد'];

    /** العلاقات المطلوبة لصفوف الجدول والتصدير، ومنها التجديد الساري لعرض «تم التجديد». */
    private const WITH = ['user:id,name,phone', 'serviceCategory', 'renewedBy:id,order_number,renewed_from_order_id,status'];

    public function index(Request $request, RenewalSearch $search)
    {
        $filters = $search->filters($request);
        $orders = $search->query($filters)->with(self::WITH)->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();

        return view('admin.hourly_renewals.index', [
            'orders' => $orders, 'filters' => $filters, 'rows' => $this->rows($orders->getCollection()),
            'services' => HourlyServiceCategory::ordered()->get(), 'teams' => HourlyTeam::orderBy('name')->get(['id', 'name']),
            'window' => $search->expiryWindow($filters), 'reminderDays' => (int) HourlyRentalSettings::get('renewal_reminder_days', 7),
            'canRenew' => AdminGuardHasPerm('hourly_orders_create'),
        ]);
    }

    public function export(Request $request, RenewalSearch $search)
    {
        $query = $search->query($search->filters($request));
        abort_if((clone $query)->count() > 10000, 422, 'حدد نطاقًا لا يتجاوز 10000 عقد للتصدير.');
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->fromArray(array_values(self::COLUMNS), null, 'A1');
        $line = 2;
        foreach ($query->with(self::WITH)->get()->chunk(200) as $chunk) {
            foreach ($this->rows($chunk) as $row) {
                $cells = [$row['order_number'], $row['customer'], $row['service'], $row['address'], $row['workers'], $row['visit_price'], $row['total'], $row['start'].' → '.$row['end'], $row['days_left'], $row['done'].'/'.$row['visits'],
                    $row['renewal'] ? 'تم التجديد — #'.$row['renewal']['order_number'] : 'لم يُجدَّد'];
                foreach ($cells as $i => $value) {
                    $sheet->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1).$line, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $line++;
            }
        }
        foreach (range(1, count(self::COLUMNS)) as $i) $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($book) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'hourly-renewals-'.today()->toDateString().'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** عداد القائمة الجانبية (غير المجدَّدة التي تنتهي خلال مدة التذكير)؛ التفاصيل في RenewalCounter. */
    public static function dueCount(bool $refresh = false): int
    {
        return app(\App\Services\Hourly\RenewalCounter::class)->get($refresh);
    }

    /** صفوف الجدول والتصدير؛ العاملات وعدد الزيارات من «زيارات العقد» (ContractPeriod) باستعلام واحد. */
    private function rows($orders): array
    {
        $visits = app(ContractPeriod::class)->scope(HourlyVisit::query()->whereIn('hourly_rental_order_id', $orders->pluck('id')))
            ->with('workers:id,name')->get(['id', 'hourly_rental_order_id', 'status'])->groupBy('hourly_rental_order_id');
        $today = today();

        return $orders->map(function ($order) use ($visits, $today) {
            $mine = $visits[$order->id] ?? collect();
            $end = Carbon::parse($order->last_visit_at);
            $renewal = $order->relationLoaded('renewedBy') ? $order->renewedBy : null;
            return [
                'id' => $order->id, 'order_number' => $order->order_number, 'customer' => $order->user?->name, 'phone' => $order->user?->phone,
                'service' => $order->serviceCategory?->name ?? '—', 'address' => $order->address_text,
                'workers' => $mine->flatMap(fn ($v) => $v->workers->pluck('name'))->unique()->implode('، ') ?: 'غير مخصص',
                'visit_price' => number_format((float) $order->price_per_visit, 2), 'total' => number_format((float) $order->total_amount, 2),
                'start' => Carbon::parse($order->first_visit_at)->format('Y-m-d'), 'end' => $end->format('Y-m-d'), 'end_time' => $end->format('H:i'),
                'days_left' => $today->diffInDays($end->copy()->startOfDay(), false),
                'visits' => $mine->count(), 'done' => $mine->whereIn('status', ContractPeriod::DONE)->count(),
                'open' => (int) $order->open_visits, 'status' => $order->status,
                'renewal' => $renewal ? ['id' => $renewal->id, 'order_number' => $renewal->order_number] : null,
            ];
        })->values()->all();
    }
}

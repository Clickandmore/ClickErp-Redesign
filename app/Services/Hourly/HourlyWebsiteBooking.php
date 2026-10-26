<?php

namespace App\Services\Hourly;

use App\Http\Controllers\Hourly\ClientController;
use App\Models\Hourly\{HourlyPackage, HourlyRentalOrder, ServiceDistrict};
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

/**
 * حجز الساعات من المواقع: موقع اللوحة المدمج (web.hourly.*) وواجهات الموقع الخارجي (api/client/hourly/*)
 * يمرّان من هنا، فتبقى قواعد الأوقات وحجز العاملات واحدة. واجهات التطبيق (api/app/hourly/*) لا تستخدمه.
 */
class HourlyWebsiteBooking
{
    public function __construct(private HourlyWebsiteCatalog $catalog) {}

    public static function slotRules(): array
    {
        return ['hourly_package_id'=>'required|integer','district_id'=>'required|integer','date'=>'required|date_format:Y-m-d',
            'workers_per_visit'=>'required|integer|between:1,20','preferred_maid_id'=>'nullable|integer','address_id'=>'nullable|integer'];
    }

    public static function storeRules(): array
    {
        return ['booking_token'=>'required|uuid','hourly_package_id'=>'required|integer','district_id'=>'required|integer',
            'payment_method'=>'required|in:moyasar,bank_transfer,cash','scheduled_starts'=>'required_without:scheduled_dates|array|min:1|max:1',
            'scheduled_dates'=>'required_without:scheduled_starts|array|min:1|max:1'];
    }

    /** الأوقات التي يتوفر فيها العدد المطلوب من العاملات، والعاملات القابلات للاختيار إن سمحت الخدمة. */
    public function slots(array $data, ?int $userId=null): array
    {
        $item=$this->catalog->item((int)$data['hourly_package_id']);
        $district=ServiceDistrict::findOrFail($data['district_id']);
        $existing=[];
        if($userId && !empty($data['address_id'])){
            $address=\App\Models\ingaz\UserAddress::where('user_id',$userId)->findOrFail($data['address_id']);
            $day=\Carbon\Carbon::parse($data['date'])->startOfDay();
            $existing=app(DuplicateBookingService::class)->matches($userId,$address->toArray(),$item,[
                ['starts_at'=>$day->toDateTimeString(),'ends_at'=>$day->copy()->addDay()->toDateTimeString()],
            ]);
        }
        $slots=$this->catalog->slots($item,$district,$data['date'],(int)$data['workers_per_visit'],isset($data['preferred_maid_id'])?(int)$data['preferred_maid_id']:null);
        $dateOnly=!app(HourlyBookingRules::class)->settings()->value($item,'show_time_options_app');
        $canChoose=(bool)app(HourlyBookingRules::class)->settings()->value($item,'customer_worker_selection');
        $publicWorkers=fn($workers)=>$workers->unique('id')->map(fn($worker)=>[
            'id'=>$worker->id,'name'=>$worker->name,'photo'=>$worker->photo_url,
            'nationality'=>$worker->nationality?->sub_title ?? $worker->nationality?->title ?? '',
        ])->values();
        return ['data'=>collect($dateOnly?array_slice($slots,0,1):$slots)->map(fn($slot)=>[
            'starts_at'=>$slot['starts_at'],'ends_at'=>$slot['ends_at'],
            'workers'=>$canChoose?$publicWorkers($slot['workers']):[]])->values(),
            'workers'=>$canChoose && $dateOnly?$publicWorkers(collect($slots)->flatMap(fn($slot)=>$slot['workers'])):[],
            'worker_groups'=>$canChoose && $dateOnly?collect($slots)->map(fn($slot)=>$slot['workers']->pluck('id')->map(fn($id)=>(int)$id)->values()->all())->unique()->values():[],
            'time_selection'=>$dateOnly?'date':'time','existing_bookings'=>['visits'=>$existing]];
    }

    /**
     * ينشئ الطلب تحت قفل العميل والباقة، ثم يحجز العاملات لكل زيارة مجدولة.
     * يتطلب أن يكون auth('api') هو العميل نفسه، لأن ClientController::store يقرأ الهوية منه.
     */
    public function place(Request $request, ClientController $client, HourlyOrderService $orders, int $userId): HourlyRentalOrder
    {
        abort_unless(Schema::hasTable('hourly_visit_reservations'),503,'لم تكتمل تهيئة الحجز بعد.');
        return DB::transaction(function()use($request,$client,$orders,$userId){
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            HourlyPackage::whereKey((int)$request->input('hourly_package_id'))->lockForUpdate()->firstOrFail();
            $item=$this->catalog->item((int)$request->input('hourly_package_id'));
            $district=ServiceDistrict::findOrFail((int)$request->input('district_id'));
            abort_unless($this->catalog->districts($item)->contains('id',$district->id),422,'الحي خارج تغطية الخدمة.');
            $candidates=$this->catalog->candidates($item,$district,true);
            $request->attributes->set('hourly_website_candidates',$candidates);
            $before=HourlyRentalOrder::where('booking_token',$request->input('booking_token'))->first();
            $response=$client->store($request,$orders);
            $order=HourlyRentalOrder::findOrFail($response->getData(true)['data']['id']);
            if ($before) return $order;
            foreach ($order->visits()->whereNotNull('scheduled_start_at')->get() as $visit) {
                $assigned=$visit->workers()->pluck('maids.id');
                // المفتاح slot يخبر صفحة الحجز أن الموعد نفسه لم يعد متاحًا، فتعيد العميل لاختيار موعد آخر
                $taken=fn(string $message)=>throw ValidationException::withMessages(['slot'=>$message]);
                foreach ($assigned as $id) if (!$candidates->contains('id',$id)) $taken('العاملة المختارة لم تعد متاحة في هذا الموعد. اختاري موعدًا آخر.');
                $remaining=(int)$visit->workers_count-$assigned->count();
                if ($remaining<=0) continue;
                $available=$candidates->whereNotIn('id',$assigned)->filter(fn($maid)=>app(HourlyWorkerAssignment::class)->available($maid,
                    $visit->scheduled_start_at->toDateTimeString(),$visit->scheduled_end_at->toDateTimeString(),$order->package_snapshot,$visit->id))->take($remaining);
                if ($available->count()<$remaining && !$request->has('selected_maid_ids')) $taken('حُجز هذا الموعد للتو ولم يعد العدد المطلوب من العاملات متاحًا فيه. اختاري موعدًا آخر من الأوقات المحدّثة.');
                foreach ($available as $maid) DB::table('hourly_visit_reservations')->insert(['hourly_visit_id'=>$visit->id,'maid_id'=>$maid->id,'created_at'=>now(),'updated_at'=>now()]);
            }
            $metadata=$order->booking_metadata;$metadata['booking_channel']='website';$order->update(['booking_metadata'=>$metadata]);
            return $order;
        });
    }
}

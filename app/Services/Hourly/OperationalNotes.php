<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder, HourlyVisit};
use Illuminate\Support\Collection;

class OperationalNotes
{
    public const AUDIENCES = ['admin','worker','driver','all','address'];

    public function forOrder(HourlyRentalOrder $order): Collection
    {
        $notes=collect($order->booking_metadata['notes'] ?? []);
        // An address note belongs to this customer's exact address snapshot, including the unit.
        $others=HourlyRentalOrder::where('user_id',$order->user_id)->where('id','!=',$order->id);
        foreach(['city_id','address_text','building','unit','latitude','longitude'] as $field)$others->where($field,$order->$field);
        foreach($others->select('id','booking_metadata')->cursor() as $other){
            foreach($other->booking_metadata['notes'] ?? [] as $note){
                if(($note['audience'] ?? 'admin')==='address')$notes->push($note+['source_order_id'=>$other->id]);
            }
        }
        return $notes->map(fn($n)=>$n+['audience'=>'admin','visit_id'=>null])->sortBy('created_at')->values();
    }

    public function forStaff(HourlyVisit $visit,string $role,int $staffId): Collection
    {
        $assigned=$role==='driver'
            ? in_array($staffId, array_filter([$visit->delivery_driver_id,$visit->pickup_driver_id,$visit->team_leader_id]))
            : ($role==='worker' && $visit->workers()->where('maids.id',$staffId)->exists());
        abort_unless($assigned,404);
        return $this->forOrder($visit->order)->filter(fn($n)=>
            in_array($n['audience'],[$role,'all','address']) && (!$n['visit_id'] || (int)$n['visit_id']===$visit->id)
        )->map(fn($n)=>collect($n)->only(['id','text','audience','visit_id','created_at'])->all())->values();
    }
}

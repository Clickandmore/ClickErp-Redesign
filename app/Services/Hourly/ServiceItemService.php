<?php
namespace App\Services\Hourly;
use App\Models\Hourly\{HourlyPackage,HourlyPackageLog};
use Illuminate\Support\Facades\Validator;

class ServiceItemService
{
    public function snapshot(HourlyPackage $item): array {
        $fresh=$item->fresh()->load(['cities','nationalities']);
        $data=collect($fresh->toArray())->except(['updated_at','created_at','deleted_at','revision','created_by','translations','cities','nationalities'])->all();
        foreach($fresh->translations as $translation){$data['title_'.$translation->locale]=$translation->title;$data['description_'.$translation->locale]=$translation->description;}
        $data['cities']=$fresh->cities->pluck('name_ar')->all();
        $data['nationalities']=$fresh->nationalities->map(fn($nationality)=>$nationality->sub_title ?: $nationality->title)->all();
        return $data;
    }
    public function audit(HourlyPackage $item, string $type, ?array $before): void {
        $after=$item->trashed() ? null : $this->snapshot($item);
        if ($before===$after) return;
        HourlyPackageLog::create(['hourly_package_id'=>$item->id,'item_name'=>$item->title,'type'=>$type,'before_data'=>$before,'after_data'=>$after,'admin_id'=>auth('admin')->id(),'admin_name'=>auth('admin')->user()?->name]);
    }
    public function shifts(array $input): array {
        $d=Validator::make($input,[
            'mode'=>['required','in:grid,discrete'],'first'=>['required_if:mode,grid','nullable','date_format:H:i'],
            'last'=>['required_if:mode,grid','nullable','date_format:H:i','after_or_equal:first'],
            'step'=>['required_if:mode,grid','nullable','integer','between:5,240'],
            'times'=>['required_if:mode,discrete','nullable','array','max:288'],'times.*'=>['date_format:H:i','distinct'],
            'access'=>['required','in:all,dispatch'],
            'dispatcher_enabled'=>['nullable','boolean'],'dispatcher_first'=>['required_if:dispatcher_enabled,1','nullable','date_format:H:i'],
            'dispatcher_last'=>['required_if:dispatcher_enabled,1','nullable','date_format:H:i','after_or_equal:dispatcher_first'],
            'dispatcher_step'=>['required_if:dispatcher_enabled,1','nullable','integer','between:5,240'],
        ])->validate();
        $grid=function($first,$last,$step){$out=[];for($n=$this->minute($first);$n<=$this->minute($last);$n+=$step)$out[]=sprintf('%02d:%02d',intdiv($n,60),$n%60);return $out;};
        $times=[];
        if(!empty($d['dispatcher_enabled'])) foreach($grid($d['dispatcher_first'],$d['dispatcher_last'],$d['dispatcher_step']) as $time)$times[$time]='dispatch';
        foreach($d['mode']==='grid' ? $grid($d['first'],$d['last'],$d['step']) : $d['times'] as $time)$times[$time]=$d['access'];
        ksort($times);return $times;
    }
    private function minute(string $time): int { [$h,$m]=explode(':',$time);return (int)$h*60+(int)$m; }
}

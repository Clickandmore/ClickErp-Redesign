<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Mail,Schema};

class HourlyVisitNotices extends Command
{
    protected $signature='hourly:visit-notices';
    protected $description='Send pending visit operation notifications and grouped customer receipts';
    public function handle(): int
    {
        if(!Schema::hasTable('hourly_visit_notices'))return 0;
        foreach(DB::table('hourly_visit_notices')->where('status','pending')->orderBy('id')->limit(50)->get() as $notice){
            if(!DB::table('hourly_visit_notices')->where('id',$notice->id)->where('status','pending')->update(['status'=>'sending','updated_at'=>now()]))continue;
            try{
                if($notice->channel==='email'){
                    $user=\App\Models\ingaz\User::findOrFail($notice->user_id);
                    if(!filter_var($user->email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('لا يوجد بريد إلكتروني صالح للعميل.');
                    $receipt=DB::table('hourly_visit_receipts')->where('batch_id',$notice->batch_id)->where('user_id',$notice->user_id)->first();
                    $body=$notice->body;
                    if($receipt)foreach(json_decode($receipt->details,true) as $line)$body.="\n".'الطلب '.$line['order_number'].' — '.$line['amount'].' ر.س';
                    Mail::raw($body,fn($mail)=>$mail->to($user->email)->subject($notice->title));
                }else{
                    // معرّفا الطلب والزيارة يسمحان للتطبيق بفتح الشاشة المقصودة مباشرة؛ يُحذفان حين يجمع الإشعار أكثر من زيارة.
                    $payload=array_filter(['type'=>'hourly_visits','batch_id'=>$notice->batch_id,
                        'hourly_order_id'=>isset($notice->order_id)?(string)$notice->order_id:null,
                        'visit_id'=>isset($notice->visit_id)?(string)$notice->visit_id:null,
                        'hourly_visit_id'=>isset($notice->visit_id)?(string)$notice->visit_id:null],fn($v)=>$v!==null);
                    // يظهر في قائمة إشعارات العميل (التطبيق والموقع) مع رقم الطلب، لا في الجوال وحده. الإشعار يُعالج مرة واحدة (pending → sending).
                    \App\Models\ingaz\Notification::create(['user_id'=>$notice->user_id,'type'=>'hourly_visits','title'=>['ar'=>$notice->title,'en'=>$notice->title],
                            'description'=>['ar'=>$notice->body,'en'=>$notice->body]]+\App\Models\ingaz\Notification::hourlyReference(
                            isset($notice->order_id)?(int)$notice->order_id:null,isset($notice->visit_id)?(int)$notice->visit_id:null));
                    $result=app(\App\Services\Firebase\FirebasePushService::class)->sendToUser($notice->user_id,$notice->title,$notice->body,$payload);
                    if(!$result->successCount())throw new \RuntimeException('لم يُسلّم الإشعار؛ تحقق من أجهزة العميل وإعدادات Firebase.');
                }
                DB::table('hourly_visit_notices')->where('id',$notice->id)->update(['status'=>'sent','updated_at'=>now()]);
            }catch(\Throwable $e){report($e);DB::table('hourly_visit_notices')->where('id',$notice->id)->update(['status'=>'failed','error'=>mb_substr($e->getMessage(),0,1000),'updated_at'=>now()]);}
        }
        return 0;
    }
}

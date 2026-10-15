<?php
namespace App\Services\Hourly;

use App\Models\ingaz\{Maid, JobType};
use App\Models\Hourly\{HourlyDriver, HourlyTeam, HourlyTeamMember};
use App\Support\HourlyRentalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class MaidSettingsService
{
    public function validate(Request $request, ?Maid $maid = null): ?array
    {
        if (!$request->has('rental_mode') && !$request->has('hourly')) return null;
        if (!HourlyRentalSettings::isEnabled()) {
            throw ValidationException::withMessages(['rental_mode'=>'موديول الساعات غير مفعل.']);
        }
        $mode = $request->validate(['rental_mode'=>['required','in:regular,hourly']])['rental_mode'];
        if ($mode === 'regular') return ['mode'=>'regular'];
        if ($maid) app(\App\Services\Rental\RentalModeGuard::class)->assertHourlyConversion($maid);
        $rentalId = JobType::whereHas('translations', fn($q) => $q->where('title','LIKE','%ايجار%')->orWhere('title','LIKE','%إيجار%')->orWhere('title','LIKE','%تأجير%')->orWhere('title','LIKE','%rent%'))->value('id') ?? 2;
        if ((int)$request->input('type_id') !== (int)$rentalId) throw ValidationException::withMessages(['rental_mode'=>'نوع الساعات متاح لعاملة الإيجار فقط.']);
        $rules = [
            'services_present'=>['nullable','boolean'],
            'attributes_present'=>['nullable','boolean'],
            'attribute_ids'=>['nullable','array'],
            'attribute_ids.*'=>['integer','distinct',Rule::exists('hourly_product_attributes','id')->whereNotNull('parent_id')],
            'service_category_ids'=>['nullable','array'],
            'service_category_ids.*'=>['integer','distinct',Rule::exists('hourly_service_categories','id')->whereNull('deleted_at')],
            'primary_team_id'=>['nullable','integer',Rule::exists('hourly_teams','id')->whereNull('deleted_at')],
            'secondary_team_ids'=>['nullable','array'],
            'secondary_team_ids.*'=>['integer','distinct',Rule::exists('hourly_teams','id')->whereNull('deleted_at')],
            'requires_driver'=>['required','boolean'],
            'max_orders_per_hour'=>['required','integer','min:1','max:100'],
            'account_enabled'=>['required','boolean'],
            'login_access'=>['required','in:web,mobile,both'],
            'email'=>['nullable','email','max:255',function ($attribute, $value, $fail) use ($maid) {
                if (!is_string($value)) return;
                if (DB::table('maids')->when($maid,fn($q)=>$q->where('id','!=',$maid->id))
                    ->whereRaw('LOWER(TRIM(hourly_email)) = ?',[$value])->exists()) {
                    $fail('البريد الإلكتروني مستخدم لعاملة أخرى.');
                }
            }],
            'login_phone'=>['nullable','regex:/^[0-9]{5,20}$/',Rule::unique('maids','hourly_login_phone')->ignore($maid?->id)],
            'password'=>[Rule::requiredIf($request->boolean('hourly.account_enabled') && !$maid?->hourly_password),'nullable','string','min:8','max:72','confirmed'],
            'work_schedule'=>['required','array:'.implode(',',array_keys(HourlyDriver::DAYS)),'size:7'],
        ];
        foreach (HourlyDriver::DAYS as $day=>$label) {
            $key="work_schedule.$day";
            $rules[$key]=['required','array:mode,start,end'];
            $rules["$key.mode"]=['required','in:hours,all_day,off'];
            $rules["$key.start"]=["exclude_unless:$key.mode,hours",'required','date_format:H:i'];
            $rules["$key.end"]=["exclude_unless:$key.mode,hours",'required','date_format:H:i',"after:$key.start"];
        }
        $input=(array)$request->input('hourly',[]);
        if (isset($input['email']) && is_string($input['email'])) $input['email']=strtolower(trim($input['email'])) ?: null;
        // هاتف الدخول: الأرقام العربية تُحوَّل للاتينية قبل الفحص
        if(isset($input['login_phone']) && is_string($input['login_phone'])) $input['login_phone']=trim(strtr($input['login_phone'],['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']));
        $messages=[
            'login_phone.regex'=>'هاتف تسجيل الدخول يقبل الأرقام فقط (من 5 إلى 20 رقمًا)، بدون حروف أو رموز أو إشارة سالب.',
            'login_phone.unique'=>'هاتف تسجيل الدخول مستخدم لعاملة أخرى.',
            'email.email'=>'البريد الإلكتروني لتطبيق العاملة غير صحيح.',
            'password.required'=>'كلمة المرور مطلوبة لتفعيل حساب تطبيق العاملة.',
            'password.min'=>'كلمة مرور التطبيق يجب ألا تقل عن 8 أحرف.',
            'password.confirmed'=>'تأكيد كلمة مرور التطبيق غير مطابق لكلمة المرور.',
            'max_orders_per_hour.*'=>'الحد الأقصى للطلبات في الساعة يجب أن يكون رقمًا صحيحًا من 1 إلى 100.',
        ];
        foreach (HourlyDriver::DAYS as $day=>$label) {
            $messages["work_schedule.$day.start.required"]="حدد بداية العمل يوم $label.";
            $messages["work_schedule.$day.end.required"]="حدد نهاية العمل يوم $label.";
            $messages["work_schedule.$day.start.date_format"]="وقت بداية العمل يوم $label غير صحيح.";
            $messages["work_schedule.$day.end.date_format"]="وقت نهاية العمل يوم $label غير صحيح.";
            $messages["work_schedule.$day.end.after"]="نهاية العمل يوم $label يجب أن تكون بعد بدايته (لا تتساوى معها).";
        }
        $validator=Validator::make($input,$rules,$messages);
        // المفاتيح بصيغة hourly.* حتى تظهر الرسالة تحت حقلها في نموذج العاملة
        if ($validator->fails()) throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn($list,$key)=>["hourly.$key"=>$list])->all());
        $data=$validator->validated();
        if(array_key_exists('attribute_ids',$data) || !empty($data['attributes_present'])){
            $ids=array_map('intval',$data['attribute_ids']??[]);
            $current=$maid?$maid->productAttributes()->pluck('hourly_product_attributes.id')->all():[];
            $new=array_diff($ids,$current);
            if(count($new)!==\App\Models\Hourly\HourlyProductAttribute::selectable()->whereIn('id',$new)->whereHas('category',fn($q)=>$q->where('is_active',true))->count())throw ValidationException::withMessages(['hourly.attribute_ids'=>'اختر سمات نشطة فقط.']);
            $data['attribute_ids']=$ids;
        }
        if(array_key_exists('service_category_ids',$data) || !empty($data['services_present'])){
            $ids=array_map('intval',$data['service_category_ids'] ?? []);
            $currentServices=$maid?->hourly_settings['service_category_ids'] ?? [];
            $wasDefault=($maid?->hourly_settings['service_category_ids'] ?? null)===null;
            if(\App\Models\Hourly\HourlyServiceCategory::whereIn('id',array_diff($ids,$currentServices))->where('is_active',false)->exists())throw ValidationException::withMessages(['hourly.service_category_ids'=>'اختر خدمات نشطة فقط.']);
            // حفظ النموذج دون أي خدمة يُبقي العاملة على «كل الخدمات (افتراضي)» إن لم تُحدد خدماتها من قبل؛
            // «لا خدمات» (لا تُرشَّح لأي حجز) فقط عند إلغاء خدمات كانت محددة صراحةً.
            $data['service_category_ids']=!$ids && $wasDefault ? null : $ids;
        }
        $primary=$data['primary_team_id'] ?? null;
        $secondary=$data['secondary_team_ids'] ?? [];
        if ($primary && in_array($primary,$secondary)) throw ValidationException::withMessages(['hourly.secondary_team_ids'=>'لا يمكن تكرار الفريق الأساسي ضمن الفرق الثانوية.']);
        $current=$maid ? HourlyTeamMember::where('member_type','maid')->where('member_id',$maid->id)->whereNull('left_at')->pluck('hourly_team_id')->all() : [];
        if (HourlyTeam::whereIn('id',array_diff(array_filter(array_merge([$primary],$secondary)),$current))->where('is_active',false)->exists()) throw ValidationException::withMessages(['hourly.primary_team_id'=>'اختر فريقًا نشطًا.']);
        return ['mode'=>$mode,'data'=>$data];
    }

    public function save(Maid $maid, ?array $validated): void
    {
        if ($validated === null) return;
        DB::transaction(function () use ($maid, $validated) {
            $locked = Maid::whereKey($maid->id)->lockForUpdate()->firstOrFail();
            if ($validated['mode'] === 'hourly') app(\App\Services\Rental\RentalModeGuard::class)->assertHourlyConversion($locked);
            $data=$validated['data'] ?? [];
            app(MaidTeamService::class)->sync($maid,$data['primary_team_id'] ?? null,$data['secondary_team_ids'] ?? []);
            $values=['rental_mode'=>$validated['mode'],'hourly_auth_version'=>($maid->hourly_auth_version ?? 1)+1];
            if ($validated['mode']==='hourly') {
                $values += [
                    'hourly_username'=>$maid->hourly_username ?: 'HM-'.$maid->id,
                    'hourly_login_phone'=>$data['login_phone'] ?? null,
                    'hourly_email'=>$data['email'] ?? null,
                    'hourly_settings'=>array_replace(array_intersect_key($maid->hourly_settings ?? [],array_flip(['service_category_ids','profile'])),array_intersect_key($data,array_flip(['requires_driver','max_orders_per_hour','account_enabled','login_access','work_schedule','service_category_ids']))),
                ];
                if (!empty($data['password'])) $values['hourly_password']=Hash::make($data['password']);
            }
            $maid->forceFill($values)->save();
            if(array_key_exists('attribute_ids',$data))$maid->productAttributes()->sync($data['attribute_ids']);
        });
    }
}

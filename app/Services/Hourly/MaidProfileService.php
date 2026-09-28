<?php

namespace App\Services\Hourly;

use App\Models\ingaz\Maid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaidProfileService
{
    public const FIELDS = [
        'employee_number'=>['رقم الموظف','text'], 'gender'=>['الجنس','gender'],
        'address'=>['العنوان','text'], 'job_start_date'=>['تاريخ بدء العمل','date'],
        'substitution_exp_date'=>['تاريخ انتهاء الاستبدال','date'],
        'contract_start_date'=>['تاريخ بدء العقد','date'], 'contract_exp_date'=>['تاريخ انتهاء العقد','date'],
        'passport_exp_date'=>['تاريخ انتهاء جواز السفر','date'], 'note'=>['ملحوظة','textarea'],
    ];
    public const PAYMENT_TYPES = ['monthly'=>'راتب شهري','hourly'=>'بالساعة','commission'=>'عمولة %','visit'=>'ثابت لكل زيارة'];

    public function save(Request $request, Maid $maid): void
    {
        // Keep the existing worker identity and rental classification authoritative.
        $request->merge(['rental_mode'=>'hourly','type_id'=>\App\Services\Rental\RentalMaidQuery::rentalJobTypeId()]);
        $settings = app(MaidSettingsService::class)->validate($request, $maid);
        $rules = [
            'name'=>['required','string','max:255'],
            'phone_1'=>['nullable','string','max:30'], 'phone_2'=>['nullable','string','max:30'],
            'birth_date'=>['nullable','date','before_or_equal:today'],
            'photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'profile.bank_name'=>['nullable','string','max:150'],
            'profile.iban_owner'=>['nullable','string','max:255'],
            'profile.iban'=>['nullable','string','regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/'],
            'profile.rates'=>['required','array','min:1','max:100'],
            'profile.rates.*'=>['array:service_category_id,payment_type,payment_value'],
            'profile.rates.*.service_category_id'=>['nullable','integer',Rule::exists('hourly_service_categories','id')->whereNull('deleted_at')],
            'profile.rates.*.payment_type'=>['required',Rule::in(array_keys(self::PAYMENT_TYPES))],
            'profile.rates.*.payment_value'=>['required','numeric','min:0','max:9999999.99'],
        ];
        foreach (self::FIELDS as $field=>[$label,$type]) {
            $rules['profile.'.$field] = $type==='date' ? ['nullable','date'] : ($type==='gender' ? ['nullable','in:female,male'] : ['nullable','string','max:'.($type==='textarea' ? 3000 : 255)]);
        }
        $data = $request->validate($rules);
        $rates = $data['profile']['rates'];
        $keys = array_map(fn($rate)=>(string)($rate['service_category_id'] ?? ''), $rates);
        if (count(array_unique($keys))!==count($keys) || count(array_filter($keys,fn($key)=>$key===''))!==1) {
            throw ValidationException::withMessages(['profile.rates'=>'أضف سعرًا افتراضيًا واحدًا، ولا تكرر الخدمة.']);
        }
        foreach ($rates as $index=>$rate) {
            if ($rate['payment_type']==='commission' && $rate['payment_value']>100) {
                throw ValidationException::withMessages(["profile.rates.$index.payment_value"=>'العمولة لا تتجاوز 100%.']);
            }
        }
        if (!empty($data['profile']['contract_start_date']) && !empty($data['profile']['contract_exp_date']) && $data['profile']['contract_exp_date']<$data['profile']['contract_start_date']) {
            throw ValidationException::withMessages(['profile.contract_exp_date'=>'انتهاء العقد يجب أن يكون بعد بدايته.']);
        }
        DB::transaction(function () use ($data, $maid, $settings, $request) {
            $maid->fill(array_intersect_key($data,array_flip(['name','phone_1','phone_2','birth_date'])))->save();
            app(MaidSettingsService::class)->save($maid,$settings);
            $values=$maid->hourly_settings ?? [];
            $values['profile']=$data['profile'];
            $maid->forceFill(['hourly_settings'=>$values]);
            if ($request->hasFile('photo')) $maid->image=uploadFile($request->file('photo'),'images/Maid/');
            $maid->save();
        });
    }
}
